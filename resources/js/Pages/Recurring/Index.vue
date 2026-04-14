<script setup>
import { Plus, ChevronDown, Pencil, Trash2 } from 'lucide-vue-next';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import TabBadge from '@/components/ui/TabBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useFmtDate } from '@/composables/core/useFmtDate';
import { useRecurringForm } from '@/composables/recurring/useRecurringForm';
import { useScheduledForm } from '@/composables/recurring/useScheduledForm';
import { usePlanLimits } from '@/composables/ui/usePlanLimits';
import { useI18n } from 'vue-i18n';
import { TransactionType } from '@/enums/TransactionType';

const { t } = useI18n();
const { fmt, symbol } = useCurrency();
const { isPro, canCreate, limit } = usePlanLimits();

const props = defineProps({
    recurring:  Array,
    scheduled:  { type: Array, default: () => [] },
    wallets:    Array,
    categories: Array,
});

const activeTab = ref('recurring');

const canCreateRecurring = computed(() => canCreate('recurring', props.recurring.length));

const { editingItem, showForm, form, openCreate, openEdit, submit, itemToDelete, confirmDelete, executeDelete, toggleActive } =
    useRecurringForm(props.wallets, props.categories);

const { editingScheduled, showScheduledForm, scheduledForm, openCreateScheduled, openEditScheduled, submitScheduled, scheduledToDelete, confirmDeleteScheduled, executeDeleteScheduled } =
    useScheduledForm(props.wallets, props.categories);

