<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flex_field_schema_heads', function (Blueprint $table): void {
            $table->string('schema_id')->primary();
            $table->unsignedInteger('last_version')->default(0);
            $table->unsignedInteger('live_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flex_field_schema_heads');
    }
};
