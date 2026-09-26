<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class ReportUploadHistory extends Model
{
    use Uuid;

    protected $fillable = [
        'provider',
        'original_filename',
        'file_size',
        'status',
        'preview_rows',
        'imported_rows',
        'message',
        'uploaded_by',
        'completed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'preview_rows' => 'integer',
        'imported_rows' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'uuid');
    }
}
