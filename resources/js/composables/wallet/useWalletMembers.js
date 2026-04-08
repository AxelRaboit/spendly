import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

export const ROLE_COLORS = {
    owner: 'text-amber-600 dark:text-amber-400 bg-amber-500/15',
    editor: 'text-sky-600 dark:text-sky-400 bg-sky-500/15',
    viewer: 'text-muted bg-surface-3',
};

export function useWalletMembers(walletId, show) {
    const { t } = useI18n();

    const members = ref([]);
    const invitations = ref([]);
    const loading = ref(false);
    const error = ref('');
    const success = ref('');

    function clearFlash() {
        setTimeout(() => {
            success.value = '';
        }, 3000);
    }

    function handleError(err) {
        error.value = err.response?.data?.message || t('common.error');
    }

    async function loadMembers() {
        loading.value = true;
        try {
            const res = await window.axios.get(`/wallets/${walletId.value}/members`);
            members.value = res.data.members;
            invitations.value = res.data.invitations;
        } finally {
            loading.value = false;
        }
    }

    async function invite(email, role) {
        error.value = '';
        success.value = '';
        try {
            await window.axios.post(`/wallets/${walletId.value}/invitations`, { email, role });
            success.value = t('sharing.inviteSent');
            await loadMembers();
            clearFlash();
            return true;
        } catch (err) {
            handleError(err);
            return false;
        }
    }

    async function revokeInvitation(invitation) {
        try {
            await window.axios.delete(`/wallets/${walletId.value}/invitations/${invitation.id}`);
            invitations.value = invitations.value.filter((i) => i.id !== invitation.id);
        } catch (err) {
            handleError(err);
        }
    }

    async function resendInvitation(invitation) {
        try {
            const { data } = await window.axios.post(`/wallets/${walletId.value}/invitations/${invitation.id}/resend`);
            const idx = invitations.value.findIndex((i) => i.id === invitation.id);
            if (idx !== -1) invitations.value[idx] = data;
            success.value = t('sharing.inviteResent');
            clearFlash();
        } catch (err) {
            handleError(err);
        }
    }

    async function updateRole(member, newRole) {
        try {
            await window.axios.patch(`/wallets/${walletId.value}/members/${member.id}`, { role: newRole });
            member.role = newRole;
        } catch (err) {
            handleError(err);
        }
    }

    async function transferOwnership(member) {
        try {
            await window.axios.post(`/wallets/${walletId.value}/members/${member.id}/transfer-ownership`);
            await loadMembers();
            return true;
        } catch (err) {
            handleError(err);
            return false;
        }
    }

    async function removeMember(member) {
        try {
            await window.axios.delete(`/wallets/${walletId.value}/members/${member.id}`);
            members.value = members.value.filter((m) => m.id !== member.id);
        } catch (err) {
            handleError(err);
        }
    }

    watch(
        show,
        (open) => {
            if (open) loadMembers();
        },
        { immediate: true }
    );

    return {
        members,
        invitations,
        loading,
        error,
        success,
        invite,
        revokeInvitation,
        resendInvitation,
        updateRole,
        transferOwnership,
        removeMember,
    };
}
