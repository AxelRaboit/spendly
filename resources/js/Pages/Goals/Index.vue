<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/components/ui/AppButton.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import DateInput from '@/components/form/DateInput.vue';
import GoalDepositModal from '@/components/budget/GoalDepositModal.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const { fmt, symbol } = useCurrency();

const props = defineProps({ goals: Array, wallets: Array, categories: Array });

// ── Form (create/edit) ───────────────────────────────────��────────────────
const editingGoal = ref(null);
const showForm    = ref(false);

const COLORS = ['#6366f1', '#8b5cf6', '#34d399', '#f59e0b', '#f43f5e', '#38bdf8', '#a3e635'];

const form = useForm({
    wallet_id:    null,
    category_id:  null,
    name:         '',
    target_amount: '',
    saved_amount:  '',
    deadline:     '',
    color:        '#6366f1',
});

function openCreate() {
    editingGoal.value = null;
    form.reset();
    form.color = '#6366f1';
    showForm.value = true;
}

function openEdit(goal) {
    editingGoal.value  = goal;
    form.wallet_id    = goal.wallet_id ?? null;
    form.category_id  = goal.category_id ?? null;
    form.name          = goal.name;
    form.target_amount = goal.target_amount;
    form.saved_amount  = goal.saved_amount;
    form.deadline      = goal.deadline ?? '';
    form.color         = goal.color;
    showForm.value     = true;
}

function submit() {
    if (editingGoal.value) {
        form.put(`/goals/${editingGoal.value.id}`, { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post('/goals', { onSuccess: () => { showForm.value = false; form.reset(); } });
    }
}

// ── Deposit ───────────────────────────────────────────────────────────────
const depositGoal = ref(null);

// ── Delete ───────────────────────────────────────────────────────────���────
const goalToDelete = ref(null);

function confirmDelete(goal) {
    goalToDelete.value = goal;
}

function executeDelete() {
    useForm({}).delete(`/goals/${goalToDelete.value.id}`);
    goalToDelete.value = null;
}

// ── Helpers ───────────────────────────────────────────────────────────────
function fmtDate(d) {
    if (!d) return '';
    return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(d));
}
</script>

<template>
    <Head :title="t('goals.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('goals.title')" />
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <AppButton v-on:click="openCreate">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('goals.newGoal') }}
                </AppButton>
            </div>

            <!-- Goals grid -->
            <div v-if="goals.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="goal in goals"
                    :key="goal.id"
                    class="bg-surface border border-base/60 rounded-2xl p-5 space-y-4"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: goal.color }" />
                            <div class="min-w-0">
                                <span class="font-semibold text-primary truncate block">{{ goal.name }}</span>
                                <span v-if="goal.wallet" class="text-xs text-muted">{{ goal.wallet.name }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="openEdit(goal)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </button>
                            <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="confirmDelete(goal)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div>
                        <div class="flex justify-between text-xs text-muted mb-1.5">
                            <span>{{ fmt(goal.saved_amount) }}</span>
                            <span>{{ fmt(goal.target_amount) }}</span>
                        </div>
                        <div class="h-2.5 bg-surface-3 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :style="{ width: goal.progress + '%', backgroundColor: goal.color }"
                            />
                        </div>
                        <div class="flex justify-between items-center mt-1.5">
                            <span class="text-xs font-semibold" :style="{ color: goal.color }">
                                {{ goal.progress >= 100 ? t('goals.completed') : t('goals.progress', { pct: goal.progress }) }}
                            </span>
                            <span v-if="goal.deadline" class="text-xs text-muted">{{ t('goals.deadline', { date: fmtDate(goal.deadline) }) }}</span>
                        </div>
                    </div>

                    <!-- Deposit -->
                    <div v-if="goal.progress < 100">
                        <div v-if="goal.category_id" class="flex items-center gap-1.5 text-xs text-indigo-400">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            {{ t('goals.autoSync', { category: goal.category.name }) }}
                        </div>
                        <button
                            v-else
                            class="w-full text-sm border border-dashed border-base/60 hover:border-indigo-500/50 text-muted hover:text-primary rounded-lg py-1.5 transition-colors"
                            v-on:click="depositGoal = goal"
                        >
                            + {{ t('goals.deposit') }}
                        </button>
                    </div>
                </div>
            </div>

            <EmptyState v-else :message="t('goals.none')" />
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
                <div class="relative z-10 w-full max-w-md bg-surface border border-base rounded-xl p-4 sm:p-6 shadow-2xl space-y-4">
                    <h3 class="text-base font-semibold text-primary">{{ editingGoal ? t('goals.editGoal') : t('goals.newGoal') }}</h3>

                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('goals.fieldName') }}</label>
                        <input v-model="form.name" type="text" class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('goals.fieldWallet') }}</label>
                            <SelectInput v-model="form.wallet_id">
                                <option :value="null">— {{ t('goals.noWallet') }} —</option>
                                <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </SelectInput>
                        </div>
                        <div>
                            <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('goals.fieldCategory') }}</label>
                            <SelectInput v-model="form.category_id">
                                <option :value="null">— {{ t('goals.noCategory') }} —</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </SelectInput>
                        </div>
                    </div>
                    <p v-if="form.category_id" class="text-xs text-indigo-400">
                        {{ t('goals.categoryHint') }}
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('goals.fieldTarget', { symbol }) }}</label>
                            <input
                                v-model="form.target_amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('goals.fieldSaved', { symbol }) }}</label>
                            <input
                                v-model="form.saved_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                            >
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('goals.fieldDeadline') }}</label>
                        <DateInput v-model="form.deadline" />
                    </div>
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('goals.fieldColor') }}</label>
                        <div class="flex gap-2 flex-wrap">
                            <button
                                v-for="c in COLORS"
                                :key="c"
                                type="button"
                                class="w-7 h-7 rounded-full border-2 transition-transform hover:scale-110"
                                :style="{ backgroundColor: c, borderColor: form.color === c ? 'white' : 'transparent' }"
                                v-on:click="form.color = c"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <AppButton variant="secondary" v-on:click="showForm = false">{{ t('common.cancel') }}</AppButton>
                        <AppButton v-on:click="submit">{{ editingGoal ? t('common.update') : t('common.create') }}</AppButton>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Goal deposit modal -->
        <GoalDepositModal
            :goal="depositGoal"
            :categories="categories"
            v-on:close="depositGoal = null"
            v-on:success="depositGoal = null"
        />

        <!-- Confirm delete -->
        <ConfirmModal
            :show="!!goalToDelete"
            :message="t('goals.confirmDelete')"
            v-on:confirm="executeDelete"
            v-on:cancel="goalToDelete = null"
        />
    </AuthenticatedLayout>
</template>
