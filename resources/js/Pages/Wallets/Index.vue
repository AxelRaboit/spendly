<script setup>
import AppTooltip from '@/components/ui/AppTooltip.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TransferModal from '@/components/wallet/TransferModal.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useConfirmDelete } from '@/composables/ui/useConfirmDelete';
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    wallets: Array,
});

const page = usePage();
const { t } = useI18n();
const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('wallets.confirmDelete'));
const { fmt } = useCurrency();
const showTransfer = ref(false);

const isPro = computed(() => page.props.auth?.plan === 'pro');
const walletLimit = computed(() => page.props.planLimits.wallet);
const canCreateWallet = computed(() => isPro.value || props.wallets.length < walletLimit.value);

function toggleFavorite(wallet) {
    router.post(`/wallets/${wallet.id}/favorite`, {}, { preserveScroll: true });
}

// ── Drag-and-drop reorder ─────────────────────────────────────────────────
const localWallets = ref([...props.wallets]);
const draggingId = ref(null);
const dragOverId = ref(null);

function onDragStart(event, wallet) {
    draggingId.value = wallet.id;
    event.dataTransfer.effectAllowed = 'move';
}

function onDragEnd() {
    draggingId.value = null;
    dragOverId.value = null;
}

function onDragOver(event, wallet) {
    event.preventDefault();
    if (wallet.id !== draggingId.value) {
        dragOverId.value = wallet.id;
    }
}

function onDrop(event, targetWallet) {
    event.preventDefault();
    const fromId = draggingId.value;
    if (!fromId || fromId === targetWallet.id) return;

    const list = [...localWallets.value];
    const fromIndex = list.findIndex((w) => w.id === fromId);
    const toIndex = list.findIndex((w) => w.id === targetWallet.id);
    const [moved] = list.splice(fromIndex, 1);
    list.splice(toIndex, 0, moved);
    localWallets.value = list;

    draggingId.value = null;
    dragOverId.value = null;

    router.patch(route('wallets.reorder'), { ids: list.map((w) => w.id) }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('wallets.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('wallets.title')" />
        </template>

        <div class="space-y-4">
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" v-on:click="showTransfer = true">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    {{ t('transfers.new') }}
                </AppButton>
                <div class="relative">
                    <Link
                        href="/wallets/create"
                        :class="[
                            'bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition inline-flex items-center gap-2',
                            !canCreateWallet ? 'opacity-60 cursor-not-allowed pointer-events-none' : ''
                        ]"
                    >
                        {{ t('wallets.createBtn') }}
                    </Link>
                    <span v-if="!isPro && props.wallets.length >= walletLimit" class="absolute -top-2 -right-2 bg-amber-500 text-xs text-white font-bold px-2 py-1 rounded-full">
                        Pro
                    </span>
                </div>
            </div>

            <div v-if="localWallets.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="wallet in localWallets"
                    :key="wallet.id"
                    class="relative overflow-hidden bg-surface border border-base/60 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all cursor-grab active:cursor-grabbing select-none"
                    :class="{
                        'opacity-50 scale-95': draggingId === wallet.id,
                        'ring-2 ring-indigo-500/50 border-indigo-500/50': dragOverId === wallet.id,
                    }"
                    draggable="true"
                    v-on:dragstart="onDragStart($event, wallet)"
                    v-on:dragend="onDragEnd"
                    v-on:dragover="onDragOver($event, wallet)"
                    v-on:drop="onDrop($event, wallet)"
                >
                    <div class="pointer-events-none absolute -top-3 -right-3 h-16 w-16 rounded-full bg-indigo-500/10" />
                    <div class="pointer-events-none absolute -bottom-4 -left-4 h-20 w-20 rounded-full bg-indigo-500/5" />

                    <div class="absolute top-3 right-3 text-muted/40 pointer-events-none">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                            <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                            <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                        </svg>
                    </div>

                    <div class="flex flex-col gap-3 pb-3 border-b border-base/40">
                        <Link
                            :href="`/wallets/${wallet.id}/budget`"
                            class="text-base font-semibold text-primary hover:text-indigo-400 transition-colors"
                            v-on:dragstart.prevent
                        >
                            {{ wallet.name }}
                        </Link>
                        <div>
                            <p class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('wallets.colBalance') }}</p>
                            <p class="text-2xl font-bold font-mono" :class="wallet.current_balance >= 0 ? 'text-primary' : 'text-rose-400'">
                                {{ fmt(wallet.current_balance) }}
                            </p>
                            <p class="text-xs text-muted mt-0.5">{{ t('wallets.startBalance') }} {{ fmt(wallet.start_balance) }}</p>
                        </div>
                        <Link
                            :href="`/wallets/${wallet.id}/budget`"
                            class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                            v-on:dragstart.prevent
                        >
                            {{ t('wallets.viewBudget') }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <div class="flex items-center justify-between pt-3">
                        <AppTooltip :text="wallet.is_favorite ? t('wallets.removeFavorite') : t('wallets.addFavorite')">
                            <button
                                class="transition-colors"
                                :class="wallet.is_favorite ? 'text-amber-400 hover:text-amber-300' : 'text-muted hover:text-amber-400'"
                                v-on:click="toggleFavorite(wallet)"
                            >
                                <svg class="w-4 h-4" :fill="wallet.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </button>
                        </AppTooltip>
                        <div class="flex items-center gap-2">
                            <EditButton :href="`/wallets/${wallet.id}/edit`" />
                            <DeleteButton v-on:click="confirmDelete(`/wallets/${wallet.id}`)" />
                        </div>
                    </div>
                </div>
            </div>

            <EmptyState v-else :message="t('wallets.none')" />
        </div>

        <ConfirmModal
            :show="isOpen"
            :message="message"
            v-on:confirm="onConfirm"
            v-on:cancel="onCancel"
        />

        <TransferModal
            :show="showTransfer"
            :wallets="localWallets"
            v-on:close="showTransfer = false"
        />
    </AuthenticatedLayout>
</template>
