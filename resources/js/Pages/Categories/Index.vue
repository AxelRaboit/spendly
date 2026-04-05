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
            <AppPageHeader :title="t('categories.title')" />
        </template>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <SearchInput :model-value="filters.search" :placeholder="t('categories.searchPlaceholder')" class="w-full sm:max-w-xs" />
                <Link href="/categories/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shrink-0 text-center sm:ml-auto transition">
                    {{ t('categories.createBtn') }}
                </Link>
            </div>

            <div v-if="categories.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="category in categories.data"
                    :key="category.id"
                    class="relative overflow-hidden bg-surface border border-base/60 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow"
                >
                    <div class="pointer-events-none absolute -top-3 -right-3 h-16 w-16 rounded-full bg-indigo-500/10" />
                    <div class="pointer-events-none absolute -bottom-4 -left-4 h-20 w-20 rounded-full bg-indigo-500/5" />

                    <div class="pb-3 border-b border-base/40">
                        <p class="text-base font-semibold text-primary truncate">{{ category.name }}</p>
                    </div>

                    <div class="flex items-center justify-end pt-3 gap-2">
                        <EditButton :href="`/categories/${category.id}/edit`" />
                        <DeleteButton v-on:click="confirmDelete(`/categories/${category.id}`)" />
                    </div>
                </div>
            </div>

            <EmptyState v-else :message="t('categories.none')" />

            <AppPagination :meta="categories" />
        </div>

        <ConfirmModal
            :show="isOpen"
            :message="message"
            v-on:confirm="onConfirm"
            v-on:cancel="onCancel"
        />
    </AuthenticatedLayout>
</template>
