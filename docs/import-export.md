# Store Locator Import/Export

This document describes the import/export functionality added in version 0.2.1 of the Store Locator List plugin.

## Overview

The import/export functionality allows administrators to:
- Export all store data to Excel format (.xlsx)
- Import store data from Excel files
- Update existing stores or add new ones
- Maintain data integrity with record key management

## Installation Requirements

### PhpSpreadsheet Library

The import/export functionality requires the PhpSpreadsheet library. Install it using Composer:

```bash
cd storelocator-list/
composer install
```

Or manually install:

```bash
composer require phpoffice/phpspreadsheet
```

## Accessing the Import/Export Feature

1. Log into your WordPress admin dashboard
2. Navigate to **Store Locator → Import/Export**
3. The page will show three sections:
   - Export Stores
   - Import Stores  
   - Current Statistics

## Export Functionality

### Features
- **Excel Format**: Exports to `.xlsx` format for maximum compatibility
- **Complete Data**: Includes all store fields, metadata, categories, and timestamps
- **Record Keys**: Uses Store ID as primary key for future imports
- **Status Options**: Option to include draft/pending stores

### Export Process
1. Choose export format (Excel .xlsx)
2. Optionally include inactive stores (draft/pending)
3. Click "Export Stores"
4. Download the generated file

### Export File Structure
The exported Excel file contains the following columns:
- Store ID (Primary Key)
- Store Name
- Status (publish/draft/pending)
- Description
- Address
- Address 2
- City
- State
- ZIP Code
- Country
- Phone
- Email
- Website
- Hours
- Latitude
- Longitude
- Category
- Created Date
- Modified Date

## Import Functionality

### Features
- **Excel Compatibility**: Accepts both `.xlsx` and `.xls` files
- **Multiple Import Modes**:
  - **Update Mode**: Updates existing stores and adds new ones
  - **Add Only**: Skips existing stores, adds new ones only
  - **Replace Mode**: Deletes all existing stores and imports fresh data
- **Data Validation**: Validates required fields and data formats
- **Error Reporting**: Detailed error reporting for failed imports
- **Category Handling**: Automatically creates missing categories

### Import Process
1. Select an Excel file (.xlsx or .xls)
2. Choose import mode:
   - **Update existing stores and add new ones** (Recommended)
   - **Add new stores only (skip existing)**
   - **Replace all stores (delete existing first)** ⚠️ Destructive
3. Click "Import Stores"
4. Review the import results

### Import File Requirements
- **Required Columns**: Store Name, Address, City
- **File Format**: Excel (.xlsx or .xls)
- **File Size**: Maximum 10MB
- **Encoding**: UTF-8 recommended

### Record Key Management
- **Primary Key**: Store ID (WordPress post ID)
- **Update Detection**: Automatically detects existing stores for updates
- **Data Integrity**: Maintains relationships and metadata during updates

## Security Features

- **Admin-Only Access**: Requires `manage_options` capability
- **Nonce Verification**: CSRF protection for all operations
- **File Validation**: Strict file type and size validation
- **Data Sanitization**: All imported data properly sanitized

## Error Handling

### Common Errors
1. **PhpSpreadsheet not installed**: Install via Composer
2. **Invalid file format**: Use .xlsx or .xls files only
3. **Missing required columns**: Ensure Store Name, Address, City are present
4. **File too large**: Maximum 10MB file size
5. **Permission denied**: User must have admin privileges

### Import Errors
- Individual row errors are reported with row numbers
- Partial imports are supported (valid rows processed, invalid rows skipped)
- Detailed error messages help identify and fix issues

## Best Practices

### Before Import
1. **Backup your data**: Always export current data before importing
2. **Test with small files**: Start with a few records to test the process
3. **Validate your data**: Ensure required fields are populated
4. **Check file format**: Use files exported from this system when possible

### Data Management
1. **Use Store IDs**: Include Store ID column for updating existing records
2. **Consistent formatting**: Use consistent date, phone, and address formats
3. **Category names**: Use exact category names (case-sensitive)
4. **Required fields**: Always include Store Name, Address, and City

### Performance
1. **File size**: Keep files under 10MB for best performance
2. **Batch processing**: For large datasets, consider splitting into smaller files
3. **Server resources**: Large imports may take time and use server resources

## Troubleshooting

### Installation Issues
```bash
# If Composer is not available
curl -sS https://getcomposer.org/installer | php
php composer.phar install

# Or download Composer globally
# See: https://getcomposer.org/doc/00-intro.md
```

### File Upload Issues
- Check WordPress upload limits in `php.ini`:
  - `upload_max_filesize`
  - `post_max_size`
  - `max_execution_time`

### Memory Issues
- Increase PHP memory limit for large files
- Consider processing large datasets in smaller batches

## File Locations

### Admin Files
- `includes/admin/class-sllist-import-export.php` - Main functionality
- `assets/css/import-export.css` - Admin styling
- `assets/js/import-export.js` - Admin JavaScript

### Dependencies
- `vendor/phpoffice/phpspreadsheet/` - Excel processing library
- `composer.json` - Dependency management

## Changelog

### Version 0.2.1
- Added Excel import/export functionality
- Added PhpSpreadsheet dependency
- Added admin menu integration
- Added comprehensive error handling
- Added multiple import modes
- Added data validation and sanitization
