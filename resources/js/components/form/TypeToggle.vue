<script setup>
import { useI18n } from 'vue-i18n';
import { TransactionType } from '@/enums/TransactionType';

const { t } = useI18n();

const model = defineModel({ type: String, default: TransactionType.Expense });
defineProps({ disabled: { type: Boolean, default: false } });
</script>

<template>
    <div class="flex w-full rounded-lg border border-base overflow-hidden">
        <button
            type="button"
            :disabled="disabled"
            :class="[
                'flex-1 px-3 py-2 text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
                model === TransactionType.Expense
                    ? 'bg-rose-500/20 text-rose-400 border-r border-base'
                    : 'text-secondary hover:text-primary border-r border-base',
            ]"
            v-on:click="model = TransactionType.Expense"
        >
            ↓ {{ t('typeToggle.expense') }}
        </button>
        <button
            type="button"
            :disabled="disabled"
            :class="[
                'flex-1 px-3 py-2 text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
                model === TransactionType.Income
                    ? 'bg-emerald-500/20 text-emerald-400'
                    : 'text-secondary hover:text-primary',
            ]"
            v-on:click="model = TransactionType.Income"
        >
            ↑ {{ t('typeToggle.income') }}
        </button>
    </div>
</template>
