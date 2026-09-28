<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class CinemaTicketPrice extends Model
{
    use HasFactory;
    use Uuid;

    protected $table = 'cinema_ticket_prices';

    protected $fillable = [
        'uuid',
        'master_bioskop_uuid',
        'type_tiket_uuid',
        'weekday_price',
        'friday_price',
        'weekend_holiday_price',
        'valid_from',
        'valid_until',
        'active',
        'created_by',
        'edited_by',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'active' => 'boolean',
        'weekday_price' => 'decimal:2',
        'friday_price' => 'decimal:2',
        'weekend_holiday_price' => 'decimal:2',
    ];

    public function cinema()
    {
        return $this->belongsTo(MasterBioskop::class, 'master_bioskop_uuid', 'uuid');
    }

    public function ticketType()
    {
        return $this->belongsTo(TypeTiket::class, 'type_tiket_uuid', 'uuid');
    }

    public function scopeValidOn(Builder $query, $date): Builder
    {
        return $query->whereDate('valid_from', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date);
            });
    }

    public function scopeOverlapping(Builder $query, $validFrom, $validUntil = null): Builder
    {
        $query->where(function (Builder $query) use ($validFrom) {
            $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $validFrom);
        });

        if ($validUntil !== null) {
            $query->whereDate('valid_from', '<=', $validUntil);
        }

        return $query;
    }
}
