<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ArcElement, BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Title, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, ArcElement, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    byCategory: Array,
    byMonth: Array,
    currentMonth: Number,
    previousMonth: Number,
});

const evolution = computed(() => {
    if (props.previousMonth === 0) return null;
    return ((props.currentMonth - props.previousMonth) / props.previousMonth) * 100;
});

const categoryColors = [
    '#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd',
    '#818cf8', '#4f46e5', '#7c3aed', '#9333ea',
    '#a855f7', '#d946ef', '#ec4899', '#f43f5e',
];

const donutData = computed(() => ({
    labels: props.byCategory.map((c) => c.name),
    datasets: [{
        data: props.byCategory.map((c) => parseFloat(c.total)),
        backgroundColor: categoryColors.slice(0, props.byCategory.length),
        borderWidth: 0,
    }],
}));

const barData = computed(() => ({
    labels: props.byMonth.map((m) => m.month),
    datasets: [{
        label: 'Dépenses (€)',
        data: props.byMonth.map((m) => parseFloat(m.total)),
        backgroundColor: '#6366f1',
        borderRadius: 6,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#d1d5db' } },
    },
};

const barOptions = {
    ...chartOptions,
    scales: {
        x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
        y: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
    },
};
</script>

<template>
    <Head title="Statistiques" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Statistiques</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="bg-gray-900 rounded-lg p-6">
                        <p class="text-sm text-gray-400 mb-1">Ce mois-ci</p>
                        <p class="text-3xl font-bold text-white">{{ currentMonth.toFixed(2) }} €</p>
                        <p v-if="evolution !== null" class="text-sm mt-1" :class="evolution > 0 ? 'text-red-400' : 'text-green-400'">
                            {{ evolution > 0 ? '+' : '' }}{{ evolution.toFixed(1) }}% vs mois dernier
                        </p>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-6">
                        <p class="text-sm text-gray-400 mb-1">Mois dernier</p>
                        <p class="text-3xl font-bold text-white">{{ previousMonth.toFixed(2) }} €</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-900 rounded-lg p-6">
                        <h3 class="text-gray-100 font-semibold mb-4">Dépenses par catégorie</h3>
                        <div class="h-64">
                            <Doughnut :data="donutData" :options="chartOptions" />
                        </div>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-6">
                        <h3 class="text-gray-100 font-semibold mb-4">Évolution sur 6 mois</h3>
                        <div class="h-64">
                            <Bar :data="barData" :options="barOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
