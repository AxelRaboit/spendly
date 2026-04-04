<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirmDelete } from '@/composables/ui/useConfirmDelete';
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';

defineProps({
    wallets: Array,
});

const { t } = useI18n();
const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('wallets.confirmDelete'));
const { fmt } = useCurrency();

function toggleFavorite(wallet) {
    router.post(`/wallets/${wallet.id}/favorite`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('wallets.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-primary leading-tight">{{ t('wallets.title') }}</h2>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link href="/wallets/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition">
                    {{ t('wallets.createBtn') }}
                </Link>
            </div>

            <div v-if="wallets.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="wallet in wallets"
                    :key="wallet.id"
                    class="relative overflow-hidden bg-surface border border-base/60 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow"
                >
                    <!-- Decorative circles -->
                    <div class="pointer-events-none absolute -top-3 -right-3 h-16 w-16 rounded-full bg-indigo-500/10" />
                    <div class="pointer-events-none absolute -bottom-4 -left-4 h-20 w-20 rounded-full bg-indigo-500/5" />

                    <!-- Card body -->
                    <div class="flex flex-col gap-3 pb-3 border-b border-base/40">
                        <Link
                            :href="`/wallets/${wallet.id}/budget`"
                            class="text-base font-semibold text-primary hover:text-indigo-400 transition-colors"
                        >
                            {{ wallet.name }}
                        </Link>
                        <div>
                            <p class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('wallets.colBalance') }}</p>
                            <p class="text-2xl font-bold font-mono text-primary">{{ fmt(wallet.start_balance) }}</p>
                        </div>
                        <Link
                            :href="`/wallets/${wallet.id}/budget`"
                            class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                        >
                            {{ t('wallets.viewBudget') }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <!-- Card footer -->
                    <div class="flex items-center justify-between pt-3">
                        <button
                            :title="wallet.is_favorite ? t('wallets.removeFavorite') : t('wallets.addFavorite')"
                            class="transition-colors"
                            :class="wallet.is_favorite ? 'text-amber-400 hover:text-amber-300' : 'text-muted hover:text-amber-400'"
                            v-on:click="toggleFavorite(wallet)"
                        >
                            <svg class="w-4 h-4" :fill="wallet.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </button>
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
    </AuthenticatedLayout>
</template>
