<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PesananGagalException extends \Exception {}

class PesananService
{
    /** @param array<int,array{size_id:int,qty:int}> $items */
    public function simpan(?string $nama, ?string $catatan, array $items): Order
    {
        $gabung = collect($items)->groupBy('size_id')
            ->map(fn ($g, $id) => ['size_id' => (int) $id, 'qty' => (int) $g->sum('qty')])
            ->values()->all();

        try {
            DB::statement('CALL sp_simpan_pesanan(?, ?, ?, @order_id)', [
                $nama, $catatan, json_encode($gabung),
            ]);
        } catch (QueryException $e) {
            throw new PesananGagalException($e->errorInfo[2] ?? 'Gagal menyimpan pesanan', 0, $e);
        }

        $id = DB::selectOne('SELECT @order_id AS id')->id;
        return Order::with('items.size.category')->findOrFail($id);
    }

    public function batalkan(Order $order, ?string $alasan): void
    {
        DB::statement('CALL sp_batalkan_pesanan(?, ?)', [$order->id, $alasan]);
    }

    public function rekap(string $dari, string $sampai): array
    {
        return DB::select('CALL sp_rekap_harian(?, ?)', [$dari, $sampai]);
    }
}
