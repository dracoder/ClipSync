<template>
    <template v-if="isNested">
        <template v-if="modelValue.$each.$response.$errors[nestedIndex][nestedField]?.length && show">
            <span v-for="(error, index) of modelValue.$each.$response.$errors[nestedIndex][nestedField]" :key="index">
                <small class="text-error">{{ prepareMessage(error) }} </small>
            </span>
        </template>
        <template v-else-if="modelValue.$serverError && show">
            <span v-for="(error, index) of modelValue.$serverError" :key="index">
                <small class="text-error">{{ error }}</small>
            </span>
        </template>
    </template>
    <template v-else>
        <template v-if="modelValue.$error && show">
            <span v-for="(error, index) of modelValue.$errors" :key="index">
                <small class="text-error">{{ prepareMessage(error) }}</small>
            </span>
        </template>
        <template v-else-if="modelValue.$serverError && show">
            <span v-for="(error, index) of modelValue.$serverError" :key="index">
                <small class="text-error">{{ error }}</small>
            </span>
        </template>
    </template>
</template>

<script setup>
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    show: {
        type: Boolean,
        required: false,
        default: true,
    },
    label: {
        type: String,
        required: false,
        default: null,
    },
    isNested: {
        type: Boolean,
        required: false,
        default: false,
    },
    nestedIndex: {
        type: Number,
        required: false,
        default: null,
    },
    nestedField: {
        type: String,
        required: false,
        default: null,
    },
});


const prepareMessage = (error) => {
    let message  = error.$message;
    if(error.$params && error.$params.type) {
       message = t(`validations.${error.$params.type}`, { attribute: props.label , ...error.$params });
    }
    // capitalize first letter
    message = message.charAt(0).toUpperCase() + message.slice(1).toLowerCase();
    return message;
};

</script>
