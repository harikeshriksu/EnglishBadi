-- English Badi - Round 2 change request
-- Adds the "Popular content" feature: an is_popular flag on each of the
-- four content types, checked from their admin edit forms, read by the
-- homepage's Popular content section.
--
-- HOW TO RUN: open phpMyAdmin on Hostinger, select your English Badi
-- database, open the "SQL" tab, paste this whole file, and click Go.
-- Safe to run once. Running it a second time will fail with a harmless
-- "column already exists" / "duplicate key name" error - that just means
-- it already applied, nothing more to do.

ALTER TABLE lessons ADD COLUMN is_popular TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE links   ADD COLUMN is_popular TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE posters ADD COLUMN is_popular TINYINT(1) NOT NULL DEFAULT 0 AFTER alt_text;
ALTER TABLE quizzes ADD COLUMN is_popular TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

ALTER TABLE lessons ADD INDEX idx_popular (is_popular);
ALTER TABLE links   ADD INDEX idx_popular (is_popular);
ALTER TABLE posters ADD INDEX idx_popular (is_popular);
ALTER TABLE quizzes ADD INDEX idx_popular (is_popular);
