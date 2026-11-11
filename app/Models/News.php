<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'summary',
        'content',
        'image_url',
        'author_id',
        'is_breaking',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_breaking'  => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public const CATEGORIES = [
        'PORT_ADVISORY'  => 'Port Advisory',
        'WEATHER_ALERT'  => 'Weather Alert',
        'REGULATORY'     => 'Maritime Regulation',
        'CARRIER_UPDATE' => 'Carrier Update',
        'TRADE_LANE'     => 'Trade Lane Intelligence',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . Str::random(5);
            }
            if (empty($model->published_at)) {
                $model->published_at = now();
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->where('published_at', '<=', now());
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? str_replace('_', ' ', $this->category);
    }
}

