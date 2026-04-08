<script setup>
import AppModal from '@/components/ui/AppModal.vue';
import { useI18n } from 'vue-i18n';
import { useTour } from '@/composables/ui/useTour';
import { useTheme } from '@/composables/ui/useTheme';
import { Theme } from '@/enums/Theme';
import { Wallet, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({
    show: { type: Boolean, required: true },
});

const emit = defineEmits(['close']);

const { t } = useI18n();
const { startTour } = useTour();
const { theme, setTheme } = useTheme();

const isDark = computed(() => theme.value === Theme.Dark);

function enableLightMode() {
    setTheme(Theme.Light);
}

const guides = [
    {
        key: 'wallet',
        icon: Wallet,
        start: () => startTour(),
    },
];

function launch(guide) {
    emit('close');
    guide.start();
}
</script>

<template>
    <AppModal :show="show" max-width="max-w-lg" v-on:close="$emit('close')">
        <div v-if="isDark" class="mb-4 p-3 rounded-lg bg-amber-500/10 border border-amber-500/30 flex items-start gap-3">
            <Sun class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" />
            <div class="flex-1">
                <p class="text-xs text-amber-100 font-medium">{{ t('tour.modal.lightModeHint') }}</p>
                <button
                    class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition-colors mt-1.5"
                    v-on:click="enableLightMode"
                >
                    {{ t('tour.modal.enableLightMode') }} →
                </button>
            </div>
        </div>

        <div class="space-y-3">
            <button
                v-for="guide in guides"
                :key="guide.key"
                class="w-full flex items-start gap-4 p-4 rounded-xl border border-base hover:border-indigo-500/50 hover:bg-indigo-500/5 transition-colors text-left group"
                v-on:click="launch(guide)"
            >
                <div class="shrink-0 w-10 h-10 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                    <component :is="guide.icon" class="w-5 h-5 text-indigo-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-primary">{{ t(`tour.guides.${guide.key}.title`) }}</p>
                    <p class="text-xs text-muted mt-0.5 leading-relaxed">{{ t(`tour.guides.${guide.key}.desc`) }}</p>
                </div>
                <span class="shrink-0 self-center text-xs font-medium text-indigo-400 group-hover:text-indigo-300 transition-colors">
                    {{ t('tour.start') }} →
                </span>
            </button>
        </div>
    </AppModal>
</template>
