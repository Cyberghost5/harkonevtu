<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundingRequest;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFundingController extends Controller
{
    public function index(Request $request)
    {
        $query = FundingRequest::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20)->withQueryString();

        return view('admin.funding.index', compact('requests'));
    }

    public function approve(Request $request, int $id)
    {
        $funding = FundingRequest::findOrFail($id);

        if (!$funding->isPending()) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($funding, $validated) {
            $wallet = $funding->user->wallet;

            if (!$wallet) {
                $wallet = $funding->user->wallet()->create([
                    'balance' => 0, 'total_funded' => 0, 'total_spent' => 0,
                ]);
            }

            $before = $wallet->balance;
            $wallet->increment('balance', $funding->amount);
            $wallet->increment('total_funded', $funding->amount);
            $wallet->refresh();

            $ref = 'MAN-APR-' . strtoupper(uniqid());

            WalletTransaction::create([
                'user_id'        => $funding->user_id,
                'wallet_id'      => $wallet->id,
                'type'           => 'credit',
                'amount'         => $funding->amount,
                'balance_before' => $before,
                'balance_after'  => $wallet->balance,
                'description'    => 'Manual funding approved by admin',
                'reference'      => $ref,
                'status'         => 'success',
            ]);

            $funding->update([
                'status'                       => 'approved',
                'admin_note'                   => $validated['note'] ?? null,
                'approved_by'                  => auth()->id(),
                'reviewed_at'                  => now(),
                'wallet_transaction_reference' => $ref,
            ]);
        });

        return back()->with('success', 'Funding request approved and wallet credited.');
    }

    public function reject(Request $request, int $id)
    {
        $funding = FundingRequest::findOrFail($id);

        if (!$funding->isPending()) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $funding->update([
            'status'      => 'rejected',
            'admin_note'  => $validated['note'],
            'approved_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Funding request rejected.');
    }
}
