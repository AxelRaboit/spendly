<script setup>
/* eslint-disable vue/no-mutating-props */
import { X, Sparkles, Plus, Split, Paperclip, Trash2 } from 'lucide-vue-next';
import AppButton from '@/components/ui/AppButton.vue';
import FormHint from '@/components/form/FormHint.vue';
import DateInput from '@/components/form/DateInput.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import TypeToggle from '@/components/form/TypeToggle.vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { evalMath } from '@/utils/evalMath';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';

const { t }       = useI18n();
const { symbol, fmt } = useCurrency();

const tagInput = ref('');

function addTag(form) {
    const tag = tagInput.value.trim().toLowerCase().replace(/\s+/g, '-');
    if (!tag || form.tags.includes(tag) || form.tags.length >= 10) return;
    form.tags = [...form.tags, tag];
    tagInput.value = '';
}

function removeTag(form, tag) {
    form.tags = form.tags.filter(t => t !== tag);
}

function onTagKeydown(e, form) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTag(form);
    } else if (e.key === 'Backspace' && !tagInput.value && form.tags.length) {
        form.tags = form.tags.slice(0, -1);
    }
}

defineProps({
    open:                { type: Boolean,  required: true },
    editing:             { type: Boolean,  default: false },
    showSectionFilter:   { type: Boolean,  default: true },
    prefillLabel:        { type: String,   default: '' },
    txSection:           { type: String,   default: null },
    txForm:              { type: Object,   required: true },
    sectionMeta:         { type: Object,   required: true },
    filteredCategories:  { type: Array,    default: () => [] },
    suggestedCategoryId: { type: Number,   default: null },
    isPro:               { type: Boolean,  default: false },
});

const emit = defineEmits(['close', 'submit', 'submit-split', 'section-change', 'category-manual-change']);

// ── Split mode ───────────────────────────────────────────────────────────
const splitMode = ref(false);
const splits = ref([
    { category_id: null, amount: '' },
    { category_id: null, amount: '' },
]);

function addSplitRow() {
    splits.value.push({ category_id: null, amount: '' });
}

function removeSplitRow(index) {
    if (splits.value.length > 2) splits.value.splice(index, 1);
}

const splitTotal = computed(() =>
    splits.value.reduce((sum, s) => sum + (parseFloat(s.amount) || 0), 0)
);

