const KEY = 'spendly-theme';

function isDark() {
    return document.documentElement.classList.contains('dark');
}

function render(btn) {
    btn.querySelector('.icon-moon').classList.toggle('hidden', isDark());
    btn.querySelector('.icon-sun').classList.toggle('hidden', !isDark());
}

document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;

    btn.addEventListener('click', function () {
        const dark = !isDark();
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem(KEY, dark ? 'dark' : 'light');
        render(btn);
    });

    render(btn);
});
