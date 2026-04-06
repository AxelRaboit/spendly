import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { useI18n } from 'vue-i18n';
import { nextTick, ref } from 'vue';
import axios from 'axios';

const STORAGE_KEY = 'spendly_tour';

// Module-level reactive ref so all components react when the tour ends
const tourActive = ref(false);

function _readActive() {
    try {
        const s = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null') ?? null;
        return s !== null && !s.completed && !s.skipped;
    } catch {
        return false;
    }
}

// Initialise on module load
tourActive.value = _readActive();

export function useTour() {
    const { t } = useI18n();

    function getState() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null') ?? null;
        } catch {
            return null;
        }
    }

    function setState(patch) {
        const current = getState() ?? {};
        const next = { ...current, ...patch };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
        tourActive.value = next !== null && !next.completed && !next.skipped;
    }

    function isActive() {
        return tourActive.value;
    }

    function isCompleted() {
        const s = getState();
        return s?.completed === true || s?.skipped === true;
    }

    function startTour() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ page: 0, completed: false, skipped: false }));
        tourActive.value = true;
        window.location.href = route('wallets.index');
    }

    function resetTour() {
        localStorage.removeItem(STORAGE_KEY);
        tourActive.value = false;
    }

    const pageHooks = {};

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

    // ── Shared driver config base ──
    function baseDriverConfig() {
        return {
            animate: true,
            stagePadding: 12,
            stageRadius: 12,
            disableActiveInteraction: true,
            overlayClickBehavior: () => {},
            scrollIntoViewOptions: { behavior: 'auto', block: 'center', inline: 'nearest' },
            onHighlightStarted: (element) => {
                if (element) {
                    element.scrollIntoView({ behavior: 'auto', block: 'center', inline: 'nearest' });
                }
            },
        };
    }

    // ── Page 0: wallets.index — intro step, then seed + navigate ──
    function runPage0() {
        setTimeout(() => {
            const el = document.querySelector('[data-tour="create-wallet"]');
            const step = el
                ? {
                      element: '[data-tour="create-wallet"]',
                      popover: {
                          title: t('tour.stepWelcome.title'),
                          description: t('tour.stepWelcome.desc'),
                          side: 'bottom',
                          align: 'end',
                      },
                  }
                : { popover: { title: t('tour.stepWelcome.title'), description: t('tour.stepWelcome.desc') } };

            const driverObj = driver({
                ...baseDriverConfig(),
                showProgress: false,
                showButtons: ['next', 'close'],
                nextBtnText: t('tour.next'),
                doneBtnText: t('tour.next'),
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

    async function seedAndNavigate() {
        try {
            const { data } = await axios.post(route('tour.seed'));
            const walletId = data.wallet_id;
            const now = new Date();
            const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const prevMonth = `${prev.getFullYear()}-${String(prev.getMonth() + 1).padStart(2, '0')}`;
            setState({ page: 1, walletId });
            window.location.href = route('wallets.budget.show', walletId) + `?month=${prevMonth}`;
        } catch (e) {
            console.error('[Tour] seed failed:', e);
            setState({ skipped: true });
        }
    }

    // ── Global progress helpers ──
    function applyGlobalProgress(steps, page) {
        const state = getState();
        const page0Count = 1; // welcome step
        const page1Count = state?.stepsPage1 ?? 0;

        let offset, total;
        if (page === 1) {
            offset = page0Count;
            // Estimate: page2 ~1 step, page3 = 1 step
            total = page0Count + steps.length + 1 + 1;
            setState({ stepsPage1: steps.length });
        } else if (page === 2) {
            offset = page0Count + page1Count;
            total = page0Count + page1Count + steps.length + 1;
            setState({ stepsPage2: steps.length });
        } else if (page === 3) {
            const page2Count = state?.stepsPage2 ?? 0;
            offset = page0Count + page1Count + page2Count;
            total = offset + steps.length;
        }

        const ofText = t('tour.progressOf');
        steps.forEach((step, i) => {
            step.popover = step.popover || {};
            step.popover.progressText = `${offset + i + 1} ${ofText} ${total}`;
        });
    }

    // ── Page 1 & 2: budgets.show ──
    function runBudgetPage(page) {
        setTimeout(() => {
            const steps = page === 1 ? buildPrevMonthSteps() : buildCurrentMonthSteps();
            if (!steps.length) {
                advanceBudgetPage(page);
                return;
            }

            applyGlobalProgress(steps, page);

            const driverObj = driver({
                ...baseDriverConfig(),
                showProgress: true,
                showButtons: ['next', 'previous', 'close'],
                nextBtnText: t('tour.next'),
                prevBtnText: t('tour.prev'),
                doneBtnText: t('tour.next'),
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
                            await new Promise((r) => setTimeout(r, 300));
                        }
                        driverObj.moveNext();
                    } else {
                        resetTxPanelZ();
                        resetDetailPanel();
                        if (pageHooks.closeTxPanel) pageHooks.closeTxPanel();
                        if (pageHooks.closeDetailPanel) pageHooks.closeDetailPanel();
                        if (pageHooks.cancelAdding) pageHooks.cancelAdding();
                        driverObj.destroy();
                        advanceBudgetPage(page);
                    }
                },
                onCloseClick: async () => {
                    resetTxPanelZ();
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

    // ── Page 3: profile.edit ──
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
            applyGlobalProgress(steps, 3);
            const driverObj = driver({
                ...baseDriverConfig(),
                showProgress: true,
                showButtons: ['next', 'close'],
                nextBtnText: t('tour.finish'),
                doneBtnText: t('tour.finish'),
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

    async function cleanupDemoWallet() {
        const walletId = getState()?.walletId;
        if (walletId) {
            try {
                await axios.delete(route('tour.cleanup'));
            } catch {}
        }
    }

    // ── Mobile helpers ──
    function isMobile() {
        return window.innerWidth < 768;
    }

    // Returns the first visible element matching selector (skips display:none elements)
    function getVisibleEl(selector) {
        const els = document.querySelectorAll(selector);
        for (const el of els) {
            if (el.offsetParent !== null) return el;
        }
        return document.querySelector(selector) ?? undefined;
    }

    // ── Helpers ──
    function showActions(itemId) {
        const el = document.querySelector(`[data-tour-actions="${itemId}"]`);
        if (el) {
            el.classList.remove('opacity-0');
            el.classList.add('opacity-100');
        }
    }
    function hideActions(itemId) {
        const el = document.querySelector(`[data-tour-actions="${itemId}"]`);
        if (el) {
            el.classList.remove('opacity-100');
            el.classList.add('opacity-0');
        }
    }
    function liftPanel(selector) {
        const wrapper = document.querySelector(selector)?.closest('.fixed');
        if (wrapper) {
            wrapper.style.zIndex = '10001';
            // Hide the backdrop — driver.js overlay handles all dimming uniformly
            const backdrop = wrapper.querySelector(':scope > .fixed');
            if (backdrop) backdrop.style.display = 'none';
        }
    }
    function resetPanel(selector) {
        const wrapper = document.querySelector(selector)?.closest('.fixed');
        if (wrapper) {
            wrapper.style.zIndex = '';
            const backdrop = wrapper.querySelector(':scope > .fixed');
            if (backdrop) backdrop.style.display = '';
        }
    }

    function liftTxPanel() {
        liftPanel('[data-tour="tx-panel"]');
    }
    function resetTxPanelZ() {
        removePanelOverlay();
        resetPanel('[data-tour="tx-panel"]');
        removePanelAnchor();
    }
    function liftDetailPanel() {
        liftPanel('[data-tour="detail-panel"]');
    }
    function resetDetailPanel() {
        resetPanel('[data-tour="detail-panel"]');
        removePanelAnchor();
    }

    // ── Dynamic anchor at the left edge of a panel ───────────────────────────
    function createPanelAnchor(panelSelector) {
        removePanelAnchor();
        const panel = document.querySelector(panelSelector);
        if (!panel) return null;
        const rect = panel.getBoundingClientRect();
        if (!rect.width) return null;
        const el = document.createElement('div');
        el.id = 'tour-panel-anchor';
        Object.assign(el.style, {
            position: 'fixed',
            top: `${Math.round(rect.top + rect.height * 0.3)}px`,
            left: `${rect.left}px`,
            width: '4px',
            height: '60px',
            pointerEvents: 'none',
            zIndex: '10002',
        });
        document.body.appendChild(el);
        return el;
    }
    function removePanelAnchor() {
        document.getElementById('tour-panel-anchor')?.remove();
    }

    // Internal overlay inside the TX panel to dim everything except the active field
    const PANEL_OVERLAY_ID = 'tour-panel-overlay';
    function showPanelOverlay(activeSelector) {
        const panel = document.querySelector('[data-tour="tx-panel"]');
        if (!panel) return;
        // Ensure panel has relative positioning for the overlay
        panel.style.position = 'relative';
        // Create or reuse overlay
        let overlay = document.getElementById(PANEL_OVERLAY_ID);
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = PANEL_OVERLAY_ID;
            Object.assign(overlay.style, {
                position: 'absolute',
                inset: '0',
                background: 'rgba(0,0,0,0.5)',
                zIndex: '1',
                pointerEvents: 'none',
                transition: 'opacity 150ms',
            });
            panel.appendChild(overlay);
        }
        // Reset previous lifted field
        panel.querySelectorAll('.tour-field-lifted').forEach((el) => {
            el.classList.remove('tour-field-lifted');
            el.style.position = '';
            el.style.zIndex = '';
            el.style.backgroundColor = '';
            el.style.borderRadius = '';
            el.style.padding = '';
            el.style.margin = '';
        });
        // Lift the active field above the overlay
        const field = panel.querySelector(activeSelector);
        if (field) {
            field.classList.add('tour-field-lifted');
            field.style.position = 'relative';
            field.style.zIndex = '2';
            field.style.backgroundColor = 'rgb(var(--color-surface))';
            field.style.borderRadius = '0.5rem';
            field.style.padding = '0.75rem';
            field.style.margin = '-0.75rem';
        }
    }
    function removePanelOverlay() {
        const overlay = document.getElementById(PANEL_OVERLAY_ID);
        if (overlay) overlay.remove();
        document.querySelectorAll('.tour-field-lifted').forEach((el) => {
            el.classList.remove('tour-field-lifted');
            el.style.position = '';
            el.style.zIndex = '';
            el.style.backgroundColor = '';
            el.style.borderRadius = '';
            el.style.padding = '';
            el.style.margin = '';
        });
    }

    // ── Step definitions: previous month (comprehensive) ──
    function buildPrevMonthSteps() {
        const firstRow = getVisibleEl('[data-tour-item]');
        const firstItemId = firstRow?.getAttribute('data-tour-item');
        const panelSide = isMobile() ? 'bottom' : 'left';

        return [
            // 1. Month navigation (top, natural starting point)
            {
                element: '[data-tour="month-nav"]',
                popover: {
                    title: t('tour.stepMonthNav.title'),
                    description: t('tour.stepMonthNav.desc'),
                    side: 'bottom',
                    align: 'center',
                },
            },
            // 2. KPIs (expand immediately)
            {
                element: '[data-tour="kpi-section"]',
                popover: {
                    title: t('tour.stepPrevKpi.title'),
                    description: t('tour.stepPrevKpi.desc'),
                    side: 'bottom',
                    align: 'start',
                    onPopoverRender: (_, { driver: d }) => {
                        if (pageHooks.expandKpi) {
                            pageHooks.expandKpi();
                            nextTick(() => d.refresh());
                        }
                    },
                },
            },
            // 3. Notes
            {
                element: '[data-tour="budget-notes"]',
                popover: {
                    title: t('tour.stepNotes.title'),
                    description: t('tour.stepNotes.desc'),
                    side: 'top',
                    align: 'start',
                },
            },
            // 4. Budget table overview
            {
                element: '[data-tour="budget-table"]',
                popover: {
                    title: t('tour.stepTable.title'),
                    description: t('tour.stepTable.desc'),
                    side: 'top',
                    align: 'start',
                },
            },
            // 4. Income section (full)
            {
                element: getVisibleEl('[data-tour-section="income"]'),
                popover: {
                    title: t('tour.section.income.title'),
                    description: t('tour.section.income.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            // 5. Zoom on a single row
            ...(firstItemId
                ? [
                      {
                          element: firstRow,
                          popover: {
                              title: t('tour.stepRow.title'),
                              description: t('tour.stepRow.desc'),
                              side: 'bottom',
                              align: 'start',
                          },
                      },
                  ]
                : []),
            // 6. Row action buttons (force visible)
            ...(firstItemId
                ? [
                      {
                          element: getVisibleEl(`[data-tour-actions="${firstItemId}"]`),
                          popover: {
                              title: t('tour.stepRowActions.title'),
                              description: t('tour.stepRowActions.desc'),
                              side: isMobile() ? 'bottom' : 'left',
                              align: 'center',
                              onPopoverRender: () => showActions(firstItemId),
                              onDeselected: () => hideActions(firstItemId),
                          },
                      },
                  ]
                : []),
            // 7. Actual amount — show it's clickable
            ...(() => {
                const actualCell = getVisibleEl('[data-tour-actual]');
                return actualCell
                    ? [
                          {
                              element: actualCell,
                              popover: {
                                  title: t('tour.stepActualAmount.title'),
                                  description: t('tour.stepActualAmount.desc'),
                                  side: isMobile() ? 'bottom' : 'left',
                                  align: 'center',
                              },
                          },
                      ]
                    : [];
            })(),
            // 8. Detail panel — opens automatically, popover anchored on dynamic anchor at panel left edge
            {
                element: '#tour-panel-anchor',
                _prep: () => {
                    if (pageHooks.openDetailPanel) pageHooks.openDetailPanel();
                    setTimeout(() => createPanelAnchor('[data-tour="detail-panel"]'), 100);
                },
                popover: {
                    title: t('tour.stepDetailPanel.title'),
                    description: t('tour.stepDetailPanel.desc'),
                    side: panelSide,
                    align: 'center',
                    onPopoverRender: () => {
                        liftDetailPanel();
                    },
                },
                _cleanup: () => {
                    resetDetailPanel();
                    if (pageHooks.closeDetailPanel) pageHooks.closeDetailPanel();
                },
            },
            // 8. Add a line button
            {
                element: getVisibleEl('[data-tour-add-line="income"]'),
                popover: {
                    title: t('tour.stepAddLine.title'),
                    description: t('tour.stepAddLine.desc'),
                    side: 'top',
                    align: 'start',
                },
            },
            // 8. Add a line form — _prep opens the form BEFORE driver.js activates this step
            {
                element: '[data-tour="add-line-form"]',
                _prep: () => {
                    if (pageHooks.openAddLine) pageHooks.openAddLine();
                },
                popover: {
                    title: t('tour.stepAddLineForm.title'),
                    description: t('tour.stepAddLineForm.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            // 9-12. Add form fields detail
            {
                element: '[data-tour="add-field-label"]',
                popover: {
                    title: t('tour.addField.label.title'),
                    description: t('tour.addField.label.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="add-field-category"]',
                popover: {
                    title: t('tour.addField.category.title'),
                    description: t('tour.addField.category.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="add-field-amount"]',
                popover: {
                    title: t('tour.addField.amount.title'),
                    description: t('tour.addField.amount.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="add-field-target"]',
                popover: {
                    title: t('tour.addField.target.title'),
                    description: t('tour.addField.target.desc'),
                    side: 'top',
                    align: 'start',
                },
                _cleanup: () => {
                    if (pageHooks.cancelAdding) pageHooks.cancelAdding();
                },
            },
            // 8-11. Remaining sections (continue scrolling down)
            ...['savings', 'bills', 'expenses', 'debt']
                .filter((s) => !!getVisibleEl(`[data-tour-section="${s}"]`))
                .map((s) => ({
                    element: getVisibleEl(`[data-tour-section="${s}"]`),
                    popover: {
                        title: t(`tour.section.${s}.title`),
                        description: t(`tour.section.${s}.desc`),
                        side: 'bottom',
                        align: 'start',
                    },
                })),
            // 12. New transaction button
            {
                element: '[data-tour="new-transaction"]',
                popover: {
                    title: t('tour.stepTransaction.title'),
                    description: t('tour.stepTransaction.desc'),
                    side: 'bottom',
                    align: 'end',
                },
            },
            // 13. Open transaction panel — overview, popover anchored on dynamic anchor at panel left edge
            {
                element: '#tour-panel-anchor',
                _prep: () => {
                    removePanelOverlay();
                    if (pageHooks.openTxPanel) pageHooks.openTxPanel();
                    setTimeout(() => createPanelAnchor('[data-tour="tx-panel"]'), 100);
                },
                popover: {
                    title: t('tour.stepTxPanel.title'),
                    description: t('tour.stepTxPanel.desc'),
                    side: panelSide,
                    align: 'center',
                    onPopoverRender: () => {
                        liftTxPanel();
                    },
                },
            },
            // 14-20. Transaction panel fields — each lifts above internal overlay
            ...[
                'tx-section',
                'tx-type',
                'tx-amount',
                'tx-category',
                'tx-date',
                'tx-description',
                'tx-tags',
                'tx-attachment',
            ].map((key) => ({
                element: `[data-tour="${key}"]`,
                popover: {
                    title: t(`tour.txField.${key.replace('tx-', '')}.title`),
                    description: t(`tour.txField.${key.replace('tx-', '')}.desc`),
                    side: panelSide,
                    align: 'start',
                    onPopoverRender: () => showPanelOverlay(`[data-tour="${key}"]`),
                },
            })),
        ];
    }

    // ── Step definitions: current month ──
    function buildCurrentMonthSteps() {
        const carriedOverCell = getVisibleEl('[data-tour-carried-over]');

        return [
            ...(carriedOverCell
                ? [
                      {
                          element: carriedOverCell,
                          popover: {
                              title: t('tour.stepCarryOver.title'),
                              description: t('tour.stepCarryOver.desc'),
                              side: isMobile() ? 'bottom' : 'left',
                              align: 'center',
                          },
                      },
                  ]
                : []),
        ];
    }

    return { startTour, resetTour, initForPage, isActive, isCompleted, getState, setState, tourActive };
}
