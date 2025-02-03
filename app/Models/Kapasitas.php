<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class Kapasitas extends Model
{
    use HasFactory;
    use Uuid;

    protected $fillable = [
        'kategori',
        'kota',
        'nama_bioskop',
        'type_tiket',
        'studio',
        'kapasitas'
    ];

    public function Categories(){
        return $this->belongsTo(KategoriBioskop::class, 'kategori', 'uuid');
    }

    public function Cinemas(){
        return $this->belongsTo(MasterBioskop::class, 'nama_bioskop', 'uuid');
    }

    public function TypeTiket(){
        return $this->belongsTo(TypeTiket::class, 'type_tiket', 'uuid');
    }
}