function toggleSplit() {
    splitMode.value = !splitMode.value;
    if (splitMode.value) {
        splits.value = [
            { category_id: null, amount: '' },
            { category_id: null, amount: '' },
        ];
    }
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
    >
        <div v-if="open" class="fixed inset-y-0 right-0 z-50 flex">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" v-on:click="emit('close')" />

            <div
                class="relative ml-auto w-full max-w-sm bg-surface border-l border-base shadow-2xl flex flex-col"
                v-on:keydown.esc="emit('close')"
            >
                <div class="flex items-center justify-between px-6 py-4 border-b border-base">
                    <div>
                        <h3 class="font-semibold text-primary">{{ editing ? t('budgets.txPanel.titleEdit') : t('budgets.txPanel.title') }}</h3>
                        <p v-if="prefillLabel" class="text-xs text-secondary mt-0.5">{{ prefillLabel }}</p>
                    </div>
                    <button class="text-secondary hover:text-primary transition-colors" v-on:click="emit('close')">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form class="flex-1 overflow-y-auto px-6 py-6 space-y-5" v-on:submit.prevent="emit('submit')">
                    <div v-if="showSectionFilter">
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">{{ t('budgets.txPanel.section') }}</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="(meta, stype) in sectionMeta"
                                :key="stype"
                                type="button"
                                class="px-2.5 py-1 rounded text-xs font-medium border transition-colors"
                                :class="txSection === stype
                                    ? [meta.bg, meta.border, meta.color]
                                    : 'bg-surface-2 border-base text-muted hover:text-secondary'"
                                v-on:click="emit('section-change', txSection === stype ? null : stype)"
                            >
                                {{ meta.label }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">{{ t('budgets.txPanel.type') }}</label>
                        <TypeToggle v-model="txForm.type" :disabled="txSection !== null" />
                        <FormHint>{{ t('budgets.txPanel.typeHint') }}</FormHint>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('budgets.txPanel.amount') }}</label>
                            <button
                                v-if="!editing && isPro"
                                type="button"
                                class="flex items-center gap-1 text-xs transition-colors"
                                :class="splitMode ? 'text-indigo-400' : 'text-muted hover:text-indigo-400'"
                                v-on:click="toggleSplit"
                            >
                                <Split class="w-3 h-3" />
                                {{ t('budgets.txPanel.split') }}
                            </button>
                        </div>
                        <div v-if="!splitMode" class="relative">
                            <input
                                id="tx-amount"
                                v-model="txForm.amount"
                                type="text"
                                inputmode="decimal"
                                placeholder="0,00"
                                required
                                class="w-full bg-surface-2 text-primary text-2xl font-bold font-mono rounded-lg px-4 py-4 pr-10 border border-base focus:border-indigo-500 focus:outline-none text-right"
                                v-on:blur="() => { const r = evalMath(String(txForm.amount)); if (r !== null) txForm.amount = r; }"
                            >
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary text-xl font-bold">{{ symbol }}</span>
                        </div>
                        <p v-if="!splitMode && txForm.errors.amount" class="text-rose-400 text-xs mt-1">{{ txForm.errors.amount }}</p>
                        <FormHint v-if="!splitMode">{{ t('budgets.txPanel.mathHint') }}</FormHint>
                    </div>

                    <!-- Normal category (non-split) -->
                    <div v-if="!splitMode">
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">{{ t('budgets.txPanel.category') }}</label>
                        <SelectInput v-model="txForm.category_id" v-on:change="emit('category-manual-change')">
                            <option :value="null" disabled>— {{ t('budgets.txPanel.pickCategory') }} —</option>
                            <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </SelectInput>
                        <p v-if="suggestedCategoryId && txForm.category_id === suggestedCategoryId" class="flex items-center gap-1 text-indigo-400 text-xs mt-1">
                            <Sparkles class="w-3 h-3" />
                            {{ t('budgets.txPanel.autoSuggested') }}
                        </p>
                        <p v-if="txForm.errors.category_id" class="text-rose-400 text-xs mt-1">{{ txForm.errors.category_id }}</p>
                        <p v-if="txSection && filteredCategories.length === 0" class="text-subtle text-xs mt-1">{{ t('budgets.txPanel.noSectionCategories') }}</p>
                        <FormHint>{{ t('budgets.txPanel.categoryHint') }}</FormHint>
                    </div>

                    <!-- Split rows -->
                    <div v-if="splitMode" class="space-y-3">
                        <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('budgets.txPanel.splitLines') }}</label>
                        <div
                            v-for="(split, idx) in splits"
                            :key="idx"
                            class="flex items-center gap-2"
                        >
                            <SelectInput v-model="split.category_id" class="flex-1">
                                <option :value="null" disabled>— {{ t('budgets.txPanel.pickCategory') }} —</option>
                                <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </SelectInput>
                            <div class="relative w-28">
                                <input
                                    v-model="split.amount"
                                    type="text"
                                    inputmode="decimal"
                                    placeholder="0,00"
                                    class="w-full bg-surface-2 text-primary font-mono rounded-lg px-3 py-2.5 pr-7 border border-base focus:border-indigo-500 focus:outline-none text-right text-sm"
                                    v-on:blur="() => { const r = evalMath(String(split.amount)); if (r !== null) split.amount = r; }"
                                >
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-muted text-xs">{{ symbol }}</span>
                            </div>
                            <button
                                v-if="splits.length > 2"
                                type="button"
                                class="text-muted hover:text-rose-400 transition-colors shrink-0"
                                v-on:click="removeSplitRow(idx)"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <button type="button" class="flex items-center gap-1 text-xs text-indigo-400 hover:text-indigo-300 transition-colors" v-on:click="addSplitRow">
                                <Plus class="w-3 h-3" />
                                {{ t('budgets.txPanel.addSplitLine') }}
                            </button>
                            <span class="text-xs font-mono" :class="splitTotal > 0 ? 'text-primary' : 'text-muted'">
                                {{ t('budgets.txPanel.splitTotal') }} {{ fmt(splitTotal) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">{{ t('budgets.txPanel.date') }}</label>
                        <DateInput v-model="txForm.date" />
                        <p v-if="txForm.errors.date" class="text-rose-400 text-xs mt-1">{{ txForm.errors.date }}</p>
                        <FormHint>{{ t('budgets.txPanel.dateHint') }}</FormHint>
                    </div>

                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">
                            {{ t('budgets.txPanel.descLabel') }}
                            <span class="normal-case text-subtle">{{ t('budgets.txPanel.descOptional') }}</span>
                        </label>
                        <input
                            v-model="txForm.description"
                            type="text"
                            :placeholder="t('budgets.txPanel.descPlaceholder')"
                            class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border border-base focus:border-indigo-500 focus:outline-none"
                        >
                        <FormHint>{{ t('budgets.txPanel.descHint') }}</FormHint>
                    </div>

                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">
                            {{ t('budgets.txPanel.tagsLabel') }}
                            <span class="normal-case text-subtle">{{ t('budgets.txPanel.descOptional') }}</span>
                        </label>
                        <div class="flex flex-wrap gap-1.5 p-2 bg-surface-2 border border-base rounded-lg focus-within:border-indigo-500 min-h-[2.75rem]">
                            <span
                                v-for="tag in txForm.tags"
                                :key="tag"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-900/60 text-indigo-300"
                            >
                                #{{ tag }}
                                <button type="button" class="hover:text-white transition-colors" v-on:click="removeTag(txForm, tag)">
                                    <X class="w-3 h-3" />
                                </button>
                            </span>
                            <input
                                v-model="tagInput"
                                type="text"
                                :placeholder="txForm.tags.length === 0 ? t('budgets.txPanel.tagsPlaceholder') : ''"
                                class="flex-1 min-w-[80px] bg-transparent text-primary text-sm focus:outline-none placeholder:text-subtle"
                                v-on:keydown="onTagKeydown($event, txForm)"
                                v-on:blur="addTag(txForm)"
                            >
                        </div>
                        <FormHint>{{ t('budgets.txPanel.tagsHint') }}</FormHint>
                    </div>

                    <div v-if="isPro">
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">
                            {{ t('budgets.txPanel.attachment') }}
                            <span class="normal-case text-subtle">{{ t('budgets.txPanel.descOptional') }}</span>
                        </label>
                        <div v-if="txForm.attachment" class="flex items-center gap-2 bg-surface-2 border border-base rounded-lg px-3 py-2">
                            <Paperclip class="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                            <span class="text-sm text-primary truncate flex-1">{{ txForm.attachment.name }}</span>
                            <button type="button" class="text-muted hover:text-rose-400 transition-colors" v-on:click="txForm.attachment = null">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <label v-else class="flex items-center gap-2 bg-surface-2 border border-dashed border-base rounded-lg px-3 py-2.5 cursor-pointer hover:border-indigo-500/50 transition-colors">
                            <Paperclip class="w-3.5 h-3.5 text-muted" />
                            <span class="text-sm text-muted">{{ t('budgets.txPanel.attachmentHint') }}</span>
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" v-on:change="txForm.attachment = $event.target.files[0]">
                        </label>
                        <p v-if="txForm.errors.attachment" class="text-rose-400 text-xs mt-1">{{ txForm.errors.attachment }}</p>
                    </div>
                </form>

                <div class="px-6 py-4 border-t border-base flex gap-3">
                    <AppButton class="flex-1" :disabled="txForm.processing" v-on:click="splitMode ? emit('submit-split', splits) : emit('submit')">
                        {{ txForm.processing ? t('budgets.txPanel.submitting') : (editing ? t('budgets.txPanel.submitEdit') : t('budgets.txPanel.submit')) }}
                    </AppButton>
                    <AppButton class="flex-1" variant="secondary" v-on:click="emit('close')">
                        {{ t('budgets.txPanel.cancel') }}
                    </AppButton>
                </div>
            </div>
        </div>
    </Transition>
</template>
