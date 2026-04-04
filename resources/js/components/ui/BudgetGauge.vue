<script setup>
import { computed } from 'vue';

const props = defineProps({
    spent:    { type: Number, default: 0 },
    total:    { type: Number, default: 0 },
    center:   { type: String, default: '' },
    sublabel: { type: String, default: '' },
    size:     { type: Number, default: 180 },
});

const cx  = computed(() => props.size / 2);
const cy  = computed(() => props.size / 2);
const r   = computed(() => props.size * 0.34);
const sw  = computed(() => props.size * 0.065);
const C   = computed(() => 2 * Math.PI * r.value);

// 270° arc
const arcLen = computed(() => 0.75 * C.value);

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

const glowId = computed(() => `glow-${Math.random().toString(36).slice(2)}`);
</script>

<template>
    <div class="flex flex-col items-center overflow-hidden" :style="{ height: size * 0.86 + 'px' }">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
            <defs>
                <filter
                    :id="glowId"
                    x="-50%"
                    y="-50%"
                    width="200%"
                    height="200%"
                >
                    <feGaussianBlur stdDeviation="3" result="blur" />
                    <feMerge>
                        <feMergeNode in="blur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
            </defs>

            <!-- Track -->
            <circle
                :cx="cx"
                :cy="cy"
                :r="r"
                fill="none"
                stroke="#1f2937"
                :stroke-width="sw"
                :stroke-dasharray="`${arcLen} ${C - arcLen}`"
                stroke-linecap="round"
                :transform="`rotate(135, ${cx}, ${cy})`"
            />

            <!-- Track inner ring (subtle) -->
            <circle
                :cx="cx"
                :cy="cy"
                :r="r"
                fill="none"
                stroke="#374151"
                :stroke-width="sw * 0.15"
                :stroke-dasharray="`${arcLen} ${C - arcLen}`"
                stroke-linecap="round"
                :transform="`rotate(135, ${cx}, ${cy})`"
            />

            <!-- Fill -->
            <circle
                :cx="cx"
                :cy="cy"
                :r="r"
                fill="none"
                :stroke="color"
                :stroke-width="sw"
                :stroke-dasharray="`${fillLen} ${C - fillLen}`"
                stroke-linecap="round"
                :transform="`rotate(135, ${cx}, ${cy})`"
                :filter="`url(#${glowId})`"
                style="transition: stroke-dasharray 0.6s cubic-bezier(.4,0,.2,1), stroke 0.4s ease"
            />

            <!-- Amount -->
            <text
                :x="cx"
                :y="cy - size * 0.06"
                text-anchor="middle"
                dominant-baseline="middle"
                :font-size="size * 0.13"
                font-weight="700"
                fill="white"
                font-family="ui-monospace, monospace"
            >{{ center }}</text>

            <!-- Sublabel -->
            <text
                :x="cx"
                :y="cy + size * 0.09"
                text-anchor="middle"
                dominant-baseline="middle"
                :font-size="size * 0.072"
                fill="#6b7280"
                letter-spacing="0.5"
            >{{ sublabel }}</text>

            <!-- Percentage -->
            <text
                :x="cx"
                :y="cy + size * 0.23"
                text-anchor="middle"
                dominant-baseline="middle"
                :font-size="size * 0.09"
                font-weight="600"
                :fill="color"
            >{{ Math.round(pct * 100) }}%</text>
        </svg>
    </div>
</template>
