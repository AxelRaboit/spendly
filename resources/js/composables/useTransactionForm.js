import { useForm } from '@inertiajs/vue3';

export function useTransactionForm(transaction = null) {
    const form = useForm({
        category_id: transaction?.category_id ?? '',
        amount:      transaction?.amount ?? '',
        description: transaction?.description ?? '',
        date:        transaction?.date ?? '',
    });

    const submit = () => {
        if (transaction) {
            form.patch(`/transactions/${transaction.id}`);
        } else {
            form.post('/transactions');
        }
    };

    return { form, submit };
}
