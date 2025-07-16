<template>
    <div class="relative w-full h-full bg-white shadow-brutal p-2 box-border rounded-lg">
    <div class="absolute right-2 top-2" v-if="messages.length">
        <i @click="exportChat" class="iconoir-download text-2xl cursor-pointer" :title="t('export_chat')"></i>
    </div>
    <div class="chat-heading flex w-full items-center justify-center pb-2">
        <h4 class="text-lg font-bold flex-grow text-center">{{ t('chat') }}</h4>
    </div>

    <!-- <h3 class="text-lg font-bold text-center">{{ t('chat') }}</h3> -->

    <div ref="messagesContainer" class="flex-grow-1 overflow-auto p-2 bg-light max-h-[75vh] pr-5">
        <div class="shadow-brutal border border-black" v-for="(message, index) in messages" :key="index"
            :class="['message', message.sender === localId ? 'sent' : 'received']">
            <strong>{{ message.user?.name ?? '' }}:</strong>
            <p class="whitespace-pre-line">{{ message.content }}</p>
        </div>
    </div>

    <div class="border-top bg-white">
        <form @submit.prevent="sendMessage">
            <div class="flex gap-2 items-end">
                <textarea type="text" class="input-theme w-full p-1 pl-2 pr-14 rounded-md text-sm shadow-brutal"
                    rows="1" @keydown="handleKeyDown" :placeholder="t('type_message')" v-model="newMessage"></textarea>
                <button @click="sendMessage" class="btn btn-theme btn-sm btn-circle rounded btn-warning mb-1">
                    <i class="iconoir-send-diagonal text-xl"></i>
                </button>
            </div>
        </form>
    </div>
</div>
</template>

<script setup>
import { ref, nextTick, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    messages: {
        type: Array,
        required: true
    },
    localId: {
        type: String,
        required: true
    }
});


const emit = defineEmits(['send-message','export-chat']);


const newMessage = ref('');
const messagesContainer = ref(null);

const messagesCount = computed(() => props.messages.length);


const sendMessage = () => {
    if (newMessage.value.trim()) {
        emit('send-message', newMessage.value);
        nextTick(() => {
            newMessage.value = '';
        });
    }
};

const exportChat = () => {
    emit('export-chat');
};

const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
};

watch(messagesCount, () => {
    if (messagesContainer.value) {
        nextTick(() => {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        });
    }
});
</script>
<style lang="scss" scoped>
.chat-heading {
    font-family: "Bungee";

}

.chat-heading h4 {
    color: black;
    margin-bottom: 0px;
}

.message {
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 10px;
    max-width: 80%;
    word-break: break-word;
}

.message.sent {
    background-color: #d9fdd3;
    margin-left: auto;
    border-top-right-radius: 0;
}

.message.received {
    background-color: #ffffff;
    border-top-left-radius: 0;
}

.message strong {
    display: block;
    margin-bottom: 5px;
}

.message.sent strong {
    text-align: right;
}

.message.received strong {
    text-align: left;
}
</style>
