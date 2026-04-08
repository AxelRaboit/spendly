import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { PlanType } from '@/enums/PlanType';

export function usePlanLimits() {
    const page = usePage();

    const isPro = computed(() => page.props.auth?.plan === PlanType.Pro);

    function canCreate(limitKey, currentCount) {
        return isPro.value || currentCount < page.props.planLimits[limitKey];
    }

    function limit(key) {
        return page.props.planLimits[key];
    }

    return { isPro, canCreate, limit };
}
