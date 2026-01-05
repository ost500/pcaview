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
        // domestic_metal_prices -> metal_domestic_prices
        Schema::rename('domestic_metal_prices', 'metal_domestic_prices');

        // international_metal_prices -> metal_international_prices
        Schema::rename('international_metal_prices', 'metal_international_prices');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // metal_domestic_prices -> domestic_metal_prices
        Schema::rename('metal_domestic_prices', 'domestic_metal_prices');

        // metal_international_prices -> international_metal_prices
        Schema::rename('metal_international_prices', 'international_metal_prices');
    }
};
