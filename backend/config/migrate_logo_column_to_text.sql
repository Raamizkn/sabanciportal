-- Migration: Change logo column from VARCHAR(255) to TEXT
-- Reason: Base64 data URLs can exceed 255 characters
-- Date: 2025-12-20

ALTER TABLE companies MODIFY COLUMN logo TEXT;

