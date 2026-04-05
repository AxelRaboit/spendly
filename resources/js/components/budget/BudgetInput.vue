<script setup>
import { evalMath } from '@/utils/evalMath';

defineOptions({ inheritAttrs: false });
const model = defineModel({ type: [String, Number] });
const props = defineProps({
    error: { type: Boolean, default: false },
    variant: { type: String, default: 'default' },
});

function onBlur() {
    if (props.variant !== 'mono') return;
    const val = String(model.value ?? '');
    if (!val || !(/[+\-*/()]/.test(val))) return;
    const result = evalMath(val);
    if (result !== null) model.value = result;
}
</script>

<template>
    <input
        v-model="model"
        v-bind="$attrs"
        :type="variant === 'mono' ? 'text' : ($attrs.type || 'text')"
        :inputmode="variant === 'mono' ? 'decimal' : undefined"
        :class="[
            'w-full bg-surface-3 text-primary rounded px-2 py-1.5 text-sm border focus:border-indigo-500 focus:outline-none',
            variant === 'mono' ? 'text-right font-mono' : '',
            error ? 'border-rose-500' : (variant === 'focus' ? 'border-indigo-500/50' : 'border-strong'),
        ]"
        v-on:blur="onBlur"
    >
</template>
