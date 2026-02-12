<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsinsData extends Model
{
    protected $fillable = [
        'asin',
        'manufacturer',
        'responsible',
    ];

    protected function casts(): array
    {
        return [
            'asin' => 'string',
            'manufacturer' => 'array',
            'responsible' => 'array',
        ];
    }
}
