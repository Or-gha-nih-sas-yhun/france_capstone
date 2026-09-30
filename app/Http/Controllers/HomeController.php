<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Login, logout and "back to store" links all land on "/"; inside the app that
        // must be the same page the app's Home tab opens, not the web variant.
        if ($this->isMobileApp()) {
            return $this->mobile();
        }

        if (request()->has('fcm_token')) {
            session(['fcm_token' => request()->query('fcm_token')]);
        }

        return view('home.index', [
            'products' => $this->catalogProducts(),
            'hideAppDownload' => null,
            'hideStaffLinks' => null,
        ]);
    }

    public function mobile()
    {
        if (request()->has('fcm_token')) {
            session(['fcm_token' => request()->query('fcm_token')]);
        }

        session(['is_mobile_app' => true]);

        return view('home.index', [
            'products' => $this->catalogProducts(),
            'hideStaffLinks' => true,
            'hideAppDownload' => true,
            'inquirySource' => 'mobile',
        ]);
    }

    /**
     * The app starts loading the store immediately and hands over its push token
     * here once Firebase returns it, instead of delaying the first page load.
     */
    public function storeFcmToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string|max:255',
        ]);

        session(['fcm_token' => $validated['fcm_token']]);

        return response()->noContent();
    }

    private function isMobileApp(): bool
    {
        return session('is_mobile_app', false)
            || str_contains(request()->userAgent() ?? '', 'MerasUserApp');
    }

    private function catalogProducts()
    {
        return Product::orderBy('name')->limit(500)->get();
    }
}
