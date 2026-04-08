<script setup>
import { useI18n } from 'vue-i18n';
import { TransactionType } from '@/enums/TransactionType';

const { t } = useI18n();

const model = defineModel({ type: String, default: TransactionType.Expense });
defineProps({ disabled: { type: Boolean, default: false } });
</script>

<template>
    <div class="flex w-full rounded-lg border border-base overflow-hidden" :class="disabled ? 'opacity-60' : ''">
        <button
            type="button"
            :disabled="disabled"
            :class="[
                'flex-1 px-3 py-2 text-sm font-medium transition-colors',
                model === TransactionType.Expense
                    ? 'bg-rose-500/20 text-rose-400 border-r border-base'
                    : 'text-secondary hover:text-primary border-r border-base',
                disabled ? 'cursor-not-allowed' : '',
            ]"
            v-on:click="model = TransactionType.Expense"
        >
            ↓ {{ t('typeToggle.expense') }}
        </button>
        <button
            type="button"
            :disabled="disabled"
            :class="[
                'flex-1 px-3 py-2 text-sm font-medium transition-colors',
                model === TransactionType.Income
                    ? 'bg-emerald-500/20 text-emerald-400'
                    : 'text-secondary hover:text-primary',
                disabled ? 'cursor-not-allowed' : '',
            ]"
            v-on:click="model = TransactionType.Income"
        >
            ↑ {{ t('typeToggle.income') }}
        </button>
    </div>
</template>
