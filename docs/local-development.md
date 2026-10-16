# Local development

LocalWP provides WordPress, PHP, the web server, and the database. This repository
is the source of truth for both Studio Hata MVPs:

- **HATA A:** the from-scratch `studio-hata` block theme.
- **HATA B:** the `hata-kadence` child theme with Kadence as its parent.

Use separate LocalWP sites for A and B so their databases, theme settings, and
navigation cannot affect one another.

## One-time setup

1. Install and open LocalWP.
2. Create sites named `studio-hata-a` and `studio-hata-b` using the **Preferred**
   environment. If Local offers a PHP choice, use PHP 8.3.
3. Start each site once so Local creates its WordPress files.
4. Install **Polylang** and **The SEO Framework** in both sites. In HATA B only,
   install the free **Kadence** parent theme; it will be activated through its
   child theme.
5. Link each Git-owned theme and the shared must-use plugin into the corresponding
   site. Adjust the Local paths if your sites use different names or locations:

   ```sh
   local_site_a='/Users/helenwhite/Local Sites/studio-hata-a'
   local_site_b='/Users/helenwhite/Local Sites/studio-hata-b'
   repo='/Users/helenwhite/Documents/Helen White/Signara/Projects/StudioHataNL/WP-codex-localclone-git/studio-hata'

   test -d "$local_site_a/app/public/wp-content/themes" || exit 1
   test -d "$local_site_b/app/public/wp-content/themes/kadence" || exit 1
   test ! -e "$local_site_a/app/public/wp-content/themes/studio-hata" || exit 1
   test ! -e "$local_site_b/app/public/wp-content/themes/hata-kadence" || exit 1

   ln -s "$repo/wp-content/themes/studio-hata" \
     "$local_site_a/app/public/wp-content/themes/studio-hata"
   ln -s "$repo/wp-content/themes/hata-kadence" \
     "$local_site_b/app/public/wp-content/themes/hata-kadence"

   for local_site in "$local_site_a" "$local_site_b"; do
     test -d "$local_site/app/public/wp-content/mu-plugins" || exit 1
     test ! -e "$local_site/app/public/wp-content/mu-plugins/hata-core.php" || exit 1
     ln -s "$repo/wp-content/mu-plugins/hata-core.php" \
       "$local_site/app/public/wp-content/mu-plugins/hata-core.php"
   done
   ```

   If either destination already exists, inspect it instead of overwriting it.
6. In HATA A, activate **Studio Hata**. In HATA B, activate
   **HATA (Kadence child)**. Activate Polylang and The SEO Framework in both.
7. Open **Settings > Permalinks**, choose **Post name**, and save once.
8. In **Languages**, add Nederlands (`nl_NL`) as the default, English (`en_GB`),
   and Українська (`uk`). Disable automatic browser-language redirection.

The symbolic links make edits in this clone appear immediately in the LocalWP
site. Do not copy the whole `wp-content` directory: LocalWP must retain ownership
of uploads, installed third-party plugins, caches, and other runtime files.

## Load the draft MVP content

The seed data contains Dutch, English, and Ukrainian draft copy. It does not
delete existing content and creates new pages as drafts unless publishing is
explicitly requested.

After adding the three languages, use the appropriate site's LocalWP
**Open site shell** action.

For HATA A, run:

```sh
HATA_BOOTSTRAP_STATUS=draft wp eval-file \
  '/Users/helenwhite/Documents/Helen White/Signara/Projects/StudioHataNL/WP-codex-localclone-git/studio-hata/tools/bootstrap/hata-a/bootstrap.php'
```

The HATA A bootstrap preserves previously created managed pages when it is run
again. Set `HATA_BOOTSTRAP_FORCE=1` only when you intentionally want to replace
their titles, status, and content from the language JSON files. Use
`HATA_BOOTSTRAP_STATUS=publish` only for a local visual test.

Navigation is also non-destructive on repeat runs. A navigation that still
matches the last bootstrap-generated version can receive safe generated updates;
a navigation edited in the Site Editor is preserved. To intentionally replace
all six HATA A navigation posts, run the bootstrap with
`HATA_BOOTSTRAP_FORCE_NAVIGATION=1`. The broader `HATA_BOOTSTRAP_FORCE=1` flag
also replaces navigation, in addition to managed page content.

For HATA B, run:

