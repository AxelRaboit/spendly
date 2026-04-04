<script setup>
defineProps({
    message: { type: String, required: true },
});
</script>

<template>
    <div class="text-center py-8 text-gray-500">
        {{ message }}
    </div>
</template>
