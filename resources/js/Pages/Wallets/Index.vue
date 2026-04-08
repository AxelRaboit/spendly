<script setup>
import { ArrowLeftRight, GripVertical, ChevronRight, Star, Users, FlaskConical, Plus, LayoutDashboard } from 'lucide-vue-next';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import QuickCreateCard from '@/components/wallet/QuickCreateCard.vue';
import TransferModal from '@/components/wallet/TransferModal.vue';
import WalletMembersModal from '@/components/wallet/WalletMembersModal.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { useConfirmDelete } from '@/composables/ui/useConfirmDelete';
import { useDragDrop } from '@/composables/ui/useDragDrop';
import { useCurrency } from '@/composables/core/useCurrency';
import { usePlanLimits } from '@/composables/ui/usePlanLimits';
import { useTour } from '@/composables/ui/useTour';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    wallets: Array,
});

const { t } = useI18n();
const { isPro, canCreate, limit } = usePlanLimits();
const { initForPage, tourActive } = useTour();
const showProFeatures = computed(() => isPro.value || tourActive.value);
onMounted(() => initForPage('wallets.index'));

function deleteDemoWallet() {
    router.delete(route('tour.cleanup'));
}
const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('wallets.confirmDelete'));
const { fmt } = useCurrency();
const showTransfer = ref(false);
const showMembers = ref(false);
const membersWallet = ref(null);

function openMembers(wallet) {
    membersWallet.value = wallet;
    showMembers.value = true;
}

const canCreateWallet = computed(() => canCreate('wallet', props.wallets.length));

function toggleFavorite(wallet) {
    router.post(`/wallets/${wallet.id}/favorite`, {}, { preserveScroll: true });
}

function toggleDashboard(wallet) {
    router.post(`/wallets/${wallet.id}/dashboard`, {}, { preserveScroll: true });
}

// ── Drag-and-drop reorder ─────────────────────────────────────────────────
const localWallets = ref([...props.wallets]);
watch(() => props.wallets, (val) => { localWallets.value = [...val]; });
const { draggingId, dragOverId, onDragStart, onDragEnd, onDragOver, onDrop } = useDragDrop(
    localWallets,
    (ids) => router.patch(route('wallets.reorder'), { ids }, { preserveScroll: true }),
);

// ── Quick-create placeholder ─────────────────────────────────────────────
const quickSuggestions = [
    { name: t('wallets.quickChecking'), key: 'checking' },
    { name: t('wallets.quickSavings'), key: 'savings' },
];

const quickForm = useForm({ name: '', start_balance: 0 });

function quickCreate(name) {
    quickForm.name = name;
    quickForm.post('/wallets');
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
                    <ArrowLeftRight class="w-4 h-4 mr-1.5" />
                    {{ t('transfers.new') }}
                </AppButton>
                <div v-if="canCreateWallet || showProFeatures" data-tour="create-wallet">
                    <Link
                        href="/wallets/create"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition inline-flex items-center gap-2"
                    >
                        <Plus class="w-4 h-4" />
                        {{ t('wallets.createBtn') }}
                    </Link>
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
                        <GripVertical class="w-4 h-4" />
                    </div>

                    <div class="flex flex-col gap-3 pb-3 border-b border-base/40">
                        <div class="flex items-center gap-2">
                            <Link
                                :href="`/wallets/${wallet.id}/budget`"
                                class="text-base font-semibold text-primary hover:text-indigo-400 transition-colors"
                                v-on:dragstart.prevent
                            >
                                {{ wallet.name }}
                            </Link>
                            <span v-if="wallet.is_demo" class="flex items-center gap-1 rounded-full bg-badge-warning-bg px-2 py-0.5 text-2xs font-medium text-badge-warning-text">
                                <FlaskConical class="w-2.5 h-2.5" />
                                {{ t('wallets.demo') }}
                            </span>
                            <span v-if="wallet.is_shared" class="flex items-center gap-1 rounded-full bg-badge-primary-bg px-2 py-0.5 text-2xs font-medium text-badge-primary-text">
                                <Users class="w-2.5 h-2.5" />
                                {{ t(`sharing.${wallet.user_role}`) }}
                            </span>
                        </div>
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
                            <ChevronRight class="w-3 h-3" />
                        </Link>
                    </div>

                    <div class="flex items-center justify-between pt-3">
                        <div class="flex items-center gap-2">
                            <AppTooltip :text="wallet.is_favorite ? t('wallets.removeFavorite') : t('wallets.addFavorite')">
                                <button
                                    class="transition-colors"
                                    :class="wallet.is_favorite ? 'text-amber-400 hover:text-amber-300' : 'text-muted hover:text-amber-400'"
                                    v-on:click="toggleFavorite(wallet)"
                                >
                                    <Star class="w-4 h-4" :fill="wallet.is_favorite ? 'currentColor' : 'none'" />
                                </button>
                            </AppTooltip>
                            <AppTooltip :text="wallet.show_on_dashboard ? t('wallets.removeFromDashboard') : t('wallets.pinToDashboard')">
                                <button
                                    class="transition-colors"
                                    :class="wallet.show_on_dashboard ? 'text-indigo-400 hover:text-indigo-300' : 'text-muted hover:text-indigo-400'"
                                    v-on:click="toggleDashboard(wallet)"
                                >
                                    <LayoutDashboard class="w-4 h-4" />
                                </button>
                            </AppTooltip>
                        </div>
                        <div class="flex items-center gap-2">
                            <AppTooltip v-if="showProFeatures || wallet.is_shared" :text="t('sharing.members')">
                                <button class="text-muted hover:text-indigo-400 transition-colors" v-on:click="openMembers(wallet)">
                                    <Users class="w-4 h-4" />
                                </button>
                            </AppTooltip>
                            <EditButton v-if="wallet.user_role === 'owner' && !wallet.is_demo" :href="`/wallets/${wallet.id}/edit`" />
                            <DeleteButton v-if="wallet.user_role === 'owner' && !wallet.is_demo" v-on:click="confirmDelete(`/wallets/${wallet.id}`)" />
                            <DeleteButton v-if="wallet.is_demo" v-on:click="deleteDemoWallet(wallet)" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <QuickCreateCard
                    v-for="suggestion in quickSuggestions"
                    :key="suggestion.key"
                    :name="suggestion.name"
                    :disabled="quickForm.processing"
                    v-on:create="quickCreate"
                />
            </div>
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

        <WalletMembersModal
            v-if="membersWallet"
            :key="membersWallet.id"
            :show="showMembers"
            :wallet-id="membersWallet.id"
            :is-owner="membersWallet.user_role === 'owner'"
            :is-pro="isPro"
            v-on:close="showMembers = false"
        />
    </AuthenticatedLayout>
</template>
