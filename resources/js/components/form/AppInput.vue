<script setup>
import { ref, computed, useAttrs } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue:  { type: String, default: '' },
    type:        { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    label:       { type: String, default: '' },
    error:       { type: String, default: '' },
    required:    { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const attrs = useAttrs();
const input = ref(null);
const revealed = ref(false);
const isPassword = computed(() => props.type === 'password');
const inputType = computed(() => isPassword.value && revealed.value ? 'text' : props.type);

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="flex flex-col gap-1.5" :class="attrs.class">
        <label v-if="label" class="block text-xs text-secondary uppercase tracking-wide">
            {{ label }}<span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        <div :class="isPassword ? 'relative' : ''">
            <input
                ref="input"
                :type="inputType"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                v-bind="{ ...attrs, class: undefined }"
                class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border border-line focus:border-indigo-500 focus:outline-none"
                :class="[{ 'border-red-500 focus:border-red-500': error }, isPassword ? 'pr-10' : '']"
                v-on:input="$emit('update:modelValue', $event.target.value)"
            >
            <button
                v-if="isPassword"
                type="button"
                tabindex="-1"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition-colors"
                v-on:click="revealed = !revealed"
            >
                <Eye v-if="!revealed" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
            </button>
        </div>
        <p v-if="error" class="text-xs text-rose-400">{{ error }}</p>
    </div>
</template>
