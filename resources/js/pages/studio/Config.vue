<template>
    <div class="container mx-auto pt-10 pb-10">
        <div class="font-semibold text-2xl">
            {{ t("configure") }}
        </div>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 w-full mx-auto pt-10 pb-10">
            <div class="bg-yellow-300 border-2 border-black p-5 shadow-brutal rounded-lg">
                <div class="font-semibold text-2xl">
                    {{ t("titles") }}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-1 xl:grid-cols-2 gap-5 mt-4">
                    <div class="md:col-span-1"
                        v-for="(field, key) in config.filter(f => f.key.includes('title') || f.key.includes('meetFormTitle') || f.key.includes('Url') || f.key.includes('FontUrl') || f.key.includes('FontFamily') || f.key.includes('cardBgColor'))"
                        :key="key"
                        :class="field.key.includes('Image') || field.key.includes('Src') ? 'col-span-2' : ''">
                        <label :for="field.key" class="text-lg font-semibold">
                            {{ t(field.label) }}
                        </label>
                        <ColorPicker v-if="field.key.includes('Color')" v-model="form[field.key]" />
                        <input v-else-if="field.key.includes('Url')" type="url" :id="field.key" v-model="form[field.key]"
                            class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t(field.label)" />
                        <input v-else-if="field.key.includes('FontWeight')" type="number" min="100" max="900" step="100"
                            :id="field.key" v-model="form[field.key]"
                            class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t(field.label)" />
                        <input v-else type="text" :id="field.key" v-model="form[field.key]"
                            class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t(field.label)" />
                    </div>
                    <div class="md:col-span-1 xl:col-span-2" v-for="(field, key) in config.filter(f => f.key.includes('mainBgImage') || f.key.includes('logoSrc'))" :key="key">
                        <label :for="field.key" class="text-lg font-semibold">
                            {{ t(field.label) }}
                        </label>
                        <input type="file" :id="field.key" class="input-theme w-full p-1 rounded-md shadow px-2"
                            @change="handleFile($event, field.key)" />
                        <div class="text-center mt-2">
                            <img v-if="field.key === 'mainBgImage' && mainBgImage" :src="mainBgImage" alt="Uploaded Image Preview"
                                class="max-w-full max-h-40 rounded-md shadow-md border" />
                            <img v-else-if="field.key === 'logoSrc' && logoSrc" :src="logoSrc" alt="Uploaded Logo Preview"
                                class="max-w-full max-h-40 rounded-md shadow-md border" />
                            <p v-else class="text-sm text-gray-500">{{ t('no_image_uploaded') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-yellow-300 border-2 border-black p-5 shadow-brutal rounded-lg">
                <div class="font-semibold text-2xl">
                    {{ t("buttons") }}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-1 xl:grid-cols-2 gap-5 mt-4">
                    <div class="md:col-span-1" v-for="(field, key) in config.filter(f => f.key.includes('button'))" :key="key">
                        <label :for="field.key" class="text-lg font-semibold">
                            {{ t(field.label) }}
                        </label>
                        <ColorPicker v-if="field.key.includes('Color')" v-model="form[field.key]" />
                        <input v-else type="text" :id="field.key" v-model="form[field.key]"
                            class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t(field.label)" />
                    </div>
                </div>
            </div>
            <div class="bg-yellow-300 border-2 border-black p-5 shadow-brutal rounded-lg">
                <div class="font-semibold text-2xl">
                    {{ t("messages") }}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-1 xl:grid-cols-2 gap-5 mt-4">
                    <div class="md:col-span-1" v-for="(field, key) in config.filter(f => f.key.includes('infoColor') || f.key.includes('warningHoverColor'))" :key="key">
                        <label :for="field.key" class="text-lg font-semibold">
                            {{ t(field.label) }}
                        </label>
                        <ColorPicker v-if="field.key.includes('Color')" v-model="form[field.key]" />
                    </div>
                </div>
            </div>
            <div class="bg-yellow-300 border-2 border-black p-5 shadow-brutal rounded-lg">
                <div class="font-semibold text-2xl">
                    {{ t("access") }}
                </div>
                <div class="grid grid-cols-1 mt-4">
                    <div >
                        <label for="admin_password" class="text-lg font-semibold">
                            {{ t("admin_password") }}
                        </label>
                        <input id="admin_password" type="password" v-model="form.admin_password"
                            class="input-theme w-full p-1 rounded-md shadow px-2" />
                    </div>
                    <div class="flex items-center gap-2 mt-5 mb-3" >
                        <input id="restrict_guest" type="checkbox" v-model="form.restrict_guest"
                        class="h-5 w-5 border-gray-300 rounded-md text-yellow-500 focus:ring focus:ring-yellow-500" />
                        <label for="restrict_guest" class="text-lg font-semibold cursor-pointer">
                            {{ t("restrict_guest") }}
                        </label>
                    </div>
                    <div v-if="form.restrict_guest">
                        <label for="guest_password" class="text-lg font-semibold">
                            {{ t("guest_password") }}
                        </label>
                        <input id="guest_password" type="password" v-model="form.guest_password"
                            class="input-theme w-full p-1 rounded-md shadow px-2" />
                            <p v-if="errors.guest_password" class="text-red-600 text-sm mt-1">
                                {{ errors.guest_password }}
                            </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end items-center w-full gap-4">
            <button class="btn btn-theme btn-warning btn-sm" :disabled="loading" @click="save">
                <i v-if="loading" class="animate-spin iconoir-refresh"></i>
                {{ t("save") }}
            </button>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from "vue";
import CryptoJS from 'crypto-js';
import { useI18n } from "vue-i18n";
import { useRoute } from 'vue-router';
import { useStudioRoomStore } from "@/stores/studio/room";
import ColorPicker from '../../components/global/ColorPicker.vue';

const { t } = useI18n();
const route = useRoute();
const studioRoomStore = useStudioRoomStore();
const roomId = computed(() => {
    const encryptedRoomId = route.query.roomId;
    if (encryptedRoomId) {
        const bytes = CryptoJS.AES.decrypt(encryptedRoomId, 'secret');
        return bytes.toString(CryptoJS.enc.Utf8);
    }
    return null;
});
const mainBgImage = ref(null);
const logoSrc = ref(null);
const errors = ref({
  guest_password: null,
});
const config = ref([
    { key: "title", label: "title", value: "" },
    { key: "meetFormTitle", label: "meeting_form_title", value: "" },
    { key: "buttonText", label: "button_text", value: "" },
    { key: "cardBgColor", label: "card_bg_color", value: "#ffffff" },
    { key: "mainFontUrl", label: "main_font_url", value: "" },
    { key: "alternativeFontUrl", label: "alternative_font_url", value: "" },
    { key: "mainFontFamily", label: "main_font_family", value: "" },
    { key: "alternativeFontFamily", label: "alternative_font_family", value: "" },
    { key: "mainBgImage", label: "main_bg_image", value: "" },
    { key: "logoSrc", label: "logo_src", value: "" },
    { key: "mainFontWeight", label: "main_font_weight", value: 400 },
    { key: "mainColor", label: "main_color", value: "#ffff00" },
    { key: "textColor", label: "text_color", value: "#000000" },
    { key: "buttonBgColor", label: "button_bg_color", value: "#ffffff" },
    { key: "buttonTextColor", label: "button_text_color", value: "#000000" },
    { key: "buttonBorderColor", label: "button_border_color", value: "#000000" },
    { key: "buttonFontSize", label: "button_font_size", value: "1rem" },
    { key: "buttonHoverBgColor", label: "button_hover_bg_color", value: "#000000" },
    { key: "buttonHoverTextColor", label: "button_hover_text_color", value: "#ffffff" },
    { key: "buttonSmFontSize", label: "button_small_font_size", value: "0.875rem" },
    { key: "buttonWidth", label: "button_width", value: "300px" },
    { key: "errorColor", label: "error_color", value: "#DC2626" },
    { key: "errorHoverColor", label: "error_hover_color", value: "#B91C1C" },
    { key: "successColor", label: "success_color", value: "#65A30D" },
    { key: "successHoverColor", label: "success_hover_color", value: "#388E3C" },
    { key: "warningColor", label: "warning_color", value: "#EAB308" },
    { key: "warningHoverColor", label: "warning_hover_color", value: "#C27C00" },
    { key: "infoColor", label: "info_color", value: "#0284C7" },
    { key: "infoHoverColor", label: "info_hover_color", value: "#0369A1" }
]);
const form = ref({
    room_id: roomId.value,
    title: "",
    buttonText: "",
    meetFormTitle: "",
    mainBgImage: "",
    cardBgColor: "#ffffff",
    logoSrc: "",
    mainFontUrl: "",
    alternativeFontUrl: "",
    mainFontFamily: "",
    alternativeFontFamily: "",
    mainColor: "#ffff00",
    textColor: "#000000",
    mainFontWeight: 400,
    buttonBgColor: "#ffffff",
    buttonTextColor: "#000000",
    buttonBorderColor: "#000000",
    buttonFontSize: "1rem",
    buttonHoverBgColor: "#000000",
    buttonHoverTextColor: "#ffffff",
    buttonSmFontSize: "0.875rem",
    buttonWidth: "300px",
    errorColor: "#DC2626",
    errorHoverColor: "#B91C1C",
    successColor: "#65A30D",
    successHoverColor: "#388E3C",
    warningColor: "#EAB308",
    warningHoverColor: "#C27C00",
    infoColor: "#0284C7",
    infoHoverColor: "#0369A1",
    restrict_guest: true,
    guest_password: '',
    admin_password: '',
});

const loading = ref(false);


const getList = async () => {
    try {
        const response = await studioRoomStore.getRoomConfiguration(roomId.value);
        if (response.data && response.data.room_configuration) {
            const configuration = response.data.room_configuration;
            form.value.room_id = configuration.configuration.room_id ?? roomId.value
            form.value.title = configuration.configuration.title == "null" ? "" : configuration.configuration.title;
            form.value.buttonText = configuration.configuration.buttonText == "null" ? "" : configuration.configuration.buttonText;
            form.value.meetFormTitle = configuration.configuration.meetFormTitle == "null" ? "" : configuration.configuration.meetFormTitle;
            form.value.mainBgImage = configuration.configuration.mainBgImage == "null" ? "" : configuration.configuration.mainBgImage;
            mainBgImage.value = configuration.configuration.mainBgImage == "null" ? "" : configuration.configuration.mainBgImage;
            form.value.cardBgColor = configuration.configuration.cardBgColor == "null" ? "" : configuration.configuration.cardBgColor;
            form.value.logoSrc = configuration.configuration.logoSrc == "null" ? "" : configuration.configuration.logoSrc;
            logoSrc.value = configuration.configuration.logoSrc == "null" ? "" : configuration.configuration.logoSrc;
            form.value.mainFontUrl = configuration.configuration.mainFontUrl == "null" ? "" : configuration.configuration.mainFontUrl;
            form.value.alternativeFontUrl = configuration.configuration.alternativeFontUrl == "null" ? "" : configuration.configuration.alternativeFontUrl;
            form.value.mainFontFamily = configuration.configuration.mainFontFamily == "null" ? "" : configuration.configuration.mainFontFamily;
            form.value.alternativeFontFamily = configuration.configuration.alternativeFontFamily == "null" ? "" : configuration.configuration.alternativeFontFamily;
            form.value.mainColor = configuration.configuration.mainColor == "null" ? "" : configuration.configuration.mainColor;
            form.value.textColor = configuration.configuration.textColor == "null" ? "" : configuration.configuration.textColor;
            form.value.mainFontWeight = configuration.configuration.mainFontWeight == "null" ? "" : configuration.configuration.mainFontWeight;
            form.value.buttonBgColor = configuration.configuration.buttonBgColor == "null" ? "" : configuration.configuration.buttonBgColor;
            form.value.buttonTextColor = configuration.configuration.buttonTextColor == "null" ? "" : configuration.configuration.buttonTextColor;
            form.value.buttonBorderColor = configuration.configuration.buttonBorderColor == "null" ? "" : configuration.configuration.buttonBorderColor;
            form.value.buttonFontSize = configuration.configuration.buttonFontSize == "null" ? "" : configuration.configuration.buttonFontSize;
            form.value.buttonHoverBgColor = configuration.configuration.buttonHoverBgColor == "null" ? "" : configuration.configuration.buttonHoverBgColor;
            form.value.buttonHoverTextColor = configuration.configuration.buttonHoverTextColor == "null" ? "" : configuration.configuration.buttonHoverTextColor;
            form.value.buttonSmFontSize = configuration.configuration.buttonSmFontSize == "null" ? "" : configuration.configuration.buttonSmFontSize;
            form.value.buttonWidth = configuration.configuration.buttonWidth == "null" ? "" : configuration.configuration.buttonWidth;
            form.value.errorColor = configuration.configuration.errorColor == "null" ? "" : configuration.configuration.errorColor;
            form.value.errorHoverColor = configuration.configuration.errorHoverColor == "null" ? "" : configuration.configuration.errorHoverColor;
            form.value.successColor = configuration.configuration.successColor == "null" ? "" : configuration.configuration.successColor;
            form.value.successHoverColor = configuration.configuration.successHoverColor == "null" ? "" : configuration.configuration.successHoverColor;
            form.value.warningColor = configuration.configuration.warningColor == "null" ? "" : configuration.configuration.warningColor;
            form.value.warningHoverColor = configuration.configuration.warningHoverColor == "null" ? "" : configuration.configuration.warningHoverColor;
            form.value.infoColor = configuration.configuration.infoColor == "null" ? "" : configuration.configuration.infoColor;
            form.value.infoHoverColor = configuration.configuration.infoHoverColor == "null" ? "" : configuration.configuration.infoHoverColor;
            form.value.restrict_guest = configuration.restrict_guest == 1 ? true : false;
            form.value.guest_password = '';
            form.value.admin_password = '';
        }
    } catch (error) {
        console.error(error);
        Toast.fire({ icon: 'error', title: t('something_went_wrong') });
    }
}

const save = async () => {
    loading.value = true;
    errors.value.guest_password = null; 
    if (form.value.restrict_guest && !form.value.guest_password) {
        errors.value.guest_password = t("guest_password_required");
        loading.value = false;
        return;
    }
    const formData = new FormData();
    for (const [key, value] of Object.entries(form.value)) {
        formData.append(key, value);
    }


    try {
        const response = await studioRoomStore.saveConfiguration(formData);
        if (response.success) {
            Toast.fire({
                icon: "success",
                title: t("crud_messages.save_success", {
                    model: t("configuration"),
                }),
            });
            const configuration = response.data.room_configuration;
            if (configuration && configuration.configuration) {
                form.value.room_id = configuration.configuration.room_id ?? roomId.value
                form.value.title = configuration.configuration.title == "null" ? "" : configuration.configuration.title;
                form.value.buttonText = configuration.configuration.buttonText == "null" ? "" : configuration.configuration.buttonText;
                form.value.meetFormTitle = configuration.configuration.meetFormTitle == "null" ? "" : configuration.configuration.meetFormTitle;
                form.value.mainBgImage = configuration.configuration.mainBgImage == "null" ? "" : configuration.configuration.mainBgImage;
                form.value.cardBgColor = configuration.configuration.cardBgColor == "null" ? "" : configuration.configuration.cardBgColor;
                form.value.logoSrc = configuration.configuration.logoSrc == "null" ? "" : configuration.configuration.logoSrc;
                form.value.mainFontUrl = configuration.configuration.mainFontUrl == "null" ? "" : configuration.configuration.mainFontUrl;
                form.value.alternativeFontUrl = configuration.configuration.alternativeFontUrl == "null" ? "" : configuration.configuration.alternativeFontUrl;
                form.value.mainFontFamily = configuration.configuration.mainFontFamily == "null" ? "" : configuration.configuration.mainFontFamily;
                form.value.alternativeFontFamily = configuration.configuration.alternativeFontFamily == "null" ? "" : configuration.configuration.alternativeFontFamily;
                form.value.mainColor = configuration.configuration.mainColor == "null" ? "" : configuration.configuration.mainColor;
                form.value.textColor = configuration.configuration.textColor == "null" ? "" : configuration.configuration.textColor;
                form.value.mainFontWeight = configuration.configuration.mainFontWeight == "null" ? "" : configuration.configuration.mainFontWeight;
                form.value.buttonBgColor = configuration.configuration.buttonBgColor == "null" ? "" : configuration.configuration.buttonBgColor;
                form.value.buttonTextColor = configuration.configuration.buttonTextColor == "null" ? "" : configuration.configuration.buttonTextColor;
                form.value.buttonBorderColor = configuration.configuration.buttonBorderColor == "null" ? "" : configuration.configuration.buttonBorderColor;
                form.value.buttonFontSize = configuration.configuration.buttonFontSize == "null" ? "" : configuration.configuration.buttonFontSize;
                form.value.buttonHoverBgColor = configuration.configuration.buttonHoverBgColor == "null" ? "" : configuration.configuration.buttonHoverBgColor;
                form.value.buttonHoverTextColor = configuration.configuration.buttonHoverTextColor == "null" ? "" : configuration.configuration.buttonHoverTextColor;
                form.value.buttonSmFontSize = configuration.configuration.buttonSmFontSize == "null" ? "" : configuration.configuration.buttonSmFontSize;
                form.value.buttonWidth = configuration.configuration.buttonWidth == "null" ? "" : configuration.configuration.buttonWidth;
                form.value.errorColor = configuration.configuration.errorColor == "null" ? "" : configuration.configuration.errorColor;
                form.value.errorHoverColor = configuration.configuration.errorHoverColor == "null" ? "" : configuration.configuration.errorHoverColor;
                form.value.successColor = configuration.configuration.successColor == "null" ? "" : configuration.configuration.successColor;
                form.value.successHoverColor = configuration.configuration.successHoverColor == "null" ? "" : configuration.configuration.successHoverColor;
                form.value.warningColor = configuration.configuration.warningColor == "null" ? "" : configuration.configuration.warningColor;
                form.value.warningHoverColor = configuration.configuration.warningHoverColor == "null" ? "" : configuration.configuration.warningHoverColor;
                form.value.infoColor = configuration.configuration.infoColor == "null" ? "" : configuration.configuration.infoColor;
                form.value.infoHoverColor = configuration.configuration.infoHoverColor == "null" ? "" : configuration.configuration.infoHoverColor;
                form.value.restrict_guest = configuration.restrict_guest == 1 ? true : false;
                form.value.guest_password = '';
                form.value.admin_password = '';
            }
        } else {
            Toast.fire({
                icon: "error",
                title: t("crud_messages.save_error", {
                    model: t("configuration"),
                }),
            });
        }
    } catch (error) {
        console.error(error);
        Toast.fire({
            icon: "error",
            title: t("crud_messages.save_error", { model: t("configuration") }),
        });
    } finally {
        loading.value = false;
    }
};

const handleFile = async (event, key) => {
    const file = event.target.files[0];
    if (file) {
        form.value[key] = file;

        const previewUrl = URL.createObjectURL(file);
        if (key == 'mainBgImage') {
            mainBgImage.value = previewUrl;

        } else {
            logoSrc.value = previewUrl;
        }
    }
};

onMounted(() => {
    getList();
});
</script>
