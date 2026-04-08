<script setup>
import { ref } from 'vue';
import { BudgetSection } from '@/enums/BudgetSection';
import { useI18n } from 'vue-i18n';
import { useCurrency } from '@/composables/core/useCurrency';
import { useDragDrop } from '@/composables/ui/useDragDrop';
import { Check, X, GripVertical, Pencil, Trash2, Plus, FlaskConical } from 'lucide-vue-next';

const { t } = useI18n();
const { fmt } = useCurrency();

const props = defineProps({
    presets: { type: Array, default: () => [] },
});

const SECTION_TYPES = Object.values(BudgetSection);
const SECTION_STYLES = {
    [BudgetSection.Income]:   { bg: 'bg-emerald-400/10', color: 'text-emerald-400' },
    [BudgetSection.Savings]:  { bg: 'bg-sky-400/10',     color: 'text-sky-400' },
    [BudgetSection.Bills]:    { bg: 'bg-amber-400/10',   color: 'text-amber-400' },
    [BudgetSection.Expenses]: { bg: 'bg-rose-400/10',    color: 'text-rose-400' },
    [BudgetSection.Debt]:     { bg: 'bg-purple-400/10',  color: 'text-purple-400' },
};

const items = ref([...props.presets]);
const adding = ref(false);
const editingId = ref(null);

const newLabel = ref('');
const newType = ref('expenses');
const newAmount = ref(0);

const editLabel = ref('');
const editType = ref('');
const editAmount = ref(0);

// ── Drag-and-drop reorder ────────────────────────────────────────────────
const { draggingId, dragOverId, onDragStart, onDragEnd, onDragOver, onDrop } = useDragDrop(
    items,
    (ids) => window.axios.patch('/budget-presets/reorder', { ids }),
);

// ── CRUD ─────────────────────────────────────────────────────────────────
async function addPreset() {
    if (!newLabel.value.trim()) return;
    try {
        const { data } = await window.axios.post('/budget-presets', {
            label: newLabel.value.trim(),
            type: newType.value,
            planned_amount: newAmount.value || 0,
        });
        items.value.push(data);
        newLabel.value = '';
        newType.value = 'expenses';
        newAmount.value = 0;
        adding.value = false;
    } catch { /* validation errors handled silently */ }
}

function startEdit(item) {
    editingId.value = item.id;
    editLabel.value = item.label;
    editType.value = item.type;
    editAmount.value = item.planned_amount;
}

async function saveEdit(item) {
    if (!editLabel.value.trim()) return;
    try {
        const { data } = await window.axios.put(`/budget-presets/${item.id}`, {
            label: editLabel.value.trim(),
            type: editType.value,
            planned_amount: editAmount.value || 0,
        });
        const idx = items.value.findIndex(i => i.id === item.id);
        if (idx !== -1) items.value[idx] = data;
        editingId.value = null;
    } catch { /* validation errors handled silently */ }
}

function cancelEdit() {
    editingId.value = null;
}

async function remove(item) {
    try {
        await window.axios.delete(`/budget-presets/${item.id}`);
        items.value = items.value.filter(i => i.id !== item.id);
    } catch { /* */ }
}

