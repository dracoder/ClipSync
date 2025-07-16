<template>
    <div v-if="showJoinForm" class="container mx-auto h-[60vh]">
        <div class="grid grid-cols-1 max-w-[500px] mx-auto mt-20">
            <JoinCard :room="room" @joined="roomJoined" />
        </div>
    </div>
    <div v-else-if="waitingAdmit" class="container mx-auto h-[60vh]">
        <div class="flex flex-col h-full items-center justify-center">
            <div class="items-center">
                <Loading />
            </div>
            <h3 class="text-center text-lg mt-20">{{ t('studio_room.waiting_admit_message') }}</h3>
        </div>
    </div>
    <div v-else-if="admitRejected" class="container mx-auto h-[60vh]">
        <div class="flex flex-col h-full items-center justify-center">
            <h3 class="text-center text-2xl font-bold mt-20">{{ t('studio_room.admit_rejected.title') }}</h3>
            <p class="text-center mt-5">{{ t('studio_room.admit_rejected.message') }}</p>
        </div>
    </div>
    <div v-else class="w-full h-[calc(100vh-5rem)] relative p-2">
        <div id="call-container" class="flex w-full h-full flex-row flex-wrap justify-center items-center  gap-5">
            <div class="bg-black sm:shadow-brutal border-2 border-black rounded-xl relative flex justify-center video-style-card" :style="videoStyle"
            v-for="(sharedScreen, index) in sharedScreens" :key="'shared_screen' + sharedScreen.id">
                <SharedScreen
                    :screen="sharedScreen"
                    @expand="expand(index, 'screen')"
                    @screen-ended="handleScreenEnded"
                    :expanded="expanded.type === 'screen' && expanded.index === index" />
            </div>
            <div class="bg-black sm:shadow-brutal border-2 border-black rounded-xl relative flex justify-center video-style-card" :style="videoStyle">
                <Presenter ref="presenterRef" :name="userName" @init="streamStarted"
                    @share-screen="shareScreen" @disconnect="disconnect" @mute="handleSelfMute"  />
            </div>
            <div class="bg-black sm:shadow-brutal border-2 border-black rounded-xl relative flex justify-center video-style-card" :style="videoStyle"
            v-for="(participant, index) in participants" :key="'participant' + participant.id">
                <Participant
                    :participant="participant"
                    :owner="isOwner" @mute-user="adminRestriction" @admin-kick-out="adminKickOut"  />
            </div>
        </div>
        <div class="absolute top-0 right-0 transition-all duration-300"
            :class="{ 'w-full lg:w-1/2 2xl:w-1/4 px-2': openTab, 'w-0 translate-x-2': !openTab }">
            <div class="flex justify-center" :class="{ 'hidden': openTab != 'participants' }">
                <ParticipantList :participants="participants" :waitingList="waitingList"
                    @response="handleWaitingParticipant" />
            </div>
            <div class="flex justify-center pb-5" :class="{ 'hidden': openTab != 'chat' }">
                <Chat :messages="messages" :localId="localClientId" @send-message="sendMessage"
                    @export-chat="exportChatMessage" />
            </div>
        </div>
        <div class="absolute bottom-0 right-0 p-4 flex gap-2 items-center">
            <div class="relative">
                <span v-if="waitingList.length"
                    class="text-2xl font-semibold absolute -top-2 right-2 animate-bounce">&excl;</span>
                <button class="btn btn-theme rounded btn-sm p-0 h-10 w-10"
                    :class="{ 'btn-warning': openTab == 'participants' }" @click="toggleTab('participants')">
                    <i class="text-2xl iconoir-community"></i>
                </button>
            </div>
            <div>
                <button class="btn btn-theme rounded btn-sm p-0 h-10 w-10"
                    :class="{ 'btn-warning': openTab == 'chat' }" @click="toggleTab('chat')">
                    <i class="text-2xl iconoir-chat-lines"></i>
                </button>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Cookies from 'universal-cookie';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useStudioRoomChatStore } from '@/stores/studio/chat';
