<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLogo from '@/components/ui/AppLogo.vue';
import AppToast from '@/components/ui/AppToast.vue';
import UpgradePrompt from '@/components/ui/UpgradePrompt.vue';
import { useFlash } from '@/composables/ui/useFlash';
import { useTheme } from '@/composables/ui/useTheme';
import { useTrialCountdown } from '@/composables/ui/useTrialCountdown';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const showMobileMenu = ref(false);
const { message: flashMessage, type: flashType, dismiss: dismissFlash } = useFlash();
const { theme, toggle: toggleTheme } = useTheme();
const page = usePage();

// ── Upgrade prompt state ──────────────────────────────────────────────────
const showUpgradePrompt = ref(false);
const planErrorKey = ref(null);
const planErrorLimit = ref(null);

function readPlanError() {
    const flash = page.props.flash ?? {};
    if (flash.plan_error) {
        planErrorKey.value = flash.plan_error;
        planErrorLimit.value = flash.plan_error_limit ?? null;
        showUpgradePrompt.value = true;
    }
}

readPlanError();

onMounted(() => {
    const off = router.on('finish', readPlanError);
    onUnmounted(off);
});

// ── Plan ──────────────────────────────────────────────────────────────────
const canExportImport = computed(() => page.props.planLimits?.canExportImport === true);
const isPro = computed(() => page.props.auth?.plan === 'pro');
const { isTrialing, label: trialLabel } = useTrialCountdown();

// ── Sidebar collapsed state (persisted) ──────────────────────────────────
const collapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true');
function toggleSidebar() {
    collapsed.value = !collapsed.value;
    localStorage.setItem('sidebar-collapsed', String(collapsed.value));
}