// ── Helpers ───────────────────────────────────────────────────────────────
const { fmtDate: fmtDateBase } = useFmtDate();
function fmtDate(d) { return d ? fmtDateBase(d) : t('recurring.never'); }

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
            <div class="flex items-center gap-1 bg-surface border border-line/60 rounded-lg p-1 w-fit">
                <button
                    class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
                    :class="activeTab === 'recurring' ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary'"
                    v-on:click="activeTab = 'recurring'"
                >
                    {{ t('scheduled.tab.recurring') }}
                    <TabBadge :count="recurring.length" />
                </button>
                <button
                    class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
                    :class="activeTab === 'scheduled' ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary'"
                    v-on:click="activeTab = 'scheduled'"
                >
                    {{ t('scheduled.tab.scheduled') }}
                    <TabBadge :count="scheduled.length" />
                </button>
            </div>

            <div v-if="activeTab === 'recurring'" class="space-y-4">
                <div class="flex justify-end">
                    <div class="relative">
                        <AppButton
                            :disabled="!canCreateRecurring"
                            :class="!canCreateRecurring ? 'opacity-60 cursor-not-allowed' : ''"
                            v-on:click="openCreate"
                        >
                            <Plus class="w-4 h-4 mr-1.5" />
                            {{ t('recurring.new') }}
                        </AppButton>
                        <span v-if="!isPro && props.recurring.length >= limit('recurring')" class="absolute -top-2 -right-2 bg-amber-500 text-xs text-white font-bold px-2 py-1 rounded-full">
                            Pro
                        </span>
                    </div>
                </div>

                <div v-if="recurring.length > 0" class="space-y-4">
                    <div v-for="group in byWallet" :key="group.wallet.id" class="bg-surface border border-line/60 rounded-2xl overflow-hidden">
                        <button
                            class="w-full flex items-center justify-between px-4 py-3 hover:bg-surface-2 transition-colors"
                            v-on:click="toggleGroup(group.wallet.id)"
                        >
                            <span class="text-sm font-semibold text-primary">{{ group.wallet.name }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-muted">{{ t('recurring.count', group.items.length, { count: group.items.length }) }}</span>
                                <ChevronDown
                                    class="w-4 h-4 text-muted transition-transform duration-200"
                                    :class="collapsed[group.wallet.id] ? '-rotate-90' : ''"
                                />
                            </div>
                        </button>
                        <div v-show="!collapsed[group.wallet.id]" class="border-t border-line/60 divide-y divide-line/40">
                            <div
                                v-for="item in group.items"
                                :key="item.id"
                                class="px-4 py-3.5"
                                :class="{ 'opacity-60': !item.active }"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="mt-0.5 w-2.5 h-2.5 rounded-full shrink-0"
                                        :class="item.type === TransactionType.Income ? 'bg-emerald-400' : 'bg-rose-400'"
                                    />

                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="font-semibold text-primary truncate">{{ item.description }}</span>
                                            <span
                                                class="text-base font-mono font-semibold"
                                                :class="item.type === TransactionType.Income ? 'text-emerald-400' : 'text-rose-400'"
                                            >
                                                {{ item.type === TransactionType.Income ? '+' : '−' }}{{ fmt(item.amount) }}
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

                                    <div class="flex items-center gap-2 shrink-0">
                                        <AppTooltip :text="t('recurring.activeTip')">
                                            <button
                                                class="text-xs px-2 py-1 rounded-full border transition-colors"
                                                :class="item.active
                                                    ? 'border-emerald-500/40 text-emerald-400 hover:bg-emerald-500/10'
                                                    : 'border-line text-muted hover:border-indigo-500/40 hover:text-indigo-400'"
                                                v-on:click="toggleActive(item)"
                                            >
                                                {{ item.active ? t('recurring.active') : t('recurring.inactive') }}
                                            </button>
                                        </AppTooltip>
                                        <AppTooltip :text="t('recurring.editTip')">
                                            <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="openEdit(item)">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                        </AppTooltip>
                                        <AppTooltip :text="t('recurring.deleteTip')">
                                            <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="confirmDelete(item)">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </AppTooltip>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState v-else-if="recurring.length === 0" :message="t('recurring.none')" icon="repeat" />
            </div>

            <div v-if="activeTab === 'scheduled'" class="space-y-4">
                <div class="flex justify-end">
                    <AppButton v-on:click="openCreateScheduled">
                        <Plus class="w-4 h-4 mr-1.5" />
                        {{ t('scheduled.new') }}
                    </AppButton>
                </div>

                <div v-if="scheduled.length > 0" class="bg-surface border border-line/60 rounded-2xl overflow-hidden divide-y divide-line/40">
                    <div
                        v-for="item in scheduled"
                        :key="item.id"
                        class="px-4 py-3 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="w-2 h-2 rounded-full shrink-0"
                                    :class="item.type === TransactionType.Income ? 'bg-emerald-400' : 'bg-rose-400'"
                                />
                                <span class="text-sm text-primary font-medium truncate">{{ item.description || '—' }}</span>
                                <span class="rounded-full bg-badge-primary-bg px-2 py-0.5 text-xs font-medium text-badge-primary-text">{{ item.category?.name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-muted">
                                <span>{{ fmtDate(item.scheduled_date) }}</span>
                                <span>{{ item.wallet?.name }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="font-mono font-semibold text-sm" :class="item.type === TransactionType.Income ? 'text-emerald-400' : 'text-primary'">
                                {{ item.type === TransactionType.Income ? '+' : '' }}{{ fmt(item.amount) }}
                            </span>
                            <div class="flex items-center gap-1">
                                <AppTooltip :text="t('scheduled.editTip')">
                                    <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="openEditScheduled(item)">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                </AppTooltip>
                                <AppTooltip :text="t('scheduled.deleteTip')">
                                    <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="confirmDeleteScheduled(item)">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </AppTooltip>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState v-else :message="t('scheduled.none')" icon="calendar" />
            </div>
        </div>

        <AppModal :show="showScheduledForm" scrollable v-on:close="showScheduledForm = false">
            <h3 class="text-base font-semibold text-primary">{{ t('scheduled.new') }}</h3>

            <FormField :label="t('scheduled.fieldDesc')">
                <input v-model="scheduledForm.description" type="text" class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none">
            </FormField>

            <FormField :label="t('scheduled.fieldAmount', { symbol })">
                <input
                    v-model="scheduledForm.amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                    class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono text-right"
                >
            </FormField>

            <FormField :label="t('scheduled.fieldType')">
                <TypeToggle v-model="scheduledForm.type" />
            </FormField>

            <FormField :label="t('scheduled.date')">
                <input v-model="scheduledForm.scheduled_date" type="date" required class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none">
            </FormField>

            <FormField :label="t('scheduled.fieldWallet')">
                <SelectInput v-model="scheduledForm.wallet_id">
                    <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                </SelectInput>
            </FormField>

            <FormField :label="t('scheduled.fieldCategory')">
                <SelectInput v-model="scheduledForm.category_id">
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </SelectInput>
            </FormField>

            <template #footer>
                <AppButton :disabled="scheduledForm.processing" v-on:click="submitScheduled">
                    {{ editingScheduled ? t('common.update') : t('common.create') }}
                </AppButton>
            </template>
        </AppModal>

        <ConfirmModal
            :show="scheduledToDelete !== null"
            :message="t('scheduled.confirmDelete')"
            v-on:confirm="executeDeleteScheduled"
            v-on:cancel="scheduledToDelete = null"
        />

        <AppModal :show="showForm" scrollable v-on:close="showForm = false">
            <h3 class="text-base font-semibold text-primary">
                {{ editingItem ? t('recurring.new') : t('recurring.new') }}
            </h3>

            <FormField :label="t('recurring.fieldDesc')">
                <input v-model="form.description" type="text" class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none" required>
            </FormField>

            <div class="grid grid-cols-2 gap-3">
                <FormField :label="t('recurring.fieldAmount', { symbol })">
                    <input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                        required
                    >
                </FormField>
                <FormField :label="t('recurring.fieldType')">
                    <SelectInput v-model="form.type">
                        <option value="expense">{{ t('search.expense') }}</option>
                        <option value="income">{{ t('search.income') }}</option>
                    </SelectInput>
                </FormField>
            </div>

            <FormField :label="t('recurring.fieldDay')">
                <input
                    v-model.number="form.day_of_month"
                    type="number"
                    min="1"
                    max="28"
                    class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                    required
                >
                <p class="mt-1 text-xs text-muted">{{ ordinalDay(form.day_of_month || 1) }}</p>
            </FormField>

            <FormField :label="t('recurring.fieldWallet')">
                <SelectInput v-model="form.wallet_id">
                    <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                </SelectInput>
            </FormField>

            <FormField :label="t('recurring.fieldCategory') + ' *'">
                <SelectInput v-model="form.category_id">
                    <option value="" disabled>—</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </SelectInput>
            </FormField>

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
        </AppModal>

        <ConfirmModal
            :show="!!itemToDelete"
            :message="t('recurring.confirmDelete')"
            v-on:confirm="executeDelete"
            v-on:cancel="itemToDelete = null"
        />
    </AuthenticatedLayout>
</template>
