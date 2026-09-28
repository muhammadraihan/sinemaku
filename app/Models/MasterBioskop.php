<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class MasterBioskop extends Model
{
    use HasFactory;
    use Uuid;

    protected $fillable = [
        'nama_bioskop','kota', 'no_telephone', 'type', 'created_by', 'edited_by', 'pajak'
    ];

    public function Categories(){
        return $this->belongsTo(KategoriBioskop::class, 'type', 'uuid');
    }

    public function ticketPrices()
    {
        return $this->hasMany(CinemaTicketPrice::class, 'master_bioskop_uuid', 'uuid');
    }

    public function capacities()
    {
        return $this->hasMany(Kapasitas::class, 'nama_bioskop', 'uuid');
    }

    public function userCreate() {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }

    public function userEdit() {
        return $this->belongsTo(User::class, 'edited_by', 'uuid');
    }
}
