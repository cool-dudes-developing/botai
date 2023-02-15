<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('telegraph_bots', function (Blueprint $table) {
            $table->boolean('maintenance')->default(false);
        });
    }

    public function down()
    {
        Schema::table('telegraph_bots', function (Blueprint $table) {
            $table->dropColumn('maintenance');
        });
    }
};
