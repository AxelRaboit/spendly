<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { evalMath } from '@/utils/evalMath';
import AppModal from '@/components/ui/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import FormField from '@/components/form/FormField.vue';
import FormHint from '@/components/form/FormHint.vue';
import InputLabel from '@/components/form/InputLabel.vue';
import TextInput from '@/components/form/TextInput.vue';
import DateInput from '@/components/form/DateInput.vue';
import InputError from '@/components/form/InputError.vue';
import { useCurrency } from '@/composables/core/useCurrency';

const { t } = useI18n();
const { fmt } = useCurrency();

const props = defineProps({
    show: { type: Boolean, required: true },
    wallet: { type: Object, required: true },
    budgetMonth: { type: String, default: null },
});

const emit = defineEmits(['close']);

function getDefaultDay() {
    const today = new Date();

    if (props.budgetMonth) {
        const [year, month] = props.budgetMonth.split('-');
        const budgetDate = new Date(year, parseInt(month) - 1, 1);

        const isCurrentMonth = today.getFullYear() === budgetDate.getFullYear() &&
            today.getMonth() === budgetDate.getMonth();

        return isCurrentMonth ? today.getDate() : 1;
    }

    return today.getDate();
}

const form = useForm({
    new_balance: '',
    day: getDefaultDay(),
    date: '', // Will be populated on submit
    description: '',
    month: props.budgetMonth,
});

const currentBalance = computed(() => props.wallet.current_balance ?? 0);

const dayConstraints = computed(() => {
    let year, month;

    if (props.budgetMonth) {
        [year, month] = props.budgetMonth.split('-');
    } else {
        const today = new Date();
        year = today.getFullYear();
        month = String(today.getMonth() + 1).padStart(2, '0');
    }

    const lastDay = new Date(year, parseInt(month), 0).getDate();

    return {
        min: 1,
        max: lastDay,
    };
});

const calculatedDiff = computed(() => {
    const result = evalMath(form.new_balance);
    if (result === null) return null;
    return result - currentBalance.value;
});

function handleNewBalanceBlur() {
    const result = evalMath(form.new_balance);
    if (result !== null) {
        form.new_balance = String(result);
    }
}

function resolveNewBalance() {
    const result = evalMath(form.new_balance);
    if (result !== null) {
        form.new_balance = String(result);
    }
}

function buildDateFromDay() {
    if (!form.day) return;

    const today = new Date();
    const [year, month] = props.budgetMonth
        ? props.budgetMonth.split('-')
        : [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0')];

    form.date = new Date(year, parseInt(month) - 1, form.day).toISOString().slice(0, 10);
}

function submit() {
    resolveNewBalance();
    buildDateFromDay();

    form.post(route('wallets.balance-adjustment.store', props.wallet.id), {
        onSuccess: () => {
            emit('close');
            form.reset();
        },
    });
}

function handleClose() {
    emit('close');
    form.reset();
}
</script>

<template>
    <AppModal :show="show" v-on:close="handleClose">
        <h3 class="text-base font-semibold text-primary">{{ t('balanceAdjustment.title') }}</h3>

        <div class="space-y-2">
            <InputLabel>{{ t('balanceAdjustment.currentBalance') }}</InputLabel>
            <div
                class="text-2xl font-bold font-mono p-3 bg-surface border border-line/60 rounded-lg"
                :class="currentBalance >= 0 ? 'text-primary' : 'text-rose-400'"
            >
                {{ fmt(currentBalance) }}
            </div>
        </div>

        <FormField :label="t('balanceAdjustment.newBalance')">
            <TextInput
                v-model="form.new_balance"
                type="text"
                inputmode="decimal"
                :placeholder="t('balanceAdjustment.newBalancePlaceholder')"
                autofocus
                v-on:blur="handleNewBalanceBlur"
            />
            <FormHint>{{ t('balanceAdjustment.newBalanceHint') }}</FormHint>
            <InputError :message="form.errors.new_balance" />
        </FormField>

        <div v-if="calculatedDiff !== null" class="space-y-2">
            <InputLabel>{{ t('balanceAdjustment.diff') }}</InputLabel>
            <div
                class="text-lg font-bold font-mono p-3 bg-surface border rounded-lg"
                :class="calculatedDiff > 0
                    ? 'border-emerald-500/40 text-emerald-400'
                    : calculatedDiff < 0
                        ? 'border-rose-500/40 text-rose-400'
                        : 'border-line/60 text-muted'"
            >
                {{ calculatedDiff > 0 ? '+' : '' }}{{ fmt(calculatedDiff) }}
            </div>
        </div>

        <FormField :label="t('balanceAdjustment.day')">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="flex items-center justify-center w-10 h-10 rounded-lg bg-surface-3 hover:bg-surface-2 text-muted hover:text-primary transition-colors"
                    :disabled="form.day <= dayConstraints.min"
                    v-on:click="form.day = Math.max(dayConstraints.min, form.day - 1)"
                >
                    −
                </button>
                <input
                    v-model.number="form.day"
                    type="number"
                    :min="dayConstraints.min"
                    :max="dayConstraints.max"
                    class="flex-1 px-4 py-2.5 text-center font-semibold font-mono text-lg bg-surface border border-line/60 rounded-lg text-primary placeholder-muted focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                >
                <button
                    type="button"
                    class="flex items-center justify-center w-10 h-10 rounded-lg bg-surface-3 hover:bg-surface-2 text-muted hover:text-primary transition-colors"
                    :disabled="form.day >= dayConstraints.max"
                    v-on:click="form.day = Math.min(dayConstraints.max, form.day + 1)"
                >
                    +
                </button>
            </div>
            <FormHint class="mt-2">{{ t('balanceAdjustment.dayHint') }}</FormHint>
            <InputError :message="form.errors.date" />
        </FormField>

        <FormField :label="t('balanceAdjustment.description')">
            <TextInput
                v-model="form.description"
                type="text"
                :placeholder="t('balanceAdjustment.descriptionPlaceholder')"
            />
            <InputError :message="form.errors.description" />
        </FormField>

        <div class="flex justify-end gap-3 pt-2">
            <AppButton variant="secondary" v-on:click="handleClose">{{ t('common.cancel') }}</AppButton>
            <AppButton
                :disabled="form.processing || calculatedDiff === null || calculatedDiff === 0"
                v-on:click="submit"
            >
                {{ t('balanceAdjustment.submit') }}
            </AppButton>
        </div>
    </AppModal>
</template>