import JoinCard from '@/components/studio/JoinCard.vue';
import Presenter from '@/components/studio/Presenter.vue';
import Participant from '@/components/studio/Participant.vue';
import { io } from 'socket.io-client';
import * as mediasoupClient from "mediasoup-client";
import Swal from 'sweetalert2';
import SharedScreen from '@/components/studio/SharedScreen.vue';
import ParticipantList from '@/components/studio/ParticipantList.vue';
import Chat from '@/components/studio/Chat.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const studioRoomChatStore = useStudioRoomChatStore();
const cookies = new Cookies();

const presenterRef = ref(null);
const room = ref(route.params.room);
const userName = ref('');
const isOwner = ref(false);
const showJoinForm = ref(true);
const waitingAdmit = ref(true);
const admitRejected = ref(false);
const participants = ref([]);
const waitingList = ref([]);
const sharedScreens = ref([]);
const expanded = ref({
    type: null,
    index: null
});
const openTab = ref('');
const localClientId = ref('');
const messages = ref([]);
const kicked_outs = ref([]);
const videoStyle = ref({});

//socket data
var device = null;
var sendTransport = null;
var recvTransport = null;
var localProducer = { video: null, audio: null, screen: null };
const socketURL = `${window.location.hostname}`
//const socketURL = `${import.meta.env.VITE_SOCKET_HOST}:${import.meta.env.VITE_SOCKET_PREMIUM_PORT}`;
const socket = io(socketURL, {
    path: '/server',
    transports: ['websocket']
});
const socketPromise = (socket) => (type, data = {}) => new Promise((resolve) => socket.emit(type, data, resolve));

const participantsCount = computed(() => participants.value.length + sharedScreens.value.length + 1);

const calculateVideoStyle = () => {
    let count = participants.value.length + sharedScreens.value.length + 1;
    
    // If a screen is expanded, return fullscreen style
    if (expanded.value.type === 'screen') {
        return {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100vw',
            height: '100vh',
            zIndex: '50',
            maxWidth: '100vw',
            maxHeight: '100vh'
        };
    }
    
    // Regular grid layout for non-expanded state
    // calculate width and height based on the number of participants, 
    // if its in mobile have 1 or 2 participant, then per row is 1, if more than 2, then per row is 2
    // if its in tablet have 1 to 4 participant, then per row is 2, if more than 4, then per row is 3
    // if its in desktop have 1 to 6 participant, then per row is 3, if more than 6, then per row is 4
    // if its in large desktop have 1 to 8 participant, then per row is 4, if more than 8, then per row is 5
    // always take center of the screen
    // always use all the width of the screen and all available height, keep minimum height to show the video
    
    let container = document.getElementById('call-container');
    let screenWidth = container.offsetWidth;
    let screenHeight = container.offsetHeight;

    let per_row = 1;
    if (screenWidth < 768) {
        per_row = count <= 2 ? 1 : 2;
    } else if (screenWidth < 1024) {
        per_row = count <= 4 ? 2 : 3;
    } else if (screenWidth < 1280) {
        if(count <= 4){
            per_row = 2;
        }else if(count <= 6){
            per_row = 3;
        }else{
            per_row = 4;
        }
    } else {
        if(count <= 4){
            per_row = 2;
        }else if(count <= 6){
            per_row = 3;
        }else if(count <= 8){
            per_row = 4;
        }else{
            per_row = 5;
        }
    }

    let rows = Math.ceil(count / per_row);

    screenWidth = screenWidth - ((per_row - 1) * 30);
    screenHeight = screenHeight - ((rows - 1) * 30);

    let style = { 
        'min-height': '15vh',
        'max-width': `100%`,
    }
    if(rows == 1){
        style['max-height'] = `50vh`;
    }
    let width = Math.floor(screenWidth / per_row);
    let height = Math.floor(screenHeight / rows);
    
    console.log('per_row', per_row);
    console.log('screenWidth', screenWidth);
    console.log('width', width);
    console.log('rows', rows);
    
    style['width'] = `${width}px`;
    style['height'] = `${height}px`;

    videoStyle.value = style;
};

