<script setup>
import AppLogo from '@/components/AppLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-950 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/" class="flex flex-col items-center gap-2">
                <AppLogo :size="64" />
                <span class="text-white font-bold text-2xl">Spendly</span>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-gray-900 border border-gray-800 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
