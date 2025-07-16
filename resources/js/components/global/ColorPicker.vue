<template>
    <div class="flex relative">
        <input ref="colorPicker" type="text" v-model="colorLabel" @input="labelChanged" class="input-theme w-full p-1 rounded-md shadow px-2" />
        <div class="absolute top-0 right-0 h-full w-12 rounded-r border-2 border-black" 
         :style="{ backgroundColor: `#${color}` }" 
         @click="focusInput">
    </div>
    </div>
</template>
<script setup>
import { ref, onMounted, watch } from 'vue'
import "@melloware/coloris/dist/coloris.css";
import Coloris from "@melloware/coloris";

const props = defineProps({
    modelValue: {
        type: String,
        default: '#000000'
    },
})

const colorPicker = ref(null);

const emit = defineEmits(['update:modelValue'])

const color = ref(props.modelValue ? props.modelValue.replace('#', '') : '000000')
const colorLabel = ref(props.modelValue ? (props.modelValue.charAt(0) === '#' ? props.modelValue : `#${props.modelValue}`) : '#000000');


onMounted(() => {
    Coloris.init();
    Coloris({
        el: colorPicker.value,
        wrap: false,
    });
})

const handleChange = (e) => {
    colorLabel.value = `#${e.value}`;
    emit('update:modelValue', colorLabel.value)
}

const focusInput = () => {
    colorPicker.value.click();
}

const labelChanged = (e) => {
    let value = e.target.value;
    color.value = value.replace('#', '');
    colorLabel.value = value;
    emit('update:modelValue', value)
}

watch(() => props.modelValue, (value) => {
    color.value = value.replace('#', '');
    colorLabel.value = value;
})

</script>
