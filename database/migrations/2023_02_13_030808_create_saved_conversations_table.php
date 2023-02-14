<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('saved_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chat_id');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_conversations');
    }
};
