<script setup>
import { Trash2 } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useConfirmDelete } from '@/composables/ui/useConfirmDelete';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    rules: Object,
    categories: Array,
});

const { t } = useI18n();
const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('rules.confirmDelete'));

function updateCategory(rule, categoryId) {
    router.put(`/categorization-rules/${rule.id}`, { category_id: categoryId }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('rules.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('rules.title')" />
        </template>

        <div class="space-y-4">
            <p class="text-sm text-secondary">{{ t('rules.subtitle') }}</p>

            <div v-if="rules.data.length > 0" class="bg-surface border border-base/60 rounded-2xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-base/40">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('rules.pattern') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('rules.category') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('rules.hits') }}</th>
                            <th class="px-4 py-3 w-12" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base/40">
                        <tr v-for="rule in rules.data" :key="rule.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-4 py-3 text-primary font-medium">{{ rule.pattern }}</td>
                            <td class="px-4 py-3">
                                <select
                                    :value="rule.category_id"
                                    class="bg-surface-2 text-primary rounded px-2 py-1 border border-base text-sm focus:border-indigo-500 focus:outline-none"
                                    v-on:change="updateCategory(rule, Number($event.target.value))"
                                >
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-right text-muted font-mono">{{ rule.hits }}</td>
                            <td class="px-4 py-3 text-right">
                                <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="confirmDelete(`/categorization-rules/${rule.id}`)">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState v-else :message="t('rules.none')" icon="list" />

            <AppPagination :meta="rules" />
        </div>

        <ConfirmModal
            :show="isOpen"
            :message="message"
            v-on:confirm="onConfirm"
            v-on:cancel="onCancel"
        />
    </AuthenticatedLayout>
</template>
