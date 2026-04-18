<script setup>
import { computed } from 'vue';

const props = defineProps({
    segments: {
        type: Array, // [{ label, value, color }]
        default: () => [],
    },
    size: {
        type: Number,
        default: 160,
    },
});

const centerX = computed(() => props.size / 2);
const centerY = computed(() => props.size / 2);
const radius  = computed(() => props.size * 0.38);
const innerR = computed(() => props.size * 0.22);

const total = computed(() => props.segments.reduce((sum, seg) => sum + seg.value, 0));

const paths = computed(() => {
    if (total.value === 0) return [];

    const result = [];
    let startAngle = -Math.PI / 2;

    for (const seg of props.segments) {
        if (seg.value <= 0) continue;
        const fraction   = seg.value / total.value;
        const endAngle   = startAngle + fraction * 2 * Math.PI;
        const largeArc   = fraction > 0.5 ? 1 : 0;

        const x1 = centerX.value + radius.value * Math.cos(startAngle);
        const y1 = centerY.value + radius.value * Math.sin(startAngle);
        const x2 = centerX.value + radius.value * Math.cos(endAngle);
        const y2 = centerY.value + radius.value * Math.sin(endAngle);

        const ix1 = centerX.value + innerR.value * Math.cos(startAngle);
        const iy1 = centerY.value + innerR.value * Math.sin(startAngle);
        const ix2 = centerX.value + innerR.value * Math.cos(endAngle);
        const iy2 = centerY.value + innerR.value * Math.sin(endAngle);

        result.push({
            d: `M ${x1} ${y1} A ${radius.value} ${radius.value} 0 ${largeArc} 1 ${x2} ${y2}
                L ${ix2} ${iy2} A ${innerR.value} ${innerR.value} 0 ${largeArc} 0 ${ix1} ${iy1} Z`,
            color:   seg.color,
            label:   seg.label,
            value:   seg.value,
            percent: Math.round(fraction * 100),
        });

        startAngle = endAngle;
    }

    return result;
});
</script>

<template>
    <div class="flex items-center gap-4">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" class="shrink-0">
            <g v-if="paths.length">
                <path
                    v-for="(seg, i) in paths"
                    :key="i"
                    :d="seg.d"
                    :fill="seg.color"
                    class="opacity-80 hover:opacity-100 transition-opacity cursor-default"
                >
                    <title>{{ seg.label }} — {{ seg.percent }}%</title>
                </path>
            </g>
            <circle
                v-else
                :cx="centerX"
                :cy="centerY"
                :r="radius"
                fill="none"
                stroke="var(--th-surface-3)"
                :stroke-width="innerR"
            />
        </svg>

        <ul class="space-y-1 text-xs min-w-0">
            <li
                v-for="seg in paths"
                :key="seg.label"
                class="flex items-center gap-1.5 text-secondary"
            >
                <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: seg.color }" />
                <span class="truncate">{{ seg.label }}</span>
                <span class="ml-auto pl-2 font-mono text-secondary shrink-0">{{ seg.percent }}%</span>
            </li>
        </ul>
    </div>
</template>
