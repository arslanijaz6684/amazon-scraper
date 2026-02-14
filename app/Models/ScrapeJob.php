<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapeJob extends Model
{
    protected $fillable = ['asins', 'status','response'];
}
