<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    totalTransactions: Number,
    totalCategories: Number,
    recentTransactions: Array,
});
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Tableau de bord
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Cartes statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Dépenses -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 p-6 shadow-lg">
                        <div class="absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                        <div class="absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
                        <p class="text-sm font-medium text-violet-200">Dépenses</p>
                        <p class="mt-2 text-5xl font-bold text-white">{{ totalTransactions }}</p>
                        <div class="mt-6 flex gap-3">
                            <Link href="/transactions" class="rounded-lg bg-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/30 transition">
                                Voir tout
                            </Link>
                            <Link href="/transactions/create" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-indigo-600 hover:bg-violet-50 transition">
                                + Ajouter
                            </Link>
                        </div>
                    </div>

                    <!-- Catégories -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 shadow-lg">
                        <div class="absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                        <div class="absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
                        <p class="text-sm font-medium text-emerald-100">Catégories</p>
                        <p class="mt-2 text-5xl font-bold text-white">{{ totalCategories }}</p>
                        <div class="mt-6 flex gap-3">
                            <Link href="/categories" class="rounded-lg bg-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/30 transition">
                                Voir tout
                            </Link>
                            <Link href="/categories/create" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-teal-600 hover:bg-emerald-50 transition">
                                + Ajouter
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Dernières dépenses -->
                <div class="overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-lg">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Dernières dépenses</h3>
                        <Link href="/transactions" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                            Voir tout →
                        </Link>
                    </div>

                    <table v-if="recentTransactions.length > 0" class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Catégorie</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="transaction in recentTransactions" :key="transaction.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ transaction.date }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ transaction.description ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-indigo-100 dark:bg-indigo-900 px-3 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                                        {{ transaction.category.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">{{ transaction.amount }} €</td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
                        <p class="text-sm">Aucune dépense pour l'instant.</p>
                        <Link href="/transactions/create" class="mt-3 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">
                            + Ajouter une dépense
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