const gridStyle = computed(()=> {
    const count = participants.value.length + sharedScreens.value.length + 1;

    const viewport = document.documentElement.clientWidth;

    let height = '';
    if (count > 6) {
        if (viewport >= 1280) { // xlClass
            height = (100 / Math.ceil(count / 4)).toFixed(2); // col-span-3
        } else if (viewport >= 1024) { // lgClass
            height = (100 / Math.ceil(count / 3)).toFixed(2); // col-span-4
        } else { // mdClas
            height = (100 / Math.ceil(count / 2)).toFixed(2); // col-span-6
        }
    };
    return height !== '' ? {'max-height': `calc(${height}vh-5rem)`} : {};
})

const init = () => {
    // check if user has already room details in cookies
    const studioRooms = cookies.get("studioRooms") || {};
    if (!studioRooms[room.value]) {
        // if logged in user then join directly
        if (authStore.user) {
            studioRooms[room.value] = {
                room: room.value,
                name: authStore.user.name,
                isOwner: false
            };
            cookies.set("studioRooms", studioRooms, { path: "/" });
            askToJoin();
        } else {
            // if not logged in then show join form
            showJoinForm.value = true;
        }
    } else {
        showJoinForm.value = false;
        let roomData = studioRooms[room.value];
        if (roomData.name) {
            userName.value = roomData.name;
        }
        if (roomData.isOwner) {
            isOwner.value = true;
            waitingAdmit.value = false;
        }
        if (!isOwner.value) {
            askToJoin();
        }
    }
    if (isOwner.value) {
        initSocket();
    }
};

// once user joined the room via join form
const roomJoined = () => {
    const studioRooms = cookies.get("studioRooms") || {};
    let roomData = studioRooms[room.value];
    if (roomData.name) {
        userName.value = roomData.name;
    }
    showJoinForm.value = false;
    askToJoin();
}

// if user is not owner then it ask to owner to admit
const askToJoin = () => {
    initSocket();
}


//initiate socket connection
const initSocket = async () => {
    socket.request = socketPromise(socket);
    // check if socket is connected
    let interval = setInterval(() => {
        if (socket.connected) {
            clearInterval(interval);
        }
    }, 1000);
    if (isOwner.value) {
        // if owner then join directly
        socket.request('join', {
            room: room.value,
            user: {
                name: userName.value,
                is_owner: isOwner.value,
                audio: true,
                video: true
            }
        });
        initSocketEvents();
    } else {
        // if not owner then ask to join
        socket.request('join_request', {
            room: room.value,
            user: {
                name: userName.value,
                is_owner: isOwner.value,
                audio: true,
                video: true
            }
        });
        socket.on('admit_accepted', (data) => handleAdmit(data));
        socket.on('admit_rejected', (data) => handleAdmitRejected(data));
    }
    socket.on('joined', (data) => {
        localClientId.value = data.id;
    });
    initMediasoup();
}

// initiate socket events to be listened
const initSocketEvents = () => {
    socket.on('disconnect', () => handleSocketDisconnect());
    socket.on('connect_error', () => handleSocketDisconnect());
    socket.on('producer_disconnect', () => handleSocketDisconnect());
    socket.on('new_producer', (data) => handleNewProducer(data));
    socket.on('producer_left', (data) => handleProducerLeft(data));
    socket.on('mute', (data) => handleParticipantMute(data));
    socket.on('chat_message', (data) => handleChatMessage(data));
    socket.on('admin_restriction', (data) => handleAdminRestriction(data));
    socket.on('admin_kickout', (data) => handleAdminKickOut(data));

    if (isOwner.value) {
        socket.on('join_request', (data) => handleJoinRequest(data));
    }
}

// if new user ask to join then owner can admit
const handleJoinRequest = (data) => {
    Swal.fire({
        title: t('studio_room.admit_request.title'),
        text: `${t('studio_room.admit_request.message', { name: data.user.name })}`,
        icon: 'info',
        showDenyButton: true,
        showCancelButton: false,
        confirmButtonText: t('studio_room.admit_request.accept'),
        denyButtonText: t('studio_room.admit_request.reject'),
    }).then(async (result) => {
        if (result.isConfirmed) {
            await socket.request('admit_accepted', {
                room: room.value,
                user: data.user,
                socket_id: data.socket_id
            });
        } else if (result.isDenied) {
            await socket.request('admit_rejected', {
                room: room.value,
                user: data.user,
                socket_id: data.socket_id
            });
        }
        fetchWaitingList();
    });
}

