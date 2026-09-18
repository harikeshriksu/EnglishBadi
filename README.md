# English Badi - Setup Guide

This guide walks you through putting English Badi online on Hostinger,
step by step. No coding knowledge needed - just follow along in order.

If you get stuck at any step, stop and re-read it - most problems come
from a small typo (an extra space, a missing character) in a name or
password. Everything you type should be copied exactly.

---

## What you need before you start

- A Hostinger hosting account (any shared hosting plan works).
- Your GoDaddy domain name (e.g. `englishbadi.com`).
- The full set of website files (the folder this README is in).

---

## Step 1: Upload the website files to Hostinger

1. Log in to Hostinger and open **hPanel**.
2. Go to **Files > File Manager**.
3. Open the `public_html` folder. This is your website's "front door" -
   anything inside it becomes visible on your domain.
4. If `public_html` already has files in it (like a default `index.html`
   Hostinger placed there), select them and delete them first, so they
   don't conflict with your new site.
5. Click **Upload**, and upload every file and folder from this project
   (everything at the same level as this README) into `public_html`.
   - Tip: it is much faster to compress this whole folder into a single
     `.zip` file first, upload that one `.zip`, then use File Manager's
     **Extract** option once it's uploaded, instead of uploading files
     one by one.
6. When you're done, `public_html` should directly contain folders like
   `admin`, `includes`, `assets`, `uploads`, plus files like `index.php`,
   `config.php.example`, `schema.sql`, `setup.php`, and this README.

---

## Step 2: Create the MySQL database

1. In hPanel, go to **Databases > MySQL Databases**.
2. Create a new database. Hostinger will ask for a database name and
   will create a database user for you (or let you create one) with a
   password.
3. Make sure the user is attached to the database with **All Privileges**.
4. Write down these four things exactly as shown - you'll need them in
   the next step:
   - **Database host** (usually `localhost`)
   - **Database name** (often looks like `u123456789_englishbadi`)
   - **Database username** (often looks like `u123456789_admin`)
   - **Database password** (the one you chose or Hostinger generated)

---

## Step 3: Create your config.php file

`config.php` holds your real database password, so the site looks for
it in **two possible places** - pick one:

- **Option A - one folder above `public_html` (recommended).** In File
  Manager, `public_html` sits inside another folder (often
  `domains/yourdomain.com/`). Navigate up one level from `public_html`
  (click the parent folder in the path bar near the top) and put
  `config.php` there instead, as a sibling of `public_html`, not inside
  it.

  This is the safer option if you ever use Hostinger's **Git-based
  deployment** (auto-deploying from a GitHub repo) instead of manually
  uploading files: a Git deploy only ever touches files *inside*
  `public_html`, so a `config.php` living just outside it can never be
  touched, overwritten, or deleted by a deploy - no matter how that
  deploy is configured.
- **Option B - directly inside `public_html`.** Simpler, and fine if
  you only ever update the site by uploading files manually through
  File Manager. Not recommended if you also use Git deployment, since a
  future deploy could remove it (see the Git warning below).

Either way, the steps are the same:

1. Find the file `config.php.example` inside `public_html`.
2. Copy it to whichever location you chose above, and rename the copy
   to exactly `config.php` (no `.example` at the end).
3. Right-click `config.php` and choose **Edit** (or **Code Editor**).
4. Fill in the four database values from Step 2:
   ```
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_username');
   define('DB_PASS', 'your_database_password');
   ```
5. Update `SITE_URL` to your real domain:
   ```
   define('SITE_URL', 'https://englishbadi.com');
   ```
6. Save the file.

**Important:** keep `config.php` private. Never send it to anyone,
upload it anywhere public, or commit it to a Git repository - it
contains your database password. If both an outside-`public_html` copy
and an inside-`public_html` copy exist at the same time, the outside
one is used.

### If you deploy with Git

If you connect this repository to Hostinger's Git deployment feature
(hPanel > **Advanced > Git**), be aware that **`config.php` and the
contents of `/uploads/` are deliberately not part of the repository**
(see `.gitignore`) - a deploy only manages files that Git tracks, so it
should never touch either. That said:

- If you ever see the site go down with a "technical problem" message
  right after a deploy, check that `config.php` is still where you left
  it (Option A above makes this impossible in the first place).
- Some Git deployment tools offer a "clean"/mirror sync mode that
  deletes anything in `public_html` not present in the repository -
  including files Git never tracked, like `config.php` sitting loose
  inside `public_html`, or real photos/posters inside `/uploads/`.
  Check your Git deployment settings for this and prefer a
  non-destructive sync mode if one is offered. Option A protects
  `config.php` from this either way; `/uploads/` cannot be moved out of
  `public_html` (the site needs to serve those files directly), so if
  your deploy tool only offers a destructive sync, avoid deploying over
  a live site that has real uploaded content, or check Hostinger's
  support docs for a way to exclude `/uploads/` from the sync.

