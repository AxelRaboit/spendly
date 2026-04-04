import { nextTick } from 'vue';

const FLASH_COLORS = {
    indigo: 'rgba(99, 102, 241, 0.25)',
    emerald: 'rgba(52, 211, 153, 0.25)',
    rose: 'rgba(251, 113, 133, 0.25)',
};

export function useRowFlash() {
    async function flash(id, color = 'indigo') {
        await nextTick();
        const row = document.querySelector(`tr[data-row-id="${id}"]`);
        if (!row) return;
        row.style.transition = 'none';
        row.style.backgroundColor = FLASH_COLORS[color] ?? FLASH_COLORS.indigo;
        row.offsetHeight; // force reflow
        setTimeout(() => {
            row.style.transition = 'background-color 2s ease-out';
            row.style.backgroundColor = '';
        }, 800);
    }

    return { flash };
}
