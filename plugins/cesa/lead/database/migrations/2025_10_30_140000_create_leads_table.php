<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone', 15)->unique();
            $table->text('address');
            $table->string('sales_person');
            $table->enum('store_team_position', ['Kepala Toko', 'Promotor', 'Kasir', 'Frontliner']);
            $table->string('store_branch');
            $table->enum('phone_transaction_range', [
                'Harga di bawah 2 juta',
                'Harga 2 - 3 juta',
                'Harga 3 - 4 juta',
                'Harga 4 - 7 juta',
                'Harga di atas 7 juta',
            ])->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['store_branch', 'created_at']);
            $table->index(['store_team_position', 'created_at']);
            $table->index(['sales_person', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
