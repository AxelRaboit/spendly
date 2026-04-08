import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { TransactionType } from '@/enums/TransactionType';

export function useRecurringForm(wallets, categories) {
    const editingItem = ref(null);
    const showForm = ref(false);

    const form = useForm({
        description: '',
        amount: '',
        type: TransactionType.Expense,
        day_of_month: 1,
        wallet_id: '',
        category_id: '',
        active: true,
    });

    function openCreate() {
        editingItem.value = null;
        form.reset();
        form.type = TransactionType.Expense;
        form.active = true;
        form.day_of_month = 1;
        if (wallets.length) form.wallet_id = wallets[0].id;
        if (categories.length) form.category_id = categories[0].id;
        showForm.value = true;
    }

    function openEdit(item) {
        editingItem.value = item;
        form.description = item.description;
        form.amount = item.amount;
        form.type = item.type;
        form.day_of_month = item.day_of_month;
        form.wallet_id = item.wallet_id;
        form.category_id = item.category_id ?? '';
        form.active = item.active;
        showForm.value = true;
    }

    function submit() {
        if (editingItem.value) {
            form.put(`/recurring/${editingItem.value.id}`, {
                onSuccess: () => {
                    showForm.value = false;
                },
            });
        } else {
            form.post('/recurring', {
                onSuccess: () => {
                    showForm.value = false;
                    form.reset();
                },
            });
        }
    }

    const itemToDelete = ref(null);

    function confirmDelete(item) {
        itemToDelete.value = item;
    }

    function executeDelete() {
        useForm({}).delete(`/recurring/${itemToDelete.value.id}`);
        itemToDelete.value = null;
    }

    function toggleActive(item) {
        useForm({}).patch(`/recurring/${item.id}/toggle`, { preserveScroll: true });
    }

    return {
        editingItem,
        showForm,
        form,
        openCreate,
        openEdit,
        submit,
        itemToDelete,
        confirmDelete,
        executeDelete,
        toggleActive,
    };
}
