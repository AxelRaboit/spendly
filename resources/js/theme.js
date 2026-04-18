const KEY = 'spendly-theme';

function isDark() {
    return document.documentElement.classList.contains('dark');
}

function render(buttonElement) {
    buttonElement.querySelector('.icon-moon').classList.toggle('hidden', isDark());
    buttonElement.querySelector('.icon-sun').classList.toggle('hidden', !isDark());
}

document.addEventListener('DOMContentLoaded', function () {
    const themeButton = document.getElementById('theme-toggle');
    if (!themeButton) return;

    themeButton.addEventListener('click', function () {
        const dark = !isDark();
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem(KEY, dark ? 'dark' : 'light');
        render(themeButton);
    });

    render(themeButton);
});
