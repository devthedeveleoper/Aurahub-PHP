# Aurahub — Open-Source Freedom Video Platform

Aurahub is a lightweight, open-source video platform template designed with a **freedom-first philosophy** (similar to Telegram). It allows for large video uploads, emphasizes user privacy, and relies on an explicit legal-only moderation policy rather than algorithmic censorship.

This template is built in pure PHP (no framework) and MySQL, making it extremely easy to deploy on free tiers like **Render** or **InfinityFree**. Video hosting is offloaded to a Cloudflare Worker (which can proxy to Streamtape or any other host), so you don't pay for video bandwidth or storage.

## Freedom-First Features

- **Upload Anything:** Supports up to 2GB videos via Streamtape / Cloudflare Worker integration. No artificial software limits.
- **Privacy by Default:** Anonymous accounts supported (email is optional during sign-up).
- **Visibility Controls:** Creators can set videos to Public, Unlisted, Private, or Subscribers Only.
- **Chronological Feeds:** No algorithmic suppression or shadow-banning. The newest content is what people see.
- **Legal-Only Moderation:** The reporting system is restricted to explicitly illegal categories (CSAM, terrorism, doxxing, real-world violence) rather than vague "community guidelines."

## Core Capabilities

- Modern, fast dark-mode UI (zero CSS/JS frameworks).
- **Topic Communities:** Telegram-style groups for discussion and video sharing.
- **Direct Messaging:** Private, unmoderated 1-on-1 messaging.
- **Creator Channels:** Featuring pinned videos, bios, and subscription feeds.
- Watch Later, Watch History (Opt-in), and user-curated Playlists.
- Creator Analytics (views, total/average breakdowns).
- Comment sections with likes and replies.
- Admin dashboard for user suspension and report resolution.
- Live **Transparency Report** detailing all legal takedowns.

---

## Deployment (Render & Cloudflare)

This architecture separates the lightweight PHP web app from the heavy video hosting. 

1. **Database:** Deploy a free MySQL database (e.g., Aiven, PlanetScale, or a shared host). Import `schema.sql`.
2. **Video Host (Cloudflare Worker):** All video uploads go directly from the user's browser to your Cloudflare Worker, bypassing the PHP server completely. The PHP server never touches the video bytes, allowing you to host the web app on extremely limited free tiers (like Render's free Web Service).
3. **Web App:** 
   - Deploy this repository to Render as a "Web Service" using the Docker environment or PHP environment.
   - Set database credentials, API keys, and limits in the `$config` array in `config.php`.
   - Update `AURA_API_BASE_URL` in `config.php` to point to your Cloudflare Worker.

### Setup Instructions

1. Configure `$config` in `config.php`.
2. Import `schema.sql` into your database.
3. Promote the first administrator with `UPDATE users SET role = 'admin' WHERE username = 'your_username';` in your database manager.
4. Local testing (PHP 8.1+):
   `php -d upload_max_filesize=512M -d post_max_size=520M -d max_execution_time=0 -S localhost:8000`

### The Wrapper Contract (Cloudflare Worker)
Aurahub expects your worker to handle the following routes. Update `config.php` if your JSON response structure differs.

| Action        | Default route         | Expected response                                           |
|---------------|-----------------------|-------------------------------------------------------------|
| Upload URL    | GET `/upload/url`     | `{url, valid_until}`; browser POSTs multipart field `file` directly to `url` |
| Upload result | POST temporary URL    | JSON containing `result.id` (or a `/v/{id}/...` link)        |
| Remote add    | GET `/remote/add?url=`| `{result:{id}}` = remote upload id                          |
| Remote status | GET `/remote/status?id=` | `{result:{<id>:{status, linkid|url}}}`, status `finished`/`error` |
| Delete        | GET `/file/delete?file=` | any 2xx JSON                                             |

*Note: For remote link imports (the default upload method in the UI), the watch page polls `status.php` until the worker reports the download is finished.*

## Public REST API

Aurahub provides a public, unauthenticated API for third-party clients and archival tools. All responses are JSON and support CORS.

- `GET /api/videos?page=1&limit=20` — Returns a paginated list of the newest public videos.
- `GET /api/video?id={id}` — Returns details for a specific public video.
- `GET /api/channel?u={username}&page=1&limit=20` — Returns channel profile info and a paginated list of their public videos.
