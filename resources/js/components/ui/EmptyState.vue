<script setup>
defineProps({
    message: { type: String, required: true },
});
</script>

<template>
    <div class="text-center py-8 text-muted">
        {{ message }}
    </div>
</template>
