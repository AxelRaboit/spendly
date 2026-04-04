<script setup>
import { CURRENCIES } from '@/composables/useCurrency';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
});

const user = usePage().props.auth.user;

const form = useForm({
    name:     user.name,
    email:    user.email,
    currency: user.currency ?? 'EUR',
});
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-gray-100">Informations du profil</h2>
            <p class="mt-1 text-sm text-gray-400">Mettez à jour votre nom, adresse e-mail et devise.</p>
        </header>

        <form class="space-y-5" v-on:submit.prevent="form.patch(route('profile.update'))">
            <div>
                <label for="name" class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Nom</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                >
                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Adresse e-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                >
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-400">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="currency" class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Devise</label>
                <select
                    id="currency"
                    v-model="form.currency"
                    class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                >
                    <option v-for="c in CURRENCIES" :key="c.code" :value="c.code">{{ c.label }}</option>
                </select>
                <p v-if="form.errors.currency" class="mt-1 text-xs text-rose-400">{{ form.errors.currency }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-400">
                    Votre adresse e-mail n'est pas vérifiée.
                    <Link :href="route('verification.send')" method="post" as="button" class="underline text-indigo-400 hover:text-indigo-300">
                        Renvoyer l'e-mail de vérification.
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="mt-2 text-xs text-emerald-400">
                    Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                </div>
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
