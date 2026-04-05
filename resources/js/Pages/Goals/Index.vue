<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoalDepositModal from '@/components/budget/GoalDepositModal.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const { fmt, symbol } = useCurrency();
const page = usePage();

const props = defineProps({ goals: Array, wallets: Array, categories: Array });

const isPro = computed(() => page.props.auth?.plan === 'pro');
const goalLimit = computed(() => page.props.planLimits.goal);
const canCreateGoal = computed(() => isPro.value || props.goals.length < goalLimit.value);

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
                <div class="relative">
                    <AppButton
                        :disabled="!canCreateGoal"
                        :class="!canCreateGoal ? 'opacity-60 cursor-not-allowed' : ''"
                        v-on:click="openCreate"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ t('goals.newGoal') }}
                    </AppButton>
                    <span v-if="!isPro && props.goals.length >= goalLimit" class="absolute -top-2 -right-2 bg-amber-500 text-xs text-white font-bold px-2 py-1 rounded-full">
                        Pro
                    </span>
                </div>
            </div>

            <div v-if="goals.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="goal in goals"
                    :key="goal.id"
                    class="bg-surface border border-base/60 rounded-2xl p-5 space-y-4"
                >
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

        <AppModal :show="showForm" v-on:close="showForm = false">
            <h3 class="text-base font-semibold text-primary">{{ editingGoal ? t('goals.editGoal') : t('goals.newGoal') }}</h3>

            <FormField :label="t('goals.fieldName')">
                <input v-model="form.name" type="text" class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none" required>
            </FormField>
            <div class="grid grid-cols-2 gap-3">
                <FormField :label="t('goals.fieldWallet')">
                    <SelectInput v-model="form.wallet_id">
                        <option :value="null">— {{ t('goals.noWallet') }} —</option>
                        <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </SelectInput>
                </FormField>
                <FormField :label="t('goals.fieldCategory')">
                    <SelectInput v-model="form.category_id">
                        <option :value="null">— {{ t('goals.noCategory') }} —</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </SelectInput>
                </FormField>
            </div>
            <p v-if="form.category_id" class="text-xs text-indigo-400">
                {{ t('goals.categoryHint') }}
            </p>
            <div class="grid grid-cols-2 gap-3">
                <FormField :label="t('goals.fieldTarget', { symbol })">
                    <input
                        v-model="form.target_amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                        required
                    >
                </FormField>
                <FormField :label="t('goals.fieldSaved', { symbol })">
                    <input
                        v-model="form.saved_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-full bg-surface-2 text-primary border border-base rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                    >
                </FormField>
            </div>
            <FormField :label="t('goals.fieldDeadline')">
                <DateInput v-model="form.deadline" />
            </FormField>
            <FormField :label="t('goals.fieldColor')">
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
            </FormField>
            <div class="flex justify-end gap-3 pt-2">
                <AppButton variant="secondary" v-on:click="showForm = false">{{ t('common.cancel') }}</AppButton>
                <AppButton v-on:click="submit">{{ editingGoal ? t('common.update') : t('common.create') }}</AppButton>
            </div>
        </AppModal>

        <GoalDepositModal
            :goal="depositGoal"
            :categories="categories"
            v-on:close="depositGoal = null"
            v-on:success="depositGoal = null"
        />

        <ConfirmModal
            :show="!!goalToDelete"
            :message="t('goals.confirmDelete')"
            v-on:confirm="executeDelete"
            v-on:cancel="goalToDelete = null"
        />
    </AuthenticatedLayout>
</template>
