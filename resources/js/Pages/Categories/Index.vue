<script setup>
import { Wallet, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/components/ui/Modal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useConfirmDelete } from '@/composables/ui/useConfirmDelete';
import { useCategoryForm } from '@/composables/forms/useCategoryForm';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    categories: Object,
    wallets: Array,
    filters: Object,
});

const { t } = useI18n();
const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('categories.confirmDelete'));

function filterByWallet(walletId) {
    router.get('/categories', { wallet_id: walletId || undefined, search: props.filters.search || undefined }, { preserveState: true, replace: true });
}

const showCreateModal = ref(false);
const { form: createForm, submit: submitCreate } = useCategoryForm(null, { onSuccess: () => { showCreateModal.value = false; } });

function openCreateModal() {
    createForm.reset();
    showCreateModal.value = true;
}
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
                <SelectInput
                    :model-value="filters.wallet_id || ''"
                    class="w-full sm:max-w-52"
                    v-on:change="filterByWallet($event.target.value)"
                >
                    <option value="">{{ t('categories.allWallets') }}</option>
                    <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                </SelectInput>
                <AppButton class="shrink-0 sm:ml-auto" v-on:click="openCreateModal">
                    <Plus class="w-4 h-4 mr-1.5" />
                    {{ t('categories.createBtn') }}
                </AppButton>
            </div>

            <div v-if="categories.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="category in categories.data"
                    :key="category.id"
                    class="relative overflow-hidden bg-surface border border-line/60 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow"
                >
                    <div class="pointer-events-none absolute -top-3 -right-3 h-16 w-16 rounded-full bg-indigo-500/10" />
                    <div class="pointer-events-none absolute -bottom-4 -left-4 h-20 w-20 rounded-full bg-indigo-500/5" />

                    <div class="pb-3 border-b border-line/40">
                        <p class="text-base font-semibold text-primary truncate">{{ category.name }}</p>
                        <div class="flex items-center justify-between mt-2">
                            <p v-if="category.wallet" class="flex items-center gap-1 text-xs text-muted">
                                <Wallet class="w-3 h-3" />
                                {{ category.wallet.name }}
                            </p>
                            <span class="text-xs font-medium px-2 py-1 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                {{ category.transactions_count }} {{ t('categories.usage', { count: category.transactions_count }) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-3 gap-2">
                        <EditButton :href="`/categories/${category.id}/edit`" />
                        <DeleteButton v-on:click="confirmDelete(`/categories/${category.id}`)" />
                    </div>
                </div>
            </div>

            <EmptyState v-else :message="t('categories.none')" icon="tag" />

            <AppPagination :meta="categories" />
        </div>

        <ConfirmModal
            :show="isOpen"
            :message="message"
            v-on:confirm="onConfirm"
            v-on:cancel="onCancel"
        />

        <Modal :show="showCreateModal" max-width="md" v-on:close="showCreateModal = false">
            <div class="bg-surface p-6 space-y-5">
                <h2 class="text-base font-semibold text-primary">{{ t('categories.createTitle') }}</h2>

                <form class="space-y-4" v-on:submit.prevent="submitCreate">
                    <div>
                        <InputLabel :value="t('categories.fieldName')" required />
                        <TextInput v-model="createForm.name" type="text" :placeholder="t('categories.placeholder')" autofocus />
                        <InputError :message="createForm.errors.name" />
                    </div>

                    <div>
                        <InputLabel :value="t('categories.fieldWallet')" required />
                        <SelectInput v-model="createForm.wallet_id">
                            <option value="" disabled>— {{ t('categories.pickWallet') }} —</option>
                            <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </SelectInput>
                        <InputError :message="createForm.errors.wallet_id" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <AppButton type="button" variant="secondary" v-on:click="showCreateModal = false">
                            {{ t('common.cancel') }}
                        </AppButton>
                        <AppButton type="submit" :disabled="createForm.processing">
                            {{ t('common.create') }}
                        </AppButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