// once owner admit the user
const handleAdmit = async (data) => {
    waitingAdmit.value = false;
    initSocketEvents();
    subscribeReceiver();
    loadMessage();
}

// if owner reject the user
const handleAdmitRejected = (data) => {
    waitingAdmit.value = false;
    admitRejected.value = true;
}


const handleSocketDisconnect = async () => {
    const producers = await socket.request('producer_list');
    if (producers !== undefined) {
        // remove from participants which are not in producers
        participants.value = participants.value.filter(participant => {
            for (const producer of producers) {
                if (participant.id === producer.id) {
                    return true;
                }
            }
            if (participant.participant.is_owner) {
                socket.disconnect();
                Toast.fire({ icon: 'error', title: `Room terminated`, timer: 3000 }).then(() => {
                    return router.push({ name: 'studio.list' });
                });
                return false;
            } else {
                if (kicked_outs.value.includes(participant.id)) {
                    kicked_outs.value = kicked_outs.value.filter(kicked => kicked !== participant.id);
                    return false;
                }
                Toast.fire({ icon: 'error', title: `${participant.participant.name} disconnected` });
                return false;
            }
        });

        sharedScreens.value = sharedScreens.value.filter(screen => {
            for (const producer of producers) {
                if (screen.id === producer.id) {
                    return true;
                }
            }
            return false;
        });
    } else {
        participants.value = [];
        sharedScreens.value = [];
        Toast.fire({ icon: 'error', title: `Disconnected` });
        return router.push({ name: 'studio.list' });
    }
}

const streamStarted = async (stream) => {
    if (localProducer.video) {
        return;
    }
    // wait for device to be loaded
    while (!device) {
        await new Promise(r => setTimeout(r, 1000));
    }
    const data = await socket.request('createProducerTransport', {
        forceTcp: false,
        rtpCapabilities: device.rtpCapabilities,
    });
    if (data.error) {
        console.error(data.error);
        return;
    }
    sendTransport = device.createSendTransport(data);
    sendTransport.on('connect', async ({ dtlsParameters }, callback, errback) => {
        try {
            await socket.request('connectProducerTransport', { dtlsParameters });
            callback();
        } catch (err) {
            errback(err);
        }
    });
    sendTransport.on('produce', async ({ kind, rtpParameters }, callback, errback) => {
        try {
            const { id } = await socket.request('produce', {
                transportId: sendTransport.id,
                kind,
                rtpParameters,
            });
            callback({ id });
        } catch (err) {
            errback(err);
        }
    });
    sendTransport.on('connectionstatechange', (state) => {
        switch (state) {
            case 'connecting':
                console.log('Sender connecting');
                break;
            case 'new':
                console.log('Sender new');
                break;
            case 'connected':
                console.log('Sender connected');
                break;
            case 'failed':
                sendTransport.close();
                console.log('Sender failed');
                break;
            case 'disconnected':
                console.log('Sender disconnected');
                break;
            default:
                break;
        }
    });

    try {
        const videoTrack = stream.getVideoTracks()[0];
        const audioTrack = stream.getAudioTracks()[0];
        localProducer.video = await sendTransport.produce({ track: videoTrack });
        localProducer.audio = await sendTransport.produce({ track: audioTrack });
        await socket.request('producer_joined', {
            id: socket.id,
            user: {
                name: userName.value,
                is_owner: isOwner.value,
                audio: true,
                video: true
            },
            producer: localProducer,
            screen: false,
        });
    } catch (err) {
        console.error('Publish error:', err);
    }

    fetchExistingStreams();
    if (isOwner.value) {
        fetchWaitingList('streamStarted');
    }
}

