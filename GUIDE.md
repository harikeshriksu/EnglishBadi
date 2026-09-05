# English Badi - Owner's Handbook

This is your day-to-day guide for running English Badi once it's online.
Everything here is done from your admin panel at:

```
https://yourdomain.com/admin
```

Log in with the username and password you created in `setup.php`.

---

## Contents

1. [The Dashboard](#the-dashboard)
2. [Adding a Lesson](#adding-a-lesson)
3. [Adding a Link](#adding-a-link)
4. [Adding Posters](#adding-posters)
5. [Adding a Quiz](#adding-a-quiz)
6. [Managing Categories](#managing-categories)
7. [Editing Start Here / About / Contact / Privacy / Terms](#editing-fixed-pages)
8. [Registered Users and Messages](#registered-users-and-messages)
9. [Settings](#settings)
10. [Recommended Image Sizes](#recommended-image-sizes)
11. [Taking a Backup](#taking-a-backup)
12. [Restoring a Backup](#restoring-a-backup)

---

## The Dashboard

After logging in, you land on the **Dashboard**. It shows:

- How many Lessons, Links, Posters and Quizzes you have.
- How many contact messages are unread, and how many people have registered.
- Four big buttons: **Add a lesson**, **Add a link**, **Add a poster**,
  **Add a quiz** - these take you straight to the right form.
- A short list of your most recently updated content of each type.

The menu on the left (tap the **menu icon** top-right on a phone) gets
you to every section: Dashboard, Lessons, Links, Posters, Quizzes,
Pages, Categories, Registered Users, Messages, Settings.

---

## Adding a Lesson

1. Click **Lessons** in the menu, then click **Add a lesson**.
2. Type a **Title**.
3. Choose a **Category** from the dropdown (or leave it as "No category").
4. Choose a **Status**:
   - **Draft** - saved, but not visible to visitors yet.
   - **Published** - live on the site immediately.
5. Set the **Publish date** if you want it dated differently to today.
6. (Optional) Click **Choose File** under **Featured image** to upload a
   picture for this lesson. Recommended size: about 800 x 800 pixels.
7. (Optional) Type a short **Excerpt** - this is the 2-4 line summary
   shown in the lesson list. If you leave it blank, the site
   automatically creates one from the first part of your lesson.
8. Write your lesson in the big **Body** box. This is explained fully in
   [Using the editor toolbar](#using-the-editor-toolbar) below.
9. (Optional) Type a **Search description** - this is what shows up
   under your lesson's title in Google search results. Leave blank to
   auto-generate one.
10. Click **Save Lesson**.

Your lesson is now live (if you set it to Published) at
`yourdomain.com/lessons/your-lesson-title`.

### Editing or deleting a lesson

Go to **Lessons** in the menu. Every lesson is listed with a small
**pencil icon** (Edit) and **bin icon** (Delete). Clicking Delete will
ask you to confirm first, since it cannot be undone.

---

## Using the editor toolbar

The Body box on Lessons and Pages has a toolbar across the top:

| Button | What it does |
|---|---|
| **B / I / U / S** | Bold, Italic, Underline, Strikethrough |
| **H2 / H3 / ¶** | Heading, smaller heading, or normal paragraph text |
| Bullet / numbered list | Bulleted or numbered lists |
| Quote mark | Indented quote block |
| **A** (colour) | Text colour - click for a palette, or type any hex code (e.g. `#4A5FBF`) |
| Highlighter | Highlight/background colour, same palette |
| Chain link | Insert a link - select some text first, click this, and type or paste the web address |
| Broken chain | Remove a link |
| Picture icon | Insert an image directly into the text, at your cursor |
| Left / centre align | Align a paragraph |
| Eraser | Clear formatting from selected text |
| Undo / Redo arrows | Step backwards or forwards through your changes |
| **Aa / &#3077;** | Switches the editor's font between English and Telugu, for comfortable typing - it doesn't change what's saved, just how it looks while you type |
| **Paste format** checkbox | Off by default: pasting text (e.g. from Word) comes in as plain text, so you don't get messy formatting. Tick it on if you specifically want to keep the original formatting from what you're pasting |
| **Preview** | Shows exactly how your text will look on the live site |

Typing in Telugu works normally anywhere in the box - no special mode
needed.

---

## Adding a Link

1. Click **Links** in the menu, then **Add a link**.
2. Type the **Link name** (a short, clear title).
3. Paste the **URL** - a YouTube link, an Instagram post, a WhatsApp
   link, a blog post, anything.
   - If it's a YouTube link, you'll immediately see a small preview
     confirming "YouTube video detected" with its thumbnail - nothing
     more to do, it's automatic.
   - For any other link, you can optionally upload a **Thumbnail
     image**. If you skip it, a simple letter icon is shown instead.
4. Write a short **Description** (1-3 lines) explaining what the
   learner will get from it.
5. Choose a **Category** and a **Display order** (lower numbers appear
   first in the list).
6. Choose **Published** or **Draft**, then click **Save Link**.

### Editing or deleting a link

Go to **Links** in the menu - same pencil/bin icons as Lessons.

---

## Adding Posters

Posters are added differently from everything else, because you can add
many at once.

1. Click **Posters** in the menu.
2. Click the big box at the top (or drag image files onto it from your
   computer). You can select multiple files at once.
3. Each file appears in a list below with a small preview:
   - If the image is **already square**, it says "Ready to upload" -
     nothing more to do for that one.
   - If it is **not square**, a **Crop** button appears. Click it, drag
     the box to choose which part of the image to keep (or drag the
     small circle handle in the corner to resize the box), then click
     **Use this crop**. Or click **Keep full image** instead if you'd
     rather not crop it at all (the whole image will be kept, centred
     on a plain background to fill the square).
   - Click **Remove** next to any file you change your mind about.
4. Once every file is ready, the **Upload** button becomes active - click
   it. All your posters are processed and added at once.
5. Your new posters appear at the bottom of the page, without captions
   yet.
6. Hover over (or tap) any poster in the grid and click the **pencil
   icon** to add a **Caption**, **Alt text**, a **Category**, and a
   **Display order** - this is the "caption them afterwards" step.

### Editing or deleting a poster

Hover/tap a poster in the grid: pencil = edit its caption/category/order,
bin = delete it permanently (with a confirmation first).

### What file types can I upload?

JPG, PNG, WEBP, GIF, BMP, TIFF, HEIC (iPhone photos) and PDF (first page
only) are all accepted. Everything is automatically converted to the
right format and size. If a particular file can't be processed (rare -
usually only very old server setups struggle with HEIC or PDF), you'll
see a plain message asking you to re-save it as a JPG and try again -
never a confusing error.

---

## Adding a Quiz

1. Click **Quizzes** in the menu, then **Add a quiz**.
2. Fill in the **Quiz title**, **Topic** (e.g. "Grammar"), and an
   optional short **Description**. Choose **Draft** or **Published**.
3. Scroll down to **Questions** and click **Add Question**.
4. Choose a question type:
   - **Multiple Choice** - type the question, then fill in all 4
     options, and click the small circle next to the correct one.
   - **Fill in the Blank** - type the question using `___` (three
     underscores) where the blank goes, e.g. `I ___ to school every
     day.` Then type the accepted answer. Click **+ Add another
     accepted answer** if you want to accept spelling variants (like
     "colour" and "color") - each one goes in its own box.
   - **One Word Answer** - same as Fill in the Blank, just for a
     question that doesn't need a blank written into the sentence.
5. (Optional but recommended) Add an **Explanation** - shown to the
   learner right after they answer, whether they got it right or wrong.
6. Repeat **Add Question** for as many questions as you like.
7. Reorder questions any time using the **up/down arrows** at the top of
   each question box, or delete one with the **bin icon**.
8. Click **Save Quiz** at the bottom once you're happy with everything.

**Important:** clicking Save Quiz saves the quiz details AND every
question together, in one go. Nothing is saved question-by-question, so
make sure you click Save Quiz after you're done adding/editing
questions.

### Editing or deleting a quiz

Go to **Quizzes** in the menu - pencil to edit (this reopens the full
question list so you can change anything), bin to delete the whole
quiz. Deleting a quiz also deletes everyone's attempt history for it, so
you'll be asked to confirm.

---

## Managing Categories

Go to **Categories** in the menu. You'll see three sections: Lesson
categories, Link categories, and Poster categories.

- **Add** a new category: type a name in the box at the bottom of each
  section and click **Add**.
- **Rename** a category: change the text in its box and click
  **Rename**.
- **Delete** a category: click the bin icon. If lessons/links/posters
  are currently using it, you'll see a warning telling you exactly how
  many items will lose that category - the items themselves are never
  deleted, they just show "No category" afterwards.

---

## Editing Fixed Pages

Go to **Pages** in the menu. This lists **Start Here**, **About**,
**Contact**, **Privacy Policy**, and **Terms** - five pages that always
exist at the same web address. Click the **pencil icon** next to any of
them to edit its title and content using the same editor toolbar as
Lessons (see [Using the editor toolbar](#using-the-editor-toolbar)
above).

The **Contact** page is special: whatever text you write there appears
above the contact form, but the form itself (name/email/message fields)
is always there automatically - you don't need to build it.

---

## Registered Users and Messages

- **Registered Users** shows everyone who has created a free learner
  account, their email, when they joined, and how many quizzes they've
  attempted. Click **View** to see their full quiz history. You can
  **delete** an account entirely (removes all their quiz history too) -
  useful if someone asks you to remove their data.
- **Messages** shows everything submitted through the Contact page,
  newest unread messages first. Mark a message as **read**, reply
  directly by email using the **Reply by email** button (opens your own
  email app), or **delete** it once you're done with it.

---

## Settings

Go to **Settings** in the menu for:

- **Site title** and **Tagline**.
- **Homepage introduction text** - the paragraph shown under the four
  tiles on the homepage.
- **Default search description** - used for pages that don't have their
  own.
- **Contact form recipient email** - where messages sent through the
  Contact page are emailed to.
- **Instagram / YouTube / Facebook URLs** - shown as icons in the
  footer. Leave any of them blank to hide that icon.
- **Change admin password** - a separate section further down. You'll
  need your current password to set a new one.

---

## Recommended Image Sizes

| Content | Recommended size | Shape |
|---|---|---|
| Posters | 1080 x 1080 pixels | Square |
| Lesson featured images | 800 x 800 pixels | Square works best, but any shape is accepted |
| Link thumbnails | 600 x 600 pixels or larger | Any shape - it will be cropped to a square automatically |

You don't need to resize images yourself before uploading - the site
does this automatically - but starting with a reasonably sized image
(not a 20-megapixel camera photo) means faster uploads.

---

## Taking a Backup

Do this before making any big change, and every so often as routine
good practice (for example, once a month). A backup has two parts - the
database, and the uploads folder - and you should keep both together.

### Part 1: Back up the database

1. Log in to **hPanel > Databases > phpMyAdmin**.
2. Click on your database name in the left-hand list.
3. Click the **Export** tab near the top.
4. Leave the method as **Quick** and format as **SQL**.
5. Click **Go** (or **Export**). A `.sql` file will download to your
   computer - keep it somewhere safe, named with today's date, e.g.
   `englishbadi-backup-2026-01-15.sql`.

### Part 2: Back up the uploads folder

1. In **hPanel > File Manager**, go into `public_html`.
2. Right-click the `uploads` folder and choose **Compress** (this
   creates a `.zip` of everything inside it - all your lesson images,
   posters, and link thumbnails).
3. Once it's created, select the new `.zip` file and click **Download**.
4. Save it next to your database backup from Part 1, with a matching
   date in the name.

That's it - together, these two files are a complete backup of your
entire site's content.

---

## Restoring a Backup

Only do this if something has gone wrong and you need to go back to an
earlier point.

### Restoring the database

1. Go to **hPanel > Databases > phpMyAdmin** and select your database.
2. **This step deletes everything currently in the database first** -
   click the **Check All** option (or select every table) and choose
   **Drop** to empty it out, or simply create a fresh, empty database
   and update `config.php` to point at it.
3. Click the **Import** tab, choose your backed-up `.sql` file, and
   click **Go**.
4. Your content (lessons, links, quizzes, settings, and so on) is now
   back to exactly how it was when that backup was taken.

### Restoring the uploads folder

1. In **File Manager**, rename the current `uploads` folder to
   something like `uploads-old` (as a safety net, in case you need
   anything from it).
2. Upload your backed-up `uploads.zip` into `public_html`, then
   **Extract** it - this recreates the `uploads` folder with your
   backed-up images inside it.
3. Once you've confirmed everything looks right on the live site, you
   can delete the `uploads-old` folder.

**Tip:** always restore the database and uploads folder from backups
taken **at the same time** - if you mix an old database with newer
uploads (or vice versa), some images referenced in the database may be
missing, or some uploaded files may not appear anywhere on the site.
