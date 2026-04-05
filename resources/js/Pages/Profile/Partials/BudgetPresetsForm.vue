<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCurrency } from '@/composables/core/useCurrency';

const { t } = useI18n();
const { fmt } = useCurrency();

const props = defineProps({
    presets: { type: Array, default: () => [] },
});

const SECTION_TYPES = ['income', 'savings', 'bills', 'expenses', 'debt'];
const SECTION_STYLES = {
    income:   { bg: 'bg-emerald-400/10', color: 'text-emerald-400' },
    savings:  { bg: 'bg-sky-400/10',     color: 'text-sky-400' },
    bills:    { bg: 'bg-amber-400/10',   color: 'text-amber-400' },
    expenses: { bg: 'bg-rose-400/10',    color: 'text-rose-400' },
    debt:     { bg: 'bg-purple-400/10',  color: 'text-purple-400' },
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
const draggingId = ref(null);
const dragOverId = ref(null);

function onDragStart(event, item) {
    draggingId.value = item.id;
    event.dataTransfer.effectAllowed = 'move';
}

function onDragEnd() {
    draggingId.value = null;
    dragOverId.value = null;
}

function onDragOver(event, item) {
    event.preventDefault();
    if (item.id !== draggingId.value) {
        dragOverId.value = item.id;
    }
}

function onDrop(event, targetItem) {
    event.preventDefault();
    const fromId = draggingId.value;
    if (!fromId || fromId === targetItem.id) return;

    const list = [...items.value];
    const fromIndex = list.findIndex(i => i.id === fromId);
    const toIndex = list.findIndex(i => i.id === targetItem.id);
    const [moved] = list.splice(fromIndex, 1);
    list.splice(toIndex, 0, moved);
    items.value = list;

    draggingId.value = null;
    dragOverId.value = null;

    window.axios.patch('/budget-presets/reorder', { ids: list.map(i => i.id) });
}

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
                :draggable="editingId !== item.id"
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button class="text-muted hover:text-secondary transition-colors" v-on:click="cancelEdit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </template>

                <template v-else>
                    <div class="text-muted/40 shrink-0 pointer-events-none">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                            <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                            <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                        </svg>
                    </div>
                    <span class="flex-1 text-sm text-primary font-medium">{{ item.label }}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded-full" :class="[SECTION_STYLES[item.type]?.bg, SECTION_STYLES[item.type]?.color]">
                        {{ t(`budgets.sections.${item.type}`) }}
                    </span>
                    <span v-if="item.planned_amount > 0" class="text-xs text-muted font-mono">{{ fmt(item.planned_amount) }}</span>
                    <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="startEdit(item)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>
                    <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="remove(item)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </template>
            </div>

            <div v-if="items.length === 0 && !adding" class="text-sm text-muted py-3 text-center">
                {{ t('profile.presets.none') }}
            </div>

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
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </button>
                <button class="text-muted hover:text-secondary transition-colors" v-on:click="adding = false">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <button
                v-if="!adding"
                class="flex items-center gap-2 text-sm text-indigo-400 hover:text-indigo-300 transition-colors pt-2"
                v-on:click="adding = true"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ t('profile.presets.add') }}
            </button>
        </div>
    </section>
</template>
