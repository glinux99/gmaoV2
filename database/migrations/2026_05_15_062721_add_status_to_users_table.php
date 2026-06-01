<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['online', 'offline', 'busy', 'away'])->nullable()->default('offline');
            $table->timestamp('last_activity_at')->nullable();
            // avatar est souvent déjà présent, sinon ajoutez :
            // $table->string('avatar')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'last_activity_at']);
        });
    }
};
