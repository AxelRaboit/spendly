/**
 * Tour Driver - Tour Orchestration & Execution
 *
 * Handles driver.js integration, page transitions, and API calls.
 * Coordinates state management with step building to run tours.
 */

import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { useI18n } from 'vue-i18n';
import { nextTick } from 'vue';
import axios from 'axios';
import { useTourState } from './useTourState';
import { useTourSteps } from './useTourSteps';

export function useTourDriver() {
    const { t: translate } = useI18n();
    const { getState, setState, isActive } = useTourState();
    const {
        buildPrevMonthSteps,
        buildCurrentMonthSteps,
        applyGlobalProgress,
        showPanelOverlay,
        removePanelOverlay,
        liftTxPanel,
        resetPanel,
        resetDetailPanel,
        getVisibleEl,
        isMobile,
    } = useTourSteps();

    const pageHooks = {};

    /**
     * Initialize tour for the current page
     */
    function initForPage(pageKey, hooks = {}) {
        const state = getState();
        if (!isActive()) return;

        Object.keys(pageHooks).forEach((k) => delete pageHooks[k]);
        Object.assign(pageHooks, hooks);

        if (pageKey === 'wallets.index' && state.page === 0) {
            runPage0();
        } else if (pageKey === 'budgets.show' && state.page === 1) {
            runBudgetPage(1);
        } else if (pageKey === 'budgets.show' && state.page === 2) {
            runBudgetPage(2);
        } else if (pageKey === 'profile.edit' && state.page === 3) {
            runProfilePage();
        }
    }

    /**
     * Shared driver config base
     */
    function baseDriverConfig() {
        return {
            animate: true,
            stagePadding: 12,
            stageRadius: 12,
            disableActiveInteraction: true,
            allowKeyboardControl: false,
            overlayClickBehavior: () => {},
            scrollIntoViewOptions: { behavior: 'auto', block: 'center', inline: 'nearest' },
            onHighlightStarted: (element) => {
                if (element) {
                    element.scrollIntoView({ behavior: 'auto', block: 'center', inline: 'nearest' });
                }
            },
        };
    }

    /**
     * Page 0: wallets.index — intro step, then seed + navigate
     */
    function runPage0() {
        setTimeout(() => {
            const tourElement = document.querySelector('[data-tour="create-wallet"]');
            const step = tourElement
                ? {
                      element: '[data-tour="create-wallet"]',
                      popover: {
                          title: translate('tour.stepWelcome.title'),
                          description: translate('tour.stepWelcome.desc'),
                          side: 'bottom',
                          align: 'end',
                      },
                  }
                : {
                      popover: {
                          title: translate('tour.stepWelcome.title'),
                          description: translate('tour.stepWelcome.desc'),
                      },
                  };

            const driverObj = driver({
                ...baseDriverConfig(),
                showProgress: false,
                showButtons: ['next', 'close'],
                nextBtnText: translate('tour.next'),
                doneBtnText: translate('tour.next'),
                steps: [step],
                onNextClick: async () => {
                    driverObj.destroy();
                    await seedAndNavigate();
                },
                onCloseClick: () => {
                    setState({ skipped: true });
                    driverObj.destroy();
                },
            });
            driverObj.drive();
        }, 500);
    }

    /**
     * Seed demo wallet and navigate to budget page
     */
    async function seedAndNavigate() {
        try {
            const { data } = await axios.post(route('tour.seed'));
            const walletId = data.wallet_id;
            const now = new Date();
            const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const prevMonth = `${prev.getFullYear()}-${String(prev.getMonth() + 1).padStart(2, '0')}`;
            setState({ page: 1, walletId });
            window.location.href = route('wallets.budget.show', walletId) + `?month=${prevMonth}`;
        } catch (exception) {
            console.error('[Tour] seed failed:', exception);
            setState({ skipped: true });
        }
    }

    /**
     * Pages 1 & 2: budgets.show (previous + current month)
     */
    function runBudgetPage(page) {
        setTimeout(() => {
            const steps = page === 1 ? buildPrevMonthSteps(pageHooks) : buildCurrentMonthSteps();
            if (!steps.length) {
                advanceBudgetPage(page);
                return;
            }

            const state = getState();
            applyGlobalProgress(steps, page, state);
            setState(state);

            const driverObj = driver({
                ...baseDriverConfig(),
                showProgress: true,
                showButtons: ['next', 'previous', 'close'],
                nextBtnText: translate('tour.next'),
                prevBtnText: translate('tour.prev'),
                doneBtnText: translate('tour.next'),
                steps,
                onNextClick: async () => {
                    if (driverObj.hasNextStep()) {
                        const currentIdx = driverObj.getActiveIndex() ?? 0;
                        const cleanup = steps[currentIdx]?._cleanup;
                        if (cleanup) cleanup();
                        const nextIdx = currentIdx + 1;
                        const prep = steps[nextIdx]?._prep;
                        if (prep) {
                            prep();
                            await nextTick();
                            await new Promise((resolve) => setTimeout(resolve, 300));
                        }
                        driverObj.moveNext();
                    } else {
                        resetPanel();
                        resetDetailPanel();
                        if (pageHooks.closeTxPanel) pageHooks.closeTxPanel();
                        if (pageHooks.closeDetailPanel) pageHooks.closeDetailPanel();
                        if (pageHooks.cancelAdding) pageHooks.cancelAdding();
                        driverObj.destroy();
                        advanceBudgetPage(page);
                    }
                },
                onCloseClick: async () => {
                    resetPanel();
                    resetDetailPanel();
                    if (pageHooks.closeTxPanel) pageHooks.closeTxPanel();
                    if (pageHooks.closeDetailPanel) pageHooks.closeDetailPanel();
                    if (pageHooks.cancelAdding) pageHooks.cancelAdding();
                    driverObj.destroy();
                    setState({ skipped: true });
                    await cleanupDemoWallet();
                    window.location.href = route('wallets.index');
                },
            });
            driverObj.drive();
        }, 500);
    }

    /**
     * Advance from one budget page to the next
     */
    function advanceBudgetPage(page) {
        const state = getState();
        const walletId = state?.walletId;
        if (page === 1) {
            const now = new Date();
            const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
            setState({ page: 2 });
            window.location.href = route('wallets.budget.show', walletId) + `?month=${currentMonth}`;
        } else if (page === 2) {
            setState({ page: 3 });
            window.location.href = route('profile.edit');
        }
    }

    /**
     * Page 3: profile.edit — budget presets step
     */
    function runProfilePage() {
        setTimeout(() => {
            if (!document.querySelector('[data-tour="budget-presets"]')) return;
            const steps = [
                {
                    element: '[data-tour="budget-presets"]',
                    popover: {
                        title: t('tour.stepPresets.title'),
                        description: t('tour.stepPresets.desc'),
                        side: 'top',
                        align: 'start',
                    },
                },
            ];
            const state = getState();
            applyGlobalProgress(steps, 3, state);
            setState(state);

            const driverObj = driver({
                ...baseDriverConfig(),
                showProgress: true,
                showButtons: ['next', 'close'],
                nextBtnText: translate('tour.finish'),
                doneBtnText: translate('tour.finish'),
                steps,
                onNextClick: async () => {
                    driverObj.destroy();
                    setState({ completed: true });
                    await cleanupDemoWallet();
                    window.location.href = route('wallets.index');
                },
                onCloseClick: async () => {
                    driverObj.destroy();
                    setState({ skipped: true });
                    await cleanupDemoWallet();
                    window.location.href = route('wallets.index');
                },
            });
            driverObj.drive();
        }, 500);
    }

    /**
     * Cleanup demo wallet after tour completion
     */
    async function cleanupDemoWallet() {
        try {
            await axios.delete(route('tour.cleanup'));
        } catch {}
    }

    return { initForPage };
}
