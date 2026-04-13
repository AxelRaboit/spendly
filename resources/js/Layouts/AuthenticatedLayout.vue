<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLogo from '@/components/ui/AppLogo.vue';
import TourSelectionModal from '@/components/ui/TourSelectionModal.vue';
import UpgradePrompt from '@/components/ui/UpgradePrompt.vue';
import { useTheme } from '@/composables/ui/useTheme';
import { Theme } from '@/enums/Theme';
import { useTrialCountdown } from '@/composables/ui/useTrialCountdown';
import { usePlanLimits } from '@/composables/ui/usePlanLimits';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    LayoutDashboard,
    BarChart3,
    Wallet,
    ClipboardList,
    BadgeCheck,
    NotebookPen,
    Repeat,
    Tag,
    Wand2,
    TrendingUp,
    Upload,
    Shield,
    UserCheck,
    ChevronsLeft,
    ChevronsRight,
    Sun,
    Moon,
    Sparkles,
    User,
    LogOut,
    Menu,
    X,
    Map,
} from 'lucide-vue-next';
import { useTour } from '@/composables/ui/useTour';
import '@css/tour/driver.css';

const { t } = useI18n();
const showMobileMenu = ref(false);
const showTourModal = ref(false);
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
const { isPro } = usePlanLimits();
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
        icon: LayoutDashboard,
    },
    {
        key: 'overview',
        route: 'overview.index',
        match: 'overview.*',
        icon: BarChart3,
    },
    {
        key: 'wallets',
        route: 'wallets.index',
        match: 'wallets.*',
        icon: Wallet,
    },
    {
        key: 'search',
        route: 'search.index',
        match: 'search.*',
        icon: ClipboardList,
    },
    {
        key: 'goals',
        route: 'goals.index',
        match: 'goals.*',
        icon: BadgeCheck,
    },
    {
        key: 'notepad',
        route: 'notes.index',
        match: 'notes.*',
        icon: NotebookPen,
        pro: true,
    },
    {
        key: 'recurring',
        route: 'recurring.index',
        match: 'recurring.*',
        icon: Repeat,
    },
    {
        key: 'categories',
        route: 'categories.index',
        match: 'categories.*',
        icon: Tag,
    },
    {
        key: 'rules',
        route: 'categorization-rules.index',
        match: 'categorization-rules.*',
        icon: Wand2,
    },
    {
        key: 'statistics',
        route: 'statistics.index',
        match: 'statistics.*',
        icon: TrendingUp,
    },
    {
        key: 'import',
        route: 'import.index',
        match: 'import.*',
        icon: Upload,
        pro: true,
    },
];

// Tour
useTour();

// Impersonation
function leaveImpersonation() {
    useForm({}).post(route('dev.impersonation.leave'));
}

// Dev dashboard item (shown only for ROLE_DEV users)
const isDev = computed(() => page.props.auth?.user?.roles?.some(r => r.name === 'ROLE_DEV') ?? false);
const devNavItem = computed(() => {
    if (!isDev.value) return null;
    return {
        key: 'dev-dashboard',
        route: 'dev.dashboard.stats',
        match: 'dev.*',
        icon: Shield,
    };
});
</script>

