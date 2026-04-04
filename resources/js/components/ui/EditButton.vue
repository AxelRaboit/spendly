<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
});
</script>

<template>
    <Link :href="href" class="inline-flex items-center rounded px-3 py-1 text-sm font-medium bg-indigo-600 hover:bg-indigo-500 text-white transition">
        <slot>Modifier</slot>
    </Link>
</template>
