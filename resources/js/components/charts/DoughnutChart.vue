<script setup>
import { shallowRef } from 'vue';
import { Doughnut } from 'vue-chartjs';

defineProps({
    data:    { type: Object,  required: true },
    options: { type: Object,  default: () => ({}) },
    plugins: { type: Array,   default: () => [] },
    width:   { type: Number,  default: undefined },
    height:  { type: Number,  default: undefined },
});

const chart = shallowRef(Doughnut);
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
