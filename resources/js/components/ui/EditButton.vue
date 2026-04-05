<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps({
    href: { type: String, required: true },
});

const { t } = useI18n();
</script>

<template>
    <Link :href="href" class="inline-flex items-center rounded px-3 py-1 text-sm font-medium bg-indigo-600 hover:bg-indigo-500 text-white transition">
        <slot>{{ t('common.edit') }}</slot>
    </Link>
</template>
