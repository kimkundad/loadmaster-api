<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'sender_id', 'message'];

    public function room()
    {
        return $this->belongsTo(Rooms::class, 'room_id');
    }


}

