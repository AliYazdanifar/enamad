<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainEmailWithDetails extends Model
{
    use HasFactory;

    protected $fillable = ['domain', 'category', 'email', 'phone', 'address', 'services'];

}
