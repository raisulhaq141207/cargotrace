<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'description',
        'quantity',
        'unit',
        'weight',
        'declared_value',
        'hs_code',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'weight' => 'float',
            'declared_value' => 'float',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}

