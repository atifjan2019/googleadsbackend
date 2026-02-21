<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = ['client_id', 'campaign_id', 'content', 'type'];
}
