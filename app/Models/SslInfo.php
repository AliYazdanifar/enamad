<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SslInfo extends Model
{
    use HasFactory;
    protected $fillable=['domain','exp_date','issuer'];
}
