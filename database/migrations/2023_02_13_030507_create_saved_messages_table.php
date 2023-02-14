<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('saved_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('message_id');
            $table->foreignId('sender_id');
            $table->foreignId('conversation_id');
            $table->longText('text');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_messages');
    }
};
