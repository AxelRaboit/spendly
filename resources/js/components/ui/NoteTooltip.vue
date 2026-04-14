<script setup>
import { MessageSquare } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({ note: { type: String, required: true } });

const iconRef = ref(null);
const show    = ref(false);
const pos     = ref({ top: 0, left: 0 });

function onMouseEnter() {
    const rect = iconRef.value?.getBoundingClientRect();
    if (rect) {
        pos.value = {
            top:  rect.top + window.scrollY,
            left: rect.left + rect.width / 2,
        };
    }
    show.value = true;
}
</script>

<template>
    <span
        ref="iconRef"
        class="inline-flex items-center cursor-default"
        v-on:click.stop
        v-on:mouseenter="onMouseEnter"
        v-on:mouseleave="show = false"
    >
        <MessageSquare class="w-3 h-3 text-subtle hover:text-secondary transition-colors" />

        <Teleport to="body">
            <span
                v-if="show"
                :style="{ position: 'absolute', top: (pos.top - 8) + 'px', left: pos.left + 'px', transform: 'translate(-50%, -100%)', zIndex: 9999 }"
                class="pointer-events-none w-56 rounded-lg border border-line bg-surface-2 px-3 py-2 text-xs text-primary shadow-xl whitespace-pre-wrap"
            >
                {{ note }}
                <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-base" />
            </span>
        </Teleport>
    </span>
</template>
