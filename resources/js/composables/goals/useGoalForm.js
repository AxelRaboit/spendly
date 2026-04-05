import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useGoalForm() {
    const editingGoal = ref(null);
    const showForm = ref(false);

    const form = useForm({
        wallet_id: null,
        category_id: null,
        name: '',
        target_amount: '',
        saved_amount: '',
        deadline: '',
        color: '#6366f1',
    });

    function openCreate() {
        editingGoal.value = null;
        form.reset();
        form.color = '#6366f1';
        showForm.value = true;
    }

    function openEdit(goal) {
        editingGoal.value = goal;
        form.wallet_id = goal.wallet_id ?? null;
        form.category_id = goal.category_id ?? null;
        form.name = goal.name;
        form.target_amount = goal.target_amount;
        form.saved_amount = goal.saved_amount;
        form.deadline = goal.deadline ?? '';
        form.color = goal.color;
        showForm.value = true;
    }

    function submit() {
        if (editingGoal.value) {
            form.put(`/goals/${editingGoal.value.id}`, {
                onSuccess: () => {
                    showForm.value = false;
                },
            });
        } else {
            form.post('/goals', {
                onSuccess: () => {
                    showForm.value = false;
                    form.reset();
                },
            });
        }
    }

    const goalToDelete = ref(null);

    function confirmDelete(goal) {
        goalToDelete.value = goal;
    }

    function executeDelete() {
        useForm({}).delete(`/goals/${goalToDelete.value.id}`);
        goalToDelete.value = null;
    }

    return { editingGoal, showForm, form, openCreate, openEdit, submit, goalToDelete, confirmDelete, executeDelete };
}
