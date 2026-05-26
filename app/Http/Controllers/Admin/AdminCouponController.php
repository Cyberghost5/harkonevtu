<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::withCount('redemptions')->latest();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $coupons = $query->paginate(20)->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'       => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'amount'     => ['required', 'numeric', 'min:1'],
            'max_uses'   => ['required', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'is_active'  => ['boolean'],
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active']  = $request->boolean('is_active', true);

        Coupon::create($validated);

        return back()->with('success', 'Coupon created successfully.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'max_uses'   => ['sometimes', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'is_active'  => ['boolean'],
        ]);

        $coupon->update($validated);

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return back()->with('success', 'Coupon deleted.');
    }
}
