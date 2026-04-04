<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useConfirmDelete } from '@/composables/ui/useConfirmDelete';
import { useI18n } from 'vue-i18n';

defineProps({
    categories: Object,
    filters: Object,
});

const { t } = useI18n();
const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('categories.confirmDelete'));
</script>

<template>
    <Head :title="t('categories.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-primary leading-tight">{{ t('categories.title') }}</h2>
        </template>

        <div class="bg-surface overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-primary">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-3">
                    <SearchInput :model-value="filters.search" :placeholder="t('categories.searchPlaceholder')" class="w-full sm:max-w-xs" />
                    <Link href="/categories/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shrink-0 text-center sm:ml-auto">
                        {{ t('categories.createBtn') }}
                    </Link>
                </div>

                <!-- Mobile cards -->
                <div class="sm:hidden divide-y divide-base">
                    <div v-for="category in categories.data" :key="category.id" class="flex items-center justify-between px-1 py-3 gap-3">
                        <span class="text-sm text-primary truncate">{{ category.name }}</span>
                        <div class="flex items-center gap-2 shrink-0">
                            <EditButton :href="`/categories/${category.id}/edit`" />
                            <DeleteButton v-on:click="confirmDelete(`/categories/${category.id}`)" />
                        </div>
                    </div>
                </div>

                <!-- Desktop table -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="border-b border-base">
                                <th class="text-left py-2">{{ t('categories.colName') }}</th>
                                <th class="text-right py-2">{{ t('categories.colActions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in categories.data" :key="category.id" class="border-b border-base hover:bg-surface-2">
                                <td class="py-2">{{ category.name }}</td>
                                <td class="py-2 space-x-2 text-right">
                                    <EditButton :href="`/categories/${category.id}/edit`" />
                                    <DeleteButton v-on:click="confirmDelete(`/categories/${category.id}`)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-if="categories.data.length === 0" :message="t('categories.none')" />

                <AppPagination :meta="categories" />
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
