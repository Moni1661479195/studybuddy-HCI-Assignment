ALTER TABLE quick_match_queue
ADD COLUMN desired_skill_level VARCHAR(50) DEFAULT 'any',
ADD COLUMN desired_gender VARCHAR(50) DEFAULT 'any';