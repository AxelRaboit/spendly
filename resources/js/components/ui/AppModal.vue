<script setup>
defineProps({
    show: { type: Boolean, required: true },
    maxWidth: { type: String, default: 'max-w-md' },
    scrollable: { type: Boolean, default: false },
});

defineEmits(['close']);
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-black/60" v-on:click="$emit('close')" />
            <div
                class="relative z-10 w-full bg-surface border border-base rounded-xl p-4 sm:p-6 shadow-2xl space-y-4"
                :class="[maxWidth, scrollable ? 'max-h-[90vh] overflow-y-auto' : '']"
                v-on:keydown.esc="$emit('close')"
            >
                <slot />
            </div>
        </div>
    </Transition>
</template>
