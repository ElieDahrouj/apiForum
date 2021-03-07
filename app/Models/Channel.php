<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    protected $hidden =[
        "created_at",
        "updated_at",
    ];

    public function thread(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Thread::class);
    }
}
