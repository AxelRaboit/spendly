import { useForm } from '@inertiajs/vue3';

export function useCategoryForm(category = null, { onSuccess } = {}) {
    const form = useForm({
        name: category?.name ?? '',
        wallet_id: category?.wallet_id ?? '',
    });

    const submit = () => {
        if (category) {
            form.patch(`/categories/${category.id}`, {
                onSuccess: () => {
                    form.reset();
                    onSuccess?.();
                },
            });
        } else {
            form.post('/categories', {
                onSuccess: () => {
                    form.reset();
                    onSuccess?.();
                },
            });
        }
    };

    return { form, submit };
}
