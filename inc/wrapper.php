<?php
declare(strict_types=1);
/**
 * Everything that talks to the Cloudflare Worker wrapper and Freeimage.host lives here.
 * If your worker's routes or JSON shapes differ, this is the only file (plus config.php) to change.
 */

/** Call the wrapper. Returns the decoded JSON body. Throws on network or non-JSON errors. */
function wrapper_call(string $method, string $path, array $query = [], ?array $post = null, int $timeout = 30): array {
    if (WRAPPER_FOLDER_ID !== '' && !isset($query['folder']) && in_array($path, [WRAPPER_PATH_UPLOAD, WRAPPER_PATH_UPLOAD_URL, WRAPPER_PATH_REMOTE_ADD], true))
        $query['folder'] = WRAPPER_FOLDER_ID;
    $url = rtrim(WRAPPER_BASE_URL, '/') . $path . ($query ? '?' . http_build_query($query) : '');

    $headers = ['Accept: application/json'];
    if (WRAPPER_API_KEY !== '') $headers[] = 'Authorization: Bearer ' . WRAPPER_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT        => $timeout,     // 0 = no limit (big uploads)
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => $method,
    ]);
    if ($post !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $body = curl_exec($ch);
    if ($body === false) { $err = curl_error($ch); curl_close($ch); throw new RuntimeException('Could not reach the Aurahub API: ' . $err); }
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $j = json_decode((string)$body, true);
    if (!is_array($j)) throw new RuntimeException('Aurahub API returned an unexpected response (HTTP ' . $code . ').');
    $st = $j['status'] ?? $code;
    if (($j['success'] ?? true) === false || (is_numeric($st) && (int)$st >= 400))
        throw new RuntimeException('Aurahub API error: ' . ($j['msg'] ?? $j['message'] ?? $j['error'] ?? ('HTTP ' . $code)));
    return $j;
}

/** Pull a file id out of an API result (id / linkid / file_id, or a /v/{id}/ or /e/{id} link). */
function wrapper_extract_file_id($data): ?string {
    if (is_string($data) && preg_match('~/(?:v|e)/([A-Za-z0-9]+)~', $data, $m)) return $m[1];
    if (!is_array($data)) return null;
    foreach (['linkid', 'file_id', 'fileId', 'id'] as $k)
        if (!empty($data[$k]) && is_string($data[$k])) return $data[$k];
    foreach (['url', 'link'] as $k)
        if (!empty($data[$k]) && ($id = wrapper_extract_file_id((string)$data[$k]))) return $id;
    if (isset($data['result'])) return wrapper_extract_file_id($data['result']);
    return null;
}

/** Local file upload through the wrapper. Returns the file id. */
function wrapper_upload(string $tmpPath, string $origName, string $mime): string {
    $j = wrapper_call('POST', WRAPPER_PATH_UPLOAD, [], [WRAPPER_UPLOAD_FIELD => new CURLFile($tmpPath, $mime, $origName)], 0);
    $id = wrapper_extract_file_id($j);
    if (!$id) throw new RuntimeException('Upload finished but the API did not return a file id.');
    return $id;
}

/** Request a temporary upload URL for a browser-to-host video upload. */
function wrapper_upload_url(): string {
    $j = wrapper_call('GET', WRAPPER_PATH_UPLOAD_URL);
    $url = $j['url'] ?? ($j['result']['url'] ?? null);
    $parts = is_string($url) ? parse_url($url) : false;
    if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) ||
        !preg_match('/(^|\.)tapecontent\.net$/i', $parts['host']))
        throw new RuntimeException('The upload API returned an invalid temporary upload URL.');
    return $url;
}

/** Start a remote upload (the wrapper/host fetches the URL itself). Returns the remote upload id. */
function wrapper_remote_add(string $url): string {
    $j = wrapper_call('GET', WRAPPER_PATH_REMOTE_ADD, ['url' => $url]);
    $r = $j['result'] ?? $j;
    $id = is_array($r) ? ($r['id'] ?? $r['remote_id'] ?? $r['remoteId'] ?? null) : null;
    if (!$id) throw new RuntimeException('Remote upload was not accepted (no upload id returned).');
    return (string)$id;
}

