export const XLSX_COLORS = {
    income: { bg: 'FF065F46', fg: 'FF6EE7B7' },
    savings: { bg: 'FF0C4A6E', fg: 'FF7DD3FC' },
    bills: { bg: 'FF78350F', fg: 'FFFDE68A' },
    expenses: { bg: 'FF881337', fg: 'FFFECDD3' },
    debt: { bg: 'FF4C1D95', fg: 'FFE9D5FF' },
    header: { bg: 'FF1F2937', fg: 'FFF9FAFB' },
};

export function xStyle(opts) {
    const styles = {};
    if (opts.bg) styles.fill = { patternType: 'solid', fgColor: { rgb: opts.bg } };
    if (opts.color) styles.font = { ...(styles.font ?? {}), color: { rgb: opts.color } };
    if (opts.bold) styles.font = { ...(styles.font ?? {}), bold: true };
    if (opts.sz) styles.font = { ...(styles.font ?? {}), sz: opts.sz };
    if (opts.numFmt) styles.numFmt = opts.numFmt;
    styles.alignment = { horizontal: opts.align ?? 'left', vertical: 'center' };
    return styles;
}

export function diffColor(val, positiveIsGood) {
    if (val === 0) return 'FF9CA3AF';
    const good = positiveIsGood ? val > 0 : val < 0;
    return good ? 'FF059669' : 'FFE11D48';
}

export function applyStyles(ws, rowStylesArr) {
    rowStylesArr.forEach((rowStyles, ri) => {
        rowStyles.forEach((style, ci) => {
            const addr = String.fromCharCode(65 + ci) + (ri + 1);
            if (!ws[addr]) ws[addr] = { t: 'z', v: '' };
            ws[addr].s = style;
        });
    });
}
