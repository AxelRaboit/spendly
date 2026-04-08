import { useForm } from '@inertiajs/vue3';
import { WalletMode } from '@/enums/WalletMode';

export function useWalletForm(wallet = null) {
    const form = useForm({
        name: wallet?.name ?? '',
        start_balance: String(wallet?.start_balance ?? 0),
        mode: wallet?.mode ?? WalletMode.Budget,
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
