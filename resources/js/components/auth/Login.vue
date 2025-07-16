<template>
        <div class="w-full max-w-[500px] bg-yellow-300 mx-auto p-5 shadow-brutal border-2 border-black rounded-lg relative">
            <button v-if="isModal" @click="$emit('close')" class="absolute top-2 right-2 text-black hover:text-red-600">
                <i class="iconoir-xmark text-2xl"></i>
            </button>
            <div class="grid grid-cols-1 gap-5">
                <div>
                    <h1 class="text-2xl font-bold ">{{ t('login') }}</h1>
                </div>
                <div v-if="verified">
                    <div class="bg-green-200 border-2 border-green-800 p-2 rounded-lg">
                        <p class="text-green">{{ t('email_verified') }}</p>
                    </div>
                </div>
                <div>
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-5">
                            <div>
                                <label for="email" class="font-semibold">{{ t('email') }}</label>
                                <input type="email" id="email" v-model="form.email" name="email" class="w-full p-2 border-2 border-black rounded-lg focus-visible:outline-none" />
                                <ValidationMessage key="email_error" :modelValue="v$.email" :label="t('email')" :show="v$.email.error" />
                            </div>
                            <div>
                                <label for="password" class="font-semibold">{{ t('password') }}</label>
                                <div class="relative">
                                    <input :type="!showPassword ? 'password' : 'text'" id="password" v-model="form.password" name="password" class="w-full p-2 pr-5 border-2 border-black rounded-lg focus-visible:outline-none" />
                                    <label class="absolute right-3 top-[0.75rem] cursor-pointer swap  text-2xl" @click="showPassword = !showPassword" :class="{'swap-active': showPassword}">
                                        <i class="iconoir-eye swap-off"></i>
                                        <i class="iconoir-eye-closed swap-on"></i>
                                    </label>
                                </div>
                                <ValidationMessage key="password_error" :modelValue="v$.password" :label="t('password')" :show="v$.password.error" />
                            </div>
                            <div>
                                <button type="submit" class="w-full p-2 bg-orange-400 border-2 border-orange-800 text-black hover:bg-orange-600 transition-all duration-300 rounded-lg uppercase font-black">
                                    {{ t('login') }}
                                </button>
                                <p class="text-center mt-4">
                                {{ t('not_registered_yet') }} <a href="/register" class=" hover:underline">{{ t('register') }}</a>
                            </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useVuelidate } from "@vuelidate/core"
import { email, required } from "@vuelidate/validators";
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';

const props = defineProps({
    isModal: {
        type: Boolean,
        default: false
    },
});

const { t } = useI18n();
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const showPassword = ref(false);
const form = ref({
    email: null,
    password: null,
});

const verified = ref(route.query.verified);

const rules = computed(() => ({
    email: { required, email },
    password: { required },
}));


const v$ = useVuelidate(rules, form);

const submit = async () => {
    v$.value.$touch();
    if (v$.value.$error) {
        return;
    }
    let response = await authStore.login(form.value);
    if (response) {
        if (!response.success) {
            if (response.errors) {
                for (let key in response.errors) {
                    let error = response.errors[key];
                    v$.value[key].$serverError = error
                    v$.value[key].error = true
                }
            }
            else if(response.status === 401){
                let message = t('invalid_credentials');
                if(response.message){
                    message = response.message;
                }
                v$.value.email.$serverError = message;
            }
            else if (response.message) {
                Toast.fire({ icon: 'error', title: response.message });
            }
        } else {
            router.push({ name: 'studio.list' })
        }
    }
};

</script>