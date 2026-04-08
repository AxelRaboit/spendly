import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { SECTION_COLORS } from './sectionColors.js';
import { BudgetSection } from '@/enums/BudgetSection';

export function useSectionMeta() {
    const { t } = useI18n();

    const SECTION_META = computed(() => ({
        [BudgetSection.Income]: {
            label: t('budgets.sections.income'),
            color: 'text-emerald-400',
            barColor: 'bg-emerald-400',
            bg: 'bg-emerald-400/10',
            border: 'border-emerald-400/30',
            positiveIsGood: true,
            glow: SECTION_COLORS[BudgetSection.Income],
        },
        [BudgetSection.Savings]: {
            label: t('budgets.sections.savings'),
            color: 'text-sky-400',
            barColor: 'bg-sky-400',
            bg: 'bg-sky-400/10',
            border: 'border-sky-400/30',
            positiveIsGood: true,
            glow: SECTION_COLORS[BudgetSection.Savings],
        },
        [BudgetSection.Bills]: {
            label: t('budgets.sections.bills'),
            color: 'text-amber-400',
            barColor: 'bg-amber-400',
            bg: 'bg-amber-400/10',
            border: 'border-amber-400/30',
            positiveIsGood: false,
            glow: SECTION_COLORS[BudgetSection.Bills],
        },
        [BudgetSection.Expenses]: {
            label: t('budgets.sections.expenses'),
            color: 'text-rose-400',
            barColor: 'bg-rose-400',
            bg: 'bg-rose-400/10',
            border: 'border-rose-400/30',
            positiveIsGood: false,
            glow: SECTION_COLORS[BudgetSection.Expenses],
        },
        [BudgetSection.Debt]: {
            label: t('budgets.sections.debt'),
            color: 'text-purple-400',
            barColor: 'bg-purple-400',
            bg: 'bg-purple-400/10',
            border: 'border-purple-400/30',
            positiveIsGood: true,
            glow: SECTION_COLORS[BudgetSection.Debt],
        },
    }));

    return { SECTION_META };
}
