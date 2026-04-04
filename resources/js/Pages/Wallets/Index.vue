<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useConfirmDelete } from '@/composables/useConfirmDelete';
import { useCurrency } from '@/composables/useCurrency';
import { useI18n } from 'vue-i18n';

defineProps({
    wallets: Array,
});

const { t } = useI18n();
const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('wallets.confirmDelete'));
const { fmt } = useCurrency();
</script>

<template>
    <Head :title="t('wallets.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">{{ t('wallets.title') }}</h2>
        </template>

        <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-100">
                <div class="mb-6 flex items-center justify-end">
                    <Link href="/wallets/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        {{ t('wallets.createBtn') }}
                    </Link>
                </div>

                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left py-2">{{ t('wallets.colName') }}</th>
                            <th class="text-left py-2">{{ t('wallets.colBalance') }}</th>
                            <th class="text-right py-2">{{ t('wallets.colActions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="wallet in wallets" :key="wallet.id" class="border-b border-gray-700 hover:bg-gray-800">
                            <td class="py-2">
                                <Link :href="`/wallets/${wallet.id}/budget`" class="text-indigo-400 hover:text-indigo-300 font-medium">
                                    {{ wallet.name }}
                                </Link>
                            </td>
                            <td class="py-2">{{ fmt(wallet.start_balance) }}</td>
                            <td class="py-2 space-x-2 text-right">
                                <EditButton :href="`/wallets/${wallet.id}/edit`" />
                                <DeleteButton v-on:click="confirmDelete(`/wallets/${wallet.id}`)" />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <EmptyState v-if="wallets.length === 0" :message="t('wallets.none')" />
            </div>
        </div>

        <ConfirmModal
            :show="isOpen"
            :message="message"
            v-on:confirm="onConfirm"
            v-on:cancel="onCancel"
        />
    </AuthenticatedLayout>
</template>
