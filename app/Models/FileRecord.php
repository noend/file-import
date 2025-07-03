<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileRecord extends Model
{
    protected $table = 'file_records';
    public $timestamps = false;

    protected $fillable = [
        'record_type',
        'file_processing_log_id',
        'line_number',
        'raw_line_data',
        'processed_at'
    ];

    protected $casts = [
        'line_number' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function fileProcessingLog(): BelongsTo
    {
        return $this->belongsTo(FileProcessingLog::class);
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }
}
