<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CategoryScale, Chart as ChartJS, Filler, LinearScale, LineElement, PointElement, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler);

const props = defineProps({
    totalTransactions: Number,
    totalCategories: Number,
    recentTransactions: Array,
    sparkline: Array,
    topCategories: Array,
    dailyAverage: Number,
    bestDay: Object,
});

const sparklineData = computed(() => ({
    labels: props.sparkline.map((d) => d.day.slice(5)),
    datasets: [{
        data: props.sparkline.map((d) => parseFloat(d.total)),
        borderColor: '#a78bfa',
        backgroundColor: 'rgba(167, 139, 250, 0.15)',
        borderWidth: 2,
        pointRadius: 0,
        tension: 0.4,
        fill: true,
    }],
}));

const sparklineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
    scales: {
        x: { display: false },
        y: { display: false },
    },
};

const topCategoryMax = computed(() => {
    if (!props.topCategories.length) return 1;
    return Math.max(...props.topCategories.map((c) => parseFloat(c.total)));
});
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-100">Tableau de bord</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 p-6 shadow-lg">
                        <div class="pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                        <div class="pointer-events-none absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
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

                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 shadow-lg">
                        <div class="pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                        <div class="pointer-events-none absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
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

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-gray-900 rounded-2xl p-6 shadow-lg">
                        <h3 class="text-sm font-medium text-gray-400 mb-1">Dépenses — 30 derniers jours</h3>
                        <div class="h-32">
                            <Line :data="sparklineData" :options="sparklineOptions" />
                        </div>
                    </div>

                    <div class="bg-gray-900 rounded-2xl p-6 shadow-lg space-y-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Moyenne / dépense ce mois</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ dailyAverage.toFixed(2) }} €</p>
                        </div>
                        <div v-if="bestDay">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Jour le plus dépensier</p>
                            <p class="text-lg font-semibold text-white mt-1">{{ bestDay.day }}</p>
                            <p class="text-sm text-gray-400">{{ parseFloat(bestDay.total).toFixed(2) }} €</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-gray-900 rounded-2xl p-6 shadow-lg">
                        <h3 class="text-sm font-medium text-gray-400 mb-4">Top catégories ce mois</h3>
                        <div v-if="topCategories.length" class="space-y-3">
                            <div v-for="cat in topCategories" :key="cat.name">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-300">{{ cat.name }}</span>
                                    <span class="text-gray-400">{{ parseFloat(cat.total).toFixed(2) }} €</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-700">
                                    <div
                                        class="h-2 rounded-full bg-indigo-500 transition-all"
                                        :style="{ width: (parseFloat(cat.total) / topCategoryMax * 100) + '%' }"
                                    />
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500">Aucune dépense ce mois.</p>
                    </div>

                    <div class="lg:col-span-2 overflow-hidden rounded-2xl bg-gray-900 shadow-lg">
                        <div class="flex items-center justify-between border-b border-gray-800 px-6 py-4">
                            <h3 class="text-base font-semibold text-gray-100">Dernières dépenses</h3>
                            <Link href="/transactions" class="text-sm text-indigo-400 hover:text-indigo-300">
                                Voir tout →
                            </Link>
                        </div>

                        <table v-if="recentTransactions.length > 0" class="min-w-full">
                            <thead>
                                <tr class="bg-gray-800/50">
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Catégorie</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800">
                                <tr v-for="transaction in recentTransactions" :key="transaction.id" class="hover:bg-gray-800/50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-300">{{ transaction.date }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-300">{{ transaction.description ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full bg-indigo-900 px-3 py-1 text-xs font-medium text-indigo-300">
                                            {{ transaction.category.name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-100">{{ transaction.amount }} €</td>
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
        </div>
    </AuthenticatedLayout>
</template>
