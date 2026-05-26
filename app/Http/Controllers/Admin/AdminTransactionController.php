<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceTransaction;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceTransaction::with('user')->latest();

        if ($request->filled('service')) {
            $query->where('service_type', $request->service);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$s%")
                      ->orWhere('email', 'like', "%$s%")
                      ->orWhere('phone', 'like', "%$s%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(25)->withQueryString();

        $serviceTypes = ServiceTransaction::select('service_type')
            ->distinct()->pluck('service_type');

        return view('admin.transactions.index', compact('transactions', 'serviceTypes'));
    }
}
