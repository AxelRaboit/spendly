import { describe, it, expect, vi, afterEach } from 'vitest';
import { ref } from 'vue';
import { useBudgetTotals } from '@/composables/budget/useBudgetTotals';
import { BudgetSection } from '@/enums/BudgetSection';

function item(planned, actual, carried = 0, available = 0) {
    return { planned_amount: planned, actual_amount: actual, carried_over: carried, available };
}

function makeSections(overrides = {}) {
    return ref({
        [BudgetSection.Income]: [],
        [BudgetSection.Savings]: [],
        [BudgetSection.Bills]: [],
        [BudgetSection.Expenses]: [],
        [BudgetSection.Debt]: [],
        ...overrides,
    });
}

afterEach(() => {
    vi.useRealTimers();
});

// ─── totals ───────────────────────────────────────────────────────────────────

describe('totals', () => {
    it('aggregates planned, actual, carried and available per section', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(2000, 1800), item(500, 400)],
            [BudgetSection.Bills]: [item(300, 280, 50, 20)],
        });

        const { totals } = useBudgetTotals(sections, ref(0));

        expect(totals.value[BudgetSection.Income]).toEqual({ planned: 2500, actual: 2200, carried: 0, available: 0 });
        expect(totals.value[BudgetSection.Bills]).toEqual({ planned: 300, actual: 280, carried: 50, available: 20 });
    });

    it('returns zeros for empty sections', () => {
        const { totals } = useBudgetTotals(makeSections(), ref(0));

        expect(totals.value[BudgetSection.Income]).toEqual({ planned: 0, actual: 0, carried: 0, available: 0 });
    });

    it('treats missing carried_over as 0', () => {
        const sections = makeSections({
            [BudgetSection.Expenses]: [{ planned_amount: 100, actual_amount: 80 }],
        });

        const { totals } = useBudgetTotals(sections, ref(0));

        expect(totals.value[BudgetSection.Expenses].carried).toBe(0);
    });
});

// ─── totalCarriedOver ─────────────────────────────────────────────────────────

describe('totalCarriedOver', () => {
    it('sums carried_over across all sections', () => {
        const sections = makeSections({
            [BudgetSection.Savings]: [item(0, 0, 100)],
            [BudgetSection.Bills]: [item(0, 0, 50)],
            [BudgetSection.Debt]: [item(0, 0, 25)],
        });

        const { totalCarriedOver } = useBudgetTotals(sections, ref(0));

        expect(totalCarriedOver.value).toBe(175);
    });

    it('returns 0 when no section has carried over amounts', () => {
        const { totalCarriedOver } = useBudgetTotals(makeSections(), ref(0));

        expect(totalCarriedOver.value).toBe(0);
    });
});

// ─── totalIncome ──────────────────────────────────────────────────────────────

describe('totalIncome', () => {
    it('aggregates income section planned and actual', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(3000, 2500), item(500, 600)],
        });

        const { totalIncome } = useBudgetTotals(sections, ref(0));

        expect(totalIncome.value).toEqual({ planned: 3500, actual: 3100 });
    });

    it('adds unbudgeted income to actual only', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(3000, 2500)],
        });

        const { totalIncome } = useBudgetTotals(sections, ref(0), null, ref({ income: 200, expenses: 0 }));

        expect(totalIncome.value).toEqual({ planned: 3000, actual: 2700 });
    });

    it('returns zeros when income section is empty and no unbudgeted', () => {
        const { totalIncome } = useBudgetTotals(makeSections(), ref(0));

        expect(totalIncome.value).toEqual({ planned: 0, actual: 0 });
    });
});

// ─── totalExpenses ────────────────────────────────────────────────────────────

describe('totalExpenses', () => {
    it('aggregates savings, bills, expenses and debt sections', () => {
        const sections = makeSections({
            [BudgetSection.Savings]: [item(500, 500)],
            [BudgetSection.Bills]: [item(200, 190)],
            [BudgetSection.Expenses]: [item(300, 320)],
            [BudgetSection.Debt]: [item(100, 100)],
        });

        const { totalExpenses } = useBudgetTotals(sections, ref(0));

        expect(totalExpenses.value).toEqual({ planned: 1100, actual: 1110 });
    });

    it('excludes income section from expenses', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(5000, 5000)],
            [BudgetSection.Bills]: [item(400, 400)],
        });

        const { totalExpenses } = useBudgetTotals(sections, ref(0));

        expect(totalExpenses.value).toEqual({ planned: 400, actual: 400 });
    });

    it('adds unbudgeted expenses to actual only', () => {
        const sections = makeSections({
            [BudgetSection.Bills]: [item(200, 200)],
        });

        const { totalExpenses } = useBudgetTotals(sections, ref(0), null, ref({ income: 0, expenses: 50 }));

        expect(totalExpenses.value).toEqual({ planned: 200, actual: 250 });
    });
});

