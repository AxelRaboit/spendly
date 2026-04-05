<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/components/ui/AppButton.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const { fmt, symbol } = useCurrency();

const props = defineProps({
    recurring:  Array,
    wallets:    Array,
    categories: Array,
});

// ── Form (create/edit) ────────────────────────────────────────────────────
const editingItem = ref(null);
const showForm    = ref(false);

const form = useForm({
    description:  '',
    amount:       '',
    type:         'expense',
    day_of_month: 1,
    wallet_id:    '',
    category_id:  '',
    active:       true,
});

function openCreate() {
    editingItem.value = null;
    form.reset();
    form.type   = 'expense';
    form.active = true;
    form.day_of_month = 1;
    if (props.wallets.length)    form.wallet_id    = props.wallets[0].id;
    if (props.categories.length) form.category_id  = props.categories[0].id;
    showForm.value = true;
}

function openEdit(item) {
    editingItem.value   = item;
    form.description    = item.description;
    form.amount         = item.amount;
    form.type           = item.type;
    form.day_of_month   = item.day_of_month;
    form.wallet_id      = item.wallet_id;
    form.category_id    = item.category_id ?? '';
    form.active         = item.active;
    showForm.value      = true;
}

function submit() {
    if (editingItem.value) {
        form.put(`/recurring/${editingItem.value.id}`, { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post('/recurring', { onSuccess: () => { showForm.value = false; form.reset(); } });
    }
}

// ── Delete ────────────────────────────────────────────────────────────────
const itemToDelete = ref(null);

function confirmDelete(item) {
    itemToDelete.value = item;
}

function executeDelete() {
    useForm({}).delete(`/recurring/${itemToDelete.value.id}`);
    itemToDelete.value = null;
}

// ── Toggle active ─────────────────────────────────────────────────────────
function toggleActive(item) {
    useForm({}).patch(`/recurring/${item.id}/toggle`, { preserveScroll: true });
}

// ── Helpers ───────────────────────────────────────────────────────────────
function fmtDate(d) {
    if (!d) return t('recurring.never');
    return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(d));
}

function walletName(id) {
    return props.wallets.find(w => w.id === id)?.name ?? '—';
}

function categoryName(id) {
    if (!id) return '—';
    return props.categories.find(c => c.id === id)?.name ?? '—';
}

const ordinalDay = (n) => t('recurring.dayLabel', { day: n });

// ── Group by wallet ───────────────────────────────────────────────────────
const byWallet = computed(() => {
    const groups = [];
    for (const wallet of props.wallets) {
        const items = props.recurring.filter((r) => r.wallet_id === wallet.id);
        if (items.length) groups.push({ wallet, items });
    }
    return groups;
});

const collapsed = ref({});
function toggleGroup(id) {
    collapsed.value[id] = !collapsed.value[id];
}
</script>

<template>
    <Head :title="t('recurring.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('recurring.title')" />
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <AppButton v-on:click="openCreate">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('recurring.new') }}
                </AppButton>
            </div>

            <!-- List grouped by wallet -->
            <div v-if="recurring.length > 0" class="space-y-4">
                <div v-for="group in byWallet" :key="group.wallet.id" class="bg-surface border border-base/60 rounded-2xl overflow-hidden">
                    <!-- Wallet header (toggle) -->
                    <button
                        class="w-full flex items-center justify-between px-4 py-3 hover:bg-surface-2 transition-colors"
                        v-on:click="toggleGroup(group.wallet.id)"
                    >
                        <span class="text-sm font-semibold text-primary">{{ group.wallet.name }}</span>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-muted">{{ t('recurring.count', group.items.length, { count: group.items.length }) }}</span>
                            <svg
                                class="w-4 h-4 text-muted transition-transform duration-200"
                                :class="collapsed[group.wallet.id] ? '-rotate-90' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>
                    <div v-show="!collapsed[group.wallet.id]" class="border-t border-base/60 divide-y divide-base/40">
                        <div
                            v-for="item in group.items"
                            :key="item.id"
                            class="px-4 py-3.5"
                            :class="{ 'opacity-60': !item.active }"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Type indicator -->
                                <div
                                    class="mt-0.5 w-2.5 h-2.5 rounded-full shrink-0"
                                    :class="item.type === 'income' ? 'bg-emerald-400' : 'bg-rose-400'"
                                />

                                <!-- Main info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-semibold text-primary truncate">{{ item.description }}</span>
                                        <span
                                            class="text-base font-mono font-semibold"
                                            :class="item.type === 'income' ? 'text-emerald-400' : 'text-rose-400'"
                                        >
                                            {{ item.type === 'income' ? '+' : '−' }}{{ fmt(item.amount) }}
                                        </span>
                                    </div>

                                    <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
                                        <span>{{ ordinalDay(item.day_of_month) }}</span>
                                        <span v-if="item.category_id">{{ categoryName(item.category_id) }}</span>
                                    </div>

                                    <div class="mt-1.5 text-xs text-subtle">
                                        {{ item.last_generated_at
                                            ? t('recurring.lastGenerated', { date: fmtDate(item.last_generated_at) })
                                            : t('recurring.never') }}
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <!-- Active toggle -->
                                    <button
                                        class="text-xs px-2 py-1 rounded-full border transition-colors"
                                        :class="item.active
                                            ? 'border-emerald-500/40 text-emerald-400 hover:bg-emerald-500/10'
                                            : 'border-base text-muted hover:border-indigo-500/40 hover:text-indigo-400'"
                                        v-on:click="toggleActive(item)"
                                    >
                                        {{ item.active ? t('recurring.active') : t('recurring.inactive') }}
                                    </button>

                                    <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="openEdit(item)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="confirmDelete(item)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <EmptyState v-else-if="recurring.length === 0" :message="t('recurring.none')" />
        </div>

        <!-- Create/Edit modal -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div class="absolute inset-0 bg-black/60" v-on:click="showForm = false" />
                <div class="relative z-10 w-full max-w-md bg-surface border border-base rounded-xl p-4 sm:p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-base font-semibold text-primary">
                        {{ editingItem ? t('recurring.new') : t('recurring.new') }}
                    </h3>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('recurring.fieldDesc') }}</label>
                        <input v-model="form.description" type="text" class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none" required>
                    </div>

                    <!-- Amount + Type -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('recurring.fieldAmount', { symbol }) }}</label>
                            <input
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('recurring.fieldType') }}</label>
                            <SelectInput v-model="form.type">
                                <option value="expense">{{ t('search.expense') }}</option>
                                <option value="income">{{ t('search.income') }}</option>
                            </SelectInput>
                        </div>
                    </div>

                    <!-- Day of month -->
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('recurring.fieldDay') }}</label>
                        <input
                            v-model.number="form.day_of_month"
                            type="number"
                            min="1"
                            max="28"
                            class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                            required
                        >
                        <p class="mt-1 text-xs text-muted">{{ ordinalDay(form.day_of_month || 1) }}</p>
                    </div>

                    <!-- Wallet -->
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('recurring.fieldWallet') }}</label>
                        <SelectInput v-model="form.wallet_id">
                            <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </SelectInput>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('recurring.fieldCategory') }} *</label>
                        <SelectInput v-model="form.category_id">
                            <option value="" disabled>—</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </SelectInput>
                    </div>

                    <!-- Active toggle -->
                    <div class="flex items-center gap-3">
                        <label class="text-xs text-secondary uppercase tracking-wide">{{ t('recurring.fieldActive') }}</label>
                        <button
                            type="button"
                            class="relative w-10 h-6 rounded-full transition-colors"
                            :class="form.active ? 'bg-indigo-600' : 'bg-surface-3'"
                            v-on:click="form.active = !form.active"
                        >
                            <span
                                class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform"
                                :class="form.active ? 'translate-x-4' : 'translate-x-0'"
                            />
                        </button>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <AppButton variant="secondary" v-on:click="showForm = false">{{ t('common.cancel') }}</AppButton>
                        <AppButton v-on:click="submit">{{ editingItem ? t('common.update') : t('common.create') }}</AppButton>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Confirm delete -->
        <ConfirmModal
            :show="!!itemToDelete"
            :message="t('recurring.confirmDelete')"
            v-on:confirm="executeDelete"
            v-on:cancel="itemToDelete = null"
        />
    </AuthenticatedLayout>
</template>