<template>
    <div class="min-h-screen bg-bg">
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
                    <div class="flex flex-col min-w-0">
                        <span class="text-primary font-bold text-lg tracking-tight truncate leading-tight">Spendly</span>
                        <span class="text-xs text-muted/50 leading-none">{{ $page.props.appVersion }}</span>
                    </div>
                </Link>
                <Link v-else :href="route('dashboard')">
                    <AppLogo :size="32" />
                </Link>

                <button
                    class="p-1.5 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors shrink-0"
                    :class="collapsed ? 'hidden' : 'ml-2'"
                    v-on:click="toggleSidebar"
                >
                    <ChevronsLeft class="w-4 h-4" />
                </button>
            </div>

            <div v-if="!collapsed" class="border-b border-base px-4 py-3 shrink-0">
                <p class="text-sm font-medium text-primary truncate">{{ $page.props.auth.user.name }}</p>
                <p class="text-xs text-muted truncate">{{ $page.props.auth.user.email }}</p>
            </div>

            <nav class="flex-1 py-4 space-y-0.5" :class="collapsed ? 'px-2' : 'px-3 overflow-y-auto'">
                <template v-for="item in navItems" :key="item.key">
                    <span
                        v-if="item.pro && !isPro"
                        class="flex items-center rounded-lg text-sm font-medium text-secondary group relative cursor-not-allowed opacity-50"
                        :class="collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5'"
                    >
                        <component
                            :is="item.icon"
                            class="w-5 h-5 shrink-0 text-muted"
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
                        <component
                            :is="item.icon"
                            class="w-5 h-5 shrink-0"
                            :class="route().current(item.match) ? 'text-indigo-400' : 'text-muted'"
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

                <Link
                    v-if="devNavItem"
                    :href="route(devNavItem.route)"
                    class="flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                    :class="[
                        collapsed ? 'justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5',
                        route().current(devNavItem.match)
                            ? 'bg-rose-600/15 text-rose-400'
                            : 'text-secondary hover:text-primary hover:bg-surface-2',
                    ]"
                >
                    <component
                        :is="devNavItem.icon"
                        class="w-5 h-5 shrink-0"
                        :class="route().current(devNavItem.match) ? 'text-rose-400' : 'text-muted'"
                    />
                    <span v-if="!collapsed" class="truncate">{{ t('nav.dev-dashboard') }}</span>

                    <span
                        v-if="collapsed"
                        class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                    >
                        {{ t('nav.dev-dashboard') }}
                    </span>
                </Link>
            </nav>

            <div class="shrink-0 border-t border-base py-3 space-y-0.5" :class="collapsed ? 'px-2' : 'px-3'">
                <button
                    v-if="collapsed"
                    class="flex items-center justify-center w-full py-2.5 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors"
                    v-on:click="toggleSidebar"
                >
                    <ChevronsRight class="w-4 h-4" />
                </button>

                <button
                    class="flex items-center rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors w-full group relative"
                    :class="collapsed ? 'justify-center py-2.5' : 'gap-3 px-3 py-2.5'"
                    v-on:click="showTourModal = true"
                >
                    <Map class="w-5 h-5 text-muted shrink-0" />
                    <span v-if="!collapsed">{{ t('nav.tour') }}</span>
                    <span
                        v-if="collapsed"
                        class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                    >
                        {{ t('nav.tour') }}
                    </span>
                </button>

                <button
                    class="flex items-center rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors w-full group relative"
                    :class="collapsed ? 'justify-center py-2.5' : 'gap-3 px-3 py-2.5'"
                    v-on:click="toggleTheme"
                >
                    <Sun
                        v-if="theme === Theme.Dark"
                        class="w-5 h-5 text-muted shrink-0"
                    />
                    <Moon
                        v-else
                        class="w-5 h-5 text-muted shrink-0"
                    />
                    <span v-if="!collapsed">{{ theme === Theme.Dark ? t('nav.lightMode') : t('nav.darkMode') }}</span>
                    <span
                        v-if="collapsed"
                        class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                    >
                        {{ theme === Theme.Dark ? t('nav.lightMode') : t('nav.darkMode') }}
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
                    <Sparkles class="w-5 h-5 shrink-0 text-muted" />
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
                    <User class="w-5 h-5 shrink-0 text-muted" />
                    <span v-if="!collapsed" class="truncate">{{ t('nav.profile') }}</span>
                    <span
                        v-if="collapsed"
                        class="absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-base text-xs font-medium text-primary whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-lg"
                    >
                        {{ t('nav.profile') }}
                    </span>
                </Link>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex items-center rounded-lg text-sm font-medium text-secondary hover:text-rose-400 hover:bg-rose-500/10 transition-colors w-full group relative"
                    :class="collapsed ? 'justify-center py-2.5' : 'gap-3 px-3 py-2.5'"
                >
                    <LogOut class="w-5 h-5 shrink-0 text-muted group-hover:text-rose-400 transition-colors" />
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
                <Menu class="w-5 h-5" />
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
                    <div v-if="showMobileMenu" class="relative w-60 max-w-[85vw] bg-surface h-full flex flex-col shadow-2xl">
                        <div class="flex items-center justify-between px-4 h-16 border-b border-base shrink-0">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <AppLogo :size="32" class="shrink-0" />
                                <div class="flex flex-col min-w-0">
                                    <span class="text-primary font-bold text-lg tracking-tight truncate leading-tight">Spendly</span>
                                    <span class="text-xs text-muted/50 leading-none">{{ $page.props.appVersion }}</span>
                                </div>
                            </div>
                            <button class="p-1.5 text-muted hover:text-primary" v-on:click="showMobileMenu = false">
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="px-4 py-3 border-b border-base shrink-0">
                            <p class="text-sm font-medium text-primary">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-muted truncate">{{ $page.props.auth.user.email }}</p>
                        </div>

                        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                            <template v-for="item in navItems" :key="item.key">
                                <span
                                    v-if="item.pro && !isPro"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-secondary cursor-not-allowed opacity-50"
                                >
                                    <component
                                        :is="item.icon"
                                        class="w-5 h-5 shrink-0 text-muted"
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
                                    <component
                                        :is="item.icon"
                                        class="w-5 h-5 shrink-0"
                                        :class="route().current(item.match) ? 'text-indigo-400' : 'text-muted'"
                                    />
                                    {{ t('nav.' + item.key) }}
                                </Link>
                            </template>

                            <Link
                                v-if="devNavItem"
                                :href="route(devNavItem.route)"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                                :class="route().current(devNavItem.match)
                                    ? 'bg-rose-600/15 text-rose-400'
                                    : 'text-secondary hover:text-primary hover:bg-surface-2'"
                                v-on:click="showMobileMenu = false"
                            >
                                <component
                                    :is="devNavItem.icon"
                                    class="w-5 h-5 shrink-0"
                                    :class="route().current(devNavItem.match) ? 'text-rose-400' : 'text-muted'"
                                />
                                {{ t('nav.dev-dashboard') }}
                            </Link>
                        </nav>

                        <div class="shrink-0 border-t border-base px-3 py-3 space-y-1">
                            <button
                                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                                v-on:click="showTourModal = true; showMobileMenu = false"
                            >
                                <Map class="w-5 h-5 text-muted shrink-0" />
                                {{ t('nav.tour') }}
                            </button>

                            <button
                                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                                v-on:click="toggleTheme"
                            >
                                <Sun
                                    v-if="theme === Theme.Dark"
                                    class="w-5 h-5 text-muted shrink-0"
                                />
                                <Moon
                                    v-else
                                    class="w-5 h-5 text-muted shrink-0"
                                />
                                {{ theme === Theme.Dark ? t('nav.lightMode') : t('nav.darkMode') }}
                            </button>
                            <Link
                                :href="route('plan.index')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                                :class="route().current('plan.*') ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                                v-on:click="showMobileMenu = false"
                            >
                                <Sparkles class="w-5 h-5 text-muted shrink-0" />
                                <span>{{ t('nav.plan') }}</span>
                                <span v-if="!isPro" class="text-xs font-bold bg-amber-500 text-white px-1.5 py-0.5 rounded-full leading-none ml-auto">Pro</span>
                            </Link>
                            <Link
                                :href="route('profile.edit')"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                                v-on:click="showMobileMenu = false"
                            >
                                <User class="w-5 h-5 text-muted shrink-0" />
                                {{ t('nav.profile') }}
                            </Link>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                            >
                                <LogOut class="w-5 h-5 text-muted shrink-0 group-hover:text-rose-400 transition-colors" />
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

            <div v-if="$page.props.impersonating" class="bg-amber-500/15 border-b border-amber-500/40 px-4 py-2 flex items-center justify-center gap-3 text-xs text-amber-300">
                <UserCheck class="w-4 h-4 shrink-0" />
                <span>{{ t('impersonation.banner', { name: $page.props.auth.user.name }) }}</span>
                <button class="ml-2 underline hover:text-amber-200 transition-colors font-medium" v-on:click="leaveImpersonation">
                    {{ t('impersonation.leave') }}
                </button>
            </div>

            <div v-if="isTrialing" class="bg-indigo-600/15 border-b border-indigo-500/40 px-4 py-2 flex items-center justify-center gap-3 text-xs text-indigo-400">
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

        <UpgradePrompt
            :show="showUpgradePrompt"
            :limit-key="planErrorKey"
            :limit-value="planErrorLimit"
            v-on:close="showUpgradePrompt = false"
        />

        <TourSelectionModal :show="showTourModal" v-on:close="showTourModal = false" />
    </div>
</template>
