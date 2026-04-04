<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});

function updatePassword() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
}
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-gray-100">Modifier le mot de passe</h2>
            <p class="mt-1 text-sm text-gray-400">Utilisez un mot de passe long et aléatoire pour sécuriser votre compte.</p>
        </header>

        <form class="space-y-5" v-on:submit.prevent="updatePassword">
            <div>
                <label for="current_password" class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Mot de passe actuel</label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                >
                <p v-if="form.errors.current_password" class="mt-1 text-xs text-rose-400">{{ form.errors.current_password }}</p>
            </div>

            <div>
                <label for="password" class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Nouveau mot de passe</label>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                >
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Confirmer le mot de passe</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                >
                <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-rose-400">{{ form.errors.password_confirmation }}</p>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <AppButton type="submit" :disabled="form.processing">Enregistrer</AppButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-400">Enregistré.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
