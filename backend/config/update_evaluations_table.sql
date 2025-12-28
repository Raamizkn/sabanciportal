-- Update evaluations table to support company evaluation fields
-- Run this on the database to add the new columns

ALTER TABLE evaluations 
ADD COLUMN IF NOT EXISTS program_satisfaction INT DEFAULT 0 COMMENT 'Rating 1-5',
ADD COLUMN IF NOT EXISTS student_impact INT DEFAULT 0 COMMENT 'Rating 1-5',
ADD COLUMN IF NOT EXISTS motivation INT DEFAULT 0 COMMENT 'Rating 1-5',
ADD COLUMN IF NOT EXISTS communication INT DEFAULT 0 COMMENT 'Rating 1-5 (renamed from communication_skills)',
ADD COLUMN IF NOT EXISTS timeliness INT DEFAULT 0 COMMENT 'Rating 1-5',
ADD COLUMN IF NOT EXISTS positive_attitude INT DEFAULT 0 COMMENT 'Rating 1-5',
ADD COLUMN IF NOT EXISTS teamwork INT DEFAULT 0 COMMENT 'Rating 1-5',
ADD COLUMN IF NOT EXISTS adaptation INT DEFAULT 0 COMMENT 'Rating 1-5',
ADD COLUMN IF NOT EXISTS digital_tools INT DEFAULT 0 COMMENT 'Rating 1-5',
ADD COLUMN IF NOT EXISTS participate_again VARCHAR(10) DEFAULT 'no' COMMENT 'yes/no',
ADD COLUMN IF NOT EXISTS recommend_program VARCHAR(10) DEFAULT 'no' COMMENT 'yes/no',
ADD COLUMN IF NOT EXISTS future_internship VARCHAR(10) DEFAULT 'no' COMMENT 'yes/no',
ADD COLUMN IF NOT EXISTS interview_interest VARCHAR(10) DEFAULT 'no' COMMENT 'yes/no',
ADD COLUMN IF NOT EXISTS redesign_suggestions TEXT COMMENT 'Open-ended feedback',
ADD COLUMN IF NOT EXISTS program_type VARCHAR(50) COMMENT 'rotation/project/department/hybrid',
ADD COLUMN IF NOT EXISTS overall_rating DECIMAL(3,2) DEFAULT 0.00;

-- Update existing rating column if it exists
UPDATE evaluations SET overall_rating = rating WHERE rating IS NOT NULL AND overall_rating = 0;

