<?php
/**
 * File upload handling for video files and thumbnail images, plus
 * embed-code sanitizing. Every upload is validated by real content
 * (finfo/getimagesize), never by filename/extension alone, saved under
 * a random name (never the client-supplied filename), and the uploads/
 * directory is script-execution-disabled via its own .htaccess as a
 * second layer of defense.
 */

const MAX_VIDEO_BYTES = 200 * 1024 * 1024; // 200MB — matches .user.ini; PHP itself will reject larger before we even see them
const MAX_THUMB_BYTES = 8 * 1024 * 1024;   // 8MB

const ALLOWED_VIDEO_MIME = [
    'video/mp4'       => 'mp4',
    'video/webm'      => 'webm',
    'video/quicktime' => 'mov',
];

const ALLOWED_IMAGE_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Validate + move an uploaded video file. Returns ['ok'=>bool, 'path'=>?string, 'error'=>?string].
 * $path, when set, is a web-servable path like /uploads/videos/xxxx.mp4.
 */
function handle_video_upload(array $file): array
{
    return handle_file_upload($file, MAX_VIDEO_BYTES, ALLOWED_VIDEO_MIME, __DIR__ . '/../uploads/videos', '/uploads/videos');
}

/** Same shape as handle_video_upload(), for thumbnail images. */
function handle_thumbnail_upload(array $file): array
{
    $result = handle_file_upload($file, MAX_THUMB_BYTES, ALLOWED_IMAGE_MIME, __DIR__ . '/../uploads/thumbnails', '/uploads/thumbnails');
    if ($result['ok'] && @getimagesize(__DIR__ . '/../uploads/thumbnails/' . basename($result['path'])) === false) {
        // finfo said "image/jpeg" etc but it isn't decodable as one — reject and clean up.
        @unlink(__DIR__ . '/../uploads/thumbnails/' . basename($result['path']));
        return ['ok' => false, 'path' => null, 'error' => 'That file is not a valid image.'];
    }
    return $result;
}

function handle_file_upload(array $file, int $maxBytes, array $allowedMime, string $destDir, string $publicPrefix): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'path' => null, 'error' => null]; // nothing submitted — not necessarily an error, caller decides
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'That file is larger than this server currently allows.',
            UPLOAD_ERR_FORM_SIZE  => 'That file is larger than this form allows.',
            UPLOAD_ERR_PARTIAL    => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server storage is misconfigured (no temp directory). Contact the site owner.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not save the file. Contact the site owner.',
        ];
        return ['ok' => false, 'path' => null, 'error' => $messages[$file['error']] ?? 'Upload failed.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'path' => null, 'error' => 'Upload failed validation.'];
    }
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        $mb = round($maxBytes / 1024 / 1024);
        return ['ok' => false, 'path' => null, 'error' => "File must be under {$mb}MB."];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMime[$mime])) {
        $types = implode(', ', array_values($allowedMime));
        return ['ok' => false, 'path' => null, 'error' => "Unsupported file type. Allowed: {$types}."];
    }

    if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        return ['ok' => false, 'path' => null, 'error' => 'Server storage is misconfigured. Contact the site owner.'];
    }

    $ext = $allowedMime[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $destDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['ok' => false, 'path' => null, 'error' => 'Could not save the uploaded file.'];
    }

    return ['ok' => true, 'path' => $publicPrefix . '/' . $filename, 'error' => null];
}

/**
 * Delete a previously-uploaded file given its public path (e.g.
 * /uploads/videos/xxxx.mp4), but only if it's actually inside our own
 * uploads/ directory — never touch anything else, and silently no-op
 * for external URLs (which don't start with /uploads/).
 */
function delete_uploaded_file(?string $publicPath): void
{
    if (!$publicPath || !str_starts_with($publicPath, '/uploads/')) {
        return;
    }
    $real = realpath(__DIR__ . '/..' . $publicPath);
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($real && $uploadsRoot && str_starts_with($real, $uploadsRoot)) {
        @unlink($real);
    }
}

/**
 * Pull a usable embed URL out of either a bare URL or a pasted
 * <iframe> embed snippet. Returns null if nothing valid was found.
 * We only ever keep the src URL — the rest of any pasted HTML is
 * discarded, never stored or rendered.
 */
function extract_embed_src(string $input): ?string
{
    $input = trim($input);
    if ($input === '') {
        return null;
    }

    $url = $input;
    if (preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $input, $m)) {
        $url = $m[1];
    }

    $url = html_entity_decode($url, ENT_QUOTES);
    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $url)) {
        return null;
    }

    return $url;
}
