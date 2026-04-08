<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/form/InputError.vue';
import InputLabel from '@/components/form/InputLabel.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

const submit = () => {
    form.post(route('dev.password.check'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Accès restreint" />

        <form v-on:submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Mot de passe" />

                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <AppButton
                type="submit"
                class="mt-6 w-full justify-center"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Accéder
            </AppButton>
        </form>
    </GuestLayout>
</template>
