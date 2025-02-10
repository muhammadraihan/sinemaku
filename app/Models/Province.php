<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class Province extends Model
{
    use HasFactory;
    use Uuid;

    protected $fillable = [
        'province_name', 'province_code'
    ];

    protected static $logAttributes = ['*'];

    protected static $logName = 'province';

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Data has been {$eventName}";
    }
}
