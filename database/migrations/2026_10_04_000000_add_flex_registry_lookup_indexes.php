<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flex_field_groups', function (Blueprint $table): void {
            $table->index(['target_type', 'order'], 'fff_groups_target_order');
            $table->index(['tenant_id', 'order'], 'fff_groups_tenant_order');
        });
        Schema::table('flex_field_schema_versions', function (Blueprint $table): void {
            $table->index(['schema_id', 'state', 'version'], 'fff_versions_schema_state');
        });
    }

    public function down(): void
    {
        Schema::table('flex_field_schema_versions', fn (Blueprint $table) => $table->dropIndex('fff_versions_schema_state'));
        Schema::table('flex_field_groups', function (Blueprint $table): void {
            $table->dropIndex('fff_groups_tenant_order');
            $table->dropIndex('fff_groups_target_order');
        });
    }
};
