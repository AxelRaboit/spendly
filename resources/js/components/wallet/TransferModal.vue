<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DateInput from '@/components/form/DateInput.vue';

const { t } = useI18n();

const props = defineProps({
    show:    { type: Boolean, default: false },
    wallets: { type: Array,   default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    from_wallet_id: '',
    to_wallet_id:   '',
    amount:         '',
    date:           new Date().toISOString().slice(0, 10),
    description:    '',
});

const toWallets = computed(() =>
    props.wallets.filter((w) => String(w.id) !== String(form.from_wallet_id))
);

function submit() {
    form.post('/transfers', {
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
}

function close() {
    form.reset();
    emit('close');
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
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60" v-on:click="close" />

            <div class="relative z-10 w-full max-w-md rounded-xl bg-surface-2 border border-base shadow-xl">
                <div class="px-6 py-4 border-b border-base">
                    <h3 class="text-base font-semibold text-primary">{{ t('transfers.title') }}</h3>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <InputLabel :value="t('transfers.from')" class="mb-1.5" />
                        <SelectInput v-model="form.from_wallet_id" :class="{ 'border-rose-500': form.errors.from_wallet_id }">
                            <option value="" disabled>—</option>
                            <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </SelectInput>
                        <InputError v-if="form.errors.from_wallet_id" :message="form.errors.from_wallet_id" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel :value="t('transfers.to')" class="mb-1.5" />
                        <SelectInput v-model="form.to_wallet_id" :class="{ 'border-rose-500': form.errors.to_wallet_id }">
                            <option value="" disabled>—</option>
                            <option v-for="w in toWallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </SelectInput>
                        <InputError v-if="form.errors.to_wallet_id" :message="form.errors.to_wallet_id" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel :value="t('transfers.amount')" class="mb-1.5" />
                            <TextInput
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :placeholder="'0.00'"
                                :class="{ 'border-rose-500': form.errors.amount }"
                            />
                            <InputError v-if="form.errors.amount" :message="form.errors.amount" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('transfers.date')" class="mb-1.5" />
                            <DateInput v-model="form.date" />
                            <InputError v-if="form.errors.date" :message="form.errors.date" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel :value="t('transfers.description')" class="mb-1.5" />
                        <TextInput
                            v-model="form.description"
                            type="text"
                            :placeholder="t('transfers.descPlaceholder')"
                        />
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-base flex justify-end gap-3">
                    <AppButton variant="secondary" v-on:click="close">{{ t('common.cancel') }}</AppButton>
                    <AppButton
                        :disabled="form.processing || !form.from_wallet_id || !form.to_wallet_id || !form.amount"
                        v-on:click="submit"
                    >
                        {{ form.processing ? t('transfers.submitting') : t('transfers.submit') }}
                    </AppButton>
                </div>
            </div>
        </div>
    </Transition>
</template>
