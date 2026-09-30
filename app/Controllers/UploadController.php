<?php
namespace App\Controllers;
use Core\Controller;
use RuntimeException;
use Throwable;
use finfo;

class UploadController extends Controller {
    public function index() {
        require_login();
        
        $currentUser = user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['complete_direct_upload', 'complete_remote_import'], true)) {
            header('Content-Type: application/json');
            try {
                csrf_check();
                $title = trim($_POST['title'] ?? '');
                $desc = trim($_POST['description'] ?? '');
                $categoryId = (int)($_POST['category_id'] ?? 0);
                if ($categoryId === 0) $categoryId = null;
                if ($title === '' || mb_strlen($title) > 150) throw new RuntimeException('Title is required (max 150 characters).');
                if (!$currentUser) throw new RuntimeException('Please log in again.');

                $action = $_POST['action'];
                $fileId = null;
                $remoteId = null;
                if ($action === 'complete_direct_upload') {
                    $uploadResponse = json_decode($_POST['upload_response'] ?? '', true);
                    $fileId = wrapper_extract_file_id($uploadResponse);
                    if (!$fileId) throw new RuntimeException('The upload service did not return a valid video ID.');
                } else {
                    $remoteId = trim($_POST['remote_id'] ?? '');
                    if ($remoteId === '' || strlen($remoteId) > 255) throw new RuntimeException('The upload service did not return a valid import ID.');
                }

                $thumbUrl = null;
                $tf = $_FILES['thumbnail'] ?? null;
                $tmime = '';
                if ($tf && $tf['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($tf['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Thumbnail: ' . upload_error_message((int)$tf['error']));
                    if ($tf['size'] > MAX_IMAGE_MB * 1048576) throw new RuntimeException('Thumbnail is larger than ' . MAX_IMAGE_MB . ' MB.');
                    $fi = new finfo(FILEINFO_MIME_TYPE);
                    $tmime = $fi->file($tf['tmp_name']) ?: '';
                    if (!in_array($tmime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true))
                        throw new RuntimeException('Thumbnail must be a JPG, PNG, WebP or GIF image.');
                }

                session_write_close();
                if ($tf && $tf['error'] === UPLOAD_ERR_OK)
                    $thumbUrl = freeimage_upload($tf['tmp_name'], $tf['name'], $tmime);
                
                $visibility = $_POST['visibility'] ?? 'public';
                if (!in_array($visibility, ['public', 'unlisted', 'private', 'subscribers'], true)) {
                    $visibility = 'public';
                }

                $videoModel = new \App\Models\VideoModel();
                if ($fileId) {
                    $thumbUrl ??= wrapper_thumbnail($fileId);
                    $newId = $videoModel->createDirectVideo($currentUser['id'], $title, $desc, $fileId, $thumbUrl, $categoryId, $visibility);
                } else {
                    $newId = $videoModel->createProcessingVideo($currentUser['id'], $title, $desc, $remoteId, $thumbUrl, $categoryId, $visibility);
                }
                echo json_encode(['ok' => true, 'redirect' => '/aurahub/public/watch?id=' . $newId]);
                exit;
            } catch (Throwable $ex) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'This form submission is no longer supported. Reload the page and try again.']);
            exit;
        }

        $videoModel = new \App\Models\VideoModel();
        $categories = $videoModel->getCategories();

        $this->view('video/upload', ['categories' => $categories]);
    }
}
