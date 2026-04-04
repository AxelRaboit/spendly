<script setup>
import { ref, watch } from 'vue';
import Datepicker from 'vue3-datepicker';
import { fr } from 'date-fns/locale';

const model = defineModel({ type: String });

const selectedDate = ref(model.value ? new Date(model.value) : null);

watch(selectedDate, (date) => {
    if (!date) { model.value = ''; return; }
    const d = new Date(date);
    model.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
});
</script>

<template>
    <Datepicker
        v-model="selectedDate"
        :locale="fr"
        input-format="dd/MM/yyyy"
        placeholder="Sélectionner une date"
    />
</template>
