<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedMessage extends Model
{
    protected $fillable = [
        'message_id',
        'sender_id',
        'conversation_id',
        'text',
    ];

    public function conversation()
    {
        return $this->belongsTo(SavedConversations::class, 'conversation_id');
    }
}
