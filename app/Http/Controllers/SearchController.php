<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = $user->transactions()
            ->with(['category', 'wallet'])
            ->latest('date');

        if ($s = $request->input('q')) {
            $query->where('description', 'ilike', sprintf('%%%s%%', $s));
        }

        if ($category = $request->input('category_id')) {
            $query->where('category_id', $category);
        }

        if ($wallet = $request->input('wallet_id')) {
            $query->where('wallet_id', $wallet);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('date', '<=', $to);
        }

        if ($tag = $request->input('tag')) {
            $query->whereJsonContains('tags', $tag);
        }

        $transactions = $query->paginate(25)->withQueryString();

        return Inertia::render('Search/Index', [
            'transactions' => $transactions,
            'categories' => $user->categories()->orderBy('name')->get(['id', 'name']),
            'wallets' => $user->wallets()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['q', 'category_id', 'wallet_id', 'type', 'date_from', 'date_to', 'tag']),
        ]);
    }
}
