-- English Badi - change request
-- Fills in the real social links (Instagram, YouTube, Facebook, Twitter/X,
-- Telegram) now that they've been provided. Same effect as typing them
-- into Admin > Settings and clicking Save - use whichever is easier.
--
-- HOW TO RUN: open phpMyAdmin on Hostinger, select your English Badi
-- database, open the "SQL" tab, paste this whole file, and click Go.
-- Safe to run more than once.

INSERT INTO settings (setting_key, setting_value) VALUES
  ('social_instagram', 'https://www.instagram.com/englishbadi'),
  ('social_youtube', 'https://youtube.com/@englishbadi'),
  ('social_facebook', 'https://facebook.com/myenglishbadi'),
  ('social_twitter', 'https://twitter.com/englishbadi'),
  ('social_telegram', 'https://t.me/englishbadi')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
