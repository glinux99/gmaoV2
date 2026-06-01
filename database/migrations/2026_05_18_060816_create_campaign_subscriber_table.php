// database/migrations/xxxx_xx_xx_create_campaign_subscriber_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('campaign_subscriber', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscriber_id')->constrained()->onDelete('cascade');
            $table->primary(['campaign_id', 'subscriber_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_subscriber');
    }
};
