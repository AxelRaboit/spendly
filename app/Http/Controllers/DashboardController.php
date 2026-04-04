<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'totalTransactions'  => $user->transactions()->count(),
            'totalCategories'    => $user->categories()->count(),
            'recentTransactions' => $user->transactions()->with('category')->latest('date')->limit(5)->get(),
        ]);
    }
}
