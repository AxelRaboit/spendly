<script setup>
import { Lightbulb } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppButton from '@/components/ui/AppButton.vue';
import Modal from '@/Components/Modal.vue';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    limitKey: { type: String, default: null },
    limitValue: { type: Number, default: null },
});

const emit = defineEmits(['close']);

const title = computed(() => {
    if (!props.limitKey) return t('upgrade.default.title');
    return t(`upgrade.${props.limitKey}.title`);
});

const message = computed(() => {
    if (!props.limitKey) return t('upgrade.default.message');
    return t(`upgrade.${props.limitKey}.message`, { limit: props.limitValue || 0 });
});
</script>

<template>
    <Modal :show="show" max-width="md" v-on:close="emit('close')">
        <div class="px-6 py-4 border-b border-base">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-badge-warning-bg">
                    <Lightbulb class="h-5 w-5 text-badge-warning-text" :stroke-width="1.5" />
                </div>
                <div>
                    <h3 class="font-semibold text-primary">{{ title }}</h3>
                </div>
            </div>
        </div>

        <div class="px-6 py-4">
            <p class="text-secondary mb-4">{{ message }}</p>
            <p class="text-sm text-muted mb-6">{{ t('upgrade.callToAction') }}</p>
        </div>

        <div class="px-6 py-4 border-t border-base flex justify-end gap-3">
            <AppButton variant="secondary" v-on:click="emit('close')">
                {{ t('common.notNow') }}
            </AppButton>
            <a
                href="https://spendly.app/pro"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center font-bold rounded transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500 py-2 px-4"
            >
                {{ t('upgrade.upgradeToPro') }}
            </a>
        </div>
    </Modal>
</template>