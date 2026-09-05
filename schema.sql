-- =====================================================================
-- English Badi (englishbadi.com) - Database Schema
-- =====================================================================
-- Import this file once, in phpMyAdmin, into an EMPTY database.
-- Character set: utf8mb4 / collation utf8mb4_unicode_ci throughout so
-- that Telugu script stores and renders correctly everywhere.
--
-- After importing this file, visit /setup.php on your site to create
-- the first admin account. Do NOT create rows in the `users` table by
-- hand - setup.php takes care of correctly hashing the password.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- users : admin / teacher accounts (there is usually only one)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- login_attempts : used to rate-limit /admin login (5 fails = 15 min lock)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- learners : optional registered accounts for people using the site
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS learners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- password_resets : time-limited tokens for the "forgot password" flow
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  learner_id INT UNSIGNED NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_learner FOREIGN KEY (learner_id) REFERENCES learners(id) ON DELETE CASCADE,
  INDEX idx_token (token_hash),
  INDEX idx_learner (learner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categories : shared category list for lessons / links / posters
-- managed entirely from the admin (Settings > Categories)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('lesson','link','poster') NOT NULL,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  display_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_type_slug (type, slug),
  INDEX idx_type_order (type, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- lessons : text articles
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lessons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  category_id INT UNSIGNED NULL,
  featured_image VARCHAR(255) NULL,
  featured_image_webp VARCHAR(255) NULL,
  featured_thumb VARCHAR(255) NULL,
  featured_thumb_webp VARCHAR(255) NULL,
  excerpt TEXT NULL,
  body LONGTEXT NOT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  meta_description VARCHAR(300) NULL,
  publish_date DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_slug (slug),
  CONSTRAINT fk_lesson_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_status_date (status, publish_date),
  INDEX idx_category (category_id),
  FULLTEXT KEY ft_lesson_search (title, body)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- links : curated external resources (YouTube / Instagram / web / etc.)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  description TEXT NULL,
  url VARCHAR(500) NOT NULL,
  category_id INT UNSIGNED NULL,
  thumbnail VARCHAR(255) NULL,
  thumbnail_webp VARCHAR(255) NULL,
  youtube_video_id VARCHAR(20) NULL,
  display_order INT NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_slug (slug),
  CONSTRAINT fk_link_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_order (display_order),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- posters : square educational images (Instagram-style gallery)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS posters (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  caption VARCHAR(300) NULL,
  image_path VARCHAR(255) NOT NULL,
  thumb_path VARCHAR(255) NOT NULL,
  webp_path VARCHAR(255) NULL,
  webp_thumb_path VARCHAR(255) NULL,
  alt_text VARCHAR(255) NULL,
  category_id INT UNSIGNED NULL,
  display_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_poster_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- quizzes / quiz_questions / quiz_options
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quizzes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  topic VARCHAR(150) NULL,
  description TEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_slug (slug),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- question_type is stored per-question (not per-quiz) so that a single
-- quiz can mix multiple-choice, fill-in-the-blank and one-word questions,
-- and so new question types can be added later without changing the table.
CREATE TABLE IF NOT EXISTS quiz_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT UNSIGNED NOT NULL,
  question_type ENUM('mcq','fill_blank','one_word') NOT NULL,
  question_text TEXT NOT NULL,
  accepted_answers TEXT NULL COMMENT 'pipe-separated accepted answers, used by fill_blank / one_word',
  explanation TEXT NULL,
  display_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_question_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
  INDEX idx_quiz_order (quiz_id, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- options only apply to question_type = 'mcq'
CREATE TABLE IF NOT EXISTS quiz_options (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id INT UNSIGNED NOT NULL,
  option_text VARCHAR(500) NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  display_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_option_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
  INDEX idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- quiz_attempts / quiz_attempt_answers : recorded for logged-in learners only
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT UNSIGNED NOT NULL,
  learner_id INT UNSIGNED NOT NULL,
  score INT UNSIGNED NOT NULL,
  total INT UNSIGNED NOT NULL,
  percentage DECIMAL(5,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attempt_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
  CONSTRAINT fk_attempt_learner FOREIGN KEY (learner_id) REFERENCES learners(id) ON DELETE CASCADE,
  INDEX idx_learner_quiz (learner_id, quiz_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempt_answers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  learner_answer TEXT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_answer_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_answer_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
  INDEX idx_attempt (attempt_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- pages : Start Here / About / Contact / Privacy / Terms (rich text)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL,
  title VARCHAR(255) NOT NULL,
  body LONGTEXT NOT NULL,
  meta_description VARCHAR(300) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_page_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- settings : single key/value store for site-wide settings
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- contact_messages : stored copies of every contact form submission
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_read (is_read),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- So the site looks alive the moment it goes live, and so the teacher
-- can see exactly what a filled-in form produces before adding his own
-- content. All of this can be edited or deleted from /admin.
-- =====================================================================

-- ---- Lesson categories -------------------------------------------------
INSERT INTO categories (type, name, slug, display_order) VALUES
('lesson', 'Spoken English', 'spoken-english', 1),
('lesson', 'Grammar', 'grammar', 2),
('lesson', 'Vocabulary', 'vocabulary', 3),
('lesson', 'Everyday English', 'everyday-english', 4),
('lesson', 'Telugu-to-English', 'telugu-to-english', 5),
('lesson', 'Common Mistakes', 'common-mistakes', 6),
('lesson', 'Interview English', 'interview-english', 7),
('lesson', 'Workplace English', 'workplace-english', 8),
('lesson', 'Practical Communication', 'practical-communication', 9);

-- ---- Link categories ----------------------------------------------------
INSERT INTO categories (type, name, slug, display_order) VALUES
('link', 'Pronunciation', 'pronunciation', 1),
('link', 'Grammar', 'grammar', 2),
('link', 'Vocabulary', 'vocabulary', 3),
('link', 'Interview English', 'interview-english', 4),
('link', 'Communication Skills', 'communication-skills', 5);

-- ---- Poster categories ---------------------------------------------------
INSERT INTO categories (type, name, slug, display_order) VALUES
('poster', 'Vocabulary', 'vocabulary', 1),
('poster', 'Grammar', 'grammar', 2),
('poster', 'Idioms', 'idioms', 3),
('poster', 'General', 'general', 4);

-- ---- Sample lessons -------------------------------------------------------
INSERT INTO lessons (title, slug, category_id, featured_image, excerpt, body, status, meta_description, publish_date) VALUES
(
  'Common Mistakes Telugu Speakers Make in English',
  'common-mistakes-telugu-speakers-make-in-english',
  (SELECT id FROM categories WHERE type='lesson' AND slug='common-mistakes'),
  NULL,
  'Telugu speakers often carry sentence patterns from Telugu into English without realising it. Here are five mistakes almost every learner makes, and simple ways to fix each one.',
  '<p>When we learn a new language, our mother tongue quietly shapes the way we build sentences. Telugu speakers often carry Telugu sentence patterns into English without realising it. Here are five very common mistakes, and how to fix each one.</p>
<h2>1. \"I am having a doubt\"</h2>
<p>In Telugu it feels natural to say <span lang=\"te\" class=\"te\">\"నాకు ఒక సందేహం ఉంది\"</span> which translates word-for-word to \"I am having a doubt\". But in English, we simply say:</p>
<blockquote>\"I have a doubt\" or \"I have a question.\"</blockquote>
<p><strong>Doubt</strong> is not normally used with the continuous form (\"having\") in this sense.</p>
<h2>2. Skipping articles: \"a\", \"an\", \"the\"</h2>
<p>Telugu does not have articles, so learners often drop them completely in English:</p>
<ul>
<li>Wrong: \"I am going to market.\"</li>
<li>Right: \"I am going to <em>the</em> market.\"</li>
</ul>
<p>A simple rule to start with: almost every singular, countable noun needs <strong>a</strong>, <strong>an</strong>, or <strong>the</strong> in front of it.</p>
<h2>3. \"Myself Ravi\" instead of \"I am Ravi\"</h2>
<p>This is one of the most common introductions we hear, but it is not correct English grammar.</p>
<blockquote>Say instead: \"I am Ravi\" or \"My name is Ravi.\"</blockquote>
<h3>4. Mixing up \"since\" and \"for\"</h3>
<p>Use <strong>since</strong> with a point in time (since 2019, since Monday) and <strong>for</strong> with a duration (for three years, for two hours).</p>
<ol>
<li>Wrong: \"I am living here since five years.\"</li>
<li>Right: \"I have been living here <strong>for</strong> five years.\"</li>
</ol>
<h3>5. Direct translation of polite requests</h3>
<p>In Telugu, requests are often framed as questions about ability. In English this can sound blunt if translated directly. Instead of \"Give me that book\", try:</p>
<blockquote>\"Could you please pass me that book?\"</blockquote>
<p>Small changes like these make a big difference in how confident and natural your English sounds. Practice one mistake at a time rather than trying to fix everything at once.</p>',
  'published',
  'Five common English mistakes Telugu speakers make and how to correct each one, explained simply with examples.',
  NOW()
),
(
  'How to Introduce Yourself in English',
  'how-to-introduce-yourself-in-english',
  (SELECT id FROM categories WHERE type='lesson' AND slug='spoken-english'),
  NULL,
  'A simple, confident self-introduction is the first English skill every learner needs — for interviews, classrooms, and everyday conversations. Here is a structure you can reuse anywhere.',
  '<p>A good self-introduction is short, clear, and confident. You will use this skill in interviews, classrooms, meetings, and even casual conversations. Here is a simple structure you can reuse anywhere.</p>
<h2>The four-part structure</h2>
<ol>
<li><strong>Greeting</strong> — \"Good morning\" / \"Hello, nice to meet you.\"</li>
<li><strong>Name</strong> — \"My name is Lakshmi.\"</li>
<li><strong>Background</strong> — where you are from, what you do or study.</li>
<li><strong>One extra detail</strong> — an interest, a goal, or why you are here.</li>
</ol>
<h2>Example</h2>
<blockquote>\"Good morning. My name is Lakshmi. I am from Vijayawada, and I currently work as a customer support executive. I am learning English because I want to move into a role where I speak with international clients.\"</blockquote>
<p>Notice that this is only three sentences, but it answers every question a listener naturally has: <em>who are you, where are you from, and why does it matter?</em></p>
<h2>Common phrases to keep ready</h2>
<ul>
<li>\"I am originally from&nbsp;...\"</li>
<li>\"I have been working in&nbsp;... for two years.\"</li>
<li>\"In my free time, I enjoy&nbsp;...\"</li>
<li>\"I am looking forward to&nbsp;...\"</li>
</ul>
<h3>A quick tip</h3>
<p>Practice saying your introduction <strong>out loud</strong>, not just in your head. In Telugu we call this <span lang=\"te\" class=\"te\">అభ్యాసం</span> — practice — and it is the fastest way to remove hesitation. Record yourself on your phone and listen back; you will notice exactly where you pause or lose confidence.</p>
<p>Once you are comfortable with this basic structure, you can expand it for interviews by adding your key skills, or shorten it for quick introductions at social events.</p>',
  'published',
  'A simple four-part structure for introducing yourself confidently in English, with example sentences and common phrases.',
  DATE_SUB(NOW(), INTERVAL 3 DAY)
);

-- ---- Sample links ----------------------------------------------------------
INSERT INTO links (name, slug, description, url, category_id, youtube_video_id, display_order, status) VALUES
(
  'English Pronunciation Practice Video',
  'english-pronunciation-practice-video',
  'A short video you can use to practice listening and pronunciation. Replace this with any YouTube video you want your learners to watch — the thumbnail is detected automatically.',
  'https://www.youtube.com/watch?v=jNQXAC9IVRw',
  (SELECT id FROM categories WHERE type='link' AND slug='pronunciation'),
  'jNQXAC9IVRw',
  1,
  'published'
),
(
  'BBC Learning English',
  'bbc-learning-english',
  'Free lessons, videos and articles for English learners at every level, published by the BBC. Good for grammar and vocabulary practice.',
  'https://www.bbc.co.uk/learningenglish',
  (SELECT id FROM categories WHERE type='link' AND slug='grammar'),
  NULL,
  2,
  'published'
),
(
  'VOA Learning English',
  'voa-learning-english',
  'News and stories written in simple English, with audio — useful for building vocabulary at your own pace.',
  'https://learningenglish.voanews.com/',
  (SELECT id FROM categories WHERE type='link' AND slug='vocabulary'),
  NULL,
  3,
  'published'
),
(
  'Follow for Daily Practice on Instagram',
  'follow-for-daily-practice-on-instagram',
  'Follow this page for short, daily English practice posts you can read in under a minute. Replace this with your own Instagram page link.',
  'https://www.instagram.com/',
  (SELECT id FROM categories WHERE type='link' AND slug='communication-skills'),
  NULL,
  4,
  'published'
);

-- ---- Sample posters (placeholder-generated, replace with your own) ---------
INSERT INTO posters (caption, image_path, thumb_path, webp_path, webp_thumb_path, alt_text, category_id, display_order) VALUES
('Common Mistake: "He go" vs "He goes"', 'uploads/posters/common-mistake-poster-1788583926-7f0f78.jpg', 'uploads/posters/thumbs/common-mistake-poster-1788583926-7f0f78.jpg', 'uploads/posters/common-mistake-poster-1788583926-7f0f78.webp', 'uploads/posters/thumbs/common-mistake-poster-1788583926-7f0f78.webp', 'Common Mistake poster: He go to school should be He goes to school', (SELECT id FROM categories WHERE type='poster' AND slug='general'), 1),
('Word of the Day: Grateful', 'uploads/posters/word-of-the-day-poster-1788583926-9c2b34.jpg', 'uploads/posters/thumbs/word-of-the-day-poster-1788583926-9c2b34.jpg', 'uploads/posters/word-of-the-day-poster-1788583926-9c2b34.webp', 'uploads/posters/thumbs/word-of-the-day-poster-1788583926-9c2b34.webp', 'Word of the Day poster: Grateful means feeling thankful', (SELECT id FROM categories WHERE type='poster' AND slug='vocabulary'), 2),
('Grammar Tip: A vs An', 'uploads/posters/grammar-tip-poster-1788583926-4f43ca.jpg', 'uploads/posters/thumbs/grammar-tip-poster-1788583926-4f43ca.jpg', 'uploads/posters/grammar-tip-poster-1788583926-4f43ca.webp', 'uploads/posters/thumbs/grammar-tip-poster-1788583926-4f43ca.webp', 'Grammar Tip poster: use an before a, e, i, o, u sounds', (SELECT id FROM categories WHERE type='poster' AND slug='grammar'), 3);

-- ---- Sample quiz (2 MCQ + 2 fill-in-the-blank + 1 one-word) -----------------
INSERT INTO quizzes (title, slug, topic, description, status) VALUES
(
  'Basic Grammar Check',
  'basic-grammar-check',
  'Grammar',
  'A short five-question quiz covering everyday grammar mistakes. Perfect for a quick daily practice.',
  'published'
);

SET @quiz_id = LAST_INSERT_ID();

INSERT INTO quiz_questions (quiz_id, question_type, question_text, accepted_answers, explanation, display_order) VALUES
(@quiz_id, 'mcq', 'Choose the correct sentence:', NULL, 'Use "doesn''t" with he / she / it in the negative present simple, not "don''t".', 1);
SET @q1 = LAST_INSERT_ID();
INSERT INTO quiz_options (question_id, option_text, is_correct, display_order) VALUES
(@q1, 'She don''t like tea.', 0, 1),
(@q1, 'She doesn''t like tea.', 1, 2),
(@q1, 'She not like tea.', 0, 3),
(@q1, 'She isn''t likes tea.', 0, 4);

INSERT INTO quiz_questions (quiz_id, question_type, question_text, accepted_answers, explanation, display_order) VALUES
(@quiz_id, 'mcq', 'What is the plural of "child"?', NULL, '"Child" has an irregular plural: "children", not "childs" or "childes".', 2);
SET @q2 = LAST_INSERT_ID();
INSERT INTO quiz_options (question_id, option_text, is_correct, display_order) VALUES
(@q2, 'childs', 0, 1),
(@q2, 'childes', 0, 2),
(@q2, 'children', 1, 3),
(@q2, 'childrens', 0, 4);

INSERT INTO quiz_questions (quiz_id, question_type, question_text, accepted_answers, explanation, display_order) VALUES
(@quiz_id, 'fill_blank', 'I ___ to the market yesterday.', 'went', 'Use the simple past tense "went" for an action that finished in the past.', 3);

INSERT INTO quiz_questions (quiz_id, question_type, question_text, accepted_answers, explanation, display_order) VALUES
(@quiz_id, 'fill_blank', 'This is ___ apple.', 'an', 'Use "an" (not "a") before a word that starts with a vowel sound.', 4);

INSERT INTO quiz_questions (quiz_id, question_type, question_text, accepted_answers, explanation, display_order) VALUES
(@quiz_id, 'one_word', 'What is the opposite of "hot"?', 'cold', '"Cold" is the antonym (opposite) of "hot".', 5);

-- ---- Pages (Start Here / About / Contact / Privacy / Terms) -----------------
INSERT INTO pages (slug, title, body, meta_description) VALUES
(
  'start-here',
  'Start Here',
  '<p>New to English Badi? Follow this simple path. Do not rush — spend at least a week on each stage before moving to the next one.</p>
<h2>Beginner</h2>
<p>Focus on the basics: simple sentences, everyday vocabulary, and confidence in speaking without fear of mistakes.</p>
<ul>
<li>Read lessons in the <strong>Spoken English</strong> and <strong>Everyday English</strong> categories.</li>
<li>Practice the <strong>Basic Grammar Check</strong> quiz until you consistently score above 80%.</li>
<li>Look through the Posters section daily — five minutes of vocabulary revision adds up fast.</li>
</ul>
<h2>Intermediate</h2>
<p>Now that basic sentences feel comfortable, focus on accuracy and everyday communication skills.</p>
<ul>
<li>Study the <strong>Grammar</strong> and <strong>Common Mistakes</strong> lesson categories closely.</li>
<li>Start using full sentences in the Links section — watch one video a day and try to summarise it out loud afterwards.</li>
<li>Revisit any quiz you scored below 70% on, and try again after a few days.</li>
</ul>
<h2>Advanced</h2>
<p>At this stage, the goal is fluency in real situations: interviews, the workplace, and natural conversation.</p>
<ul>
<li>Focus on <strong>Interview English</strong>, <strong>Workplace English</strong> and <strong>Practical Communication</strong> lessons.</li>
<li>Take every quiz available and aim to beat your previous score each time — your progress is tracked automatically once you register.</li>
<li>Try explaining a lesson topic to a friend or family member in English — teaching something is the best test of whether you truly know it.</li>
</ul>
<p>Whichever stage you are at, the most important habit is consistency: a little English every day beats a lot of English once a month.</p>',
  'A simple beginner, intermediate and advanced learning path through English Badi''s lessons and quizzes.'
),
(
  'about',
  'About English Badi',
  '<p>English Badi (<span lang=\"te\" class=\"te\">ఇంగ్లీష్ బడి</span> — "English School") was created for Telugu speakers who want to learn practical, everyday English without expensive classes or complicated grammar books.</p>
<p>Everything here — lessons, curated video links, posters and quizzes — is written and chosen by a single English teacher with years of experience teaching learners exactly these challenges. The goal is simple: help you speak and understand English with confidence, at your own pace, for free.</p>
<h2>What you will find here</h2>
<ul>
<li><strong>Lessons</strong> — short, practical articles that explain one idea at a time.</li>
<li><strong>Links</strong> — hand-picked videos and resources worth your time.</li>
<li><strong>Posters</strong> — simple visual vocabulary and grammar reminders you can scroll through in a minute.</li>
<li><strong>Quizzes</strong> — quick, self-scoring practice tests so you can check what you have actually learned.</li>
</ul>
<p>New content is added regularly. If there is a topic you are struggling with, please use the Contact page to let us know — future lessons are often written directly from learner questions.</p>',
  'About English Badi, a free English learning resource for Telugu speakers, and the teacher behind it.'
),
(
  'contact',
  'Contact Us',
  '<p>Have a question, a topic request, or found something that is not working correctly? Send a message using the form below and we will get back to you as soon as possible.</p>',
  'Get in touch with English Badi with questions, topic requests, or feedback.'
),
(
  'privacy',
  'Privacy Policy',
  '<p>This Privacy Policy explains what information English Badi collects and how it is used. We collect only what is necessary to run the site and keep it simple.</p>
<h2>Information we collect</h2>
<ul>
<li><strong>Registered account details</strong> — your name and email address, if you choose to register.</li>
<li><strong>Quiz results</strong> — your scores, stored against your account so you can track your own progress over time.</li>
<li><strong>Contact form messages</strong> — the name, email and message you submit through the Contact page.</li>
</ul>
<p>We do not sell or share your personal information with third parties for advertising purposes.</p>
<h2>Cookies</h2>
<p>We use a small number of essential cookies to keep you logged in and to remember whether you have dismissed the registration prompt after a quiz. We do not use tracking or advertising cookies.</p>
<h2>Your rights</h2>
<p>You can ask us at any time, via the Contact page, to delete your account and all data associated with it, including your quiz history.</p>
<h2>Contact</h2>
<p>If you have any questions about this policy, please use the Contact page to reach us.</p>',
  'How English Badi collects, uses and protects your personal information.'
),
(
  'terms',
  'Terms of Use',
  '<p>By using English Badi, you agree to the following simple terms.</p>
<h2>Use of content</h2>
<p>All lessons, posters, and original written material on this site are provided free of charge for personal learning. You may not republish or resell this content elsewhere without permission.</p>
<h2>Linked content</h2>
<p>The Links section points to videos and resources hosted on other websites (such as YouTube or Instagram). We do not control and are not responsible for the content, availability, or policies of those external sites.</p>
<h2>Accounts</h2>
<p>Registration is optional and free. You are responsible for keeping your password secure. You may request deletion of your account at any time via the Contact page.</p>
<h2>No guarantees</h2>
<p>English Badi is provided "as is", as a free learning resource. While we try to keep information accurate and the site running smoothly, we cannot guarantee uninterrupted availability or that the site will be completely error-free.</p>
<h2>Changes to these terms</h2>
<p>These terms may be updated from time to time. Continued use of the site after changes are posted means you accept the updated terms.</p>',
  'Terms of use for English Badi, covering content use, linked resources, and accounts.'
);

-- ---- Settings -----------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('site_title', 'English Badi'),
('site_tagline', 'Learn English the easy way — in Telugu and English'),
('homepage_intro', 'English Badi helps Telugu speakers learn practical, everyday English through simple lessons, curated videos, visual posters and self-scoring quizzes — all free, all in one place.'),
('contact_email', 'hello@englishbadi.com'),
('social_instagram', ''),
('social_youtube', ''),
('social_facebook', ''),
('meta_description_default', 'English Badi helps Telugu speakers learn practical, everyday English through lessons, videos, posters and self-scoring quizzes.');
