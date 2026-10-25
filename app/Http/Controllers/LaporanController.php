<?php

namespace App\Http\Controllers;

use App\Services\PesananService;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function __construct(protected PesananService $service) {}

    public function index()
    {
        $dari = request('dari', Carbon::today()->subDays(30)->toDateString());
        $sampai = request('sampai', Carbon::today()->toDateString());
        
        $rekap = $this->service->rekap($dari, $sampai);
        
        return view('laporan', compact('rekap', 'dari', 'sampai'));
    }
}
