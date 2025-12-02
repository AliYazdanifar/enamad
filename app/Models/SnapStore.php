<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SnapStore extends Model
{
    use HasFactory;

    protected $fillable = ['name','slug','phone','website','address','raw'];
}
