# File Import Processor

## Requirements

- PHP 8.1 or higher
- Laravel 10.x
- MySQL 5.7+ or compatible database
- Composer

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/file-import.git
   cd file-import
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy the environment file and configure your database:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Run migrations:
   ```bash
   php artisan migrate
   ```

## Usage

### Importing Files

To import a file, use the following command:

```bash
php artisan import:file resources/PAYARC_DDF import_1
```

### File Format Specification

Create a CSV file named `import_file_specs.csv` in the `resources` directory with the following columns:

1. Field Name
2. Start Position
3. End Position
4. Field Length
5. Description
6. Record Type

## Database Schema

The system uses the following tables:

- `file_processing_log` - Tracks file imports and processing status
- `file_records` - Stores individual records from processed files
- `field_definitions` - Defines the structure of the imported files
- `field_values` - Stores the actual field values from the imported records

## Error Handling

- Failed imports are logged with detailed error messages
- The system tracks the number of processed, skipped, and failed records
- Detailed logs are available in the `file_processing_log` table

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
