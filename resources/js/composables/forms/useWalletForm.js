import { useForm } from '@inertiajs/vue3';

export function useWalletForm(wallet = null) {
    const form = useForm({
        name: wallet?.name ?? '',
        start_balance: String(wallet?.start_balance ?? 0),
    });

    const submit = () => {
        if (wallet) {
            form.patch(`/wallets/${wallet.id}`, {
                onSuccess: () => form.reset(),
            });
        } else {
            form.post('/wallets', {
                onSuccess: () => form.reset(),
            });
        }
    };

    return { form, submit };
}