const shareScreen = async () => {
    if (localProducer.screen) {
        // Reset expanded state if needed
        if (expanded.value.type === 'screen') {
            expanded.value = {
                type: null,
                index: null
            };
            calculateVideoStyle();
        }
        
        // Stop all tracks before cleanup
        const myScreen = sharedScreens.value.find(screen => 
            screen.participant.name === userName.value || screen.id === localProducer.screen.id
        );
        if (myScreen && myScreen.stream) {
            myScreen.stream.getTracks().forEach(track => track.stop());
        }
        
        // Notify other participants that screen sharing stopped
        await socket.request('producer_left', {
            id: localProducer.screen.id,
            user: {
                name: userName.value,
                is_owner: isOwner.value
            },
            producer: localProducer,
            screen: true,
        });
        
        const screenId = localProducer.screen.id;
        localProducer.screen.close();
        localProducer.screen = null;
        
        //remove from local shared screens immediately
        sharedScreens.value = sharedScreens.value.filter(screen => 
            screen.id !== screenId && screen.participant.name !== userName.value);
            
        calculateVideoStyle();
    } else {
        // const stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
        // const videoTrack = stream.getVideoTracks()[0];
        // localProducer.screen = await sendTransport.produce({ track: videoTrack });
        // await socket.request('producer_joined', {
        //     id: socket.id,
        //     user: {
        //         name: userName.value,
        //         is_owner: isOwner.value
        //     },
        //     producer: localProducer,
        //     screen: true,
        // });
        // sharedScreens.value.push({
        //     id: localProducer.screen.id,
        //     participant: { name: userName.value },
        //     stream
        // });
        //calculateVideoStyle();
        try {
            const stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
            
            // Handle stream ending from browser UI
            stream.getVideoTracks()[0].onended = () => {
                console.log('Local screen sharing ended for user:', userName.value);
                if (expanded.value.type === 'screen') {
                    expanded.value = {
                        type: null,
                        index: null
                    };
                    calculateVideoStyle();
                }
                shareScreen(); // This will trigger cleanup
            };
            
            const videoTrack = stream.getVideoTracks()[0];
            localProducer.screen = await sendTransport.produce({ track: videoTrack });
            await socket.request('producer_joined', {
                id: socket.id,
                user: {
                    name: userName.value,
                    is_owner: isOwner.value
                },
                producer: localProducer,
                screen: true,
            });
            sharedScreens.value.push({
                id: localProducer.screen.id,
                participant: { name: userName.value },
                name: userName.value,
                stream
            });
            calculateVideoStyle();
        } catch (err) {
            console.error('Error sharing screen:', err);
            // Reset state if user cancels sharing
            if (expanded.value.type === 'screen') {
                expanded.value = {
                    type: null,
                    index: null
                };
                calculateVideoStyle();
            }
        }
    }
}

const initMediasoup = async () => {
    const data = await socket.request('getRouterRtpCapabilities');
    await loadDevice(data);
    if (isOwner.value) {
        subscribeReceiver();
    }
}

const loadDevice = async (routerRtpCapabilities) => {
    try {
        device = new mediasoupClient.Device();
        await device.load({ routerRtpCapabilities });
    } catch (error) {
        console.error('load device error', error);
        if (error.name === 'UnsupportedError') {
            console.error('Browser not supported');
        }
    }
};

const subscribeReceiver = async () => {
    const data = await socket.request('createConsumerTransport', { forceTcp: false });
    if (data.error) {
        console.error('Subscribe error:', data.error);
        return;
    }

    recvTransport = device.createRecvTransport(data);
    recvTransport.on('connect', ({ dtlsParameters }, callback, errback) => {
        socket.request('connectConsumerTransport', { transportId: recvTransport.id, dtlsParameters })
            .then(callback)
            .catch(errback);
    });
    recvTransport.on('connectionstatechange', async (state) => {
        switch (state) {
            case 'connecting':
                console.log('Receiver connecting');
                break;
            case 'new':
                console.log('Receiver new');
                break;
            case 'connected':
                console.log('Receiver connected');
                break;
            case 'failed':
                recvTransport.close();
                console.log('Receiver failed');
                break;
            case 'disconnected':
                console.log('Receiver disconnected');
                break;
            default:
                console.log('Recv state changed to :', state);
                break;
        }
    });
};

const fetchExistingStreams = async () => {
    const producers = await socket.request('producer_list');
    if (producers !== undefined) {
        for (const producer of producers) {
            handleNewProducer(producer);
        }
    }
};

