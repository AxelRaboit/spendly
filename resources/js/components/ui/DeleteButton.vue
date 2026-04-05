<script setup>
import { useI18n } from 'vue-i18n';

defineOptions({ inheritAttrs: false });

const { t } = useI18n();
</script>

<template>
    <button class="inline-flex items-center rounded px-3 py-1 text-sm font-medium bg-red-600 hover:bg-red-500 text-white transition" v-bind="$attrs">
        <slot>{{ t('common.delete') }}</slot>
    </button>
</template>
