<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

function openModal() {
    confirmingDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
}

function closeModal() {
    confirmingDeletion.value = false;
    form.clearErrors();
    form.reset();
}

function deleteUser() {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError:   () => passwordInput.value?.focus(),
        onFinish:  () => form.reset(),
    });
}
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-rose-400">Supprimer le compte</h2>
            <p class="mt-1 text-sm text-gray-400">
                Une fois votre compte supprimé, toutes les données associées seront définitivement effacées.
            </p>
        </header>

        <button
            type="button"
            class="px-4 py-2 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-600/40 rounded-lg text-sm font-medium transition-colors"
            v-on:click="openModal"
        >
            Supprimer mon compte
        </button>
    </section>

    <!-- Confirmation modal -->
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="confirmingDeletion" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-black/60" v-on:click="closeModal" />

            <div class="relative z-10 w-full max-w-md bg-gray-900 border border-gray-700 rounded-xl p-6 shadow-2xl" v-on:keydown.esc="closeModal">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-900/40">
                        <svg
                            class="h-5 w-5 text-rose-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-100">Supprimer le compte</h3>
                        <p class="text-sm text-gray-400 mt-0.5">Cette action est irréversible. Confirmez avec votre mot de passe.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="delete-password" class="sr-only">Mot de passe</label>
                    <input
                        id="delete-password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder="Votre mot de passe"
                        class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-rose-500 focus:outline-none"
                        v-on:keyup.enter="deleteUser"
                    >
                    <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</p>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm text-gray-400 hover:text-gray-200 border border-gray-700 rounded-lg transition-colors"
                        v-on:click="closeModal"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white rounded-lg transition-colors"
                        v-on:click="deleteUser"
                    >
                        {{ form.processing ? 'Suppression…' : 'Supprimer définitivement' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
