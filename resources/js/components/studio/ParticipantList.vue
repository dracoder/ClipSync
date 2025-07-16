<template>
    <div class="w-full h-full bg-white shadow-brutal p-2 box-border rounded-lg">
        <h3 class="text-lg font-bold text-center">{{ t('participants') }}</h3>
        <div class="grid grid-cols-1 gap-2">
            <div v-for="waiting in waitingList" :key="waiting.id" class="flex items-center justify-between p-2 border-b rounded-lg rounded-b-none">
                <div class="flex items-center gap-1">
                    <i class="iconoir-user-circle text-2xl"></i>
                    <div class="flex flex-col">
                        <span class="font-bold">{{ waiting.user.name }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <i class="rounded-full iconoir-check text-white bg-green-600 text-xl border-2 border-black cursor-pointer" @click="approve(waiting)"></i>
                    <i class="rounded-full iconoir-xmark text-white bg-red-600 text-xl border-2 border-black cursor-pointer" @click="reject(waiting)"></i>
                </div>
            </div>
            <div v-for="participant in participants" :key="participant.id" class="flex items-center justify-between p-2 border-b rounded-lg rounded-b-none">
                <div class="flex items-center gap-1">
                    <i class="iconoir-user-circle text-2xl"></i>
                    <div class="flex flex-col">
                        <span class="font-bold">{{ participant.participant.name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const emit = defineEmits(['response'])

const props = defineProps({
    participants: {
        type: Array,
        required: true
    },
    waitingList: {
        type: Array,
        required: false,
        default: () => []
    }
})

const approve = (waiting) => {
    emit('response', { waiting, approve: true })
}

const reject = (waiting) => {
    emit('response', { waiting, approve: false })
}


</script>
