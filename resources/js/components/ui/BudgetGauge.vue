<script setup>
import { computed } from 'vue';

const props = defineProps({
    spent:    { type: Number, default: 0 },
    total:    { type: Number, default: 0 },
    center:   { type: String, default: '' },
    sublabel: { type: String, default: '' },
    size:     { type: Number, default: 180 },
});

const centerX       = computed(() => props.size / 2);
const centerY       = computed(() => props.size / 2);
const radius        = computed(() => props.size * 0.34);
const strokeWidth   = computed(() => props.size * 0.065);
const circumference = computed(() => 2 * Math.PI * radius.value);

// 270° arc
const arcLen = computed(() => 0.75 * circumference.value);

const pct = computed(() => {
    if (!props.total || props.total <= 0) return 0;
    return Math.min(1, Math.max(0, props.spent / props.total));
});

const fillLen = computed(() => pct.value * arcLen.value);

const color = computed(() => {
    if (pct.value < 0.6)  return '#34d399'; // emerald
    if (pct.value < 0.85) return '#fbbf24'; // amber
    return '#fb7185';                        // rose
});

</script>

<template>
    <div class="flex flex-col items-center overflow-hidden" :style="{ height: size * 0.86 + 'px' }">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
            <circle
                :cx="centerX"
                :cy="centerY"
                :r="radius"
                fill="none"
                stroke="var(--th-surface-3)"
                :stroke-width="strokeWidth"
                :stroke-dasharray="`${arcLen} ${circumference - arcLen}`"
                stroke-linecap="round"
                :transform="`rotate(135, ${centerX}, ${centerY})`"
            />

            <circle
                :cx="centerX"
                :cy="centerY"
                :r="radius"
                fill="none"
                stroke="var(--color-border)"
                :stroke-width="sw * 0.15"
                :stroke-dasharray="`${arcLen} ${circumference - arcLen}`"
                stroke-linecap="round"
                :transform="`rotate(135, ${centerX}, ${centerY})`"
            />

            <circle
                v-if="pct > 0"
                :cx="centerX"
                :cy="centerY"
                :r="radius"
                fill="none"
                :stroke="color"
                :stroke-width="strokeWidth"
                :stroke-dasharray="`${fillLen} ${circumference - fillLen}`"
                stroke-linecap="round"
                :transform="`rotate(135, ${centerX}, ${centerY})`"
                style="transition: stroke-dasharray 0.6s cubic-bezier(.4,0,.2,1), stroke 0.4s ease"
            />

            <text
                :x="centerX"
                :y="centerY - size * 0.06"
                text-anchor="middle"
                dominant-baseline="middle"
                :font-size="size * 0.13"
                font-weight="700"
                fill="var(--color-primary)"
                font-family="ui-monospace, monospace"
            >{{ center }}</text>

            <text
                :x="centerX"
                :y="centerY + size * 0.09"
                text-anchor="middle"
                dominant-baseline="middle"
                :font-size="size * 0.072"
                fill="var(--color-muted)"
                letter-spacing="0.5"
            >{{ sublabel }}</text>

            <text
                :x="centerX"
                :y="centerY + size * 0.23"
                text-anchor="middle"
                dominant-baseline="middle"
                :font-size="size * 0.09"
                font-weight="600"
                :fill="color"
            >{{ Math.round(pct * 100) }}%</text>
        </svg>
    </div>
</template>
