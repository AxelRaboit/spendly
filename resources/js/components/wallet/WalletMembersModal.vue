<script setup>
import { Users, Trash2, Send, X, Clock, RefreshCw, ArrowRightLeft } from 'lucide-vue-next';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import { ref, computed, toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import { useWalletMembers, ROLE_COLORS } from '@/composables/wallet/useWalletMembers';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    walletId: { type: Number, required: true },
    isOwner: { type: Boolean, default: false },
    isPro: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const {
    members,
    invitations,
    loading,
    error: memberError,
    success: memberSuccess,
    invite: submitInvite,
    revokeInvitation,
    resendInvitation,
    updateRole,
    transferOwnership,
    removeMember,
} = useWalletMembers(toRef(props, 'walletId'), toRef(props, 'show'));

const inviteEmail = ref('');
const inviteRole = ref('editor');
const inviting = ref(false);

async function invite() {
    if (!inviteEmail.value.trim()) return;
    inviting.value = true;
    const ok = await submitInvite(inviteEmail.value.trim(), inviteRole.value);
    if (ok) inviteEmail.value = '';
    inviting.value = false;
}

const confirmAction = ref(null);
const confirmMessage = ref('');

function requestTransfer(member) {
    confirmAction.value = async () => {
        const ok = await transferOwnership(member);
        confirmAction.value = null;
        if (ok) emit('close');
    };
    confirmMessage.value = t('sharing.confirmTransfer', { name: member.user?.name });
}

function requestRemove(member) {
    confirmAction.value = async () => {
        await removeMember(member);
        confirmAction.value = null;
    };
    confirmMessage.value = t('sharing.confirmRemove', { name: member.user?.name });
}

function cancelConfirm() {
    confirmAction.value = null;
}
</script>

<template>
    <AppModal :show="show" v-on:close="emit('close')">
        <div class="flex items-center gap-3 mb-6">
            <Users class="w-5 h-5 text-indigo-400" />
            <h3 class="text-base font-semibold text-primary">{{ t('sharing.members') }}</h3>
        </div>

        <div v-if="loading" class="text-center py-8 text-muted text-sm">{{ t('common.loading') }}</div>

        <div v-else class="space-y-4">
            <div class="space-y-2">
                <div
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center gap-3 px-3 py-2.5 bg-surface-2 rounded-lg"
                >
                    <div class="w-8 h-8 rounded-full bg-indigo-600/20 flex items-center justify-center text-xs font-bold text-indigo-400 shrink-0">
                        {{ member.user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-primary font-medium truncate">{{ member.user?.name }}</p>
                        <p class="text-xs text-muted truncate">{{ member.user?.email }}</p>
                    </div>

                    <template v-if="isOwner && member.role !== 'owner'">
                        <select
                            :value="member.role"
                            class="bg-surface text-primary rounded px-2 py-1 border border-base text-xs focus:border-indigo-500 focus:outline-none"
                            v-on:change="updateRole(member, $event.target.value)"
                        >
                            <option value="editor">{{ t('sharing.editor') }}</option>
                            <option value="viewer">{{ t('sharing.viewer') }}</option>
                        </select>
                        <AppTooltip :text="t('sharing.transferOwnership')">
                            <button class="text-muted hover:text-amber-400 transition-colors" v-on:click="requestTransfer(member)">
                                <ArrowRightLeft class="w-3.5 h-3.5" />
                            </button>
                        </AppTooltip>
                        <AppTooltip :text="t('sharing.removeMember')">
                            <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="requestRemove(member)">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </AppTooltip>
                    </template>

                    <span v-else class="text-xs px-2 py-0.5 rounded-full" :class="ROLE_COLORS[member.role]">
                        {{ t(`sharing.${member.role}`) }}
                    </span>
                </div>
            </div>

            <div v-if="isOwner && invitations.length > 0" class="space-y-2">
                <p class="text-xs text-secondary uppercase tracking-wide">{{ t('sharing.pending') }}</p>
                <div
                    v-for="inv in invitations"
                    :key="inv.id"
                    class="flex items-center gap-3 px-3 py-2 bg-surface-2 rounded-lg border border-dashed border-amber-500/30"
                >
                    <Clock class="w-4 h-4 text-amber-400 shrink-0" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-primary truncate">{{ inv.email }}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full" :class="ROLE_COLORS[inv.role]">
                        {{ t(`sharing.${inv.role}`) }}
                    </span>
                    <AppTooltip :text="t('sharing.resend')">
                        <button class="text-muted hover:text-indigo-400 transition-colors" v-on:click="resendInvitation(inv)">
                            <RefreshCw class="w-3.5 h-3.5" />
                        </button>
                    </AppTooltip>
                    <AppTooltip :text="t('sharing.revokeInvitation')">
                        <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="revokeInvitation(inv)">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </AppTooltip>
                </div>
            </div>

            <p v-if="memberError" class="text-rose-400 text-xs">{{ memberError }}</p>

            <div v-if="isOwner && isPro" class="border-t border-base pt-4">
                <p class="text-xs text-secondary uppercase tracking-wide mb-2">{{ t('sharing.invite') }}</p>
                <div class="flex items-center gap-2">
                    <input
                        v-model="inviteEmail"
                        type="email"
                        :placeholder="t('sharing.emailPlaceholder')"
                        class="flex-1 bg-surface-2 text-primary rounded-lg px-3 py-2 border border-base text-sm focus:border-indigo-500 focus:outline-none"
                        v-on:keydown.enter="invite"
                    >
                    <select
                        v-model="inviteRole"
                        class="bg-surface-2 text-primary rounded-lg px-2 py-2 border border-base text-sm focus:border-indigo-500 focus:outline-none"
                    >
                        <option value="editor">{{ t('sharing.editor') }}</option>
                        <option value="viewer">{{ t('sharing.viewer') }}</option>
                    </select>
                    <AppButton :disabled="inviting || !inviteEmail.trim()" v-on:click="invite">
                        <Send class="w-4 h-4" />
                    </AppButton>
                </div>
                <p v-if="memberSuccess" class="text-emerald-400 text-xs mt-2">{{ memberSuccess }}</p>
            </div>
        </div>
    </AppModal>

    <ConfirmModal
        :show="confirmAction !== null"
        :message="confirmMessage"
        :confirm-label="t('common.confirm')"
        confirm-variant="danger"
        v-on:confirm="confirmAction?.()"
        v-on:cancel="cancelConfirm"
    />
</template>
