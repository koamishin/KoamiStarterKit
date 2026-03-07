<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('feature');
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_features');
    }
};
