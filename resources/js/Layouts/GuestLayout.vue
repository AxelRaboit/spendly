<script setup>
import AppLogo from '@/components/ui/AppLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';

const appVersion = usePage().props.appVersion;
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-12 transition-colors duration-200">
        <div>
            <Link href="/" class="flex flex-col items-center gap-2">
                <AppLogo :size="64" />
                <span class="text-primary font-bold text-2xl">Spendly</span>
                <span v-if="appVersion" class="text-xs text-secondary">{{ appVersion }}</span>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-surface border border-line px-6 py-4 shadow-md rounded-lg sm:max-w-md">
            <slot />
        </div>
    </div>
</template>
