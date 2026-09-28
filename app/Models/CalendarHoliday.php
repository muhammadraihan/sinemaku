<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class CalendarHoliday extends Model
{
    use HasFactory;
    use Uuid;

    protected $table = 'calendar_holidays';

    protected $fillable = [
        'uuid',
        'holiday_date',
        'name',
        'active',
        'created_by',
        'edited_by',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'active' => 'boolean',
    ];
}
