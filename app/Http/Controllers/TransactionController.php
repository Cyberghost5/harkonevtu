<?php

namespace App\Http\Controllers;

use App\Models\ServiceTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $user  = auth()->user();

        $query = ServiceTransaction::where('user_id', $user->id)->latest();

        if ($request->filled('service')) {
            $query->where('service_type', $request->service);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                  ->orWhere('recipient', 'like', "%$s%")
                  ->orWhere('provider', 'like', "%$s%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        $serviceTypes = ServiceTransaction::where('user_id', $user->id)
            ->distinct()
            ->pluck('service_type');

        return view('transactions.index', compact('transactions', 'serviceTypes'));
    }
}
