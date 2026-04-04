<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: 'Êtes-vous sûr de vouloir supprimer cet élément ?',
    },
});

defineEmits(['confirm', 'cancel']);
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
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60" v-on:click="$emit('cancel')" />

            <div class="relative z-10 w-full max-w-md rounded-xl bg-gray-800 border border-gray-700 p-6 shadow-xl">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-900/50">
                        <svg
                            class="h-5 w-5 text-red-400"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-100">Confirmation</h3>
                        <p class="text-sm text-gray-400 mt-1">{{ message }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <SecondaryButton type="button" v-on:click="$emit('cancel')">
                        Annuler
                    </SecondaryButton>
                    <DangerButton type="button" v-on:click="$emit('confirm')">
                        Supprimer
                    </DangerButton>
                </div>
            </div>
        </div>
    </Transition>
</template>
