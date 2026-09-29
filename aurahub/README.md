# Aurahub (PHP + MySQL prototype)

All video hosting goes through your Cloudflare Worker wrapper. Streamtape is never called directly.
Thumbnails still go to Freeimage.host.

Signed-in users can save ready videos to their Watch Later list from a video's watch page.
Signed-in users also have a private Recently watched history with per-video removal and clear-all controls.
Comment authors can edit their own comments from the video discussion.
Viewers can like comments, and playlist owners can reorder playlist videos.
Creators can view published-video counts, total and average views, and a per-video view breakdown in Creator analytics.
Admins can suspend or reactivate regular accounts; reporters can read moderator decision notes in My reports.
Users can change their password from Account settings after verifying the current password.
Video owners can edit a video's title and description or replace its thumbnail from the watch page.
Public creator channels list each user's published videos and are linked from creator names throughout the site.
Creators can add a short bio to their channel page.
Users can create playlists, add videos from the watch page, and choose public or private visibility.
The home feed and search results can filter by creator and sort by newest, oldest, or most viewed.
Users can report videos, and administrators can review reports and remove videos.
Reporters can track pending, resolved, and dismissed reports from My reports.

## Setup
1. Set database credentials, API keys, and limits in the `$config` array in `config.php`.
2. Import `schema.sql`. For an existing installation, run each applicable `migration_*.sql` file once. `migration_feature_bundle.sql` adds comment likes, playlist ordering, account suspension, and persistent report updates; other feature migrations add their named feature tables/columns.
3. Promote the first administrator with `UPDATE users SET role = 'admin' WHERE username = 'your_username';` in phpMyAdmin. New accounts always receive the regular user role.
4. Serve with PHP 8.1+ (pdo_mysql, curl, fileinfo). Local: 
   `php -d upload_max_filesize=512M -d post_max_size=520M -d max_execution_time=0 -S localhost:8000`
   Nginx also needs `client_max_body_size 520M;`.


## InfinityFree deployment
Create a MySQL database in the hosting panel, then import `schema.sql` into that database using phpMyAdmin. The schema intentionally does not create or select a database.
Upload the project files into `htdocs`, including the replacement `.htaccess`; do not copy PHP `php_value` directives into `.htaccess`.
Use PHP 8.1+ and confirm `pdo_mysql`, `curl`, and `fileinfo` are enabled. Set the database credentials and API keys in the `$config` array near the top of `config.php`. `.htaccess` denies direct web access to `config.php`.

Remote link import is the default. Choosing a local file requests a temporary URL from the Worker, then the browser uploads the video directly to that URL; the PHP server does not receive the video bytes. The app currently limits direct video uploads to 8 MB. The optional thumbnail still passes through PHP and must fit the host's PHP upload limits.

The watch page checks import status every 15 seconds and stops after 80 attempts (about 20 minutes). Visitors can refresh afterward. InfinityFree's exact daily hit quota, PHP extensions/settings, database version, and ability to make outbound HTTPS requests can change; verify these in the hosting panel and with a temporary server-side test before relying on the deployment. Delete any diagnostic PHP file immediately after testing. Free shared hosting may not provide cron, so scheduled cleanup/auto-clone jobs must run elsewhere.

## The wrapper contract (config.php + inc/wrapper.php)
The worker's routes were not visible to me, so these are assumptions. Change the `AURA_PATH_*` / `PLAYER_URL_TEMPLATE` variables
(and the small parsing helpers in `inc/wrapper.php` if the JSON differs).

| Action        | Default route         | Expected response                                           |
|---------------|-----------------------|-------------------------------------------------------------|
| Upload URL    | GET `/upload/url`     | `{url, valid_until}`; browser POSTs multipart field `file` directly to `url` |
| Upload result | POST temporary URL    | JSON containing `result.id` (or a `/v/{id}/...` link)        |
| Remote add    | GET `/remote/add?url=`| `{result:{id}}` = remote upload id                          |
| Remote status | GET `/remote/status?id=` | `{result:{<id>:{status, linkid|url}}}`, status `finished`/`error` |
| Delete        | GET `/file/delete?file=` | any 2xx JSON                                             |
| Player        | `PLAYER_URL_TEMPLATE` | default `https://streamtape.com/e/{id}` (`{id}` = stored file id) |

## Remote upload flow
Add a video from a link -> row saved as `processing` with the remote upload id -> the watch page polls
`status.php` every 5s (throttled server-side to one wrapper call per 4s per video) -> on `finished` the
file id is stored and the video goes `ready` (and appears in the feed). On error it shows the message.
