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
    <div v-else-if="fullStudio" class="container mx-auto h-[60vh]">
        <div class="flex flex-col h-full items-center justify-center">
                  <h3 class="text-center text-2xl font-bold mt-20">{{ t('studio_room.full_studio.title') }}</h3>
      <p class="text-center mt-5">{{ t('studio_room.full_studio.message') }}</p>

      <button class="btn btn-theme mt-5" @click="reloadPage()">{{ t('studio_room.full_studio.retry') }}</button>
        </div>
    </div>
    <div v-else class="w-full h-[calc(100vh-5rem)] relative p-2">
        <div id="call-container" class="flex w-full h-full flex-row flex-wrap justify-center items-center gap-5">
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
                    :owner="isOwner" @mute-user="adminRestriction" @admin-kick-out="adminKickOut"
                    @export-chat="exportChatMessage"  />
            </div>
        </div>
        <div class="absolute top-0 right-0 transition-all duration-300"
            :class="{ 'w-full lg:w-1/2 2xl:w-1/4 px-2': openTab, 'w-0 translate-x-2': !openTab }">
            <div class="flex justify-center" :class="{ 'hidden': openTab != 'participants' }">
                <ParticipantList :participants="participants" :waitingList="waitingList"
                    @response="handleWaitingParticipant" />
            </div>
            <div class="flex justify-center pb-5" :class="{ 'hidden': openTab != 'chat' }">
                <Chat :messages="messages" :localId="localClientId" @send-message="sendMessage" />
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
import Swal from 'sweetalert2';
import SharedScreen from '@/components/studio/SharedScreen.vue';
import ParticipantList from '@/components/studio/ParticipantList.vue';
import Chat from '@/components/studio/Chat.vue';
import { io } from 'socket.io-client';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const studioRoomChatStore = useStudioRoomChatStore();
const cookies = new Cookies();

const room = ref(route.params.room);
const userName = ref('');
const isOwner = ref(false);
const showJoinForm = ref(true);
const waitingAdmit = ref(true);
const admitRejected = ref(false);
const fullStudio = ref(false);
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
const presenterRef = ref(null);
const kicked_outs = ref([]);
const participantsLimit = 5; //TODO: make it dynamic
const videoStyle = ref({});


// WebRTC Variables
const peerConnections = ref({});
const localStream = ref(null);
const localScreenStream = ref(null);


const configuration = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ],
    iceCandidatePoolSize: 10
};