```sh
HATA_SEED_STATUS=draft wp eval-file \
  '/Users/helenwhite/Documents/Helen White/Signara/Projects/StudioHataNL/WP-codex-localclone-git/studio-hata/tools/seed/seed.php'
```

Review and publish pages from the dashboard after replacing the marked
placeholders. Never run either command on production.

## Daily workflow

1. Start the relevant `studio-hata-a` or `studio-hata-b` site in LocalWP.
2. Edit code in this repository.
3. Check the site and WordPress editor through LocalWP's **Open site** and
   **WP Admin** buttons.
4. Commit only project code and documentation. Do not commit the LocalWP site,
   database exports, uploads, logs, or credentials.

The local database contains content and WordPress settings. It is disposable and
is not promoted to staging or production as part of a code deployment.

## Local contact configuration

Set the footer contact values in the LocalWP site's `wp-config.php`, which lives
outside this repository and must not be committed. Add the constants above the
`/* That's all, stop editing! */` line:

```php
define('HATA_CONTACT_EMAIL', 'hello@example.test');
define('HATA_INSTAGRAM_URL', 'https://www.instagram.com/example/');
define('HATA_BOOKING_URL', 'https://booking.meetaimy.com/your-booking-link');
```

Replace the examples with the approved client values. Leave either constant
unset to hide that contact method. Deployments may provide the same names as
environment variables instead; constants take precedence when both are present.
`HATA_BOOKING_URL` is required by both content loaders and should point to the
approved Aimy booking page; it is never replaced by an Instagram fallback.

## HATA B hero video

Upload the final MP4 through **Media > Add New**; WordPress uploads remain
gitignored. Put its Media URL in the untracked LocalWP `wp-config.php` as
`HATA_HERO_VIDEO_URL`. With no value, reduced motion, or Save-Data enabled, the
hero does not create or load a video element. It keeps the local still-life
placeholder visible over its Linen-to-Warm-White fallback until approved
photography is installed. Before enabling the video, add an approved 16:9 poster at
`wp-content/themes/hata-kadence/assets/img/hero-poster.jpg`. The child theme
will not load a configured video unless that poster exists.

```php
define('HATA_HERO_VIDEO_URL', 'http://studio-hata-b.local/wp-content/uploads/video.mp4');
```

Prepare a silent 720p loop, 8–15 seconds long and under about 2.5 MB:

```sh
ffmpeg -i in.mp4 -an -vf scale=1280:-2 -c:v libx264 -crf 28 -preset slow -movflags +faststart out.mp4
```

## HATA B placeholder photography

While final photography is pending, local generated examples may be placed in
`wp-content/themes/hata-kadence/assets/img/placeholders/` as `still-life.jpg`,
`session.jpg`, and `studio.jpg`. The directory is ignored by Git and must not be
committed. The seed reuses those files across Home, About, Services, the service
detail pages, Book, and Contact; if a file is absent, its Image block is omitted.

An approved `assets/img/hero-poster.jpg` takes precedence the next time the seed
is run. Because WordPress stores the selected poster URL in the Home page's block
markup, rerun the HATA B seed before removing the ignored still-life file. Verify
NL, EN, and UK Home after reseeding. Replace all placeholders with licensed,
approved photography before launch and record meaningful alt text in all three
languages.

## HATA A placeholder photography

HATA A uses the same three local examples from
`wp-content/themes/studio-hata/assets/images/placeholders/`. The ignored files
are deliberately reused: still life on Home and Book, the session image on
Reiki and AromaTouch, and the studio on Home's About section, Breath, and
Contact. FAQ, Privacy, and Cookies stay image-free. The bootstrap inserts only
missing managed Image blocks, so rerunning it does not duplicate them or replace
copy edited in the Site Editor.

Replace a placeholder in place with approved, licensed photography of the same
filename, or update the bootstrap mapping before removing it. Verify NL, EN, and
UK after replacement.

## Troubleshooting the link

Verify the link with:

```sh
readlink '/Users/helenwhite/Local Sites/studio-hata-a/app/public/wp-content/themes/studio-hata'
readlink '/Users/helenwhite/Local Sites/studio-hata-b/app/public/wp-content/themes/hata-kadence'
```

Each command should print the corresponding absolute path in this repository.
Verify `hata-core.php` in each site's `mu-plugins` directory the same way. If a
Local site was created under a different path, use that path in both the setup
and verification commands.
