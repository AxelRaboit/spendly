<script setup>
import { Check, Download, FileSpreadsheet, ChevronRight, X, Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/components/ui/AppButton.vue';
import FormHint from '@/components/form/FormHint.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useImportUpload } from '@/composables/import/useImportUpload';
import { useImportRows } from '@/composables/import/useImportRows';

const { t } = useI18n();

const props = defineProps({
    wallets:    Array,
    categories: Array,
});

const { step, file, isDragging, uploadError, uploading, walletId, selectedWalletName, onFileInput, onDrop, uploadAndPreview: doUpload } =
    useImportUpload(props.wallets, t);

const { rows, bulkCategory, processing, allCategorized, loadRows, applyBulkCategory, addRow, removeRow, submitImport } =
    useImportRows(walletId);

function uploadAndPreview() {
    doUpload(bulkCategory.value, loadRows);
}
</script>

<template>
    <Head :title="t('import.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('import.title')" />
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex items-center gap-2">
                <template v-for="(label, idx) in [t('import.step1'), t('import.step3')]" :key="idx">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                            :class="step > idx + 1 ? 'bg-emerald-500 text-white' : step === idx + 1 ? 'bg-indigo-600 text-white' : 'bg-surface-2 text-muted'"
                        >
                            <Check v-if="step > idx + 1" class="w-4 h-4" />
                            <span v-else>{{ idx + 1 }}</span>
                        </div>
                        <span class="text-sm" :class="step === idx + 1 ? 'text-primary font-medium' : 'text-muted'">{{ label }}</span>
                    </div>
                    <div v-if="idx < 1" class="flex-1 h-px bg-base" />
                </template>
            </div>

            <div v-if="step === 1" class="bg-surface border border-base/60 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-between bg-indigo-500/5 border border-indigo-500/20 rounded-lg px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-primary">{{ t('import.templateTitle') }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ t('import.templateDesc') }}</p>
                    </div>
                    <a
                        href="/import/template"
                        class="flex items-center gap-1.5 text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors shrink-0 ml-4"
                    >
                        <Download class="w-4 h-4" />
                        {{ t('import.templateDownload') }}
                    </a>
                </div>

                <label
                    class="block cursor-pointer border-2 border-dashed rounded-xl p-10 text-center transition-colors"
                    :class="isDragging ? 'border-indigo-500 bg-indigo-500/5' : 'border-base hover:border-indigo-500/50'"
                    v-on:dragover.prevent="isDragging = true"
                    v-on:dragleave="isDragging = false"
                    v-on:drop.prevent="onDrop"
                >
                    <input type="file" accept=".xlsx" class="hidden" v-on:change="onFileInput">
                    <FileSpreadsheet class="w-10 h-10 text-muted mx-auto mb-3" :stroke-width="1.5" />
                    <p v-if="!file" class="text-secondary text-sm font-medium">{{ t('import.dropzone') }}</p>
                    <p v-if="!file" class="text-muted text-xs mt-1">{{ t('import.dropzoneOr') }}</p>
                    <p v-if="file" class="text-primary font-medium text-sm">{{ t('import.fileSelected') }}: {{ file.name }}</p>
                    <FormHint>{{ t('import.dropzoneHint') }}</FormHint>
                </label>

                <p v-if="uploadError" class="text-rose-400 text-sm">{{ uploadError }}</p>

                <div class="flex justify-end">
                    <AppButton :disabled="!file || uploading" v-on:click="uploadAndPreview">
                        <svg v-if="uploading" class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ uploading ? '…' : t('import.next') }}
                        <ChevronRight v-if="!uploading" class="w-4 h-4 ml-1.5" />
                    </AppButton>
                </div>
            </div>

            <div v-if="step === 2" class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-muted text-sm">{{ t('import.totalRows', { n: rows.length }) }}</span>
                    <div class="flex items-center gap-3">
                        <div class="w-44">
                            <SelectInput v-model="walletId">
                                <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </SelectInput>
                        </div>
                        <div class="w-52">
                            <SelectInput
                                v-model="bulkCategory"
                                v-on:change="applyBulkCategory(bulkCategory)"
                            >
                                <option value="" disabled>{{ t('import.bulkCategory') }}</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </SelectInput>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-base/60 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr class="bg-surface-2/60 border-b border-base/40">
                                    <th class="px-3 py-2 text-left font-medium text-muted">{{ t('common.date') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-muted">{{ t('common.amount') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-muted">Type</th>
                                    <th class="px-3 py-2 text-left font-medium text-muted">{{ t('common.category') }} *</th>
                                    <th class="px-3 py-2 text-left font-medium text-muted">{{ t('common.description') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-muted">{{ t('common.tags') }}</th>
                                    <th class="w-8" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base/30">
                                <tr v-for="row in rows" :key="row._id" class="group hover:bg-surface-2/20">
                                    <td class="px-2 py-1.5">
                                        <input
                                            v-model="row.date"
                                            type="text"
                                            placeholder="YYYY-MM-DD"
                                            class="w-28 bg-transparent text-primary focus:bg-surface-2 rounded px-1.5 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500/50"
                                        >
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input
                                            v-model="row.amount"
                                            type="text"
                                            placeholder="0.00"
                                            class="w-24 bg-transparent text-primary font-mono focus:bg-surface-2 rounded px-1.5 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500/50"
                                        >
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <select
                                            v-model="row.type"
                                            class="bg-transparent focus:bg-surface-2 rounded px-1.5 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500/50 cursor-pointer"
                                            :class="row.type === 'income' ? 'text-emerald-400' : 'text-rose-400'"
                                        >
                                            <option value="expense">expense</option>
                                            <option value="income">income</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <select
                                            v-model="row.category_id"
                                            class="w-36 bg-transparent focus:bg-surface-2 rounded px-1.5 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500/50 cursor-pointer"
                                            :class="!row.category_id ? 'text-rose-400' : 'text-primary'"
                                        >
                                            <option value="" disabled>—</option>
                                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input
                                            v-model="row.description"
                                            type="text"
                                            class="w-full min-w-30 bg-transparent text-primary focus:bg-surface-2 rounded px-1.5 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500/50"
                                        >
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input
                                            v-model="row.tags"
                                            type="text"
                                            placeholder="tag1 tag2"
                                            class="w-28 bg-transparent text-muted focus:bg-surface-2 rounded px-1.5 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500/50"
                                        >
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <button
                                            class="opacity-0 group-hover:opacity-100 text-muted hover:text-rose-400 transition-all"
                                            v-on:click="removeRow(row._id)"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="7" class="px-3 py-2">
                                        <button
                                            class="text-xs text-muted hover:text-indigo-400 transition-colors flex items-center gap-1.5"
                                            v-on:click="addRow"
                                        >
                                            <Plus class="w-3.5 h-3.5" />
                                            {{ t('import.addRow') }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p v-if="rows.length > 0 && !allCategorized" class="text-xs text-rose-400">
                    {{ t('import.categoryRequired') }}
                </p>

                <div class="flex justify-between">
                    <AppButton variant="secondary" v-on:click="step = 1">{{ t('import.back') }}</AppButton>
                    <AppButton :disabled="processing || !allCategorized" v-on:click="submitImport">
                        <svg v-if="processing" class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ processing ? t('import.importing') : t('import.import') }}
                    </AppButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
