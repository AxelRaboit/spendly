import { useForm } from '@inertiajs/vue3';

export function useCategoryForm(category = null) {
    const form = useForm({
        name: category?.name ?? '',
    });

    const submit = () => {
        if (category) {
            form.patch(`/categories/${category.id}`, {
                onSuccess: () => form.reset(),
            });
        } else {
            form.post('/categories', {
                onSuccess: () => form.reset(),
            });
        }
    };

    return { form, submit };
}
