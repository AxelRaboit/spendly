/**
 * Tour Steps Building
 *
 * Builds step definitions for each page of the tour.
 * Contains all UI element selection and step configuration logic.
 */

import { useI18n } from 'vue-i18n';
import { BudgetSection } from '@/enums/BudgetSection';
import { nextTick } from 'vue';

export function useTourSteps() {
    const { t } = useI18n();

    // ── Utility Helpers ──
    function isMobile() {
        return window.innerWidth < 768;
    }

    /**
     * Returns the first visible element matching selector (skips display:none elements)
     */
    function getVisibleEl(selector) {
        const els = document.querySelectorAll(selector);
        for (const el of els) {
            if (el.offsetParent !== null) return el;
        }
        return document.querySelector(selector) ?? undefined;
    }

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

    // ── Panel overlay for transaction panel ─────────────────────────────────
    const PANEL_OVERLAY_ID = 'tour-panel-overlay';

    /**
     * Show overlay inside TX panel to dim everything except the active field
     */
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
            field.style.backgroundColor = 'var(--th-surface)';
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

    // ── Progress counter ───────────────────────────────────────────────────
    /**
     * Apply global progress counter to all steps
     */
    function applyGlobalProgress(steps, page, state) {
        const page0Count = 1; // welcome step
        const page1Count = state?.stepsPage1 ?? 0;

        let offset, total;
        if (page === 1) {
            offset = page0Count;
            // Estimate: page2 ~1 step, page3 = 1 step
            total = page0Count + steps.length + 1 + 1;
            state.stepsPage1 = steps.length;
        } else if (page === 2) {
            offset = page0Count + page1Count;
            total = page0Count + page1Count + steps.length + 1;
            state.stepsPage2 = steps.length;
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

    // ── Step building for previous month (comprehensive) ────────────────────
    /**
     * Build step definitions for previous month budget tour
     */
    function buildPrevMonthSteps(pageHooks) {
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
            // 5. Income section (full)
            {
                element: getVisibleEl('[data-tour-section="income"]'),
                popover: {
                    title: t('tour.section.income.title'),
                    description: t('tour.section.income.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            // 6. Zoom on a single row
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
            // 7. Row action buttons (force visible)
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
            // 8. Actual amount — show it's clickable
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
            // 9. Detail panel — opens automatically, popover anchored on dynamic anchor at panel left edge
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
            // 10. Add a line button
            {
                element: getVisibleEl('[data-tour-add-line="income"]'),
                popover: {
                    title: t('tour.stepAddLine.title'),
                    description: t('tour.stepAddLine.desc'),
                    side: 'top',
                    align: 'start',
                },
            },
            // 11. Add a line form — _prep opens the form BEFORE driver.js activates this step
            {
                element: () => getVisibleEl('[data-tour="add-line-form"]'),
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
            // 12-15. Add form fields detail
            {
                element: () => getVisibleEl('[data-tour="add-field-label"]'),
                popover: {
                    title: t('tour.addField.label.title'),
                    description: t('tour.addField.label.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: () => getVisibleEl('[data-tour="add-field-category"]'),
                popover: {
                    title: t('tour.addField.category.title'),
                    description: t('tour.addField.category.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: () => getVisibleEl('[data-tour="add-field-amount"]'),
                popover: {
                    title: t('tour.addField.amount.title'),
                    description: t('tour.addField.amount.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: () => getVisibleEl('[data-tour="add-field-target"]'),
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
            // 16-19. Remaining sections (continue scrolling down)
            ...[BudgetSection.Savings, BudgetSection.Bills, BudgetSection.Expenses, BudgetSection.Debt]
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
            // 20. New transaction button
            {
                element: '[data-tour="new-transaction"]',
                popover: {
                    title: t('tour.stepTransaction.title'),
                    description: t('tour.stepTransaction.desc'),
                    side: 'bottom',
                    align: 'end',
                },
            },
            // 21. Open transaction panel — overview, popover anchored on dynamic anchor at panel left edge
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
            // 22-29. Transaction panel fields — each lifts above internal overlay
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

    // ── Step building for current month ────────────────────────────────────
    /**
     * Build step definitions for current month budget tour
     */
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

    // ── Panel lifting helpers ──
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

    function liftDetailPanel() {
        liftPanel('[data-tour="detail-panel"]');
    }

    function resetDetailPanel() {
        resetPanel('[data-tour="detail-panel"]');
        removePanelAnchor();
    }

    return {
        buildPrevMonthSteps,
        buildCurrentMonthSteps,
        applyGlobalProgress,
        showPanelOverlay,
        removePanelOverlay,
        liftTxPanel,
        resetPanel: () => {
            resetPanel('[data-tour="tx-panel"]');
            removePanelAnchor();
            removePanelOverlay();
        },
        resetDetailPanel,
        getVisibleEl,
        isMobile,
    };
}
