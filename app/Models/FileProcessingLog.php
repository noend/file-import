<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileProcessingLog extends Model
{
    protected $table = 'file_processing_log';
    public $timestamps = false;

    protected $fillable = [
        'file_import_id',
        'file_name',
        'file_path',
        'file_size',
        'total_records',
        'processed_records',
        'skipped_records',
        'failed_records',
        'status',
        'error_message',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
        'total_records' => 'integer',
        'skipped_records' => 'integer',
        'processed_records' => 'integer',
        'failed_records' => 'integer',
    ];

    public function fileRecords(): HasMany
    {
        return $this->hasMany(FileRecord::class);
    }
}
