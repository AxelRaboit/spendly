<script setup>
import { X } from 'lucide-vue-next';
import AppButton from '@/components/ui/AppButton.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import { watch, nextTick, ref, computed } from 'vue';

const { t }           = useI18n();
const { fmt, symbol } = useCurrency();

const props = defineProps({
    goal:       { type: Object, default: null },
    categories: { type: Array,  default: () => [] },
});

const emit = defineEmits(['close', 'success']);

const amountInput = ref(null);

const form = useForm({
    amount:      '',
    category_id: null,
    date:        '',
    description: '',
});

// If the goal has a linked category, pre-select it; otherwise leave blank for the user to pick.
watch(() => props.goal, (goal) => {
    if (!goal) return;
    form.amount      = '';
    form.category_id = goal.category_id ?? null;
    form.description = goal.name;
    form.date        = new Date().toISOString().slice(0, 10);
    nextTick(() => amountInput.value?.focus());
});

const categoryRequired = computed(() => !props.goal?.category_id);

// ── Simulation ────────────────────────────────────────────────────────────────
const depositValue = computed(() => Math.max(0, parseFloat(form.amount) || 0));

const previewSaved = computed(() =>
    props.goal ? parseFloat(props.goal.saved_amount) + depositValue.value : 0
);

const previewProgress = computed(() => {
    if (!props.goal || !props.goal.target_amount) return 0;
    return Math.min(Math.round((previewSaved.value / parseFloat(props.goal.target_amount)) * 100), 100);
});

const hasDeposit = computed(() => depositValue.value > 0);

function submit() {
    form.post(`/goals/${props.goal.id}/deposit`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
        },
    });
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
        <div v-if="goal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" v-on:click="emit('close')" />

            <div
                class="relative z-10 w-full max-w-sm bg-surface-2 border border-line rounded-2xl shadow-2xl"
                v-on:keydown.esc="emit('close')"
            >
                <div class="px-6 pt-6 pb-4 border-b border-line">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: goal.color }" />
                            <h3 class="font-semibold text-primary">{{ goal.name }}</h3>
                        </div>
                        <button class="text-muted hover:text-secondary transition-colors" v-on:click="emit('close')">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="h-2 bg-surface-3 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :style="hasDeposit && previewProgress > goal.progress ? {
                                width: previewProgress + '%',
                                background: `linear-gradient(to right, ${goal.color} ${(goal.progress / previewProgress * 100).toFixed(1)}%, ${goal.color}66 ${(goal.progress / previewProgress * 100).toFixed(1)}%)`,
                            } : {
                                width: goal.progress + '%',
                                backgroundColor: goal.color,
                            }"
                        />
                    </div>

                    <div class="flex justify-between items-center mt-2 text-xs font-mono">
                        <div class="text-muted">
                            {{ fmt(goal.saved_amount) }}
                            <Transition
                                enter-active-class="transition duration-200"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                            >
                                <span v-if="hasDeposit" class="text-emerald-400 ml-1">→ {{ fmt(previewSaved) }}</span>
                            </Transition>
                        </div>
                        <span class="font-semibold transition-all duration-300" :style="{ color: goal.color }">
                            {{ hasDeposit ? previewProgress : goal.progress }}%
                        </span>
                        <span class="text-muted">{{ fmt(goal.target_amount) }}</span>
                    </div>
                </div>

                <form class="px-6 py-5 space-y-4" v-on:submit.prevent="submit">
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">{{ t('budgets.txPanel.amount') }}</label>
                        <div class="relative">
                            <input
                                ref="amountInput"
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="0,00"
                                required
                                class="w-full bg-surface text-primary text-2xl font-bold font-mono rounded-lg px-4 py-4 pr-10 border border-line focus:border-indigo-500 focus:outline-none text-right"
                            >
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary text-xl font-bold">{{ symbol }}</span>
                        </div>
                        <p v-if="form.errors.amount" class="text-rose-400 text-xs mt-1">{{ form.errors.amount }}</p>
                    </div>

                    <div v-if="goal?.wallet_id">
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-2">
                            {{ t('budgets.txPanel.category') }}
                            <span v-if="categoryRequired" class="text-rose-400 ml-0.5">*</span>
                        </label>
                        <SelectInput v-model="form.category_id" :disabled="!categoryRequired">
                            <option :value="null" disabled>{{ t('budgets.txPanel.pickCategory') }}</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </SelectInput>
                        <p v-if="form.errors.category_id" class="text-rose-400 text-xs mt-1">{{ form.errors.category_id }}</p>
                    </div>
                </form>

                <div class="px-6 pb-6 flex gap-3">
                    <AppButton class="flex-1" :disabled="form.processing || (goal?.wallet_id && !form.category_id)" v-on:click="submit">
                        {{ form.processing ? t('budgets.txPanel.submitting') : t('goals.deposit') }}
                    </AppButton>
                    <AppButton variant="secondary" v-on:click="emit('close')">
                        {{ t('common.cancel') }}
                    </AppButton>
                </div>
            </div>
        </div>
    </Transition>
</template>
