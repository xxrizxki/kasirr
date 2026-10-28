<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class KasirStructureTest extends TestCase
{
    public function test_bar_element_is_inside_x_data_kasir()
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get('/');
        
        $response->assertStatus(200);
        
        // Parse HTML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        
        // Cari div dengan class "bar"
        $barElements = $xpath->query("//*[@class='bar']");
        $this->assertGreaterThan(0, $barElements->length, 'Elemen .bar tidak ditemukan');
        
        // Ambil elemen bar pertama
        $barElement = $barElements->item(0);
        
        // Periksa parent-nya punya x-data yang diawali "kasir("
        $parent = $barElement->parentNode;
        $foundKasirDiv = false;
        
        while ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
            $xDataAttr = $parent->getAttribute('x-data');
            if ($xDataAttr && strpos($xDataAttr, 'kasir(') === 0) {
                $foundKasirDiv = true;
                break;
            }
            $parent = $parent->parentNode;
        }
        
        $this->assertTrue(
            $foundKasirDiv,
            'Elemen .bar harus berada di dalam div x-data="kasir(...)"'
        );
    }
}
