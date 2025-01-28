<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class Pelaporan extends Model
{
    use HasFactory;
    use Uuid;

    protected $fillable = [
        'kategori',
        'kota',
        'nama_bioskop',
        'nama_film',
        'tgl_tayang',
        'jam_tayang',
        'show',
        'type_tiket',
        'harga',
        'jumlah',
        'gross',
        'tax',
        'net',
        'created_by',
        'edited_by'
    ];

    public function userCreate() {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }

    public function userEdit() {
        return $this->belongsTo(User::class, 'edited_by', 'uuid');
    }

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
