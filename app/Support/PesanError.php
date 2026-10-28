<?php

namespace App\Support;

use Throwable;
use Illuminate\Database\QueryException;

class PesanError
{
    public static function dari(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            if ($e->errorInfo[1] === 1644) {
                return $e->errorInfo[2];
            }
        }
        report($e);
        return 'Terjadi kesalahan. Coba lagi atau hubungi admin.';
    }
}
