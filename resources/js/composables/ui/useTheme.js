import { ref, watch } from 'vue';
import { Theme } from '@/enums/Theme';

const STORAGE_KEY = 'spendly-theme';

function getInitial() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === Theme.Dark || stored === Theme.Light) return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? Theme.Dark : Theme.Light;
}

function apply(t) {
    document.documentElement.classList.add('theme-switching');
    document.documentElement.classList.toggle('dark', t === Theme.Dark);
    window.setTimeout(() => document.documentElement.classList.remove('theme-switching'), 300);
}

// Singleton state — shared across all composable calls
const theme = ref(getInitial());
apply(theme.value);

export function useTheme() {
    watch(theme, (t) => {
        apply(t);
        localStorage.setItem(STORAGE_KEY, t);
    });

    function toggle() {
        theme.value = theme.value === Theme.Dark ? Theme.Light : Theme.Dark;
    }

    function setTheme(t) {
        theme.value = t;
    }

    return { theme, toggle, setTheme };
}
