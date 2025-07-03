<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldValue extends Model
{
    protected $table = 'field_values';
    public $timestamps = false;

    protected $fillable = [
        'file_record_id',
        'field_name',
        'field_value',
        'start_position',
        'end_position',
        'data_type',
        'validation_status',
        'validation_errors',
        'processed_at'
    ];

    protected $casts = [
        'start_position' => 'integer',
        'end_position' => 'integer',
        'validation_errors' => 'array',
        'processed_at' => 'datetime',
    ];

    public function fileRecord(): BelongsTo
    {
        return $this->belongsTo(FileRecord::class);
    }
}
