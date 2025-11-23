-- Migration SQL: Add file_content column to documents table
-- Run this in phpMyAdmin or MySQL command line

-- Add file_content column as LONGBLOB to store documents in database
ALTER TABLE documents ADD COLUMN file_content LONGBLOB NULL AFTER file_path;

-- Verify the column was added
-- SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'documents' AND COLUMN_NAME = 'file_content';

