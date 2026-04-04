<script setup>
import { ref } from 'vue';
import AppLogo from '@/components/ui/AppLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-950">
        <nav class="border-b border-gray-800 bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2">
                                <AppLogo :size="36" />
                                <span class="text-white font-semibold text-lg">Spendly</span>
                            </Link>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Tableau de bord
                            </NavLink>
                            <NavLink :href="route('categories.index')" :active="route().current('categories.*')">
                                Catégories
                            </NavLink>
                            <NavLink :href="route('transactions.index')" :active="route().current('transactions.*')">
                                Dépenses
                            </NavLink>
                            <NavLink :href="route('wallets.index')" :active="route().current('wallets.*')">
                                Portefeuilles
                            </NavLink>
                            <NavLink :href="route('statistics.index')" :active="route().current('statistics.*')">
                                Statistiques
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-3 py-2 text-sm font-medium leading-4 text-gray-300 transition hover:text-white focus:outline-none">
                                        {{ $page.props.auth.user.name }}
                                        <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Déconnexion</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-800 hover:text-white focus:outline-none" v-on:click="showingNavigationDropdown = !showingNavigationDropdown">
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
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Tableau de bord</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('categories.index')" :active="route().current('categories.*')">Catégories</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('transactions.index')" :active="route().current('transactions.*')">Dépenses</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('wallets.index')" :active="route().current('wallets.*')">Portefeuilles</ResponsiveNavLink>
                </div>

                <div class="border-t border-gray-800 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-white">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-gray-400">{{ $page.props.auth.user.email }}</div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profil</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Déconnexion</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-gray-900 border-b border-gray-800">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <slot />
            </div>
        </main>
    </div>
</template>