// Socket setup
const port = import.meta.env.VITE_SOCKET_FREE_PORT || 3001;
const socketURL = `${window.location.hostname}`;
//const socketURL = `${import.meta.env.VITE_SOCKET_HOST}:${port}`;
const socket = io(socketURL, {
    path: '/rtc-server',
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

const init = () => {
    const studioRooms = cookies.get("studioRooms") || {};
    if (!studioRooms[room.value]) {
        if (authStore.user) {
            studioRooms[room.value] = {
                room: room.value,
                name: authStore.user.name,
                isOwner: false
            };
            cookies.set("studioRooms", studioRooms, { path: "/" });
            askToJoin();
        } else {
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

const roomJoined = () => {
    const studioRooms = cookies.get("studioRooms") || {};
    let roomData = studioRooms[room.value];
    if (roomData.name) {
        userName.value = roomData.name;
    }
    showJoinForm.value = false;
    askToJoin();
}

const askToJoin = () => {
    initSocket();
}

const initSocket = async () => {
    socket.request = socketPromise(socket);

    let interval = setInterval(() => {
        if (socket.connected) {
            clearInterval(interval);
        }
    }, 1000);

    if (isOwner.value) {
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
        var users = await socket.request('participant_list', { room: room.value });
        const waiting = await socket.request('waiting_list', { room: room.value });

        // filter users to only get is_owner = false
        users = users.filter(user => user.is_owner != true);

        // check if room is full
        if(((users.length + 1) + waiting.length) == participantsLimit) {
            // block user
            handleFullStudio();

            // send socket to warn owner that someone requests to join but the room is full
            socket.request('alert_full_studio', {
                room: room.value,
                user: {
                    name: userName.value,
                    is_owner: isOwner.value,
                    audio: true,
                    video: true
                }
            });
            return;
        }

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
    initWebRTC();
}

const initSocketEvents = () => {
    socket.on('disconnect', () => handleSocketDisconnect());
    socket.on('connect_error', () => handleSocketDisconnect());
    socket.on('participant_disconnected', (data) => handleParticipantDisconnection(data));


    socket.on('mute', (data) => handleParticipantMute(data));
    socket.on('chat_message', (data) => handleChatMessage(data));
    socket.on('admin_restriction', (data) => handleAdminRestriction(data));
    socket.on('admin_kickout', (data) => handleAdminKickOut(data));
    socket.on('share_screen', (data) => handleShareScreen(data));

    if (isOwner.value) {
        socket.on('join_request', (data) => handleJoinRequest(data));
        socket.on('alert_full_studio', (data) => handleFullStudioAlert(data));
    }
}

function reloadPage() {
    window.location.reload();
}

const handleJoinRequest = (data) => {
    Swal.fire({
        title: t('studio_room.admit_request.title'),
        text: `${t('studio_room.admit_request.message', { name: data.user.name })}`,
        icon: 'info',
        showDenyButton: true,
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

const handleFullStudioAlert = (data) => {
    Toast.fire({
        title: t('studio_room.full_studio_alert.title'),
        text: `${t('studio_room.full_studio_alert.message', { name: data.user.name })}`,
        icon: 'warning',
    });
}

const handleAdmit = async (data) => {
    waitingAdmit.value = false;
    initSocketEvents();
    loadMessage();
}

const handleAdmitRejected = (data) => {
    waitingAdmit.value = false;
    admitRejected.value = true;
}

const handleFullStudio = (data) => {
    waitingAdmit.value = false;
    fullStudio.value = true;
}

const streamStarted = async (stream) => {
    localStream.value = stream;
    const users = await socket.request('participant_list');
    if (users.length) {
        users.forEach(user => {
            initNewParticipant(user);
        });
    }
    if (isOwner.value) {
        fetchWaitingList('streamStarted');
    }
}

const handleSocketDisconnect = async () => {
    for (const peerId in peerConnections.value) {
        peerConnections.value[peerId].close();
    }
    peerConnections.value = {};
    if (participants.value.length) {
        Toast.fire({ icon: 'error', title: `Disconnected` });
    }
    participants.value = [];
    sharedScreens.value = [];
    // return router.push({ name: 'studio.list' });
}

// WebRTC setup
const initWebRTC = async () => {

    socket.on('new_participant', async (data) => {
        const peerConnection = createPeerConnection(data.user);
        peerConnections.value[data.id] = peerConnection;
        sendOffer(data.id);
    });

    socket.on('offer', async (data) => {
        const peerConnection = createPeerConnection(data.user);
        peerConnections.value[data.id] = peerConnection;

        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);

        socket.request('answer', {
            answer: peerConnection.localDescription,
            to: data.id
        });
    });

    socket.on('answer', async (data) => {
        const peerConnection = peerConnections.value[data.id];
        if (peerConnection) {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
        }
    });

    socket.on('ice-candidate', (data) => {
        const peerConnection = peerConnections.value[data.id];
        if (peerConnection) {
            peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
        }
    });
}

const createPeerConnection = (user) => {
    let peerId = user.id;
    if(peerConnections.value[peerId]) {
        return peerConnections.value[peerId];
    }
    const peerConnection = new RTCPeerConnection(configuration);

    peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
            socket.request('ice-candidate', {
                candidate: event.candidate,
                to: peerId
            });
        }
    };

    peerConnection.ontrack = (event) => {
        if (!participants.value.find(p => p.id === peerId)) {
            participants.value.push({
                id: peerId,
                participant: user,
                stream: event.streams[0]
            });
        }
    };

    localStream.value.getTracks().forEach(track => {
        peerConnection.addTrack(track, localStream.value);
    });

    return peerConnection;
};

const initNewParticipant = (user) => {
    const peerConnection = createPeerConnection(user);
    peerConnections.value[user.id] = peerConnection;
    sendOffer(user.id);
}

const sendOffer = async (peerId) => {
    const peerConnection = peerConnections.value[peerId];
    const offer = await peerConnection.createOffer();
    await peerConnection.setLocalDescription(offer);

    socket.request('offer', {
        to: peerId,
        offer: peerConnection.localDescription
    });

};

const shareScreen = async () => {
    if (localScreenStream.value) {
        if (expanded.value.type === 'screen') {
            expanded.value = {
                type: null,
                index: null
            };
            calculateVideoStyle();
        }
        
        // Notify all other participants FIRST before cleaning up locally
        await socket.request('share_screen', {
            sharing: false,
            user: {
                name: userName.value,
                is_owner: isOwner.value,
                id: localClientId.value
            }
        });

        // Stop all tracks and clean up locally
        localScreenStream.value.getTracks().forEach(track => track.stop());
        localScreenStream.value = null;
        
        // Remove from local shared screens
        sharedScreens.value = sharedScreens.value.filter(screen => 
            screen.id !== localClientId.value
        );
        
        calculateVideoStyle();
        return;
    } 
    
    try {
        const stream = await navigator.mediaDevices.getDisplayMedia({ 
            video: true,
            audio: false 
        });
        
        localScreenStream.value = stream;
        
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
        
        sharedScreens.value.push({
            id: localClientId.value,
            name: userName.value,
            participant: { name: userName.value },
            stream: stream
        });
        
        await socket.request('share_screen', {
            sharing: true,
            user: {
                name: userName.value,
                is_owner: isOwner.value,
                id: localClientId.value
            }
        });
        
        // Share screen with all peers
        Object.keys(peerConnections.value).forEach((peerId) => {
            let pc = peerConnections.value[peerId];
            stream.getTracks().forEach(track => pc.addTrack(track, stream));
            sendOffer(peerId);
        });
        
        calculateVideoStyle();
    } catch (err) {
        console.error('Error sharing screen:', err);
        // Reset state if user cancels sharing
        localScreenStream.value = null;
        if (expanded.value.type === 'screen') {
            expanded.value = {
                type: null,
                index: null
            };
            calculateVideoStyle();
        }
    }
}

const handleShareScreen = (data) => {
    console.log('Screen sharing event received:', data);
    if (data.sharing) {
        let peerConnection = peerConnections.value[data.id];
        if (peerConnection) {
            peerConnection.ontrack = (event) => {
                if (event.streams[0].getVideoTracks().length > 0) {
                    // Check if screen already exists
                    const existingScreen = sharedScreens.value.find(s => s.id === data.id);
                    if (existingScreen) {
                        existingScreen.stream = event.streams[0];
                    } else {
                        sharedScreens.value.push({
                            id: data.id,
                            stream: event.streams[0],
                            name: data.user.name,
                            participant: data.user
                        });
                    }
                    
                    // Handle stream ending
                    event.streams[0].getVideoTracks()[0].onended = () => {
                        console.log('Screen track ended for user:', data.user.name);
                        removeScreenShare(data.id);
                    };
                    calculateVideoStyle();
                }
            };
        }
    } else {
        console.log('Screen sharing stopped for user:', data.user.name, 'with ID:', data.id);
        // Use the more robust removal function
        removeScreenShare(data.id);
    }
}

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
                console.log('Stopped track for screen:', screenToRemove.name);
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
        console.log('Screen with ID not found in sharedScreens:', screenId);
      
    }
}

const handleScreenEnded = (screen) => {
    console.log('Screen sharing ended locally for:', screen.name);
    
    // Find and remove the screen from sharedScreens
    const screenIndex = sharedScreens.value.findIndex(s => s.id === screen.id);
    
    if (screenIndex !== -1) {
        // Reset expanded state
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

const handleSelfMute = (data) => {
    if (data.type === 'audio') {
        socket.request('mute', { type: 'audio', value: data.value });
    } else {
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

const disconnect = () => {
    localStream.value.getTracks().forEach(track => track.stop());
    socket.disconnect();
    router.push({ name: 'studio.list' });
}

const handleParticipantDisconnection = (data) => {
    const participantIndex = participants.value.findIndex(participant => participant.id === data.id);
    let participant = participants.value[participantIndex];
    if (participantIndex > -1) {
        participants.value.splice(participantIndex, 1);
    }
    if (participant) {
        if (participant.participant.is_owner) {
            socket.disconnect();
            Toast.fire({ icon: 'error', title: `Room terminated`, timer: null  }).then(() => {
                return router.push({ name: 'studio.list' });
            });
            return false;
        } else {
            participant.stream.getTracks().forEach(track => track.stop());
            Toast.fire({ icon: 'error', title: `${participant.participant.name} disconnected` });
        }
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

const adminKickOut = (participant) => {
    kicked_outs.value.push(participant.id);
    socket.request('admin_kickout', {
        kick_target: participant.id,
        room: room.value,
        by: userName.value
    });
};

const handleAdminRestriction = (data) => {
    presenterRef.value.handleMute(data.mute_type, data.state);
    // data.mute_type == 'video' ? localProducer.video.pause() : localProducer.audio.pause();
    socket.request('mute', { type: data.mute_type, value: data.state });
}


const handleAdminKickOut = async (data) => {
    participants.value = [];
    sharedScreens.value = [];
    clearUpdate();
    Toast.fire({ icon: 'error', title: t('admin_kickout'), timer: 2000 }).then(() => {
        router.push({ name: 'studio.list' });
    });
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
const fetchWaitingList = async (trigger = '') => {
    waitingList.value = [];
    const list = await socket.request('waiting_list');
    if (list !== undefined) {
        if (trigger == 'streamStarted' && list.length > 0) {
            openTab.value = 'participants'
        }
        for (const waiting of list) {
            waitingList.value.push(waiting);
        }
    }
};


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
        const response = await studioRoomChatStore.getList({
            per_page: -1,
        }, room.value);
        messages.value = response.data.chats.data;
    } catch (error) {
        console.error(error);
        Toast.fire({ icon: 'error', title: t('something_went_wrong') });
    }
}

watch(participantsCount, (newVal, oldVal) => {
    console.log('participants', newVal);
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
};

window.addEventListener('resize', () => {
    calculateVideoStyle();
});

</script>
