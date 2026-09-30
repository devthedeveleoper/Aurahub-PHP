<?php
// Set the database credentials and API keys here.
$config = [
    'DB_URI' => getenv('DATABASE_URL') ?: '',
    'AURA_API_BASE_URL' => 'https://aurahub-api-hono.ashwathama249.workers.dev',
    'AURA_API_KEY' => '',
    'UPLOAD_FOLDER_ID' => 'WOUhdqk7kEc',
    'AURA_PATH_UPLOAD' => '/file/upload',
    'AURA_PATH_UPLOAD_URL' => '/upload/url',
    'AURA_PATH_REMOTE_ADD' => '/remote/add',
    'AURA_PATH_REMOTE_STATUS' => '/remote/status',
    'AURA_PATH_DELETE' => '/file/delete',
    'AURA_PATH_THUMBNAIL' => '/fs/files/thumbnail',
    'AURA_UPLOAD_FIELD' => 'file',
    'PLAYER_URL_TEMPLATE' => 'https://streamtape.com/e/{id}',
    'FREEIMAGE_API_KEY' => '6d207e02198a847aa98d0a2a901485a5',
    'MAX_VIDEO_MB' => '8',
    'MAX_IMAGE_MB' => '8',
];



// ---- Database ----
define('DB_URI', $config['DB_URI'] ?? '');

// ---- Aurahub API wrapper (Cloudflare Worker). Streamtape is never called directly. ----
define('WRAPPER_BASE_URL', rtrim($config['AURA_API_BASE_URL'], '/'));
define('WRAPPER_API_KEY', $config['AURA_API_KEY']);          // sent as "Authorization: Bearer ..." if set
define('WRAPPER_FOLDER_ID', $config['UPLOAD_FOLDER_ID']);      // optional upload folder

// Route paths on the wrapper.
define('WRAPPER_PATH_UPLOAD', $config['AURA_PATH_UPLOAD']);
define('WRAPPER_PATH_UPLOAD_URL', $config['AURA_PATH_UPLOAD_URL']);
define('WRAPPER_PATH_REMOTE_ADD', $config['AURA_PATH_REMOTE_ADD']);
define('WRAPPER_PATH_REMOTE_STATUS', $config['AURA_PATH_REMOTE_STATUS']);
define('WRAPPER_PATH_DELETE', $config['AURA_PATH_DELETE']);
define('WRAPPER_PATH_THUMBNAIL', $config['AURA_PATH_THUMBNAIL']);
define('WRAPPER_UPLOAD_FIELD', $config['AURA_UPLOAD_FIELD']);

// Player URL; {id} is replaced with the stored file id. Playback uses the Streamtape embed link.
define('PLAYER_URL_TEMPLATE', $config['PLAYER_URL_TEMPLATE']);

// ---- Freeimage.host (thumbnails) ----
define('FREEIMAGE_API_KEY', $config['FREEIMAGE_API_KEY']);

// ---- Limits ----
// For Render free tier / Streamtape, max video size relies on the Cloudflare worker limits
// and Streamtape's own limits (which are generous). PHP limits must also be adjusted in php.ini.
define('MAX_VIDEO_MB', 2048); // 2GB limit for freedom!
define('MAX_IMAGE_MB', 10);