const fetchWaitingList = async (trigger = '') => {
    waitingList.value = [];
    const list = await socket.request('waiting_list');
    if (list !== undefined) {
        if (trigger == 'streamStarted' && list.length > 0) openTab.value = 'participants'
        for (const waiting of list) {
            waitingList.value.push(waiting);
        }
    }
};

const consume = async (transport, producer, screen) => {
    const { rtpCapabilities } = device;
    const newConsumer = await socket.request('consume', { rtpCapabilities, producer });
    const stream = new MediaStream();
    if (screen) {
        if (newConsumer.screen) {
            const { producerId, id, kind, rtpParameters } = newConsumer.screen;
            const consumer = await transport.consume({
                id,
                producerId,
                kind,
                rtpParameters,
            });
            stream.addTrack(consumer.track);
            consumer.on('trackended', () => {
                stream.getTracks().forEach(track => track.stop());
                sharedScreens.value = sharedScreens.value.filter(screen => screen.participant.name !== producer.user.name);
            });
        }
    } else {
        if (newConsumer.video) {
            const { producerId, id, kind, rtpParameters } = newConsumer.video;
            const consumer = await transport.consume({
                id,
                producerId,
                kind,
                rtpParameters,
            });
            stream.addTrack(consumer.track);
        }
        if (newConsumer.audio) {
            const { producerId, id, kind, rtpParameters } = newConsumer.audio;
            const consumer = await transport.consume({
                id,
                producerId,
                kind,
                rtpParameters,
            });
            stream.addTrack(consumer.track);
        }
    }
    return stream;
};

const handleNewProducer = async (data) => {
    console.log('New producer received:', data);
    var stream = await consume(recvTransport, data.producer, data.screen);
    if (data.screen) {
        console.log('Adding new screen share with ID:', data.producer.id, 'from user:', data.user.name);
        //check if id already exists
        const screen = sharedScreens.value.find(screen => screen.id === data.producer.id);
        if (screen) {
            console.log('Updating existing screen share');
            screen.stream = stream;
        } else {
            console.log('Adding new screen share to array');
            sharedScreens.value.push({
                id: data.producer.id,
                participant: data.user,
                name: data.user.name,
                stream
            });
        }
        
        // Handle stream ending for screen shares
        stream.getTracks().forEach(track => {
            track.onended = () => {
                console.log('Screen track ended for user:', data.user.name);
                removeScreenShare(data.producer.id);
            };
        });
        
        calculateVideoStyle();
    } else {
        //check if id already exists
        const participant = participants.value.find(participant => participant.id === data.id);
        if (participant) {
            participant.stream = stream;
        } else {
            participants.value.push({
                id: data.id,
                participant: data.user,
                stream
            });
        }
    }
};

const handleProducerLeft = (data) => {
    console.log('Producer left event received:', data);
    if (data.screen) {
        console.log('Screen producer left with ID:', data.producer.id);
        // Use producer.id for screen removal
        removeScreenShare(data.producer.id);
    } else {
        console.log('Video producer left with ID:', data.id);
        participants.value = participants.value.filter(participant => participant.id !== data.id);
    }
};

// Helper function to robustly remove screen shares
const removeScreenShare = (screenId) => {
    console.log('Removing screen share with ID:', screenId);
    const screenIndex = sharedScreens.value.findIndex(screen => screen.id === screenId);
    
    if (screenIndex !== -1) {
        console.log('Found screen at index:', screenIndex);
        
        // Reset expanded state if this was the expanded screen
        if (expanded.value.type === 'screen' && expanded.value.index === screenIndex) {
            expanded.value = { type: null, index: null };
            console.log('Reset expanded state');
        }
        
        // Stop all tracks before removing
        const screenToRemove = sharedScreens.value[screenIndex];
        if (screenToRemove && screenToRemove.stream) {
            screenToRemove.stream.getTracks().forEach(track => {
                track.stop();
                console.log('Stopped track for screen:', screenToRemove.name || screenToRemove.participant?.name);
            });
        }
        
        // Remove from array
        sharedScreens.value.splice(screenIndex, 1);
        console.log('Removed screen from array. Remaining screens:', sharedScreens.value.length);
        
        // Adjust expanded index if needed
        if (expanded.value.type === 'screen' && expanded.value.index > screenIndex) {
            expanded.value.index--;
        }
        
        calculateVideoStyle();
    } else {
        console.error('Screen with ID not found in sharedScreens:', screenId);
        //console.log('Current shared screens:', sharedScreens.value);
    }
};

