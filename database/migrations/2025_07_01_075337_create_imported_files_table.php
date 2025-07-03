<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('file_processing_log', function (Blueprint $table) {
            $table->id();
            $table->string('file_import_id');
            $table->string('file_name')->index();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->unsignedInteger('skipped_records')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED'])->default('PENDING')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
        });

        Schema::create('file_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_type', 2)->index();
            $table->unsignedBigInteger('file_processing_log_id');
            $table->unsignedInteger('line_number')->nullable();
            $table->text('raw_line_data')->nullable();
            $table->timestamp('processed_at')->useCurrent()->index();
        });

        Schema::create('field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('file_records')->cascadeOnDelete();
            $table->unsignedInteger('field_definition_id', );
            $table->text('field_value')->nullable();
            $table->index(['record_id', 'field_definition_id']);
            $table->index('field_definition_id');
        });

        Schema::create('field_definitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_processing_log_id');
            $table->string('record_type', 2)->index();
            $table->unsignedInteger('start_range');
            $table->unsignedInteger('end_range');
            $table->unsignedInteger('length');
            $table->text('description')->nullable();
            $table->foreign('file_processing_log_id')
                ->references('id')
                ->on('file_processing_log')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_processing_log');
        Schema::dropIfExists('file_records');
        Schema::dropIfExists('field_values');
        Schema::dropIfExists('field_definitions');
    }
};
