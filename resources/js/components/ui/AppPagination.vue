<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    meta: { type: Object, required: true },
});

function decodeLabel(html) {
    const el = document.createElement('textarea');
    el.innerHTML = html;
    return el.value;
}
</script>

<template>
    <div v-if="meta.last_page > 1" class="flex items-center justify-between mt-6">
        <p class="text-sm text-secondary">
            {{ meta.from }}–{{ meta.to }} sur {{ meta.total }} résultats
        </p>

        <div class="flex gap-1">
            <template v-for="link in meta.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="px-3 py-1 rounded text-sm transition"
                    :class="link.active ? 'bg-indigo-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                >
                    {{ decodeLabel(link.label) }}
                </Link>
                <span
                    v-else
                    class="px-3 py-1 rounded text-sm text-subtle"
                >{{ decodeLabel(link.label) }}</span>
            </template>
        </div>
    </div>
</template>