---

## Step 4: Import the database structure

1. In hPanel, go to **Databases > phpMyAdmin** and open it for the
   database you created in Step 2 (phpMyAdmin will usually log you
   straight into it).
2. Click the **Import** tab near the top.
3. Click **Choose File** and select the `schema.sql` file from this
   project.
4. Scroll down and click **Go** (or **Import**).
5. You should see a success message, and a new list of tables (users,
   lessons, links, posters, quizzes, and more) will appear on the left.

This also adds a small amount of sample content (2 lessons, 4 links, 3
posters, and 1 quiz) so the site looks alive immediately and you can see
what a filled-in form produces. Feel free to edit or delete all of it
later from the admin panel.

---

## Step 5: Create your admin account

1. In your browser, go to `https://yourdomain.com/setup.php`
   (replace `yourdomain.com` with your real domain).
2. Fill in your name, a username, and a password (at least 8 characters).
3. Click **Create admin account**.
4. You'll be taken straight to your new Dashboard - you're logged in!

This page only works once. As soon as an admin account exists, it
refuses to run again and simply tells you to log in instead, so nobody
else can use it to create a second account later.

Your admin panel is at: `https://yourdomain.com/admin`

---

## Step 6: Point your GoDaddy domain to Hostinger

If your domain is registered with GoDaddy but you're hosting on
Hostinger, you need to tell GoDaddy to send visitors to Hostinger's
servers. The easiest way is to change your domain's **nameservers**.

1. In hPanel, go to **Domains** and find your domain's nameserver
   details, or go to **Websites > (your site) > Hosting details** -
   Hostinger usually shows nameservers like:
   ```
   ns1.dns-parking.com
   ns2.dns-parking.com
   ```
   (Hostinger's exact nameservers are shown in your hPanel - use the
   ones shown there, they may differ slightly from this example.)
2. Log in to your GoDaddy account and go to **My Products > Domains**.
3. Click your domain, then find **Nameservers** and click **Change**.
4. Choose **Enter my own nameservers (advanced)**.
5. Replace GoDaddy's default nameservers with Hostinger's two
   nameservers from step 1.
6. Save. This step is called "propagation" and can take anywhere from
   a few minutes up to 24-48 hours to fully take effect everywhere.

You can keep checking your domain in a browser - once it starts showing
your English Badi site instead of a GoDaddy parking page, it's done.

---

## Step 7: Turn on free SSL (the padlock / https://)

Hostinger includes a free SSL certificate for every domain.

1. In hPanel, go to **Security > SSL** (sometimes listed under
   **Advanced > SSL**).
2. Select your domain and click **Install** (Hostinger uses a free
   Let's Encrypt certificate).
3. Wait a few minutes for it to activate. Hostinger will usually install
   it automatically once your domain is correctly pointed at Hostinger
   (Step 6) - if you don't see an install button, it may already be
   active.
4. Once active, visit `https://yourdomain.com` and confirm you see a
   padlock icon in the address bar.

The website is already built to force every visitor onto the `https://`
(secure) version automatically, so once SSL is active you don't need to
change anything else.

---

## Troubleshooting

- **"One more step" / "config.php was not found" message** - you
  haven't completed Step 3 yet, or the file isn't named exactly
  `config.php` (check for a leftover `.txt` or `.example` at the end of
  the filename), or it's in neither of the two locations Step 3
  describes.
- **"We're having a technical problem" message on a site that was
  already working** - this is the same underlying cause (`config.php`
  is missing) but shown differently because the site had been
  successfully set up before. Something removed `config.php` after the
  fact - most likely a Git deploy (see the Git warning in Step 3).
  Re-check that the file is still in place; moving it to Option A
  (outside `public_html`) prevents this from happening again.
- **A blank white page** - this usually means a database detail in
  `config.php` is wrong. Double-check the four values from Step 2 for
  typos, extra spaces, or missing quote marks.
- **"setup.php" says setup is already complete, but you're locked out** -
  this means an admin account already exists. Go to `/admin` and log in
  normally. If you've genuinely forgotten the password, you (or your
  developer) can reset it directly in phpMyAdmin by deleting the row in
  the `users` table and reloading `/setup.php`.
- **Images not showing after uploading a poster** - check that the
  `/uploads/` folder (and its subfolders) uploaded correctly and is
  writable; most Hostinger accounts allow this by default with no extra
  steps needed.
- **Real photos/posters disappeared after a Git deploy** - `/uploads/`
  content is real, uploaded data, not part of the repository (see
  `.gitignore`), so a deploy should never touch it - unless your Git
  deployment tool is set to a destructive "clean"/mirror sync mode. See
  the Git warning in Step 3.

For day-to-day use of the site once it's live - adding lessons, links,
posters and quizzes, and how to back everything up - see **GUIDE.md**.
