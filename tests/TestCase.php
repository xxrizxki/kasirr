<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function seeder()
    {
        return \Database\Seeders\DatabaseSeeder::class;
    }

    protected function setUpTraits()
    {
        $this->pastikanDatabaseTest();
        return parent::setUpTraits();
    }

    protected function pastikanDatabaseTest(): void
    {
        $koneksi = config('database.default');
        $nama    = config("database.connections.$koneksi.database");

        if ($koneksi !== 'mysql' || $nama !== 'hitung_gamis_test') {
            throw new \RuntimeException(
                "DIHENTIKAN: test mengarah ke koneksi '$koneksi', database '$nama'. Seharusnya mysql / hitung_gamis_test."
            );
        }
    }
}