const handleScreenEnded = (screen) => {
    console.log('Screen sharing ended locally for:', screen.name);
    
    // Find and remove the screen from sharedScreens
    const screenIndex = sharedScreens.value.findIndex(s => s.id === screen.id);
    
    if (screenIndex !== -1) {
        // Reset expanded state if this was the expanded screen
        if (expanded.value.type === 'screen' && expanded.value.index === screenIndex) {
            expanded.value = {
                type: null,
                index: null
            };
        }
        
        // Stop all tracks
        const screenToRemove = sharedScreens.value[screenIndex];
        if (screenToRemove && screenToRemove.stream) {
            screenToRemove.stream.getTracks().forEach(track => track.stop());
        }
        
        // Remove from array
        sharedScreens.value.splice(screenIndex, 1);
        
        // Adjust expanded index if needed
        if (expanded.value.type === 'screen' && expanded.value.index > screenIndex) {
            expanded.value.index--;
        }
        
        calculateVideoStyle();
    }
};

const expand = (index, type) => {
    if (expanded.value.type === type && expanded.value.index === index) {
        expanded.value = {
            type: null,
            index: null
        };
    } else {
        expanded.value = {
            type,
            index
        };
    }
    // Force recalculation of video styles for proper layout
    calculateVideoStyle();
}

const expandedClass = (type, index) => {
    let count = participants.value.length + 1;
    count = count > 5 ? 5 : count;
    if (expanded.value.type === type && expanded.value.index === index) {
        return `order-first col-span-1 md:col-span-2 lg:col-span-${count}`;
    }
    return '';
}

const disconnect = async () => {
    if (localProducer.video) {
        await socket.request('producer_left', {
            user: {
                name: userName.value,
                is_owner: isOwner.value
            },
            producer: localProducer,
            screen: false,
        });
        localProducer.video.close();
        localProducer.audio.close();
        localProducer.video = null;
        localProducer.audio = null;
    }
    if (localProducer.screen) {
        await socket.request('producer_left', {
            user: {
                name: userName.value,
                is_owner: isOwner.value
            },
            producer: localProducer,
            screen: true,
        });
        localProducer.screen.close();
        localProducer.screen = null;
    }
    router.push({ name: 'studio.list' });
}

const handleSelfMute = (data) => {
    if (data.type === 'audio') {
        localProducer.audio.pause();
        socket.request('mute', { type: 'audio', value: data.value });
    } else {
        localProducer.video.pause();
        socket.request('mute', { type: 'video', value: data.value });
    }
}

const handleParticipantMute = (data) => {
    const participant = participants.value.find(participant => participant.id === data.id);
    if (participant) {
        if (data.type === 'audio') {
            participant.stream.getAudioTracks().forEach(track => track.enabled = data.value);
            participant.participant.audio = data.value;
        } else {
            participant.stream.getVideoTracks().forEach(track => track.enabled = data.value);
            participant.participant.video = data.value;
        }
    }
}

const toggleTab = (tab) => {
    if (openTab.value == tab) {
        openTab.value = false;
    } else {
        openTab.value = tab;
    }
}

const handleWaitingParticipant = ({ waiting, approve }) => {
    if (approve) {
        socket.request('admit_accepted', {
            room: room.value,
            user: waiting.user,
            socket_id: waiting.id
        });
    } else {
        socket.request('admit_rejected', {
            room: room.value,
            user: waiting.user,
            socket_id: waiting.id
        });
    }
    fetchWaitingList();
}
const handleChatMessage = (data) => {
    messages.value.push(data);
}
const sendMessage = async (message) => {

    try {
        let data = {
            sender_name: userName.value,
            message: message
        }
        const response = await studioRoomChatStore.store(data, room.value);
        if (response.success) {
            if (message !== "") {
                let data = {
                    sender: localClientId.value,
                    user: {
                        name: userName.value,
                        is_owner: isOwner.value
                    },
                    content: message
                };
                socket.request('chat_message', data);
                messages.value.push(data);
            }
        } else {
            Toast.fire({ icon: 'error', title: t('crud_messages.chat_error') });
        }
    } catch (e) {
        console.error(e);
        Toast.fire({ icon: 'error', title: t('crud_messages.chat_error') });
    }
};

