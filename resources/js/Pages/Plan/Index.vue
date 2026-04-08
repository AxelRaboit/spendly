<script setup>
import { Check, X, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import AppButton from '@/components/ui/AppButton.vue';
import { useTrialCountdown } from '@/composables/ui/useTrialCountdown';
import { useCurrency } from '@/composables/core/useCurrency';
import { usePlanLimits } from '@/composables/ui/usePlanLimits';

const { t } = useI18n();
const { fmt } = useCurrency();
const page = usePage();

const { isPro } = usePlanLimits();
const { isTrialing, label: trialLabel } = useTrialCountdown();
const limits = computed(() => page.props.planLimits);

const upgradeForm = useForm({});
const downgradeForm = useForm({});

function upgrade() {
    upgradeForm.post(route('plan.upgrade'));
}

function downgrade() {
    downgradeForm.post(route('plan.downgrade'));
}
</script>

<template>
    <Head :title="t('plan.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('plan.title')" />
        </template>

        <div class="mx-auto max-w-4xl space-y-8">
            <div v-if="isTrialing" class="rounded-lg bg-indigo-600/15 border border-indigo-500/40 px-4 py-3 text-center text-sm text-indigo-400">
                {{ trialLabel }}
            </div>

            <p class="text-center text-secondary text-sm">
                {{ t('plan.subtitle') }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-surface border border-base/60 rounded-xl p-6 flex flex-col relative">
                    <span
                        v-if="!isPro"
                        class="absolute top-4 right-4 text-xs font-bold bg-indigo-600/15 text-indigo-400 px-2 py-0.5 rounded-full"
                    >
                        {{ t('plan.currentPlan') }}
                    </span>

                    <h2 class="text-xl font-bold text-primary">{{ t('plan.free.name') }}</h2>

                    <p class="text-3xl font-extrabold text-primary mt-2">
                        {{ t('plan.free.price') }}
                        <span class="text-base font-normal text-muted">/{{ t('plan.perMonth') }}</span>
                    </p>

                    <p class="text-secondary text-sm mt-1">{{ t('plan.free.tagline') }}</p>

                    <ul class="mt-6 space-y-3 flex-1">
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.wallets', { n: limits.wallet }) }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.goals', { n: limits.goal }) }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.recurring', { n: limits.recurring }) }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.history', { n: limits.transactionHistoryDays }) }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.budgetBasic') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.statsMonths') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <X class="w-4 h-4 shrink-0 text-muted" />
                            <span class="text-muted line-through">{{ t('plan.features.notes') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <X class="w-4 h-4 shrink-0 text-muted" />
                            <span class="text-muted line-through">{{ t('plan.features.budgetAdvanced') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <X class="w-4 h-4 shrink-0 text-muted" />
                            <span class="text-muted line-through">{{ t('plan.features.importExport') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <X class="w-4 h-4 shrink-0 text-muted" />
                            <span class="text-muted line-through">{{ t('plan.features.splitTransactions') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <X class="w-4 h-4 shrink-0 text-muted" />
                            <span class="text-muted line-through">{{ t('plan.features.attachments') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <X class="w-4 h-4 shrink-0 text-muted" />
                            <span class="text-muted line-through">{{ t('plan.features.sharedWallets') }}</span>
                        </li>
                    </ul>

                    <div class="mt-6">
                        <AppButton
                            v-if="isPro"
                            variant="link"
                            size="none"
                            :disabled="downgradeForm.processing"
                            class="text-sm"
                            v-on:click="downgrade"
                        >
                            {{ t('plan.downgradeFree') }}
                        </AppButton>
                        <div v-else class="h-9" />
                    </div>
                </div>

                <div class="bg-surface border-2 border-indigo-600/60 rounded-xl p-6 flex flex-col relative shadow-lg shadow-indigo-500/10">
                    <span
                        v-if="isPro"
                        class="absolute top-4 right-4 text-xs font-bold bg-indigo-600/15 text-indigo-400 px-2 py-0.5 rounded-full"
                    >
                        {{ t('plan.currentPlan') }}
                    </span>

                    <span
                        v-else
                        class="absolute top-4 right-4 text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full"
                    >
                        {{ t('plan.pro.name') }}
                    </span>

                    <h2 class="text-xl font-bold text-primary">{{ t('plan.pro.name') }}</h2>

                    <p class="text-3xl font-extrabold text-primary mt-2">
                        {{ fmt(limits.proPrice) }}
                        <span class="text-base font-normal text-muted">/{{ t('plan.perMonth') }}</span>
                    </p>

                    <p class="text-secondary text-sm mt-1">{{ t('plan.pro.tagline') }}</p>

                    <ul class="mt-6 space-y-3 flex-1">
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.walletsUnlimited') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.goalsUnlimited') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.recurringUnlimited') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.historyUnlimited') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.budgetBasic') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.statsMonthsPro') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.notes') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.budgetAdvanced') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.importExport') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.splitTransactions') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.attachments') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Check class="w-4 h-4 shrink-0 text-emerald-400" />
                            <span class="text-secondary">{{ t('plan.features.sharedWallets') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-sm">
                            <Sparkles class="w-4 h-4 shrink-0 text-indigo-400" />
                            <span class="text-indigo-400 italic">{{ t('plan.features.andMore') }}</span>
                        </li>
                    </ul>

                    <div class="mt-6">
                        <div v-if="!isPro">
                            <AppButton class="w-full" :disabled="upgradeForm.processing" v-on:click="upgrade">
                                {{ t('plan.upgradeCta') }}
                            </AppButton>
                            <p class="text-xs text-muted text-center mt-2">
                                🔒 {{ t('plan.stripeSoon') }}
                            </p>
                        </div>
                        <div v-else class="text-sm text-indigo-400 text-center font-medium py-2">
                            {{ t('plan.alreadyPro') }}
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-center text-xs text-muted">
                {{ t('plan.disclaimer') }}
            </p>
        </div>
    </AuthenticatedLayout>
</template>
