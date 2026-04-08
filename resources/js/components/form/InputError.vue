<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <p v-show="message" class="text-sm text-red-400 mt-1">{{ message }}</p>
</template>