function onKeydown(e, submitFn) {
    if (e.key === 'Enter') { e.preventDefault(); submitFn(); }
    if (e.key === 'Escape') { e.preventDefault(); adding.value = false; cancelEdit(); }
}
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-primary">{{ t('profile.presets.title') }}</h2>
            <p class="mt-1 text-sm text-secondary">{{ t('profile.presets.subtitle') }}</p>
        </header>

        <div class="space-y-2">
            <div
                v-for="item in items"
                :key="item.id"
                class="flex items-center gap-3 px-3 py-2.5 bg-surface-2 rounded-lg border border-base/40 transition-all select-none"
                :class="{
                    'opacity-50 scale-95': draggingId === item.id,
                    'ring-2 ring-indigo-500/50 border-indigo-500/50': dragOverId === item.id,
                    'cursor-grab active:cursor-grabbing': editingId !== item.id,
                }"
                :draggable="editingId !== item.id && !item.is_demo"
                v-on:dragstart="onDragStart($event, item)"
                v-on:dragend="onDragEnd"
                v-on:dragover="onDragOver($event, item)"
                v-on:drop="onDrop($event, item)"
            >
                <template v-if="editingId === item.id">
                    <input
                        v-model="editLabel"
                        type="text"
                        class="flex-1 bg-surface text-primary rounded px-2 py-1.5 border border-base text-sm focus:border-indigo-500 focus:outline-none"
                        v-on:keydown="onKeydown($event, () => saveEdit(item))"
                    >
                    <select
                        v-model="editType"
                        class="bg-surface text-primary rounded px-2 py-1.5 border border-base text-sm focus:border-indigo-500 focus:outline-none"
                    >
                        <option v-for="s in SECTION_TYPES" :key="s" :value="s">{{ t(`budgets.sections.${s}`) }}</option>
                    </select>
                    <input
                        v-model.number="editAmount"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-24 bg-surface text-primary rounded px-2 py-1.5 border border-base text-sm font-mono text-right focus:border-indigo-500 focus:outline-none"
                        v-on:keydown="onKeydown($event, () => saveEdit(item))"
                    >
                    <button class="text-emerald-400 hover:text-emerald-300 transition-colors" v-on:click="saveEdit(item)">
                        <Check class="w-4 h-4" />
                    </button>
                    <button class="text-muted hover:text-secondary transition-colors" v-on:click="cancelEdit">
                        <X class="w-4 h-4" />
                    </button>
                </template>

                <template v-else>
                    <div class="shrink-0 pointer-events-none" :class="item.is_demo ? 'text-transparent' : 'text-muted/40'">
                        <GripVertical class="w-4 h-4" />
                    </div>
                    <span class="flex-1 text-sm text-primary font-medium">{{ item.label }}</span>
                    <span v-if="item.is_demo" class="flex items-center gap-1 rounded-full bg-badge-warning-bg px-2 py-0.5 text-2xs font-medium text-badge-warning-text shrink-0">
                        <FlaskConical class="w-2.5 h-2.5" />
                        {{ t('wallets.demo') }}
                    </span>
                    <span class="text-xs px-1.5 py-0.5 rounded-full" :class="[SECTION_STYLES[item.type]?.bg, SECTION_STYLES[item.type]?.color]">
                        {{ t(`budgets.sections.${item.type}`) }}
                    </span>
                    <span v-if="item.planned_amount > 0" class="text-xs text-muted font-mono">{{ fmt(item.planned_amount) }}</span>
                    <template v-if="!item.is_demo">
                        <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="startEdit(item)">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                        <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="remove(item)">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </template>
                </template>
            </div>

            <EmptyState v-if="items.length === 0 && !adding" :message="t('profile.presets.none')" icon="settings" compact />

            <div v-if="adding" class="flex items-center gap-3 px-3 py-2.5 bg-surface-2 rounded-lg border border-dashed border-indigo-500/40">
                <input
                    v-model="newLabel"
                    type="text"
                    :placeholder="t('profile.presets.labelPlaceholder')"
                    class="flex-1 bg-surface text-primary rounded px-2 py-1.5 border border-base text-sm focus:border-indigo-500 focus:outline-none"
                    autofocus
                    v-on:keydown="onKeydown($event, addPreset)"
                >
                <select
                    v-model="newType"
                    class="bg-surface text-primary rounded px-2 py-1.5 border border-base text-sm focus:border-indigo-500 focus:outline-none"
                >
                    <option v-for="s in SECTION_TYPES" :key="s" :value="s">{{ t(`budgets.sections.${s}`) }}</option>
                </select>
                <input
                    v-model.number="newAmount"
                    type="number"
                    step="0.01"
                    min="0"
                    :placeholder="t('profile.presets.amountPlaceholder')"
                    class="w-24 bg-surface text-primary rounded px-2 py-1.5 border border-base text-sm font-mono text-right focus:border-indigo-500 focus:outline-none"
                    v-on:keydown="onKeydown($event, addPreset)"
                >
                <button class="text-emerald-400 hover:text-emerald-300 transition-colors" v-on:click="addPreset">
                    <Check class="w-4 h-4" />
                </button>
                <button class="text-muted hover:text-secondary transition-colors" v-on:click="adding = false">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <button
                v-if="!adding"
                class="flex items-center gap-2 text-sm text-indigo-400 hover:text-indigo-300 transition-colors pt-2"
                v-on:click="adding = true"
            >
                <Plus class="w-4 h-4" />
                {{ t('profile.presets.add') }}
            </button>
        </div>
    </section>
</template>
