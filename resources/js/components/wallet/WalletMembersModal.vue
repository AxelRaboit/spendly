<script setup>
import { Users, Trash2, Send, X, Clock, RefreshCw, ArrowRightLeft, Plus } from 'lucide-vue-next';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import { ref, computed, toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import { useWalletMembers, ROLE_COLORS } from '@/composables/wallet/useWalletMembers';
import { WalletRole } from '@/enums/WalletRole';

const { t: translate } = useI18n();

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

const inviteRows = ref([{ email: '', role: WalletRole.Editor }]);
const inviting = ref(false);

function addInviteRow() {
    inviteRows.value.push({ email: '', role: WalletRole.Editor });
}

function removeInviteRow(index) {
    inviteRows.value.splice(index, 1);
}

async function invite() {
    const rows = inviteRows.value.filter(inviteRow => inviteRow.email.trim());
    if (!rows.length) return;
    inviting.value = true;
    for (const row of rows) {
        await submitInvite(row.email.trim(), row.role);
    }
    inviteRows.value = [{ email: '', role: WalletRole.Editor }];
    inviting.value = false;
}

const confirmAction = ref(null);
const confirmMessage = ref('');

function requestTransfer(member) {
    confirmAction.value = async () => {
        const isSuccess = await transferOwnership(member);
        confirmAction.value = null;
        if (isSuccess) emit('close');
    };
    confirmMessage.value = translate('sharing.confirmTransfer', { name: member.user?.name });
}

function requestRemove(member) {
    confirmAction.value = async () => {
        await removeMember(member);
        confirmAction.value = null;
    };
    confirmMessage.value = translate('sharing.confirmRemove', { name: member.user?.name });
}

function cancelConfirm() {
    confirmAction.value = null;
}
</script>

<template>
    <AppModal :show="show" v-on:close="emit('close')">
        <div class="flex items-center gap-3 mb-6">
            <Users class="w-5 h-5 text-indigo-400" />
            <h3 class="text-base font-semibold text-primary">{{ translate('sharing.members') }}</h3>
        </div>

        <div v-if="loading" class="text-center py-8 text-muted text-sm">{{ translate('common.loading') }}</div>

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

                    <template v-if="isOwner && member.role !== WalletRole.Owner">
                        <select
                            :value="member.role"
                            class="bg-surface text-primary rounded px-2 py-1 border border-line text-xs focus:border-indigo-500 focus:outline-none"
                            v-on:change="updateRole(member, $event.target.value)"
                        >
                            <option :value="WalletRole.Editor">{{ translate('sharing.editor') }}</option>
                            <option :value="WalletRole.Viewer">{{ translate('sharing.viewer') }}</option>
                        </select>
                        <AppTooltip :text="translate('sharing.transferOwnership')">
                            <button class="text-muted hover:text-amber-400 transition-colors" v-on:click="requestTransfer(member)">
                                <ArrowRightLeft class="w-3.5 h-3.5" />
                            </button>
                        </AppTooltip>
                        <AppTooltip :text="translate('sharing.removeMember')">
                            <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="requestRemove(member)">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </AppTooltip>
                    </template>

                    <span v-else class="text-xs px-2 py-0.5 rounded-full" :class="ROLE_COLORS[member.role]">
                        {{ translate(`sharing.${member.role}`) }}
                    </span>
                </div>
            </div>

            <div v-if="isOwner && invitations.length > 0" class="space-y-2">
                <p class="text-xs text-secondary uppercase tracking-wide">{{ translate('sharing.pending') }}</p>
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
                        {{ translate(`sharing.${inv.role}`) }}
                    </span>
                    <AppTooltip :text="translate('sharing.resend')">
                        <button class="text-muted hover:text-indigo-400 transition-colors" v-on:click="resendInvitation(inv)">
                            <RefreshCw class="w-3.5 h-3.5" />
                        </button>
                    </AppTooltip>
                    <AppTooltip :text="translate('sharing.revokeInvitation')">
                        <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="revokeInvitation(inv)">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </AppTooltip>
                </div>
            </div>

            <p v-if="memberError" class="text-rose-400 text-xs">{{ memberError }}</p>

            <div v-if="isOwner && isPro" class="border-t border-line pt-4">
                <p class="text-xs text-secondary uppercase tracking-wide mb-3">{{ translate('sharing.invite') }}</p>
                <div class="flex flex-col gap-3">
                    <div v-for="(row, index) in inviteRows" :key="index" class="flex flex-col gap-2 border border-line rounded-lg p-3 bg-surface-2">
                        <div class="flex items-center gap-2">
                            <input
                                v-model="row.email"
                                type="email"
                                :placeholder="translate('sharing.emailPlaceholder')"
                                class="flex-1 bg-surface text-primary rounded-lg px-3 py-2 border border-line text-sm focus:border-indigo-500 focus:outline-none"
                                v-on:keydown.enter="invite"
                            >
                            <button
                                v-if="inviteRows.length > 1"
                                class="text-muted hover:text-rose-400 transition-colors"
                                v-on:click="removeInviteRow(index)"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <select
                            v-model="row.role"
                            class="w-full bg-surface text-primary rounded-lg px-2 py-2 border border-line text-sm focus:border-indigo-500 focus:outline-none"
                        >
                            <option value="editor">{{ translate('sharing.editor') }}</option>
                            <option value="viewer">{{ translate('sharing.viewer') }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <button
                            class="flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                            v-on:click="addInviteRow"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            {{ translate('sharing.addAddress') }}
                        </button>
                        <AppButton :disabled="inviting || !inviteRows.some(inviteRow => inviteRow.email.trim())" v-on:click="invite">
                            <Send class="w-4 h-4" />
                        </AppButton>
                    </div>
                </div>
                <p v-if="memberSuccess" class="text-emerald-400 text-xs mt-2">{{ memberSuccess }}</p>
            </div>
        </div>
    </AppModal>

    <ConfirmModal
        :show="confirmAction !== null"
        :message="confirmMessage"
        :confirm-label="translate('common.confirm')"
        confirm-variant="danger"
        v-on:confirm="confirmAction?.()"
        v-on:cancel="cancelConfirm"
    />
</template>
