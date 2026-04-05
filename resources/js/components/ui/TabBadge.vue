<script setup>
defineProps({
    count: { type: Number, required: true },
});
</script>

<template>
    <span v-if="count" class="ml-1 text-xs text-muted">({{ count }})</span>
</template>
