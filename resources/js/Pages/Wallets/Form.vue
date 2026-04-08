<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useWalletForm } from '@/composables/forms/useWalletForm';
import { useCurrency } from '@/composables/core/useCurrency';
import { Head, Link } from '@inertiajs/vue3';
import { LayoutList, BookOpen } from 'lucide-vue-next';
import { WalletMode } from '@/enums/WalletMode';
import { useI18n } from 'vue-i18n';

const { t }      = useI18n();
const { symbol } = useCurrency();

const props = defineProps({
    wallet: { type: Object, default: null },
});

const isEdit           = !!props.wallet;
const { form, submit } = useWalletForm(props.wallet ?? undefined);

const modes = [
    {
        key: WalletMode.Budget,
        icon: BookOpen,
        title: () => t('wallets.modeBudgetTitle'),
        description: () => t('wallets.modeBudgetDesc'),
    },
    {
        key: WalletMode.Simple,
        icon: LayoutList,
        title: () => t('wallets.modeSimpleTitle'),
        description: () => t('wallets.modeSimpleDesc'),
    },
];
</script>

<template>
    <Head :title="isEdit ? t('wallets.editTitle') : t('wallets.createTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="isEdit ? t('wallets.editTitle') : t('wallets.createTitle')" />
        </template>

        <div class="max-w-lg">
            <FormSection>
                <form class="space-y-5" v-on:submit.prevent="submit">
                    <div v-if="!isEdit" class="space-y-2">
                        <InputLabel :value="t('wallets.fieldMode')" />
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                v-for="mode in modes"
                                :key="mode.key"
                                type="button"
                                class="flex flex-col items-start gap-2 rounded-xl border-2 p-4 text-left transition-all"
                                :class="form.mode === mode.key
                                    ? 'border-indigo-500 bg-indigo-500/10'
                                    : 'border-base/60 bg-surface hover:border-indigo-500/40'"
                                v-on:click="form.mode = mode.key"
                            >
                                <div class="flex items-center gap-2">
                                    <component
                                        :is="mode.icon"
                                        class="w-4 h-4"
                                        :class="form.mode === mode.key ? 'text-indigo-400' : 'text-muted'"
                                    />
                                    <span
                                        class="text-sm font-semibold"
                                        :class="form.mode === mode.key ? 'text-indigo-400' : 'text-primary'"
                                    >
                                        {{ mode.title() }}
                                    </span>
                                </div>
                                <p class="text-xs text-muted leading-relaxed">{{ mode.description() }}</p>
                            </button>
                        </div>
                        <InputError :message="form.errors.mode" />
                    </div>

                    <div>
                        <InputLabel :value="t('wallets.fieldName')" />
                        <TextInput v-model="form.name" type="text" :placeholder="t('wallets.placeholder')" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel :value="t('wallets.fieldBalance', { symbol })" />
                        <TextInput
                            v-model="form.start_balance"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                        />
                        <InputError :message="form.errors.start_balance" />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <AppButton type="submit">{{ isEdit ? t('common.update') : t('common.create') }}</AppButton>
                        <Link href="/wallets">
                            <AppButton variant="secondary">{{ t('common.cancel') }}</AppButton>
                        </Link>
                    </div>
                </form>
            </FormSection>
        </div>
    </AuthenticatedLayout>
</template>
