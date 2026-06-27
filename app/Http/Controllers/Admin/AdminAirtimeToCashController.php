<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AirtimeToCashRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminAirtimeToCashController extends Controller
{
    public function index(Request $request): View
    {
        $query = AirtimeToCashRequest::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20)->withQueryString();

        return view('admin.airtime-to-cash.index', compact('requests'));
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $reqToCash = AirtimeToCashRequest::findOrFail($id);

        if ($reqToCash->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($reqToCash, $validated) {
            $user = $reqToCash->user;
            $wallet = $user->wallet;

            if (!$wallet) {
                $wallet = $user->wallet()->create([
                    'balance' => 0, 'total_funded' => 0, 'total_spent' => 0,
                ]);
            }

            $ref = 'ATC-APR-' . strtoupper(uniqid());

            $wallet->credit(
                (float) $reqToCash->receive_amount,
                "Airtime conversion approved: {$reqToCash->network} (₦" . number_format($reqToCash->amount, 2) . ")",
                $ref,
                ['source' => 'airtime_to_cash', 'request_id' => $reqToCash->id]
            );

            $reqToCash->update([
                'status'     => 'approved',
                'admin_note' => $validated['note'] ?? null,
            ]);
        });

        return back()->with('success', 'Airtime conversion request approved and wallet credited.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $reqToCash = AirtimeToCashRequest::findOrFail($id);

        if ($reqToCash->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $reqToCash->update([
            'status'     => 'rejected',
            'admin_note' => $validated['note'],
        ]);

        return back()->with('success', 'Airtime conversion request rejected.');
    }
}
