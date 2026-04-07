<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SystemCategoryKey;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Text;
use Illuminate\Support\Str;

class WalletTransferService
{
    public function create(User $user, array $data): void
    {
        $fromWallet = Wallet::findOrFail($data['from_wallet_id']);
        $toWallet = Wallet::findOrFail($data['to_wallet_id']);
        $transferId = (string) Str::uuid();

        $expenseCategory = $this->getOrCreateTransferExpenseCategory($user, $fromWallet, $toWallet);
        $incomeCategory = $this->getOrCreateSystemCategory($user, $toWallet, SystemCategoryKey::TransferIncome);

        $raw = trim($data['description'] ?? '');
        $description = blank($raw) ? __('transfer.default_description', ['from' => $fromWallet->name, 'to' => $toWallet->name]) : Text::normalize($raw);

        $base = [
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'description' => $description,
            'date' => $data['date'],
            'tags' => null,
            'transfer_id' => $transferId,
        ];

        Transaction::create([...$base, 'wallet_id' => $fromWallet->id, 'type' => TransactionType::Expense->value, 'category_id' => $expenseCategory->id]);
        Transaction::create([...$base, 'wallet_id' => $toWallet->id, 'type' => TransactionType::Income->value, 'category_id' => $incomeCategory->id]);
    }

    public function getOrCreateTransferExpenseCategory(User $user, Wallet $fromWallet, Wallet $toWallet): Category
    {
        $key = SystemCategoryKey::transferExpenseKey($toWallet->id);

        return Category::firstOrCreate(
            ['wallet_id' => $fromWallet->id, 'system_key' => $key],
            ['user_id' => $user->id, 'name' => __('categories.system.transfer_expense', ['wallet' => $toWallet->name]), 'is_system' => true],
        );
    }

    public function getOrCreateSystemCategory(User $user, Wallet $wallet, SystemCategoryKey $key): Category
    {
        return Category::firstOrCreate(
            ['wallet_id' => $wallet->id, 'system_key' => $key->value],
            ['user_id' => $user->id, 'name' => __('categories.system.'.$key->value), 'is_system' => true],
        );
    }

    public function update(string $transferId, User $user, array $data): void
    {
        $accessibleWalletIds = $user->accessibleWallets()->pluck('id');

        $raw = trim($data['description'] ?? '');
        if (blank($raw)) {
            $txs = Transaction::where('transfer_id', $transferId)
                ->whereIn('wallet_id', $accessibleWalletIds)
                ->get(['type', 'wallet_id']);

            $fromWalletId = $txs->firstWhere('type', TransactionType::Expense->value)?->wallet_id;
            $toWalletId = $txs->firstWhere('type', TransactionType::Income->value)?->wallet_id;

            $fromWallet = $fromWalletId ? Wallet::find($fromWalletId) : null;
            $toWallet = $toWalletId ? Wallet::find($toWalletId) : null;

            $description = ($fromWallet && $toWallet)
                ? __('transfer.default_description', ['from' => $fromWallet->name, 'to' => $toWallet->name])
                : null;
        } else {
            $description = Text::normalize($raw);
        }

        Transaction::where('transfer_id', $transferId)
            ->whereIn('wallet_id', $accessibleWalletIds)
            ->update([
                'amount' => $data['amount'],
                'date' => $data['date'],
                'description' => $description,
            ]);
    }

    public function deleteByTransferId(string $transferId, User $user): void
    {
        $accessibleWalletIds = $user->accessibleWallets()->pluck('id');

        Transaction::where('transfer_id', $transferId)
            ->whereIn('wallet_id', $accessibleWalletIds)
            ->delete();
    }
}
