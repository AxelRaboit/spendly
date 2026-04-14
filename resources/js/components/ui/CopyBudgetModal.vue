<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCurrency } from '@/composables/core/useCurrency';

const { t } = useI18n();
const { fmt } = useCurrency();

const props = defineProps({
    show:    { type: Boolean, default: false },
    items:   { type: Array, default: () => [] }, // flat list of BudgetItems
    title:   { type: String, default: '' },
    message: { type: String, default: '' },
});

const emit = defineEmits(['confirm', 'cancel']);

const SECTION_LABELS = {
    income:   'budgets.sections.income',
    savings:  'budgets.sections.savings',
    bills:    'budgets.sections.bills',
    expenses: 'budgets.sections.expenses',
    debt:     'budgets.sections.debt',
};

const SECTION_COLORS = {
    income:   'text-emerald-400',
    savings:  'text-sky-400',
    bills:    'text-amber-400',
    expenses: 'text-rose-400',
    debt:     'text-purple-400',
};

// Selected IDs
const selected = ref([]);

// Reset selection when modal opens
watch(() => props.show, (val) => {
    if (val) selected.value = props.items.map(i => i.id);
});

// Group items by type
const grouped = computed(() => {
    const groups = {};
    for (const item of props.items) {
        if (!groups[item.type]) groups[item.type] = [];
        groups[item.type].push(item);
    }
    return groups;
});

const allSelected = computed(() => selected.value.length === props.items.length);

function toggleAll() {
    selected.value = allSelected.value ? [] : props.items.map(i => i.id);
}

function toggleItem(id) {
    if (selected.value.includes(id)) {
        selected.value = selected.value.filter(i => i !== id);
    } else {
        selected.value = [...selected.value, id];
    }
}

function confirm() {
    emit('confirm', selected.value);
}
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60" v-on:click="$emit('cancel')" />

            <div class="relative z-10 w-full max-w-lg rounded-xl bg-surface-2 border border-line shadow-xl flex flex-col max-h-[80vh]">
                <div class="px-6 py-4 border-b border-line">
                    <h3 class="text-base font-semibold text-primary">{{ title }}</h3>
                    <p class="text-sm text-secondary mt-0.5">{{ message }}</p>
                </div>

                <div class="px-6 py-2.5 border-b border-line flex items-center justify-between">
                    <label class="flex items-center gap-2 text-xs text-secondary cursor-pointer select-none">
                        <input
                            type="checkbox"
                            :checked="allSelected"
                            class="rounded border-line accent-indigo-500"
                            v-on:change="toggleAll"
                        >
                        {{ allSelected ? t('common.deselectAll') : t('common.selectAll') }}
                    </label>
                    <span class="text-xs text-muted">{{ selected.length }} / {{ items.length }}</span>
                </div>

                <div class="overflow-y-auto flex-1 px-6 py-3 space-y-4">
                    <div v-for="(groupItems, type) in grouped" :key="type">
                        <p class="text-xs font-semibold uppercase tracking-wide mb-2" :class="SECTION_COLORS[type]">
                            {{ t(SECTION_LABELS[type]) }}
                        </p>
                        <div class="space-y-1">
                            <label
                                v-for="item in groupItems"
                                :key="item.id"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer hover:bg-surface-3/50 transition-colors select-none"
                                :class="selected.includes(item.id) ? 'bg-surface-3/30' : 'opacity-50'"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selected.includes(item.id)"
                                    class="rounded border-line accent-indigo-500 shrink-0"
                                    v-on:change="toggleItem(item.id)"
                                >
                                <span class="flex-1 min-w-0">
                                    <span class="text-sm text-primary block truncate">{{ item.label }}</span>
                                    <span v-if="item.category" class="text-xs text-muted">{{ item.category.name }}</span>
                                </span>
                                <span class="text-xs font-mono text-secondary shrink-0">{{ fmt(item.planned_amount) }}</span>
                                <span v-if="item.repeat_next_month" class="text-xs text-indigo-400 shrink-0">↻</span>
                            </label>
                        </div>
                    </div>

                    <p v-if="items.length === 0" class="text-sm text-muted text-center py-4">
                        {{ t('budgets.noPrevItems') }}
                    </p>
                </div>

                <div class="px-6 py-4 border-t border-line flex justify-end gap-3">
                    <AppButton variant="secondary" v-on:click="$emit('cancel')">
                        {{ t('common.cancel') }}
                    </AppButton>
                    <AppButton :disabled="selected.length === 0" v-on:click="confirm">
                        {{ t('common.confirm') }} ({{ selected.length }})
                    </AppButton>
                </div>
            </div>
        </div>
    </Transition>
</template>
