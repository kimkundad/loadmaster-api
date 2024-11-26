<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rooms extends Model
{
    use HasFactory;

    protected $fillable = ['staff_id'];

    public function participants()
    {
        return $this->hasMany(RoomParticipants::class, 'room_id');
    }

    public function messages()
    {
        return $this->hasMany(Messages::class, 'room_id');
    }
}
