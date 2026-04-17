<script setup>
import { Plus, Pencil, Trash2, Zap } from 'lucide-vue-next';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoalDepositModal from '@/components/budget/GoalDepositModal.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useFmtDate } from '@/composables/core/useFmtDate';
import { useGoalForm } from '@/composables/goals/useGoalForm';
import { usePlanLimits } from '@/composables/ui/usePlanLimits';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { fmt, symbol } = useCurrency();
const { fmtDate } = useFmtDate();
const { isPro, canCreate, limit } = usePlanLimits();

const props = defineProps({ goals: Array, wallets: Array, categories: Array });

const canCreateGoal = computed(() => canCreate('goal', props.goals.length));

const COLORS = ['#6366f1', '#8b5cf6', '#34d399', '#f59e0b', '#f43f5e', '#38bdf8', '#a3e635'];

const { editingGoal, showForm, form, openCreate, openEdit, submit, goalToDelete, confirmDelete, executeDelete } = useGoalForm();

const depositGoal = ref(null);

// ── Sort ──────────────────────────────────────────────────────────────────
const SORTS = ['progress', 'deadline', 'amount'];
const sortBy = ref('progress');

const sortedGoals = computed(() => {
    return [...props.goals].sort((a, b) => {
        if (sortBy.value === 'progress') return a.progress - b.progress;
        if (sortBy.value === 'amount')   return b.target_amount - a.target_amount;
        if (sortBy.value === 'deadline') {
            if (!a.deadline && !b.deadline) return 0;
            if (!a.deadline) return 1;
            if (!b.deadline) return -1;
            return new Date(a.deadline) - new Date(b.deadline);
        }
        return 0;
    });
});

function monthlyContribution(goal) {
    if (!goal.deadline || goal.progress >= 100) return null;
    const remaining = goal.target_amount - goal.saved_amount;
    if (remaining <= 0) return null;
    const today = new Date();
    const deadline = new Date(goal.deadline);
    if (deadline <= today) return null;
    const months = (deadline.getFullYear() - today.getFullYear()) * 12 + (deadline.getMonth() - today.getMonth());
    return Math.ceil((remaining / Math.max(1, months)) * 100) / 100;
}
</script>

<template>
    <Head :title="t('goals.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('goals.title')" />
        </template>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex gap-2">
                    <button
                        v-for="sort in SORTS"
                        :key="sort"
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition-all border"
                        :class="sortBy === sort
                            ? 'bg-indigo-500/15 border-indigo-500/60 text-indigo-400'
                            : 'bg-surface border-line/60 text-muted hover:border-indigo-500/40'"
                        v-on:click="sortBy = sort"
                    >
                        {{ t(`goals.sort${sort.charAt(0).toUpperCase() + sort.slice(1)}`) }}
                    </button>
                </div>
                <div class="relative">
                    <AppButton
                        :disabled="!canCreateGoal"
                        :class="!canCreateGoal ? 'opacity-60 cursor-not-allowed' : ''"
                        v-on:click="openCreate"
                    >
                        <Plus class="w-4 h-4 mr-1.5" />
                        {{ t('goals.newGoal') }}
                    </AppButton>
                    <span v-if="!isPro && props.goals.length >= limit('goal')" class="absolute -top-2 -right-2 bg-amber-500 text-xs text-white font-bold px-2 py-1 rounded-full">
                        Pro
                    </span>
                </div>
            </div>

            <div v-if="goals.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="goal in sortedGoals"
                    :key="goal.id"
                    class="bg-surface border border-line/60 rounded-2xl p-5 space-y-4"
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
                            <AppTooltip :text="t('goals.editTip')">
                                <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="openEdit(goal)">
                                    <Pencil class="w-4 h-4" />
                                </button>
                            </AppTooltip>
                            <AppTooltip :text="t('goals.deleteTip')">
                                <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="confirmDelete(goal)">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </AppTooltip>
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
                            <div class="text-right">
                                <span v-if="goal.deadline" class="text-xs text-muted block">{{ t('goals.deadline', { date: fmtDate(goal.deadline) }) }}</span>
                                <span v-if="monthlyContribution(goal)" class="text-xs text-indigo-400">
                                    {{ t('goals.monthlyContribution', { amount: fmt(monthlyContribution(goal)) }) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="goal.progress < 100">
                        <div v-if="goal.category_id" class="flex items-center gap-1.5 text-xs text-indigo-400">
                            <Zap class="w-3.5 h-3.5 shrink-0" />
                            {{ t('goals.autoSync', { category: goal.category?.name }) }}
                        </div>
                        <button
                            v-else
                            class="w-full text-sm border border-dashed border-line/60 hover:border-indigo-500/50 text-muted hover:text-primary rounded-lg py-1.5 transition-colors"
                            v-on:click="depositGoal = goal"
                        >
                            + {{ t('goals.deposit') }}
                        </button>
                    </div>
                </div>
            </div>

            <EmptyState v-else :message="t('goals.none')" icon="target" />
        </div>

        <AppModal :show="showForm" v-on:close="showForm = false">
            <h3 class="text-base font-semibold text-primary">{{ editingGoal ? t('goals.editGoal') : t('goals.newGoal') }}</h3>

            <FormField :label="t('goals.fieldName')">
                <input v-model="form.name" type="text" class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none" required>
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
                        class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
                        required
                    >
                </FormField>
                <FormField :label="t('goals.fieldSaved', { symbol })">
                    <input
                        v-model="form.saved_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-full bg-surface-2 text-primary border border-line rounded-lg px-3 py-2.5 focus:border-indigo-500 focus:outline-none font-mono"
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
