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
        Schema::table('network_nodes', function (Blueprint $table) {
            $table->boolean('is_busbar')->default(false)->after('h');
            $table->string('color')->nullable()->after('is_busbar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('network_nodes', function (Blueprint $table) {
            $table->dropColumn('is_busbar');
            $table->dropColumn('color');
        });
    }
};
