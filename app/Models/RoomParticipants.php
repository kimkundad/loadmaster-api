<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomParticipants extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'customer_id'];

    public function room()
    {
        return $this->belongsTo(Rooms::class, 'room_id');
    }
}
