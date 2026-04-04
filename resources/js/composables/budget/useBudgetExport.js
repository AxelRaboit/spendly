const XLSX_COLORS = {
    income: { bg: 'FF065F46', fg: 'FF6EE7B7' },
    savings: { bg: 'FF0C4A6E', fg: 'FF7DD3FC' },
    bills: { bg: 'FF78350F', fg: 'FFFDE68A' },
    expenses: { bg: 'FF881337', fg: 'FFFECDD3' },
    debt: { bg: 'FF4C1D95', fg: 'FFE9D5FF' },
    header: { bg: 'FF1F2937', fg: 'FFF9FAFB' },
};

function xStyle(opts) {
    const s = {};
    if (opts.bg) s.fill = { patternType: 'solid', fgColor: { rgb: opts.bg } };
    if (opts.color) s.font = { ...(s.font ?? {}), color: { rgb: opts.color } };
    if (opts.bold) s.font = { ...(s.font ?? {}), bold: true };
    if (opts.sz) s.font = { ...(s.font ?? {}), sz: opts.sz };
    if (opts.numFmt) s.numFmt = opts.numFmt;
    s.alignment = { horizontal: opts.align ?? 'left', vertical: 'center' };
    return s;
}

function diffColor(val, positiveIsGood) {
    if (val === 0) return 'FF9CA3AF';
    const good = positiveIsGood ? val > 0 : val < 0;
    return good ? 'FF059669' : 'FFE11D48';
}

function applyStyles(ws, rowStylesArr) {
    rowStylesArr.forEach((rowStyles, ri) => {
        rowStyles.forEach((style, ci) => {
            const addr = String.fromCharCode(65 + ci) + (ri + 1);
            if (!ws[addr]) ws[addr] = { t: 'z', v: '' };
            ws[addr].s = style;
        });
    });
}

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

        // ── Feuille Budget ────────────────────────────────────────────────────
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

        // ── Feuille Résumé ────────────────────────────────────────────────────
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