const exportChatMessage = async () => {
    try {
        const response = await studioRoomChatStore.exportChat(room.value);

        if (response.success) {
            const text = response.data;
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `messages_${room.value}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        } else {
            Toast.fire({ icon: 'error', title: t('crud_messages.chat_export_error') });
        }
    } catch (e) {
        console.error(e);
        Toast.fire({ icon: 'error', title: t('crud_messages.chat_export_error') });
    }
};

const loadMessage = async () => {
    try {
        room
        const response = await studioRoomChatStore.getList({
            per_page: -1,
        }, room.value);
        messages.value = response.data.chats.data;
    } catch (error) {
        console.error(error);
        Toast.fire({ icon: 'error', title: t('something_went_wrong') });
    }
}
const clearChat = async () => {
    try {
        let response = await studioRoomChatStore.destroy(room.value);
        if (response.success) {
            return true;
        } else {
            Toast.fire({ icon: 'error', title: response.message ?? t('crud_messages.chat_delete_error') });
        }
    } catch (e) {
        console.error(e);
        Toast.fire({ icon: 'error', title: t('crud_messages.chat_delete_error') });
    }
}

const adminRestriction = (participant, type) => {

    let muteState = (type == 'video' ? participant.participant.video : participant.participant.audio);
    if (!muteState) {
        // Toast.fire({ icon: 'error', title: t('mute_error', { model: type }) });
        return;
    }
    socket.request('admin_restriction', {
        mute_type: type,
        mute_target: participant.id,
        room: room.value,
        state: !muteState,
        by: userName.value
    });
};

const handleAdminRestriction = (data) => {
    presenterRef.value.handleMute(data.mute_type, data.state);
    data.mute_type == 'video' ? localProducer.video.pause() : localProducer.audio.pause();
    socket.request('mute', { type: data.mute_type, value: data.state });
}

const adminKickOut = (participant) => {
    kicked_outs.value.push(participant.id);
    socket.request('admin_kickout', {
        kick_target: participant.id,
        room: room.value,
        by: userName.value
    });
};

const handleAdminKickOut = async (data) => {
    if (localProducer.video) {
        await socket.request('producer_left', {
            user: {
                name: userName.value,
                is_owner: isOwner.value
            },
            producer: localProducer,
            screen: false,
        });
        localProducer.video.close();
        localProducer.audio.close();
        localProducer.video = null;
        localProducer.audio = null;
    }
    if (localProducer.screen) {
        await socket.request('producer_left', {
            user: {
                name: userName.value,
                is_owner: isOwner.value
            },
            producer: localProducer,
            screen: true,
        });
        localProducer.screen.close();
        localProducer.screen = null;
    }
    participants.value = [];
    sharedScreens.value = [];
    clearUpdate();
    Toast.fire({ icon: 'error', title: t('admin_kickout'), timer: 2000 }).then(() => {
        router.push({ name: 'studio.list' });
    });
}

watch(participantsCount, (newVal, oldVal) => {
    calculateVideoStyle();
});

watch(sharedScreens, (newSharedScreens) => {
    // Reset expanded state if the expanded screen no longer exists
    if (expanded.value.type === 'screen' && 
        (expanded.value.index === null || expanded.value.index >= newSharedScreens.length)) {
        expanded.value = {
            type: null,
            index: null
        };
        calculateVideoStyle();
    }
}, { deep: true });


onMounted(() => {
    init();
});

const clearUpdate = () => {
    if (socket) {
        socket.disconnect();
    }
    if (isOwner.value) {
        clearChat();
    }
    window.onbeforeunload = null;
}

onBeforeUnmount(() => {
    clearUpdate();
});

window.onbeforeunload = function () {
    clearUpdate();
}
window.addEventListener('resize', () => {
    calculateVideoStyle();
});

</script>
