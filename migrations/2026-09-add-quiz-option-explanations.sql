-- English Badi - change request
-- Lets each individual MCQ answer option carry its own explanation,
-- shown to the learner based on which option they picked (instead of
-- one generic explanation for the whole question).
--
-- HOW TO RUN: open phpMyAdmin on Hostinger, select your English Badi
-- database, open the "SQL" tab, paste this whole file, and click Go.
-- Safe to run once. Running it a second time will fail with a harmless
-- "column already exists" error - that just means it already applied,
-- nothing more to do.

ALTER TABLE quiz_options ADD COLUMN explanation TEXT NULL AFTER is_correct;
