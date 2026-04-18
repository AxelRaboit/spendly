<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    meta: { type: Object, required: true },
});

const { t: translate } = useI18n({ useScope: 'global' });

function decodeHtml(html) {
    const textareaElement = document.createElement('textarea');
    textareaElement.innerHTML = html;
    return textareaElement.value;
}

const prevLink = computed(() => props.meta.links?.[0] ?? null);
const nextLink = computed(() => props.meta.links?.[props.meta.links.length - 1] ?? null);
const pageLinks = computed(() => props.meta.links?.slice(1, -1) ?? []);
</script>

<template>
    <div v-if="meta.last_page > 1" class="mt-6 space-y-3 flex flex-col items-center">
        <p class="text-sm text-secondary">
            {{ translate('pagination.results', { from: meta.from, to: meta.to, total: meta.total }) }}
        </p>

        <div class="flex flex-wrap gap-1 items-center justify-center">
            <component
                :is="prevLink?.url ? Link : 'span'"
                :href="prevLink?.url ?? undefined"
                preserve-scroll
                preserve-state
                class="px-3 py-1 rounded text-sm transition"
                :class="prevLink?.url ? 'bg-surface-2 text-secondary hover:bg-surface-3' : 'bg-surface-2/50 text-subtle cursor-not-allowed'"
            >
                {{ translate('pagination.previous') }}
            </component>

            <template v-for="link in pageLinks" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="px-3 py-1 rounded text-sm transition"
                    :class="link.active ? 'bg-indigo-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                >
                    {{ decodeHtml(link.label) }}
                </Link>
                <span v-else class="px-3 py-1 rounded text-sm text-subtle">
                    {{ decodeHtml(link.label) }}
                </span>
            </template>

            <component
                :is="nextLink?.url ? Link : 'span'"
                :href="nextLink?.url ?? undefined"
                preserve-scroll
                preserve-state
                class="px-3 py-1 rounded text-sm transition"
                :class="nextLink?.url ? 'bg-surface-2 text-secondary hover:bg-surface-3' : 'bg-surface-2/50 text-subtle cursor-not-allowed'"
            >
                {{ translate('pagination.next') }}
            </component>
        </div>
    </div>
</template>
