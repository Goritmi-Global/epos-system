<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { EyeIcon, EyeOffIcon } from 'lucide-vue-next'; // 👁️ Use lucide icons

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

// 👁️ Reactive toggles for visibility
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const togglePassword = () => (showPassword.value = !showPassword.value);
const toggleConfirmPassword = () =>
    (showConfirmPassword.value = !showConfirmPassword.value);

const submit = () => {
    form.post(route('password.store'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />
        
        <form @submit.prevent="submit">
            <!-- Email -->
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="mt-4 relative">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    class="mt-1 block w-full pr-10"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <span
                    class="absolute right-3 top-9 cursor-pointer text-gray-500"
                    @click="togglePassword"
                >
                    <component :is="showPassword ? EyeOffIcon : EyeIcon" size="20" />
                </span>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4 relative">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    class="mt-1 block w-full pr-10"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <span
                    class="absolute right-3 top-9 cursor-pointer text-gray-500"
                    @click="toggleConfirmPassword"
                >
                    <component
                        :is="showConfirmPassword ? EyeOffIcon : EyeIcon"
                        size="20"
                    />
                </span>
                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <!-- Submit -->
            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Reset Password
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
