import { useForm } from '@inertiajs/vue3';

export function useTransactionForm(transaction = null) {
    const form = useForm({
        type: transaction?.type ?? 'expense',
        category_id: transaction?.category_id ?? '',
        amount: transaction?.amount ?? '',
        description: transaction?.description ?? '',
        date: transaction?.date ?? '',
    });

    const submit = () => {
        if (transaction) {
            form.patch(`/transactions/${transaction.id}`);
        } else {
            form.post('/transactions', {
                onSuccess: () => form.reset(),
            });
        }
    };

    return { form, submit };
}
