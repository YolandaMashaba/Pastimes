-- Add brand column to tblclothes table
ALTER TABLE tblclothes ADD COLUMN brand VARCHAR(255) DEFAULT NULL AFTER description;
