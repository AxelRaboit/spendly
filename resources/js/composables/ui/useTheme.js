import { ref, watch } from 'vue';

const STORAGE_KEY = 'spendly-theme';

function getInitial() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function apply(t) {
    document.documentElement.classList.toggle('dark', t === 'dark');
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
        theme.value = theme.value === 'dark' ? 'light' : 'dark';
    }

    function setTheme(t) {
        theme.value = t;
    }

    return { theme, toggle, setTheme };
}
