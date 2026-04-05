<script setup>
import { X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    message:  { type: String, required: true },
    action:   { type: String, default: null },
    duration: { type: Number, default: 5000 },
    type:     { type: String, default: 'success' }, // success | error | warning | info
});
const emit = defineEmits(['action', 'dismiss']);

let timer = null;
onMounted(() => { timer = setTimeout(() => emit('dismiss'), props.duration); });
onUnmounted(() => clearTimeout(timer));

const styles = computed(() => ({
    success: { wrap: 'bg-surface-2 border-emerald-600/60', icon: 'text-emerald-400', dot: 'bg-emerald-400' },
    error:   { wrap: 'bg-surface-2 border-rose-600/60',    icon: 'text-rose-400',    dot: 'bg-rose-400' },
    warning: { wrap: 'bg-surface-2 border-amber-600/60',   icon: 'text-amber-400',   dot: 'bg-amber-400' },
    info:    { wrap: 'bg-surface-2 border-indigo-600/60',  icon: 'text-indigo-400',  dot: 'bg-indigo-400' },
}[props.type] ?? { wrap: 'bg-surface-2 border-strong', icon: 'text-secondary', dot: 'bg-gray-400' }));
</script>

<template>
    <div
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] flex items-center gap-3 border text-primary text-sm px-4 py-3 rounded-xl shadow-2xl w-max max-w-[calc(100vw-2rem)]"
        :class="styles.wrap"
    >
        <span class="w-2 h-2 rounded-full shrink-0" :class="styles.dot" />
        <span>{{ message }}</span>
        <button
            v-if="action"
            class="font-semibold transition-colors"
            :class="styles.icon"
            v-on:click="emit('action')"
        >
            {{ action }}
        </button>
        <button class="ml-1 text-muted hover:text-secondary transition-colors" v-on:click="emit('dismiss')">
            <X class="w-3.5 h-3.5" />
        </button>
    </div>
</template>
