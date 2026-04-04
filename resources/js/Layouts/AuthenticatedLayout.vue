<script setup>
import { ref } from 'vue';
import AppLogo from '@/components/ui/AppLogo.vue';
import AppToast from '@/components/ui/AppToast.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { useFlash } from '@/composables/ui/useFlash';
import { useTheme } from '@/composables/ui/useTheme';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const showingNavigationDropdown = ref(false);
const { message: flashMessage, type: flashType, dismiss: dismissFlash } = useFlash();
const { theme, toggle: toggleTheme } = useTheme();
</script>

<template>
    <div class="min-h-screen bg-bg transition-colors duration-200">
        <nav class="border-b border-base bg-surface">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2">
                                <AppLogo :size="36" />
                                <span class="text-primary font-semibold text-lg">Spendly</span>
                            </Link>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">{{ t('nav.dashboard') }}</NavLink>
                            <NavLink :href="route('categories.index')" :active="route().current('categories.*')">{{ t('nav.categories') }}</NavLink>
                            <NavLink :href="route('transactions.index')" :active="route().current('transactions.*')">{{ t('nav.transactions') }}</NavLink>
                            <NavLink :href="route('wallets.index')" :active="route().current('wallets.*')">{{ t('nav.wallets') }}</NavLink>
                            <NavLink :href="route('statistics.index')" :active="route().current('statistics.*')">{{ t('nav.statistics') }}</NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center gap-3">
                        <!-- Theme toggle -->
                        <button
                            type="button"
                            class="p-2 rounded-lg text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                            :title="theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'"
                            v-on:click="toggleTheme"
                        >
                            <!-- Sun (light mode) -->
                            <svg
                                v-if="theme === 'dark'"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M18.364 18.364l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Moon (dark mode) -->
                            <svg
                                v-else
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center rounded-md border border-base bg-surface-2 px-3 py-2 text-sm font-medium leading-4 text-secondary transition hover:text-primary focus:outline-none">
                                        {{ $page.props.auth.user.name }}
                                        <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">{{ t('nav.profile') }}</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">{{ t('nav.logout') }}</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button class="inline-flex items-center justify-center rounded-md p-2 text-secondary transition hover:bg-surface-2 hover:text-primary focus:outline-none" v-on:click="showingNavigationDropdown = !showingNavigationDropdown">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">{{ t('nav.dashboard') }}</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('categories.index')" :active="route().current('categories.*')">{{ t('nav.categories') }}</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('transactions.index')" :active="route().current('transactions.*')">{{ t('nav.transactions') }}</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('wallets.index')" :active="route().current('wallets.*')">{{ t('nav.wallets') }}</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('statistics.index')" :active="route().current('statistics.*')">{{ t('nav.statistics') }}</ResponsiveNavLink>
                </div>

                <div class="border-t border-base pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-primary">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-secondary">{{ $page.props.auth.user.email }}</div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">{{ t('nav.profile') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">{{ t('nav.logout') }}</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-surface border-b border-base">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <slot />
            </div>
        </main>

        <!-- ── Global flash toast ── -->
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
    </div>
</template>
