<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    meta: { type: Object, required: true },
});

const { t } = useI18n();

const prevLink = computed(() => props.meta.links?.find(l => l.label.includes('&laquo;') || l.label === '«') ?? null);
const nextLink = computed(() => props.meta.links?.find(l => l.label.includes('&raquo;') || l.label === '»') ?? null);

function decodeLabel(html) {
    const el = document.createElement('textarea');
    el.innerHTML = html;
    return el.value;
}
</script>

<template>
    <div v-if="meta.last_page > 1" class="mt-6 space-y-3">
        <!-- Result count -->
        <p class="text-sm text-secondary text-center sm:text-left">
            {{ t('pagination.results', { from: meta.from, to: meta.to, total: meta.total }) }}
        </p>

        <!-- Mobile: prev / next only -->
        <div class="flex gap-2 sm:hidden">
            <component
                :is="prevLink?.url ? Link : 'span'"
                :href="prevLink?.url ?? undefined"
                preserve-scroll
                preserve-state
                class="flex-1 text-center px-4 py-2 rounded-lg text-sm font-medium transition"
                :class="prevLink?.url ? 'bg-surface-2 text-secondary hover:bg-surface-3' : 'bg-surface-2/50 text-subtle cursor-not-allowed'"
            >
                ← {{ t('pagination.previous') }}
            </component>
            <component
                :is="nextLink?.url ? Link : 'span'"
                :href="nextLink?.url ?? undefined"
                preserve-scroll
                preserve-state
                class="flex-1 text-center px-4 py-2 rounded-lg text-sm font-medium transition"
                :class="nextLink?.url ? 'bg-surface-2 text-secondary hover:bg-surface-3' : 'bg-surface-2/50 text-subtle cursor-not-allowed'"
            >
                {{ t('pagination.next') }} →
            </component>
        </div>

        <!-- Desktop: full page links -->
        <div class="hidden sm:flex gap-1">
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
