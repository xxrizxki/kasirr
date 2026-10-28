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

    public function index()
    {
        $orders = Order::with('items.size.category')->latest()->paginate(15);
        return view('pesanan.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items.size.category');
        return view('pesanan.show', compact('order'));
    }

    public function batal(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'alasan' => 'nullable|string|max:100',
        ], [], [], ['server' => 'Validasi gagal']);
        
        try {
            $this->service->batalkan($order, $request->input('alasan'));
            return back()->with('pesan', 'Pesanan ' . $order->kode_pesanan . ' dibatalkan');
        } catch (\Throwable $e) {
            return back()->withErrors(['server' => PesanError::dari($e)]);
        }
    }
}