/** Check a remote upload. Returns ['state' => processing|ready|failed, 'file_id' => ?string, 'message' => ?string]. */
function wrapper_remote_status(string $remoteId): array {
    $j = wrapper_call('GET', WRAPPER_PATH_REMOTE_STATUS, ['id' => $remoteId]);
    return wrapper_parse_remote_status_response($remoteId, $j);
}

/** Parse a remote status response received from either the wrapper or the browser. */
function wrapper_parse_remote_status_response(string $remoteId, array $j): array {
    $r = $j['result'] ?? $j;
    $item = $r;
    if (is_array($r) && !isset($r['status'])) $item = $r[$remoteId] ?? (reset($r) ?: []);
    if (!is_array($item)) $item = [];

    $st = strtolower((string)($item['status'] ?? ''));
    if (in_array($st, ['finished', 'completed', 'complete', 'done', 'ready', 'success'], true)) {
        $fid = wrapper_extract_file_id($item);
        // Don't mistake the remote-upload id for the file id.
        if ($fid === $remoteId) $fid = wrapper_extract_file_id(['linkid' => $item['linkid'] ?? null, 'url' => $item['url'] ?? ($item['link'] ?? null)]);
        return $fid ? ['state' => 'ready', 'file_id' => $fid, 'message' => null]
                    : ['state' => 'failed', 'file_id' => null, 'message' => 'Finished, but no file id was returned.'];
    }
    if (in_array($st, ['error', 'failed', 'cancelled', 'canceled'], true))
        return ['state' => 'failed', 'file_id' => null, 'message' => (string)($item['msg'] ?? $item['message'] ?? 'The host could not fetch that URL.')];
    return ['state' => 'processing', 'file_id' => null, 'message' => null];
}

/** Thumbnail URL for a hosted file, from the wrapper. Returns null if it isn't available (yet). */
function wrapper_thumbnail(string $fileId): ?string {
    try {
        $j = wrapper_call('GET', rtrim(WRAPPER_PATH_THUMBNAIL, '/') . '/' . rawurlencode($fileId), [], null, 10);
        $u = $j['thumbnail_url'] ?? null;
        return (is_string($u) && preg_match('~^https?://~i', $u)) ? $u : null;
    } catch (Throwable $e) { return null; }
}

/** Best-effort file removal. */
function wrapper_delete(string $fileId): void {
    try { wrapper_call('GET', WRAPPER_PATH_DELETE, ['file' => $fileId], null, 20); } catch (Throwable $e) { /* ignore */ }
}

function player_url(string $fileId): string { return str_replace('{id}', rawurlencode($fileId), PLAYER_URL_TEMPLATE); }

/** Thumbnail upload to Freeimage.host (unchanged). Returns the image URL. */
function freeimage_upload(string $tmpPath, string $origName, string $mime): string {
    $ch = curl_init('https://freeimage.host/api/1/upload');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 120, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['key' => FREEIMAGE_API_KEY, 'action' => 'upload', 'format' => 'json',
                               'source' => new CURLFile($tmpPath, $mime, $origName)],
    ]);
    $body = curl_exec($ch);
    if ($body === false) { $err = curl_error($ch); curl_close($ch); throw new RuntimeException('Thumbnail upload failed: ' . $err); }
    curl_close($ch);
    $j = json_decode((string)$body, true);
    $url = $j['image']['url'] ?? null;
    if (!$url) throw new RuntimeException('Thumbnail upload failed: ' . ($j['error']['message'] ?? $j['status_txt'] ?? 'unknown error'));
    return (string)$url;
}

function upload_error_message(int $code): string {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is larger than the server allows. Raise upload_max_filesize / post_max_size.',
        UPLOAD_ERR_PARTIAL   => 'The upload was interrupted. Try again.',
        UPLOAD_ERR_NO_FILE   => 'Choose a video file.',
        default              => 'Upload failed (code ' . $code . ').',
    };
}
