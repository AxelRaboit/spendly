<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DateInput from '@/components/form/DateInput.vue';

const { t } = useI18n();

const props = defineProps({
    show:    { type: Boolean, default: false },
    wallets: { type: Array,   default: () => [] },
    editing: { type: Object,  default: null }, // { transfer_id, amount, date, description }
});

const emit = defineEmits(['close']);

const isEditing = computed(() => props.editing !== null);

const form = useForm({
    from_wallet_id: '',
    to_wallet_id:   '',
    amount:         '',
    date:           new Date().toISOString().slice(0, 10),
    description:    '',
});

watch(() => props.editing, (val) => {
    if (val) {
        form.amount      = val.amount;
        form.date        = val.date?.slice(0, 10) ?? '';
        form.description = val.description ?? '';
    }
}, { immediate: true });

const toWallets = computed(() =>
    props.wallets.filter((w) => String(w.id) !== String(form.from_wallet_id))
);

function submit() {
    if (isEditing.value) {
        form.put(route('transfers.update', props.editing.transfer_id), {
            onSuccess: () => close(),
        });
    } else {
        form.post('/transfers', {
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
    }
}

function close() {
    form.reset();
    form.date = new Date().toISOString().slice(0, 10);
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

            <div class="relative z-10 w-full max-w-md rounded-xl bg-surface-2 border border-line shadow-xl">
                <div class="px-6 py-4 border-b border-line">
                    <h3 class="text-base font-semibold text-primary">
                        {{ isEditing ? t('transfers.editTitle') : t('transfers.title') }}
                    </h3>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <template v-if="!isEditing">
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
                    </template>

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

                <div class="px-6 py-4 border-t border-line flex justify-end gap-3">
                    <AppButton variant="secondary" v-on:click="close">{{ t('common.cancel') }}</AppButton>
                    <AppButton
                        :disabled="form.processing || (!isEditing && (!form.from_wallet_id || !form.to_wallet_id)) || !form.amount"
                        v-on:click="submit"
                    >
                        <template v-if="isEditing">
                            {{ form.processing ? t('transfers.updating') : t('transfers.update') }}
                        </template>
                        <template v-else>
                            {{ form.processing ? t('transfers.submitting') : t('transfers.submit') }}
                        </template>
                    </AppButton>
                </div>
            </div>
        </div>
    </Transition>
</template>
