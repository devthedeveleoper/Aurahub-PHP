<?php
namespace App\Controllers;
use Core\Controller;
use PDO;

class VideoController extends Controller {
    public function watch() {
        $id = (int)($_GET['id'] ?? 0);
        $videoModel = new \App\Models\VideoModel();
        $v = $videoModel->getVideoById($id);
        
        if (!$v) { 
            http_response_code(404); 
            $this->view('video/not_found');
            return;
        }

        $me = user();

        // Backfill thumbnail if missing
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $v['status'] === 'ready' && $v['stream_id'] && !$v['thumbnail_url']) {
            if ($t = wrapper_thumbnail($v['stream_id'])) {
                $videoModel->updateThumbnail($id, $t);
                $v['thumbnail_url'] = $t;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            if (!$me) redirect('/aurahub/public/login.php'); // Note: adjust route if login is ported
            $action = $_POST['action'] ?? '';

            $commentModel = new \App\Models\CommentModel();
            if ($action === 'like') {
                $videoModel->toggleLike($me['id'], $id);
            } elseif ($action === 'watch_later' && $v['status'] === 'ready') {
                $videoModel->toggleWatchLater($me['id'], $id);
            } elseif ($action === 'add_to_playlist' && $v['status'] === 'ready') {
                $playlistModel = new \App\Models\PlaylistModel();
                $playlistModel->addVideoToPlaylist((int)($_POST['playlist_id'] ?? 0), $id);
            } elseif ($action === 'comment') {
                $body = trim($_POST['body'] ?? '');
                if ($body !== '' && mb_strlen($body) <= 1000)
                    $commentModel->addComment($me['id'], $id, $body);
            } elseif ($action === 'edit_comment') {
                $body = trim($_POST['body'] ?? '');
                if ($body === '' || mb_strlen($body) > 1000)
                    redirect('/aurahub/public/watch?id=' . $id . '&comment_error=invalid#comments');
                $commentModel->editComment((int)($_POST['comment_id'] ?? 0), $me['id'], $id, $body);
            } elseif ($action === 'comment_like') {
                $commentModel->toggleCommentLike($me['id'], (int)($_POST['comment_id'] ?? 0), $id);
            } elseif ($action === 'delete_comment') {
                $commentModel->deleteComment((int)($_POST['comment_id'] ?? 0), $me['id'], $id, $v['user_id']);
            } elseif ($action === 'delete_video' && (int)$me['id'] === (int)$v['user_id']) {
                $videoModel->deleteVideo($id);
                if ($v['stream_id']) wrapper_delete($v['stream_id']);
                redirect('/aurahub/public/');
            }
            redirect('/aurahub/public/watch?id=' . $id . ($action === 'comment' ? '#comments' : ''));
        }

        // Count one view per session per video
        if ($v['status'] === 'ready' && empty($_SESSION['seen'][$id])) {
            $videoModel->incrementViewCount($id);
            $_SESSION['seen'][$id] = true; $v['views']++;
        }
        if ($me && $v['status'] === 'ready') {
            $videoModel->saveToHistory($me['id'], $id);
        }

        $likes = $videoModel->likeCount($id);
        $liked = $me ? $videoModel->hasLiked($me['id'], $id) : false;
        
        $saved = $me ? $videoModel->hasWatchLater($me['id'], $id) : false;
        
        $myPlaylists = [];
        if ($me && $v['status'] === 'ready') {
            $playlistModel = new \App\Models\PlaylistModel();
            $myPlaylists = $playlistModel->getAllPlaylistsWithVideoCount($me['id']);
        }

        $sortComments = $_GET['sort'] ?? 'newest';
        if (!in_array($sortComments, ['newest', 'popular'], true)) $sortComments = 'newest';
        $commentOrderBy = $sortComments === 'popular' ? 'like_count DESC, c.created_at DESC' : 'c.created_at DESC';

        $commentModel = new \App\Models\CommentModel();
        $comments = $commentModel->getCommentsForVideo($id, $me['id'] ?? null, $sortComments);

        $related = $videoModel->getRelatedVideos($id, 8);

        $this->view('video/watch', [
            'id' => $id,
            'v' => $v,
            'me' => $me,
            'likes' => $likes,
            'liked' => $liked,
            'saved' => $saved,
            'myPlaylists' => $myPlaylists,
            'sortComments' => $sortComments,
            'comments' => $comments,
            'related' => $related
        ]);
    }

