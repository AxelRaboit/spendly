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
    const { t: translate } = useI18n();

    // ── Utility Helpers ──
    function isMobile() {
        return window.innerWidth < 768;
    }

    /**
     * Returns the first visible element matching selector (skips display:none elements)
     */
    function getVisibleEl(selector) {
        const els = document.querySelectorAll(selector);
        for (const element of els) {
            if (element.offsetParent !== null) return element;
        }
        return document.querySelector(selector) ?? undefined;
    }

    function showActions(itemId) {
        const actionElement = document.querySelector(`[data-tour-actions="${itemId}"]`);
        if (actionElement) {
            actionElement.classList.remove('opacity-0');
            actionElement.classList.add('opacity-100');
        }
    }

    function hideActions(itemId) {
        const actionElement = document.querySelector(`[data-tour-actions="${itemId}"]`);
        if (actionElement) {
            actionElement.classList.remove('opacity-100');
            actionElement.classList.add('opacity-0');
        }
    }

    // ── Dynamic anchor at the left edge of a panel ───────────────────────────
    function createPanelAnchor(panelSelector) {
        removePanelAnchor();
        const panel = document.querySelector(panelSelector);
        if (!panel) return null;
        const rect = panel.getBoundingClientRect();
        if (!rect.width) return null;
        const anchorElement = document.createElement('div');
        anchorElement.id = 'tour-panel-anchor';
        Object.assign(anchorElement.style, {
            position: 'fixed',
            top: `${Math.round(rect.top + rect.height * 0.3)}px`,
            left: `${rect.left}px`,
            width: '4px',
            height: '60px',
            pointerEvents: 'none',
            zIndex: '10002',
        });
        document.body.appendChild(anchorElement);
        return anchorElement;
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
        panel.querySelectorAll('.tour-field-lifted').forEach((tourField) => {
            tourField.classList.remove('tour-field-lifted');
            tourField.style.position = '';
            tourField.style.zIndex = '';
            tourField.style.backgroundColor = '';
            tourField.style.borderRadius = '';
            tourField.style.padding = '';
            tourField.style.margin = '';
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
        document.querySelectorAll('.tour-field-lifted').forEach((tourField) => {
            tourField.classList.remove('tour-field-lifted');
            tourField.style.position = '';
            tourField.style.zIndex = '';
            tourField.style.backgroundColor = '';
            tourField.style.borderRadius = '';
            tourField.style.padding = '';
            tourField.style.margin = '';
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

        const ofText = translate('tour.progressOf');
        steps.forEach((step, index) => {
            step.popover = step.popover || {};
            step.popover.progressText = `${offset + index + 1} ${ofText} ${total}`;
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
                    title: translate('tour.stepMonthNav.title'),
                    description: translate('tour.stepMonthNav.desc'),
                    side: 'bottom',
                    align: 'center',
                },
            },
            // 2. KPIs (expand immediately)
            {
                element: '[data-tour="kpi-section"]',
                popover: {
                    title: translate('tour.stepPrevKpi.title'),
                    description: translate('tour.stepPrevKpi.desc'),
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
                    title: translate('tour.stepNotes.title'),
                    description: translate('tour.stepNotes.desc'),
                    side: 'top',
                    align: 'start',
                },
            },
            // 4. Budget table overview
            {
                element: '[data-tour="budget-table"]',
                popover: {
                    title: translate('tour.stepTable.title'),
                    description: translate('tour.stepTable.desc'),
                    side: 'top',
                    align: 'start',
                },
            },
            // 5. Income section (full)
            {
                element: getVisibleEl('[data-tour-section="income"]'),
                popover: {
                    title: translate('tour.section.income.title'),
                    description: translate('tour.section.income.desc'),
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
                              title: translate('tour.stepRow.title'),
                              description: translate('tour.stepRow.desc'),
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
                              title: translate('tour.stepRowActions.title'),
                              description: translate('tour.stepRowActions.desc'),
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
                                  title: translate('tour.stepActualAmount.title'),
                                  description: translate('tour.stepActualAmount.desc'),
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
                    title: translate('tour.stepDetailPanel.title'),
                    description: translate('tour.stepDetailPanel.desc'),
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
                    title: translate('tour.stepAddLine.title'),
                    description: translate('tour.stepAddLine.desc'),
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
                    title: translate('tour.stepAddLineForm.title'),
                    description: translate('tour.stepAddLineForm.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            // 12-15. Add form fields detail
            {
                element: () => getVisibleEl('[data-tour="add-field-label"]'),
                popover: {
                    title: translate('tour.addField.label.title'),
                    description: translate('tour.addField.label.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: () => getVisibleEl('[data-tour="add-field-category"]'),
                popover: {
                    title: translate('tour.addField.category.title'),
                    description: translate('tour.addField.category.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: () => getVisibleEl('[data-tour="add-field-amount"]'),
                popover: {
                    title: translate('tour.addField.amount.title'),
                    description: translate('tour.addField.amount.desc'),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: () => getVisibleEl('[data-tour="add-field-target"]'),
                popover: {
                    title: translate('tour.addField.target.title'),
                    description: translate('tour.addField.target.desc'),
                    side: 'top',
                    align: 'start',
                },
                _cleanup: () => {
                    if (pageHooks.cancelAdding) pageHooks.cancelAdding();
                },
            },
            // 16-19. Remaining sections (continue scrolling down)
            ...[BudgetSection.Savings, BudgetSection.Bills, BudgetSection.Expenses, BudgetSection.Debt]
                .filter((section) => !!getVisibleEl(`[data-tour-section="${section}"]`))
                .map((section) => ({
                    element: getVisibleEl(`[data-tour-section="${section}"]`),
                    popover: {
                        title: translate(`tour.section.${section}.title`),
                        description: translate(`tour.section.${section}.desc`),
                        side: 'bottom',
                        align: 'start',
                    },
                })),
            // 20. New transaction button
            {
                element: '[data-tour="new-transaction"]',
                popover: {
                    title: translate('tour.stepTransaction.title'),
                    description: translate('tour.stepTransaction.desc'),
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
                    title: translate('tour.stepTxPanel.title'),
                    description: translate('tour.stepTxPanel.desc'),
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
                              title: translate('tour.stepCarryOver.title'),
                              description: translate('tour.stepCarryOver.desc'),
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
