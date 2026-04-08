import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { TransactionType } from '@/enums/TransactionType';

export function useScheduledForm(wallets, categories) {
    const editingScheduled  = ref(null);
    const showScheduledForm = ref(false);

    const scheduledForm = useForm({
        description:    '',
        amount:         '',
        type:           TransactionType.Expense,
        scheduled_date: new Date().toISOString().slice(0, 10),
        wallet_id:      '',
        category_id:    '',
    });

    function openCreateScheduled() {
        editingScheduled.value    = null;
        scheduledForm.reset();
        scheduledForm.type           = TransactionType.Expense;
        scheduledForm.scheduled_date = new Date().toISOString().slice(0, 10);
        if (wallets.length) scheduledForm.wallet_id    = wallets[0].id;
        if (categories.length) scheduledForm.category_id = categories[0].id;
        showScheduledForm.value = true;
    }

    function openEditScheduled(item) {
        editingScheduled.value       = item;
        scheduledForm.description    = item.description;
        scheduledForm.amount         = item.amount;
        scheduledForm.type           = item.type;
        scheduledForm.scheduled_date = item.scheduled_date;
        scheduledForm.wallet_id      = item.wallet_id;
        scheduledForm.category_id    = item.category_id ?? '';
        showScheduledForm.value      = true;
    }

    function submitScheduled() {
        if (editingScheduled.value) {
            scheduledForm.put(`/scheduled/${editingScheduled.value.id}`, {
                onSuccess: () => {
                    showScheduledForm.value = false;
                },
            });
        } else {
            scheduledForm.post('/scheduled', {
                onSuccess: () => {
                    showScheduledForm.value = false;
                    scheduledForm.reset();
                },
            });
        }
    }

    const scheduledToDelete = ref(null);

    function confirmDeleteScheduled(item) {
        scheduledToDelete.value = item;
    }

    function executeDeleteScheduled() {
        useForm({}).delete(`/scheduled/${scheduledToDelete.value.id}`);
        scheduledToDelete.value = null;
    }

    return {
        editingScheduled,
        showScheduledForm,
        scheduledForm,
        openCreateScheduled,
        openEditScheduled,
        submitScheduled,
        scheduledToDelete,
        confirmDeleteScheduled,
        executeDeleteScheduled,
    };
}
