<script setup>
import { onMounted, ref, computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: String });

const input = ref(null);
const attrs = useAttrs();
const showPassword = ref(false);

const isPasswordField = computed(() => attrs.type === 'password');
const inputType = computed(() => {
    if (isPasswordField.value && showPassword.value) return 'text';
    return attrs.type || 'text';
});

onMounted(() => {
    if (input.value.hasAttribute('autofocus') && document.activeElement === document.body) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div v-if="isPasswordField" class="relative" :class="$attrs.class">
        <input
            ref="input"
            v-model="model"
            v-bind="{ ...$attrs, class: undefined, type: inputType }"
            class="w-full px-3 py-2 pr-10 border border-line rounded-md bg-surface-2 text-primary placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        >
        <button
            type="button"
            tabindex="-1"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-primary transition-colors"
            v-on:click="showPassword = !showPassword"
        >
            <svg
                v-if="!showPassword"
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg
                v-else
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
        </button>
    </div>
    <input
        v-else
        ref="input"
        v-model="model"
        class="w-full px-3 py-2 border border-line rounded-md bg-surface-2 text-primary placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        v-bind="$attrs"
    >
</template>
