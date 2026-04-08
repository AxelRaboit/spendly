<script setup>
import AppModal from '@/components/ui/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import { Check, X, Sparkles } from 'lucide-vue-next';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';

const { t } = useI18n();
const { fmt } = useCurrency();
const page = usePage();

defineProps({
    show: { type: Boolean, required: true },
});

const emit = defineEmits(['close']);

const limits = computed(() => page.props.planLimits);

const downgradeForm = useForm({});

function chooseFree() {
    downgradeForm.post(route('plan.downgrade'), {
        onSuccess: () => emit('close'),
    });
}

function choosePro() {
    emit('close');
}
</script>

<template>
    <AppModal :show="show" max-width="max-w-3xl" :scrollable="true" v-on:close="$emit('close')">
        <div class="text-center mb-6">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-indigo-600/10 mx-auto mb-3">
                <Sparkles class="w-6 h-6 text-indigo-400" />
            </div>
            <h2 class="text-2xl font-bold text-primary">{{ t('onboarding.title') }}</h2>
            <p class="text-secondary text-sm mt-1">{{ t('onboarding.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Free -->
            <div class="bg-surface-2 border border-base rounded-xl p-5 flex flex-col">
                <h3 class="text-lg font-bold text-primary">{{ t('plan.free.name') }}</h3>
                <p class="text-2xl font-extrabold text-primary mt-1">
                    {{ t('plan.free.price') }}
                    <span class="text-sm font-normal text-muted">/{{ t('plan.perMonth') }}</span>
                </p>
                <p class="text-secondary text-xs mt-1">{{ t('plan.free.tagline') }}</p>

                <ul class="mt-4 space-y-2 flex-1">
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.wallets', { n: limits.wallet }) }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.goals', { n: limits.goal }) }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.recurring', { n: limits.recurring }) }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.history', { n: limits.transactionHistoryDays }) }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.budgetBasic') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.statsMonths') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <X class="w-3.5 h-3.5 shrink-0 text-muted" />
                        <span class="text-muted line-through">{{ t('plan.features.budgetAdvanced') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <X class="w-3.5 h-3.5 shrink-0 text-muted" />
                        <span class="text-muted line-through">{{ t('plan.features.importExport') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <X class="w-3.5 h-3.5 shrink-0 text-muted" />
                        <span class="text-muted line-through">{{ t('plan.features.splitTransactions') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <X class="w-3.5 h-3.5 shrink-0 text-muted" />
                        <span class="text-muted line-through">{{ t('plan.features.attachments') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <X class="w-3.5 h-3.5 shrink-0 text-muted" />
                        <span class="text-muted line-through">{{ t('plan.features.sharedWallets') }}</span>
                    </li>
                </ul>

                <AppButton
                    variant="outline"
                    class="w-full mt-5"
                    :disabled="downgradeForm.processing"
                    v-on:click="chooseFree"
                >
                    {{ t('onboarding.continueFree') }}
                </AppButton>
            </div>

            <!-- Pro -->
            <div class="bg-surface-2 border-2 border-indigo-600/60 rounded-xl p-5 flex flex-col relative shadow-lg shadow-indigo-500/10">
                <span class="absolute top-4 right-4 text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full">
                    {{ t('onboarding.trialBadge') }}
                </span>

                <h3 class="text-lg font-bold text-primary">{{ t('plan.pro.name') }}</h3>
                <p class="text-2xl font-extrabold text-primary mt-1">
                    {{ fmt(limits.proPrice) }}
                    <span class="text-sm font-normal text-muted">/{{ t('plan.perMonth') }}</span>
                </p>
                <p class="text-secondary text-xs mt-1">{{ t('plan.pro.tagline') }}</p>

                <ul class="mt-4 space-y-2 flex-1">
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.walletsUnlimited') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.goalsUnlimited') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.recurringUnlimited') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.historyUnlimited') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.budgetBasic') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.statsMonthsPro') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.budgetAdvanced') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.importExport') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.splitTransactions') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.attachments') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Check class="w-3.5 h-3.5 shrink-0 text-emerald-400" />
                        <span class="text-secondary">{{ t('plan.features.sharedWallets') }}</span>
                    </li>
                    <li class="flex items-center gap-2 text-xs">
                        <Sparkles class="w-3.5 h-3.5 shrink-0 text-indigo-400" />
                        <span class="text-indigo-400 italic">{{ t('plan.features.andMore') }}</span>
                    </li>
                </ul>

                <AppButton
                    variant="primary"
                    class="w-full mt-5"
                    v-on:click="choosePro"
                >
                    {{ t('onboarding.startTrial') }}
                </AppButton>
            </div>
        </div>

        <p class="text-center text-xs text-muted mt-4">{{ t('onboarding.trialNote') }}</p>
    </AppModal>
</template>
