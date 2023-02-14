<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedConversations extends Model
{
    protected $fillable = [
        'chat_id',
    ];

    public function messages()
    {
        return $this->hasMany(SavedMessage::class, 'conversation_id');
    }

    public function history()
    {
        return $this->messages()->orderBy('message_id')->get()->map(function ($message) {
            return ($message->sender_id === 0 ? ' BOT:' : ' USER:') . $message->text;
        })->implode(" ");
    }
}
