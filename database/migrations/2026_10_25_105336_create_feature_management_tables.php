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
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('stage')->default('draft')->index();
            $table->boolean('enabled')->default(false);
            $table->boolean('default_value')->default(false);
            $table->boolean('user_controllable')->default(false);
            $table->unsignedTinyInteger('rollout_percentage')->default(100);
            $table->json('environments')->nullable();
            $table->json('target_roles')->nullable();
            $table->json('rules')->nullable();
            $table->json('tags')->nullable();
            $table->string('owner')->nullable();
            $table->string('release_version')->nullable()->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('feature_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_flag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->boolean('active');
            $table->timestamps();
            $table->unique(['feature_flag_id', 'user_id', 'kind']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('beta_opted_in_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_assignments');
        Schema::dropIfExists('feature_flags');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('beta_opted_in_at');
        });
    }
};
