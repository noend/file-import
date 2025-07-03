<?php

namespace App\Console\Commands;

use App\Models\FieldDefinition;
use App\Models\FieldValue;
use App\Models\FileProcessingLog;
use App\Models\FileRecord;
use App\Services\FileImporter;
use Exception;
use Illuminate\Console\Command;

class ImportFileCommand extends Command
{
    const string IMPORT_FILE_SPECS_CSV = 'import_file_specs.csv';
    protected $signature = 'import:file {path_to_file : Path to the file to import} {id : Unique identifier for the file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from a fixed-width text file to the database';

    protected $fileLog;
    protected array $specsMap = [];
    protected string|array|bool|null $filePath;
    protected string|array|bool|null $fileImportId;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->filePath = $this->argument('path_to_file');
        $this->fileImportId = $this->argument('id');

        if (!file_exists($this->filePath)) {
            $this->error("File not found: {$this->filePath}");
            return 1;
        }

        try {

            $this->startFileProcessing();

            $this->getSpecsFromCsv(self::IMPORT_FILE_SPECS_CSV);

        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $this->processFile();

        $this->completeFileProcessing();

        return 0;
    }

    /**
     * @throws Exception
     */
    protected function startFileProcessing(): void
    {
        $this->info("Starting import process for file: {$this->filePath}");

        $fileName = basename($this->filePath);

        $this->fileLog = FileProcessingLog::create([
            'file_import_id' => $this->fileImportId,
            'file_name' => $fileName,
            'file_path' => $this->filePath,
            'file_size' => filesize($this->filePath),
            'status' => 'PROCESSING',
            'started_at' => now(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function getSpecsFromCsv($csvFileName): void
    {
        $csvPath = resource_path($csvFileName);

        if (!file_exists($csvPath)) {
            throw new Exception("Specification file not found: {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');

        // Skip header row
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 6) continue;

            $startRange = (int)$row[1];
            $endRange = (int)$row[2];
            $length = (int)$row[3];
            $description = $row[4] ?? '';
            $recordType = trim($row[5]);

            $this->specsMap[$recordType][] = FieldDefinition::firstOrCreate([
                'file_processing_log_id' => $this->fileLog->id,
                'record_type' => $recordType,
                'start_range' => $startRange,
                'end_range' => $endRange,
                'length' => $length,
                'description' => $description
            ]);
        }

        fclose($handle);
    }

    protected function processFile(): void
    {
        $handle = fopen($this->filePath, 'r');
        $lineNumber = 0;

        $existingImports = $this->getExistingImportIds();


        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = rtrim($line, "\r\n");

            if (empty(trim($line))) {
                continue;
            }

            // Extract record type
            $recordType = substr($line, 17, 2);

            if (count($existingImports) === 1) {
                $isRecordExists = false;
            } else {
                $isRecordExists = FileRecord::whereIn('file_processing_log_id', $existingImports)
                    ->where('raw_line_data', $line)->exists();
            }

            if ($isRecordExists) {
                $this->warn("Skipping duplicate record type '{$recordType}' on line {$lineNumber}");
                $this->fileLog->increment('skipped_records');
                $this->fileLog->increment('total_records');
                continue;
            }

            if (!isset($this->specsMap[$recordType])) {
                $this->warn("Skipping unknown record type '{$recordType}' on line {$lineNumber}");
                $this->fileLog->increment('failed_records');
                $this->fileLog->increment('total_records');
                continue;
            }

            try {
                $fileRecord = $this->createFileRecord($line, $lineNumber, $recordType);
                $processedRecord = $this->processLineRecord($line, $recordType, $fileRecord->id);
                $this->saveLineRecordValues($processedRecord);

                $this->fileLog->increment('processed_records');
                $this->fileLog->increment('total_records');
            } catch (Exception $e) {
                $this->error("Error processing line {$lineNumber}: " . $e->getMessage());
                $this->fileLog->increment('failed_records');
                $this->fileLog->increment('total_records');
            }
        }

        fclose($handle);
    }
    protected function processLineRecord($line, $recordType, $fileRecordId): array
    {
        $fieldValues = [];

        // Parse each field according to the spec map
        foreach ($this->specsMap[$recordType] as $spec) {
            $value = substr($line, $spec['start_range'] - 1, $spec['length']);

            $fieldValues[] = [
                'record_id' => $fileRecordId,
                'field_definition_id' => $spec['id'],
                'field_value' => $value,
            ];
        }

        return $fieldValues;
    }

    protected function createFileRecord(string $line, int $lineNumber, string $recordType)
    {
        return FileRecord::create([
            'file_processing_log_id' => $this->fileLog->id,
            'line_number' => $lineNumber,
            'record_type' => $recordType,
            'raw_line_data' => $line,
        ]);
    }

    protected function saveLineRecordValues(array $processedRecord): void
    {
        FieldValue::insert($processedRecord);
    }

    protected function completeFileProcessing(): void
    {
        $this->fileLog->update([
            'status' => 'COMPLETED',
            'completed_at' => now()
        ]);

        $this->info("Import completed successfully!");
        $this->info("Total records processed: {$this->fileLog->processed_records}");
        $this->info("Skipped records: {$this->fileLog->skipped_records}");
        $this->info("Records failed: {$this->fileLog->failed_records}");
    }

    /**
     * @return mixed
     */
    private function getExistingImportIds()
    {
        $fileName = basename($this->filePath);

        return FileProcessingLog::where('file_name', $fileName)
            ->where('file_import_id', $this->fileImportId)
            ->select('id')
            ->get()
            ->pluck('id');
    }
}
