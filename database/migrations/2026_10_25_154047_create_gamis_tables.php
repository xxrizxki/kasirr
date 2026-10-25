<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 20)->unique();
            $t->string('nama', 50);
            $t->unsignedTinyInteger('urutan')->default(0);
            $t->timestamps();
        });

        Schema::create('sizes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->string('nama', 20);
            $t->unsignedInteger('harga');
            $t->unsignedTinyInteger('urutan')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['category_id', 'nama']);
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('kode_pesanan', 40)->unique();
            $t->string('nama_pembeli', 100)->nullable();
            $t->unsignedInteger('total_item')->default(0);
            $t->unsignedBigInteger('total_harga')->default(0);
            $t->enum('status', ['selesai', 'batal'])->default('selesai');
            $t->string('catatan', 255)->nullable();
            $t->timestamp('dibatalkan_pada')->nullable();
            $t->timestamps();
            $t->index('created_at');
            $t->index('status');
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('size_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('qty');
            $t->unsignedInteger('harga_satuan');
            $t->unsignedBigInteger('subtotal');
            $t->unique(['order_id', 'size_id']);
        });

        Schema::create('price_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('size_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('harga_lama');
            $t->unsignedInteger('harga_baru');
            $t->timestamp('diubah_pada')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('categories');
    }
};