// ─── cashFlow ─────────────────────────────────────────────────────────────────

describe('cashFlow', () => {
    it('returns income minus expenses for planned and actual', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(3000, 2800)],
            [BudgetSection.Bills]: [item(1000, 900)],
        });

        const { cashFlow } = useBudgetTotals(sections, ref(0));

        expect(cashFlow.value).toEqual({ planned: 2000, actual: 1900 });
    });

    it('is negative when expenses exceed income', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(1000, 900)],
            [BudgetSection.Bills]: [item(1500, 1200)],
        });

        const { cashFlow } = useBudgetTotals(sections, ref(0));

        expect(cashFlow.value).toEqual({ planned: -500, actual: -300 });
    });
});

// ─── leftToSpend ──────────────────────────────────────────────────────────────

describe('leftToSpend', () => {
    it('adds startBalance, cashFlow and carriedOver', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(3000, 3000)],
            [BudgetSection.Bills]: [item(1000, 1000)],
            [BudgetSection.Savings]: [item(0, 0, 200)],
        });

        const { leftToSpend } = useBudgetTotals(sections, ref(500));

        // planned: 500 + (3000 - 1000) + 200 = 2700
        // actual:  500 + (3000 - 1000) + 200 = 2700
        expect(leftToSpend.value).toEqual({ planned: 2700, actual: 2700 });
    });

    it('is influenced by the start balance', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(1000, 1000)],
            [BudgetSection.Bills]: [item(600, 600)],
        });

        const { leftToSpend: low } = useBudgetTotals(sections, ref(0));
        const { leftToSpend: high } = useBudgetTotals(sections, ref(1000));

        expect(low.value.actual).toBe(400);
        expect(high.value.actual).toBe(1400);
    });
});

// ─── savingsRate ──────────────────────────────────────────────────────────────

describe('savingsRate', () => {
    it('returns the percentage of actual income going to savings', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(0, 4000)],
            [BudgetSection.Savings]: [item(0, 1000)],
        });

        const { savingsRate } = useBudgetTotals(sections, ref(0));

        expect(savingsRate.value).toBe(25);
    });

    it('rounds to the nearest integer', () => {
        const sections = makeSections({
            [BudgetSection.Income]: [item(0, 3000)],
            [BudgetSection.Savings]: [item(0, 1000)],
        });

        const { savingsRate } = useBudgetTotals(sections, ref(0));

        expect(savingsRate.value).toBe(33);
    });

    it('returns null when income is zero', () => {
        const { savingsRate } = useBudgetTotals(makeSections(), ref(0));

        expect(savingsRate.value).toBeNull();
    });
});

// ─── isCurrentMonth ───────────────────────────────────────────────────────────

describe('isCurrentMonth', () => {
    it('returns true when budget month matches current month', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-04-15'));

        const budget = ref({ month: '2026-04' });
        const { isCurrentMonth } = useBudgetTotals(makeSections(), ref(0), budget);

        expect(isCurrentMonth.value).toBe(true);
    });

    it('returns false when budget month differs from current month', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-04-15'));

        const budget = ref({ month: '2026-03' });
        const { isCurrentMonth } = useBudgetTotals(makeSections(), ref(0), budget);

        expect(isCurrentMonth.value).toBe(false);
    });

    it('returns false when no budget is provided', () => {
        const { isCurrentMonth } = useBudgetTotals(makeSections(), ref(0));

        expect(isCurrentMonth.value).toBe(false);
    });
});

// ─── projectedExpenses ────────────────────────────────────────────────────────

describe('projectedExpenses', () => {
    it('projects expenses based on actual spending rate for the current month', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-04-15'));

        const sections = makeSections({
            [BudgetSection.Bills]: [item(0, 600)],
        });
        const budget = ref({ month: '2026-04' });

        const { projectedExpenses } = useBudgetTotals(sections, ref(0), budget);

        // April has 30 days. Day 15: (600 / 15) * 30 = 1200
        expect(projectedExpenses.value).toBe(1200);
    });

    it('returns null for a past month', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-04-15'));

        const budget = ref({ month: '2026-03' });
        const { projectedExpenses } = useBudgetTotals(makeSections(), ref(0), budget);

        expect(projectedExpenses.value).toBeNull();
    });

    it('returns null when no budget is provided', () => {
        const { projectedExpenses } = useBudgetTotals(makeSections(), ref(0));

        expect(projectedExpenses.value).toBeNull();
    });
});
