<script setup>
import { shallowRef } from 'vue';
import { Line } from 'vue-chartjs';

defineProps({
    data:    { type: Object,  required: true },
    options: { type: Object,  default: () => ({}) },
    plugins: { type: Array,   default: () => [] },
    width:   { type: Number,  default: undefined },
    height:  { type: Number,  default: undefined },
});

const chart = shallowRef(Line);
</script>

<template>
    <component
        :is="chart"
        :data="data"
        :options="options"
        :plugins="plugins"
        :width="width"
        :height="height"
    />
</template>
