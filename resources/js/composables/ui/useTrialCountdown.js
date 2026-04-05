import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

export function useTrialCountdown() {
    const page = usePage();
    const { t } = useI18n();
    const now = ref(Date.now());
    let timer = null;

    onMounted(() => {
        timer = setInterval(() => {
            now.value = Date.now();
        }, 60_000);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    const isTrialing = computed(() => page.props.auth?.isTrialing === true);

    const trialEndsAt = computed(() =>
        page.props.auth?.trialEndsAt ? new Date(page.props.auth.trialEndsAt).getTime() : null
    );

    const diff = computed(() => {
        if (!trialEndsAt.value) return 0;
        return Math.max(0, trialEndsAt.value - now.value);
    });

    const days = computed(() => Math.floor(diff.value / (1000 * 60 * 60 * 24)));
    const hours = computed(() => Math.floor((diff.value % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)));
    const minutes = computed(() => Math.floor((diff.value % (1000 * 60 * 60)) / (1000 * 60)));

    const label = computed(() => {
        if (!isTrialing.value) return '';
        if (days.value >= 1) {
            return t('trial.countdown.days', { days: days.value }, days.value);
        }
        if (hours.value >= 1) {
            return t('trial.countdown.hours', { hours: hours.value, minutes: minutes.value });
        }
        return t('trial.countdown.minutes', { minutes: minutes.value }, minutes.value);
    });

    return { isTrialing, days, hours, minutes, label };
}
