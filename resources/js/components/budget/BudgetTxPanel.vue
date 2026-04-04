<script setup>
/* eslint-disable vue/no-mutating-props */
import DateInput from '@/components/form/DateInput.vue';
import TypeToggle from '@/components/form/TypeToggle.vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';

const { t }      = useI18n();
const { symbol } = useCurrency();

defineProps({
    open:               { type: Boolean,  required: true },
    prefillLabel:       { type: String,   default: '' },
    txSection:          { type: String,   default: null },
    txForm:             { type: Object,   required: true },
    sectionMeta:        { type: Object,   required: true },
    filteredCategories: { type: Array,    default: () => [] },
});

const emit = defineEmits(['close', 'submit', 'section-change']);
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
                class="relative ml-auto w-full max-w-sm bg-gray-900 border-l border-gray-700 shadow-2xl flex flex-col"
                v-on:keydown.esc="emit('close')"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <div>
                        <h3 class="font-semibold text-gray-100">{{ t('budgets.txPanel.title') }}</h3>
                        <p v-if="prefillLabel" class="text-xs text-gray-400 mt-0.5">{{ prefillLabel }}</p>
                    </div>
                    <button class="text-gray-400 hover:text-gray-200 transition-colors" v-on:click="emit('close')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Form -->
                <form class="flex-1 overflow-y-auto px-6 py-6 space-y-5" v-on:submit.prevent="emit('submit')">
                    <!-- Section -->
                    <div>
                        <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">{{ t('budgets.txPanel.section') }}</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="(meta, stype) in sectionMeta"
                                :key="stype"
                                type="button"
                                class="px-2.5 py-1 rounded text-xs font-medium border transition-colors"
                                :class="txSection === stype
                                    ? [meta.bg, meta.border, meta.color]
                                    : 'bg-gray-800 border-gray-700 text-gray-500 hover:text-gray-300'"
                                v-on:click="emit('section-change', txSection === stype ? null : stype)"
                            >
                                {{ meta.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">{{ t('budgets.txPanel.type') }}</label>
                        <TypeToggle v-model="txForm.type" />
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">{{ t('budgets.txPanel.amount') }}</label>
                        <div class="relative">
                            <input
                                id="tx-amount"
                                v-model="txForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="0,00"
                                required
                                class="w-full bg-gray-800 text-gray-100 text-2xl font-bold font-mono rounded-lg px-4 py-4 pr-10 border border-gray-700 focus:border-indigo-500 focus:outline-none text-right"
                            >
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl font-bold">{{ symbol }}</span>
                        </div>
                        <p v-if="txForm.errors.amount" class="text-rose-400 text-xs mt-1">{{ txForm.errors.amount }}</p>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">{{ t('budgets.txPanel.category') }}</label>
                        <select
                            v-model="txForm.category_id"
                            required
                            class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                        >
                            <option :value="null" disabled>{{ t('budgets.txPanel.pickCategory') }}</option>
                            <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <p v-if="txForm.errors.category_id" class="text-rose-400 text-xs mt-1">{{ txForm.errors.category_id }}</p>
                        <p v-if="txSection && filteredCategories.length === 0" class="text-gray-600 text-xs mt-1">{{ t('budgets.txPanel.noSectionCategories') }}</p>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">{{ t('budgets.txPanel.date') }}</label>
                        <DateInput v-model="txForm.date" />
                        <p v-if="txForm.errors.date" class="text-rose-400 text-xs mt-1">{{ txForm.errors.date }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">
                            {{ t('budgets.txPanel.descLabel') }}
                            <span class="normal-case text-gray-600">{{ t('budgets.txPanel.descOptional') }}</span>
                        </label>
                        <input
                            v-model="txForm.description"
                            type="text"
                            :placeholder="t('budgets.txPanel.descPlaceholder')"
                            class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                        >
                    </div>
                </form>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-700 flex gap-3">
                    <button
                        type="button"
                        :disabled="txForm.processing"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold py-2.5 rounded-lg transition-colors"
                        v-on:click="emit('submit')"
                    >
                        {{ txForm.processing ? t('budgets.txPanel.submitting') : t('budgets.txPanel.submit') }}
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2.5 text-gray-400 hover:text-gray-200 border border-gray-700 rounded-lg transition-colors"
                        v-on:click="emit('close')"
                    >
                        {{ t('budgets.txPanel.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
