<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldDefinition extends Model
{
    protected $table = 'field_definitions';
    public $timestamps = false;

    protected $fillable = [
        'file_processing_log_id',
        'record_type',
        'start_range',
        'end_range',
        'length',
        'description'
    ];

    protected $casts = [
        'start_range' => 'integer',
        'end_range' => 'integer',
        'length' => 'integer',
        'file_processing_log_id' => 'integer',
    ];

    public function fileProcessingLog(): BelongsTo
    {
        return $this->belongsTo(FileProcessingLog::class);
    }
}
