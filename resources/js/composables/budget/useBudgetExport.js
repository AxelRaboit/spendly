import { applyStyles, diffColor, XLSX_COLORS, xStyle } from './excelStyles.js';

export function useBudgetExport(sections, totals, sectionMeta, budget, t) {
    async function exportXlsx() {
        const XLSXModule = await import('xlsx-js-style');
        const { utils, writeFile } = XLSXModule.default ?? XLSXModule;

        const wb = utils.book_new();
        const COLS = 5;

        const cLabel = t('budgets.table.label');
        const cCat = t('budgets.table.category');
        const cPlan = t('budgets.table.planned');
        const cAct = t('budgets.table.actual');
        const cDiff = t('budgets.table.diff');

        const aoa = [[cLabel, cCat, cPlan, cAct, cDiff]];
        const styles = [
            Array(COLS).fill(
                xStyle({ bg: XLSX_COLORS.header.bg, color: XLSX_COLORS.header.fg, bold: true, sz: 11, align: 'center' })
            ),
        ];

        Object.entries(sections.value).forEach(([type, items]) => {
            const c = XLSX_COLORS[type] ?? XLSX_COLORS.header;
            const tot = totals.value[type] ?? { planned: 0, actual: 0 };
            const d = tot.actual - tot.planned;
            const pig = sectionMeta.value[type]?.positiveIsGood ?? false;
            const lbl = sectionMeta.value[type]?.label ?? type;

            aoa.push([lbl, '', tot.planned, tot.actual, d]);
            const secBase = { bg: c.bg, color: c.fg, bold: true, sz: 11 };
            styles.push([
                xStyle(secBase),
                xStyle(secBase),
                xStyle({ ...secBase, numFmt: '#,##0.00', align: 'right' }),
                xStyle({ ...secBase, numFmt: '#,##0.00', align: 'right' }),
                xStyle({ ...secBase, numFmt: '#,##0.00', align: 'right', color: diffColor(d, pig) }),
            ]);

            items.forEach((item) => {
                const itemDiff = item.actual_amount - item.planned_amount;
                aoa.push([item.label, item.category?.name ?? '', item.planned_amount, item.actual_amount, itemDiff]);
                styles.push([
                    xStyle({ sz: 10 }),
                    xStyle({ sz: 10, color: 'FF6B7280' }),
                    xStyle({ sz: 10, numFmt: '#,##0.00', align: 'right', color: 'FF9CA3AF' }),
                    xStyle({ sz: 10, numFmt: '#,##0.00', align: 'right' }),
                    xStyle({ sz: 10, numFmt: '#,##0.00', align: 'right', color: diffColor(itemDiff, pig) }),
                ]);
            });

            aoa.push(Array(COLS).fill(''));
            styles.push(Array(COLS).fill({}));
        });

        const ws = utils.aoa_to_sheet(aoa);
        ws['!cols'] = [{ wch: 28 }, { wch: 20 }, { wch: 14 }, { wch: 14 }, { wch: 14 }];
        applyStyles(ws, styles);
        utils.book_append_sheet(wb, ws, 'Budget');

        const saoa = [[t('budgets.table.label'), cPlan, cAct, cDiff]];
        const sstyles = [
            Array(4).fill(
                xStyle({ bg: XLSX_COLORS.header.bg, color: XLSX_COLORS.header.fg, bold: true, sz: 11, align: 'center' })
            ),
        ];

        Object.entries(totals.value).forEach(([type, sec]) => {
            const d = sec.actual - sec.planned;
            const pig = sectionMeta.value[type]?.positiveIsGood ?? false;
            saoa.push([sectionMeta.value[type]?.label ?? type, sec.planned, sec.actual, d]);
            sstyles.push([
                xStyle({ sz: 10 }),
                xStyle({ sz: 10, numFmt: '#,##0.00', align: 'right', color: 'FF9CA3AF' }),
                xStyle({ sz: 10, numFmt: '#,##0.00', align: 'right' }),
                xStyle({ sz: 10, numFmt: '#,##0.00', align: 'right', color: diffColor(d, pig) }),
            ]);
        });

        const wsSum = utils.aoa_to_sheet(saoa);
        wsSum['!cols'] = [{ wch: 20 }, { wch: 14 }, { wch: 14 }, { wch: 14 }];
        applyStyles(wsSum, sstyles);
        utils.book_append_sheet(wb, wsSum, t('budgets.kpi.distribution'));

        writeFile(wb, `budget-${budget.value.month}.xlsx`);
    }

    return { exportXlsx };
}
