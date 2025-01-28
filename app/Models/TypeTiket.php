<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class TypeTiket extends Model
{
    use HasFactory;
    use Uuid;

    protected $fillable = [
        'name', 'kategori','created_by', 'edited_by'
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
}
