<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanPesananRequest;
use App\Models\Order;
use App\Services\PesananGagalException;
use App\Services\PesananService;
use App\Support\PesanError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PesananController extends Controller
{
    public function __construct(protected PesananService $service) {}

    public function store(SimpanPesananRequest $request): JsonResponse
    {
        try {
            $order = $this->service->simpan(
                $request->nama_pembeli,
                $request->catatan,
                $request->items
            );
            return response()->json([
                'ok' => true,
                'kode' => $order->kode_pesanan,
                'total' => $order->total_harga
            ]);
        } catch (PesananGagalException $e) {
            return response()->json(['ok' => false, 'pesan' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request)
    {
        $q = Order::with('items.size.category')->latest();
        
        if ($request->filled('dari'))   $q->whereDate('created_at', '>=', $request->dari);
        if ($request->filled('sampai')) $q->whereDate('created_at', '<=', $request->sampai);
        if (in_array($request->status, ['selesai', 'batal'], true)) $q->where('status', $request->status);
        
        $orders = $q->paginate(15)->withQueryString();
        
        return view('pesanan.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items.size.category');
        return view('pesanan.show', compact('order'));
    }

    public function batal(Request $request, Order $order): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'alasan' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors(['server' => $validator->errors()->first()])->withInput();
        }
        
        try {
            $this->service->batalkan($order, $request->input('alasan'));
            return back()->with('pesan', 'Pesanan ' . $order->kode_pesanan . ' dibatalkan');
        } catch (\Throwable $e) {
            return back()->withErrors(['server' => PesanError::dari($e)]);
        }
    }
}