const navItems = [
    {
        key: 'dashboard',
        route: 'dashboard',
        match: 'dashboard',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />`,
    },
    {
        key: 'overview',
        route: 'overview.index',
        match: 'overview.*',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />`,
    },
    {
        key: 'wallets',
        route: 'wallets.index',
        match: 'wallets.*',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />`,
    },
    {
        key: 'search',
        route: 'search.index',
        match: 'search.*',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />`,
    },
    {
        key: 'goals',
        route: 'goals.index',
        match: 'goals.*',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />`,
    },
    {
        key: 'recurring',
        route: 'recurring.index',
        match: 'recurring.*',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />`,
    },
    {
        key: 'categories',
        route: 'categories.index',
        match: 'categories.*',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />`,
    },
    {
        key: 'statistics',
        route: 'statistics.index',
        match: 'statistics.*',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />`,
    },
    {
        key: 'import',
        route: 'import.index',
        match: 'import.*',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />`,
        pro: true,
    },
];
</script>

<template>
    <div class="min-h-screen bg-bg transition-colors duration-200">
        <aside
            class="hidden lg:flex flex-col fixed inset-y-0 left-0 bg-surface border-r border-base z-30 transition-all duration-200"
            :class="collapsed ? 'w-16' : 'w-60'"
        >
            <div
                class="flex items-center h-16 border-b border-base shrink-0 transition-all duration-200"
                :class="collapsed ? 'justify-center px-0' : 'justify-between px-4'"
            >
                <Link v-if="!collapsed" :href="route('dashboard')" class="flex items-center gap-2.5 min-w-0">
                    <AppLogo :size="32" />
                    <span class="text-primary font-bold text-lg tracking-tight truncate">Spendly</span>
                </Link>
                <Link v-else :href="route('dashboard')">
                    <AppLogo :size="32" />
                </Link>

                <button
                    class="p-1.5 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors shrink-0"
                    :class="collapsed ? 'hidden' : 'ml-2'"
                    v-on:click="toggleSidebar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 py-4 space-y-0.5" :class="collapsed ? 'px-2' : 'px-3 overflow-y-auto'">
                <template v-for="item in navItems" :key="item.key">
                    <span
                        v-if="item.pro && !canExportImport"
                        class="flex items-center rounded-lg text-sm font-medium text-secondary group relative cursor-not-allowed opacity-50"
                        :class="collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5'"
                    >
                        <svg
                            class="w-5 h-5 shrink-0 text-muted"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            v-html="item.icon"
                        />
                        <span v-if="!collapsed" class="truncate flex-1">{{ t('nav.' + item.key) }}</span>
                        <span v-if="!collapsed" class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full leading-none shrink-0">Pro</span>
                        <span
                            v-if="collapsed"
                            class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg flex items-center gap-1.5"
                        >
                            {{ t('nav.' + item.key) }}
                            <span class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full leading-none">Pro</span>
                        </span>
                    </span>

                    <Link
                        v-else
                        :href="route(item.route)"
                        class="flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                        :class="[
                            collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5',
                            route().current(item.match)
                                ? 'bg-indigo-600/15 text-indigo-400'
                                : 'text-secondary hover:text-primary hover:bg-surface-2',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 shrink-0"
                            :class="route().current(item.match) ? 'text-indigo-400' : 'text-muted'"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            v-html="item.icon"
                        />
                        <span v-if="!collapsed" class="truncate">{{ t('nav.' + item.key) }}</span>

                        <span
                            v-if="collapsed"
                            class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                        >
                            {{ t('nav.' + item.key) }}
                        </span>
                    </Link>
                </template>
            </nav>

            <div class="shrink-0 border-t border-base py-3 space-y-0.5" :class="collapsed ? 'px-2' : 'px-3'">
                <button
                    v-if="collapsed"
                    class="flex items-center justify-center w-full py-2.5 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors"
                    v-on:click="toggleSidebar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>

                <button
                    class="flex items-center rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors w-full group relative"
                    :class="collapsed ? 'justify-center py-2.5' : 'gap-3 px-3 py-2.5'"
                    v-on:click="toggleTheme"
                >
                    <svg
                        v-if="theme === 'dark'"
                        class="w-5 h-5 text-muted shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M18.364 18.364l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5 text-muted shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <span v-if="!collapsed">{{ theme === 'dark' ? t('nav.lightMode') : t('nav.darkMode') }}</span>
                    <span
                        v-if="collapsed"
                        class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                    >
                        {{ theme === 'dark' ? t('nav.lightMode') : t('nav.darkMode') }}
                    </span>
                </button>

                <Link
                    :href="route('plan.index')"
                    class="flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                    :class="[
                        collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5',
                        route().current('plan.*')
                            ? 'bg-indigo-600/15 text-indigo-400'
                            : 'text-secondary hover:text-primary hover:bg-surface-2',
                    ]"
                >
                    <svg class="w-5 h-5 shrink-0 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <span v-if="!collapsed" class="truncate flex-1">{{ t('nav.plan') }}</span>
                    <span v-if="!collapsed && !isPro" class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full leading-none shrink-0">Pro</span>
                    <span
                        v-if="collapsed"
                        class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                    >
                        {{ t('nav.plan') }}
                    </span>
                </Link>

                <Link
                    :href="route('profile.edit')"
                    class="flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                    :class="[
                        collapsed ? 'justify-center py-2.5' : 'gap-3 px-3 py-2.5',
                        route().current('profile.*') ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2',
                    ]"
                >
                    <svg class="w-5 h-5 shrink-0 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span v-if="!collapsed" class="truncate">{{ $page.props.auth.user.name }}</span>
                    <span
                        v-if="collapsed"
                        class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                    >
                        {{ $page.props.auth.user.name }}
                    </span>
                </Link>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex items-center rounded-lg text-sm font-medium text-secondary hover:text-rose-400 hover:bg-rose-500/10 transition-colors w-full group relative"
                    :class="collapsed ? 'justify-center py-2.5' : 'gap-3 px-3 py-2.5'"
                >
                    <svg class="w-5 h-5 shrink-0 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span v-if="!collapsed">{{ t('nav.logout') }}</span>
                    <span
                        v-if="collapsed"
                        class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                    >
                        {{ t('nav.logout') }}
                    </span>
                </Link>
            </div>
        </aside>

        <div class="lg:hidden fixed top-0 inset-x-0 h-14 bg-surface border-b border-base z-30 flex items-center justify-between px-4">
            <Link :href="route('dashboard')" class="flex items-center gap-2">
                <AppLogo :size="28" />
                <span class="text-primary font-bold text-base tracking-tight">Spendly</span>
            </Link>
            <button
                class="p-2 rounded-lg text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                v-on:click="showMobileMenu = true"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showMobileMenu" class="lg:hidden fixed inset-0 z-50 flex">
                <div class="absolute inset-0 bg-black/60" v-on:click="showMobileMenu = false" />
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <div v-if="showMobileMenu" class="relative w-72 max-w-[85vw] bg-surface h-full flex flex-col shadow-2xl">
                        <div class="flex items-center justify-between px-5 h-14 border-b border-base shrink-0">
                            <div class="flex items-center gap-2">
                                <AppLogo :size="28" />
                                <span class="text-primary font-bold text-base">Spendly</span>
                            </div>
                            <button class="p-1.5 text-muted hover:text-primary" v-on:click="showMobileMenu = false">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                            <template v-for="item in navItems" :key="item.key">
                                <span
                                    v-if="item.pro && !canExportImport"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-secondary cursor-not-allowed opacity-50"
                                >
                                    <svg
                                        class="w-5 h-5 shrink-0 text-muted"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        v-html="item.icon"
                                    />
                                    <span class="flex-1">{{ t('nav.' + item.key) }}</span>
                                    <span class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full leading-none">Pro</span>
                                </span>

                                <Link
                                    v-else
                                    :href="route(item.route)"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                                    :class="route().current(item.match)
                                        ? 'bg-indigo-600/15 text-indigo-400'
                                        : 'text-secondary hover:text-primary hover:bg-surface-2'"
                                    v-on:click="showMobileMenu = false"
                                >
                                    <svg
                                        class="w-5 h-5 shrink-0"
                                        :class="route().current(item.match) ? 'text-indigo-400' : 'text-muted'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        v-html="item.icon"
                                    />
                                    {{ t('nav.' + item.key) }}
                                </Link>
                            </template>
                        </nav>

                        <div class="shrink-0 border-t border-base px-3 py-3 space-y-1">
                            <div class="px-3 py-2 mb-1">
                                <p class="text-sm font-medium text-primary">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-muted truncate">{{ $page.props.auth.user.email }}</p>
                            </div>
                            <button
                                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                                v-on:click="toggleTheme"
                            >
                                <svg
                                    v-if="theme === 'dark'"
                                    class="w-5 h-5 text-muted shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M18.364 18.364l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5 text-muted shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                                {{ theme === 'dark' ? t('nav.lightMode') : t('nav.darkMode') }}
                            </button>
                            <Link
                                :href="route('plan.index')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                                :class="route().current('plan.*') ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                                v-on:click="showMobileMenu = false"
                            >
                                <svg class="w-5 h-5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                <span>{{ t('nav.plan') }}</span>
                                <span v-if="!isPro" class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full leading-none ml-auto">Pro</span>
                            </Link>
                            <Link
                                :href="route('profile.edit')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                                v-on:click="showMobileMenu = false"
                            >
                                <svg class="w-5 h-5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ t('nav.profile') }}
                            </Link>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                            >
                                <svg class="w-5 h-5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                {{ t('nav.logout') }}
                            </Link>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <div
            class="transition-all duration-200 pt-14 lg:pt-0"
            :class="collapsed ? 'lg:pl-16' : 'lg:pl-60'"
        >
            <header v-if="$slots.header" class="bg-surface border-b border-base h-16 flex items-center lg:sticky lg:top-0 lg:z-20">
                <div class="px-4 sm:px-6 lg:px-8 w-full">
                    <slot name="header" />
                </div>
            </header>

            <div v-if="isTrialing" class="bg-indigo-600/10 border-b border-indigo-500/30 px-4 py-2 flex items-center justify-center gap-3 text-xs text-indigo-300">
                <span>{{ trialLabel }}</span>
                <Link :href="route('plan.index')" class="underline hover:text-indigo-200 transition-colors font-medium">
                    {{ t('trial.bannerLink') }}
                </Link>
            </div>

            <main>
                <div class="px-4 sm:px-6 lg:px-8 py-8">
                    <slot />
                </div>
            </main>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <AppToast
                v-if="flashMessage"
                :message="flashMessage"
                :type="flashType"
                :duration="4000"
                v-on:dismiss="dismissFlash"
            />
        </Transition>

        <UpgradePrompt
            :show="showUpgradePrompt"
            :limit-key="planErrorKey"
            :limit-value="planErrorLimit"
            v-on:close="showUpgradePrompt = false"
        />
    </div>
</template>
