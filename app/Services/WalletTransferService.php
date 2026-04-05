<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SystemCategoryKey;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;

class WalletTransferService
{
    /**
     * Create a transfer between two wallets.
     * Expense leg gets a per-destination-wallet category ("Virement → Livret A").
     * Income leg gets the shared transfer_income system category.
     */
    public function create(User $user, array $data): void
    {
        $toWallet = Wallet::findOrFail($data['to_wallet_id']);
        $transferId = (string) Str::uuid();
        $expenseCategory = $this->getOrCreateTransferExpenseCategory($user, $toWallet);
        $incomeCategory = $this->getOrCreateSystemCategory($user, SystemCategoryKey::TransferIncome);

        $base = [
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'],
            'tags' => null,
            'transfer_id' => $transferId,
        ];

        Transaction::create([...$base, 'wallet_id' => $data['from_wallet_id'], 'type' => TransactionType::Expense->value, 'category_id' => $expenseCategory->id]);
        Transaction::create([...$base, 'wallet_id' => $data['to_wallet_id'],   'type' => TransactionType::Income->value,  'category_id' => $incomeCategory->id]);
    }

    /**
     * Get or create the per-destination transfer expense category.
     * e.g. "Virement → Livret A" with system_key = "transfer_expense_42"
     */
    public function getOrCreateTransferExpenseCategory(User $user, Wallet $toWallet): Category
    {
        $key = SystemCategoryKey::transferExpenseKey($toWallet->id);

        return Category::firstOrCreate(
            ['user_id' => $user->id, 'system_key' => $key],
            ['name' => __('categories.system.transfer_expense', ['wallet' => $toWallet->name]), 'is_system' => true],
        );
    }

    /**
     * Get or create a system category by key (e.g. transfer_income).
     */
    public function getOrCreateSystemCategory(User $user, SystemCategoryKey $key): Category
    {
        return Category::firstOrCreate(
            ['user_id' => $user->id, 'system_key' => $key->value],
            ['name' => __('categories.system.'.$key->value), 'is_system' => true],
        );
    }

    /**
     * Delete both legs of a transfer by its shared UUID.
     */
    public function deleteByTransferId(string $transferId, User $user): void
    {
        Transaction::where('transfer_id', $transferId)
            ->where('user_id', $user->id)
            ->delete();
    }
}
