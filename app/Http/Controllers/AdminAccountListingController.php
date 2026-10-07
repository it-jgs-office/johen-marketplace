<?php

namespace App\Http\Controllers;

use App\Models\AccountListing;
use App\Models\Brand;
use App\Jobs\RunJohenGamingSync;
use App\Services\ImageOptimizer;
use App\Services\JohenGamingSyncService;
use App\Services\MediaStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminAccountListingController extends Controller
{
    public function index()
    {
        $listings = AccountListing::orderBy('game')->orderBy('product_name')->paginate(20);
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $syncStatus = Cache::get(RunJohenGamingSync::STATUS_KEY);

        return view('admin.account-listings.index', compact('listings', 'brands', 'syncStatus'));
    }

    public function create()
    {
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('admin.account-listings.create', compact('brands'));
    }

    public function store(Request $request)
    {
        $validator = validator($request->all(), [
            'game' => 'required|string',
            'product_name' => 'required|string',
            'specifications' => 'required|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'owner_name' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'promo_type' => 'nullable|string|in:none,promo,flash_sale,diskon,best_seller,hot,new,limited',
            'discount_percent' => 'nullable|integer|min:1|max:100',
            'collector_tier' => 'nullable|string|in:ternama,terhormat,juragan,sultan',
            'deal_type' => 'nullable|string|in:normal,bundle',
            'is_sold' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_4' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'video_url' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()->all()], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [
            'game' => $request->game,
            'product_name' => $request->product_name,
            'specifications' => $request->specifications,
            'price' => $request->price,
            'original_price' => $request->original_price,
            'owner_name' => $request->owner_name,
            'whatsapp' => $request->whatsapp,
            'promo_type' => $request->promo_type ?? 'none',
            'discount_percent' => $request->promo_type === 'diskon' ? $request->discount_percent : null,
            'collector_tier' => $request->collector_tier ?: null,
            'deal_type' => $request->deal_type ?: 'normal',
            'is_sold' => $request->boolean('is_sold', false),
            'is_active' => true,
            'video_url' => $request->video_url,
        ];

        foreach (['photo', 'detail_photo_1', 'detail_photo_2', 'detail_photo_3', 'detail_photo_4'] as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                $data[$field] = ImageOptimizer::storeOptimized($request->file($field), 'account-listings', 1600, 1600);
            }
        }

        AccountListing::create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Listing akun berhasil ditambahkan']);
        }

        return redirect()->route('admin.account-listings')->with('success', 'Listing akun berhasil ditambahkan');
    }

    public function edit(AccountListing $accountListing)
    {
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('admin.account-listings.edit', compact('accountListing', 'brands'));
    }

    public function update(Request $request, AccountListing $accountListing)
    {
        $validator = validator($request->all(), [
            'game' => 'required|string',
            'product_name' => 'required|string',
            'specifications' => 'required|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'owner_name' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'promo_type' => 'nullable|string|in:none,promo,flash_sale,diskon,best_seller,hot,new,limited',
            'discount_percent' => 'nullable|integer|min:1|max:100',
            'collector_tier' => 'nullable|string|in:ternama,terhormat,juragan,sultan',
            'deal_type' => 'nullable|string|in:normal,bundle',
            'is_sold' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_4' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'video_url' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [
            'game' => $request->game,
            'product_name' => $request->product_name,
            'specifications' => $request->specifications,
            'price' => $request->price,
            'original_price' => $request->original_price,
            'owner_name' => $request->owner_name,
            'whatsapp' => $request->whatsapp,
            'promo_type' => $request->promo_type ?? 'none',
            'discount_percent' => $request->promo_type === 'diskon' ? $request->discount_percent : null,
            'collector_tier' => $request->collector_tier ?: null,
            'deal_type' => $request->deal_type ?: 'normal',
            'is_sold' => $request->boolean('is_sold', false),
            'is_active' => $request->boolean('is_active', true),
            'video_url' => $request->video_url,
        ];

        foreach (['photo', 'detail_photo_1', 'detail_photo_2', 'detail_photo_3', 'detail_photo_4'] as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                if ($accountListing->$field) {
                    MediaStore::delete($accountListing->$field);
                }
                $data[$field] = ImageOptimizer::storeOptimized($request->file($field), 'account-listings', 1600, 1600);
            }
        }

        $accountListing->update($data);

        return redirect()->route('admin.account-listings')->with('success', 'Listing akun berhasil diperbarui');
    }

    public function sync(Request $request)
    {
        $game = $request->query('game');
        if ($game !== null && ! in_array($game, JohenGamingSyncService::gameSlugsSupported(), true)) {
            return back()->with('error', 'Kategori game tidak dikenal.');
        }

        if (! Cache::add(RunJohenGamingSync::RUNNING_KEY, true, now()->addMinutes(30))) {
            return back()->with('error', 'Sinkronisasi masih berjalan. Tunggu hingga selesai.');
        }

        Cache::put(RunJohenGamingSync::STATUS_KEY, [
            'state' => 'running',
            'started_at' => now()->toDateTimeString(),
        ], now()->addMinutes(30));

        RunJohenGamingSync::dispatchAfterResponse($game);

        return back()->with('success', 'Sinkronisasi dimulai. Status dan hasilnya akan tampil di halaman ini.');
    }

    public function syncStatus()
    {
        return response()->json(Cache::get(RunJohenGamingSync::STATUS_KEY, ['state' => 'idle']));
    }

    public function toggle(AccountListing $accountListing)
    {
        $accountListing->update(['is_active' => ! $accountListing->is_active]);

        return back()->with('success', 'Status listing akun berhasil diubah');
    }

    public function destroy(AccountListing $accountListing)
    {
        foreach (['photo', 'detail_photo_1', 'detail_photo_2', 'detail_photo_3', 'detail_photo_4'] as $field) {
            if ($accountListing->$field) {
                MediaStore::delete($accountListing->$field);
            }
        }
        $accountListing->delete();

        return redirect()->route('admin.account-listings')->with('success', 'Listing akun berhasil dihapus');
    }
}
