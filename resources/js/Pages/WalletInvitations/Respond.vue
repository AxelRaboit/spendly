<script setup>
import { Check, X, LogIn, UserPlus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    invitation: Object,
    isAuthenticated: { type: Boolean, default: false },
});

const processing = ref(false);

function accept() {
    processing.value = true;
    router.post(`/wallet-invitations/${props.invitation.token}/accept`, {}, {
        onFinish: () => { processing.value = false; },
    });
}

function decline() {
    processing.value = true;
    router.post(`/wallet-invitations/${props.invitation.token}/decline`, {}, {
        onFinish: () => { processing.value = false; },
    });
}

const invitationUrl = `/wallet-invitations/${props.invitation.token}`;
</script>

<template>
    <Head :title="t('invitation.title')" />

    <component :is="isAuthenticated ? AuthenticatedLayout : GuestLayout">
        <div :class="isAuthenticated ? 'mx-auto max-w-md mt-12 bg-surface border border-base/60 rounded-2xl p-8 text-center space-y-6' : 'text-center space-y-6 py-4'">
            <h2 class="text-xl font-bold text-primary">{{ t('invitation.title') }}</h2>

            <p class="text-secondary">
                {{ t('invitation.description', { name: invitation.inviter_name, wallet: invitation.wallet_name }) }}
            </p>

            <div class="inline-flex items-center gap-2 bg-indigo-600/15 text-indigo-400 text-sm font-medium px-3 py-1.5 rounded-full">
                {{ t(`invitation.role.${invitation.role}`) }}
            </div>

            <!-- Authenticated: accept/decline -->
            <template v-if="isAuthenticated">
                <div class="flex gap-3 justify-center pt-2">
                    <AppButton :disabled="processing" v-on:click="accept">
                        <Check class="w-4 h-4 mr-1.5" />
                        {{ t('invitation.accept') }}
                    </AppButton>
                    <AppButton variant="secondary" :disabled="processing" v-on:click="decline">
                        <X class="w-4 h-4 mr-1.5" />
                        {{ t('invitation.decline') }}
                    </AppButton>
                </div>
            </template>

            <!-- Not authenticated: login/register prompt -->
            <template v-else>
                <p class="text-sm text-muted">
                    {{ t('invitation.authHint', { email: invitation.email }) }}
                </p>
                <div class="flex flex-col gap-3 pt-2">
                    <Link
                        :href="`/login?redirect=${encodeURIComponent(invitationUrl)}&email=${encodeURIComponent(invitation.email)}`"
                        class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition"
                    >
                        <LogIn class="w-4 h-4" />
                        {{ t('invitation.login') }}
                    </Link>
                    <Link
                        :href="`/register?redirect=${encodeURIComponent(invitationUrl)}&email=${encodeURIComponent(invitation.email)}`"
                        class="inline-flex items-center justify-center gap-2 bg-surface-3 hover:bg-surface-2 text-primary font-bold py-2 px-4 rounded border border-base transition"
                    >
                        <UserPlus class="w-4 h-4" />
                        {{ t('invitation.register') }}
                    </Link>
                </div>
            </template>
        </div>
    </component>
</template>
