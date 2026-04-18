<script setup>
import AppLogo from '@/components/ui/AppLogo.vue';
import ThemeToggle from '@/components/ui/ThemeToggle.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Wallet, CreditCard, Target, BarChart2 } from 'lucide-vue-next';

const appVersion = usePage().props.appVersion;

const features = [
    { icon: Wallet,      label: 'Plusieurs portefeuilles en mode Budget ou Simplifié' },
    { icon: CreditCard,  label: 'Suivi complet des transactions et abonnements' },
    { icon: Target,      label: 'Objectifs d\'épargne avec progression visuelle' },
    { icon: BarChart2,   label: 'Statistiques et projections mensuelles' },
];
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center bg-bg px-4 py-12 transition-colors duration-200">
        <ThemeToggle class="absolute top-4 right-4" />

        <div class="w-full max-w-5xl grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <!-- Left — marketing -->
            <div>
                <Link href="/" class="inline-flex items-center gap-3 mb-8">
                    <AppLogo :size="56" />
                    <div class="flex flex-col">
                        <span class="text-primary font-bold text-2xl leading-tight">Spendly</span>
                        <span v-if="appVersion" class="text-xs text-secondary leading-none">{{ appVersion }}</span>
                    </div>
                </Link>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-primary leading-tight mb-4">
                    Vos finances,<br>
                    <span class="text-indigo-500">maîtrisées.</span>
                </h1>

                <p class="text-secondary text-base leading-relaxed mb-8">
                    Portefeuilles, transactions, objectifs d'épargne et statistiques. Tout ce qu'il faut pour piloter votre budget.
                </p>

                <ul class="space-y-3">
                    <li
                        v-for="feature in features"
                        :key="feature.label"
                        class="flex items-center gap-3 text-secondary text-sm"
                    >
                        <component :is="feature.icon" :size="16" class="text-indigo-500 shrink-0" />
                        {{ feature.label }}
                    </li>
                </ul>
            </div>

            <!-- Right — form -->
            <div class="w-full overflow-hidden bg-surface border border-line px-6 py-4 shadow-md rounded-lg">
                <!-- Mobile logo -->
                <Link href="/" class="flex flex-col items-center gap-2 mb-6 lg:hidden">
                    <AppLogo :size="48" />
                    <span class="text-primary font-bold text-xl">Spendly</span>
                </Link>

                <slot />
            </div>
        </div>
    </div>
</template>
