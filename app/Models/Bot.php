<?php

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Database\Eloquent\Model;

/**
 * @inheritDoc
 * @property bool $maintenance
 */
class Bot extends TelegraphBot
{
    protected $table = 'telegraph_bots';
    protected $fillable = [
        'token',
        'name',
        'maintenance'
    ];
}
