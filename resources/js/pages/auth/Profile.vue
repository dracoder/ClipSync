<template>
    <div class="container mx-auto">
        <div class="flex flex-col gap-4 w-full xl:w-1/3 2xl:w-1/2 mx-auto pt-10">

            <div class="flex flex-col gap-5 bg-yellow-300 border-2 border-black p-5 shadow-brutal rounded-lg">
                <div class="font-semibold text-3xl">
                    {{ t('profile') }}
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 ">
                    <div>
                        <label for="name" class="text-lg font-semibold">{{ t('name') }}</label>
                        <input type="text" id="name" v-model="form.name"
                            class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t('name')">
                        <ValidationMessage key="name_error" :modelValue="v$.name" :label="t('name')"
                            :show="v$.name.error" />
                    </div>
                    <div>
                        <label for="email" class="text-lg font-semibold">{{ t('email') }}</label>
                        <input type="email" id="email" v-model="form.email"
                            class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t('email')">
                        <ValidationMessage key="email_error" :modelValue="v$.email" :label="t('email')"
                            :show="v$.email.error" />
                    </div>
                    <div>
                        <label for="password" class="text-lg font-semibold">{{ t('password') }}</label>
                        <input type="password" id="password" v-model="form.password"
                            class="input-theme w-full p-1 rounded-md shadow px-2" :placeholder="t('password')">
                        <ValidationMessage key="password_error" :modelValue="v$.password" :label="t('password')"
                            :show="v$.password.error" />
                    </div>
                </div>
                <div class="flex justify-end items-center w-full gap-4 ">
                    <button class="btn btn-theme btn-warning btn-sm" :disabled="loading" @click="save">
                        <i v-if="loading" class="animate-spin iconoir-refresh"></i>
                        {{ t('save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useVuelidate } from "@vuelidate/core"
import { required, email } from "@vuelidate/validators";
import ValidationMessage from '@/components/global/ValidationMessage.vue';

const { t } = useI18n();
const authStore = useAuthStore();


const form = ref({
    name: authStore.user.name,
    email: authStore.user.email,
    password: '',
});

const rules = computed(() => ({
    name: { required },
    email: { required, email },
    password: {}
}));

const v$ = useVuelidate(rules, form);

const loading = ref(false);

const save = async () => {
    for (let key in v$.value) {
     if(v$.value[key] && v$.value[key].$serverError){
         v$.value[key].$serverError = null;
     }
    }
    v$.value.$reset();
    
    v$.value.$touch();
    if (v$.value.$error) {
        return;
    }
    loading.value = true;
    try {
        let data = {
            name: form.value.name,
            email: form.value.email
        }
        if (form.value.password) {
            data.password = form.value.password
        }
        const response = await authStore.updateProfile(data);
        if (response.success) {
            authStore.getUserDetail();
            return true;
        } else {
            if (response.errors) {
                for (let key in response.errors) {
                    let error = response.errors[key];
                    v$.value[key].$serverError = response.errors[key]
                    v$.value[key].error = true
                }
            } else {
                Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: t('profile') }) });
            }
        }
    } catch (e) {
        console.error(e);
        Toast.fire({ icon: 'error', title: t('crud_messages.save_error', { model: t('profile') }) });
    } finally {
        loading.value = false;
    }
    return false;
}

</script>