    public function edit() {
        require_login();
        $me = user();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        
        $videoModel = new \App\Models\VideoModel();
        $video = $videoModel->getVideoForEdit($id, $me['id']);
        
        if (!$video) {
            http_response_code(404);
            $this->view('video/not_found');
            return;
        }

        $error = null;
        $title = $video['title'];
        $description = (string)($video['description'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($title === '' || mb_strlen($title) > 150) {
                $error = 'Title is required and must be 150 characters or fewer.';
            } elseif (mb_strlen($description) > 10000) {
                $error = 'Description must be 10,000 characters or fewer.';
            }

            $thumbnail = $_FILES['thumbnail'] ?? null;
            $thumbnailMime = null;
            if (!$error && $thumbnail && $thumbnail['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($thumbnail['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Thumbnail: ' . upload_error_message((int)$thumbnail['error']);
                } elseif ($thumbnail['size'] > MAX_IMAGE_MB * 1048576) {
                    $error = 'Thumbnail is larger than ' . MAX_IMAGE_MB . ' MB.';
                } else {
                    $fi = new \finfo(FILEINFO_MIME_TYPE);
                    $thumbnailMime = $fi->file($thumbnail['tmp_name']) ?: '';
                    if (!in_array($thumbnailMime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true))
                        $error = 'Thumbnail must be a JPG, PNG, WebP or GIF image.';
                }
            }

            if (!$error) {
                try {
                    $thumbnailUrl = null;
                    if ($thumbnail && $thumbnail['error'] === UPLOAD_ERR_OK) {
                        session_write_close();
                        $thumbnailUrl = freeimage_upload($thumbnail['tmp_name'], $thumbnail['name'], $thumbnailMime);
                    }

                    $videoModel->updateVideoDetails($id, $me['id'], $title, $description, $thumbnailUrl);
                    redirect('/aurahub/public/watch?id=' . $id);
                } catch (\Throwable $ex) {
                    $error = $ex->getMessage();
                }
            }
        }

        $this->view('video/edit', [
            'id' => $id,
            'video' => $video,
            'title' => $title,
            'description' => $description,
            'error' => $error
        ]);
    }

    public function report() {
        require_login();
        $me = user();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        
        $videoModel = new \App\Models\VideoModel();
        $video = $videoModel->getVideoForReport($id);
        
        if (!$video || (int)$video['user_id'] === (int)$me['id']) {
            http_response_code(404);
            $this->view('video/not_found');
            return;
        }

        $existingReport = $videoModel->hasExistingReport($me['id'], $id);
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingReport) {
            csrf_check();
            $reason = $_POST['reason'] ?? '';
            $details = trim($_POST['details'] ?? '');
            $reasons = ['spam', 'harassment', 'copyright', 'misleading', 'other'];
            if (!in_array($reason, $reasons, true)) {
                $error = 'Choose a report reason.';
            } elseif (mb_strlen($details) > 1000) {
                $error = 'Details must be 1,000 characters or fewer.';
            } else {
                $reportModel = new \App\Models\ReportModel();
                $reportModel->createReport($id, $me['id'], $reason, $details, $video['title']);
                redirect('/aurahub/public/watch?id=' . $id . '&reported=1');
            }
        }

        $this->view('video/report', [
            'id' => $id,
            'video' => $video,
            'existingReport' => $existingReport,
            'error' => $error
        ]);
    }

    public function status() {
        header('Content-Type: application/json'); 
        header('Cache-Control: no-store');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sync_remote_status') {
            try {
                csrf_check();
                $me = user();
                $id = (int)($_POST['id'] ?? 0);
                $videoModel = new \App\Models\VideoModel();
                $video = $videoModel->getVideoForStatus($id);
                if (!$me || !$video || (int)$video['user_id'] !== (int)$me['id']) {
                    http_response_code(403);
                    echo json_encode(['state' => 'failed', 'message' => 'Not allowed']);
                    exit;
                }

                $apiResponse = json_decode($_POST['api_response'] ?? '', true);
                if (!is_array($apiResponse)) throw new \RuntimeException('Invalid status response.');
                $result = wrapper_parse_remote_status_response((string)$video['remote_id'], $apiResponse);
                if ($result['state'] === 'ready') {
                    $thumb = wrapper_thumbnail($result['file_id']);
                    $videoModel->updateVideoStatusReady($id, $result['file_id'], $thumb);
                } elseif ($result['state'] === 'failed') {
                    $videoModel->updateVideoStatusFailed($id, $result['message']);
                }
                echo json_encode(['state' => $result['state'], 'message' => $result['message']]);
            } catch (\Throwable $ex) {
                http_response_code(400);
                echo json_encode(['state' => 'failed', 'message' => $ex->getMessage()]);
            }
            exit;
        }

        session_write_close();

        $id = (int)($_GET['id'] ?? 0);
        $videoModel = new \App\Models\VideoModel();
        $v = $videoModel->getVideoForStatus($id);
        
        if (!$v) { http_response_code(404); echo json_encode(['state' => 'failed', 'message' => 'Not found']); exit; }
        if ($v['status'] !== 'processing') { echo json_encode(['state' => $v['status'], 'message' => $v['status_msg']]); exit; }

        if ($v['checked_at'] && time() - strtotime($v['checked_at']) < 4) { echo json_encode(['state' => 'processing']); exit; }
        $videoModel->updateVideoCheckedAt($id);

        try {
            $r = wrapper_remote_status((string)$v['remote_id']);
        } catch (\Throwable $ex) {
            echo json_encode(['state' => 'processing']); exit;   
        }
        
        if ($r['state'] === 'ready') {
            $thumb = wrapper_thumbnail($r['file_id']);
            $videoModel->updateVideoStatusReady($id, $r['file_id'], $thumb);
        } elseif ($r['state'] === 'failed') {
            $videoModel->updateVideoStatusFailed($id, $r['message']);
        }
        echo json_encode(['state' => $r['state'], 'message' => $r['message']]);
        exit;
    }
}
