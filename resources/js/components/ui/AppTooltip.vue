<script setup>
import { ref } from 'vue';

defineProps({
    text: { type: String, required: true },
    position: { type: String, default: 'top' },
});

const triggerRef = ref(null);
const show = ref(false);
const pos = ref({ top: 0, left: 0 });

function onMouseEnter() {
    const rect = triggerRef.value?.getBoundingClientRect();
    if (!rect) return;
    pos.value = {
        top: rect.top + window.scrollY,
        left: rect.left + rect.width / 2,
    };
    show.value = true;
}
</script>

<template>
    <span
        ref="triggerRef"
        class="inline-flex"
        v-on:mouseenter="onMouseEnter"
        v-on:mouseleave="show = false"
    >
        <slot />

        <Teleport to="body">
            <span
                v-if="show"
                :style="{
                    position: 'absolute',
                    top: (pos.top - 8) + 'px',
                    left: pos.left + 'px',
                    transform: 'translate(-50%, -100%)',
                    zIndex: 9999,
                }"
                class="pointer-events-none max-w-xs rounded-lg border border-line bg-surface-2 px-3 py-2 text-xs text-secondary shadow-xl whitespace-pre-wrap"
            >
                {{ text }}
                <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-base" />
            </span>
        </Teleport>
    </span>
</template>
