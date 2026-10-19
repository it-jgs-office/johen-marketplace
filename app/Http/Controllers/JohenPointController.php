<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Services\BalanceService;
use Illuminate\Support\Facades\Auth;

class JohenPointController extends Controller
{
    public function index(BalanceService $balance)
    {
        $user = Auth::user();
        $transactions = BalanceTransaction::where('user_id', $user->id)->latest()->paginate(20);
        $earned = (float) BalanceTransaction::where('user_id', $user->id)->where('amount', '>', 0)->sum('amount');
        $used = abs((float) BalanceTransaction::where('user_id', $user->id)->where('amount', '<', 0)->sum('amount'));

        return view('points.index', [
            'balance' => $balance->balanceFor($user->id),
            'transactions' => $transactions,
            'earned' => $earned,
            'used' => $used,
        ]);
    }
}
