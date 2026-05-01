import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial) => ({ ...initial, reset: vi.fn(), post: vi.fn(), put: vi.fn() }),
    router: { post: vi.fn() },
}));

vi.mock('@/composables/budget/useSectionCategoryFilter.js', () => ({
    useSectionCategoryFilter: () => ({
        txFilteredCategories: ref([]),
        onTxSectionChange: vi.fn(),
    }),
}));

const markManualSpy = vi.fn();
const resetSpy = vi.fn();

vi.mock('@/composables/forms/useAutoCategory.js', () => ({
    useAutoCategory: () => ({
        suggestedCategoryId: ref(null),
        categoryManuallySet: ref(false),
        markManual: markManualSpy,
        reset: resetSpy,
    }),
}));

import { useTransactionPanel } from '@/composables/budget/useTransactionPanel.js';

function makeComposable() {
    return useTransactionPanel(ref(1), ref({ month: '2026-05' }), ref({}), vi.fn(), ref([]));
}

beforeEach(() => {
    vi.clearAllMocks();
});

// ─── openTxPanel ──────────────────────────────────────────────────────────────

describe('openTxPanel', () => {
    it('marks category as manual when a categoryId is provided', () => {
        const { openTxPanel } = makeComposable();

        openTxPanel(42);

        expect(markManualSpy).toHaveBeenCalledOnce();
    });

    it('does not mark category as manual when no categoryId is given', () => {
        const { openTxPanel } = makeComposable();

        openTxPanel();

        expect(markManualSpy).not.toHaveBeenCalled();
    });

    it('does not mark category as manual when categoryId is null', () => {
        const { openTxPanel } = makeComposable();

        openTxPanel(null);

        expect(markManualSpy).not.toHaveBeenCalled();
    });

    it('resets auto-category before marking manual', () => {
        const { openTxPanel } = makeComposable();
        const callOrder = [];
        resetSpy.mockImplementation(() => callOrder.push('reset'));
        markManualSpy.mockImplementation(() => callOrder.push('markManual'));

        openTxPanel(42);

        expect(callOrder).toEqual(['reset', 'markManual']);
    });

    it('opens the panel', () => {
        const { openTxPanel, txPanel } = makeComposable();

        openTxPanel();

        expect(txPanel.value).toBe(true);
    });

    it('prefills category_id and label', () => {
        const { openTxPanel, txForm, txPrefillLabel } = makeComposable();

        openTxPanel(7, 'Groceries');

        expect(txForm.category_id).toBe(7);
        expect(txPrefillLabel.value).toBe('Groceries');
    });
});
