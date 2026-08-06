<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterFilm extends Model
{
    use HasFactory;
    use Uuid;

    protected $fillable = [
        'name',
        'tgl_tayang',
        'created_by',
        'edited_by',
    ];

    protected $casts = [
        'tgl_tayang' => 'date',
    ];

    public static function options()
    {
        return static::query()
            ->orderBy('name')
            ->pluck('name', 'name');
    }

    public static function normalizeName($name)
    {
        return mb_strtoupper(trim((string) $name));
    }
}
