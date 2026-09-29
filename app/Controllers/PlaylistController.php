<?php
namespace App\Controllers;
use Core\Controller;
use PDO;
use Throwable;

class PlaylistController extends Controller {
    
    // Equivalent to playlists.php
    public function index() {
        require_login();
        $me = user();
        $error = null;

        $playlistModel = new \App\Models\PlaylistModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $name = trim($_POST['name'] ?? '');
                $visibility = $_POST['visibility'] ?? 'private';
                if ($name === '' || mb_strlen($name) > 100) {
                    $error = 'Playlist name is required and must be 100 characters or fewer.';
                } elseif (!in_array($visibility, ['private', 'public'], true)) {
                    $error = 'Choose public or private visibility.';
                } else {
                    $newId = $playlistModel->createPlaylist($me['id'], $name, $visibility);
                    redirect('/aurahub/public/playlist?id=' . $newId);
                }
            } elseif ($action === 'delete') {
                $playlistModel->deletePlaylist((int)($_POST['playlist_id'] ?? 0), $me['id']);
                redirect('/aurahub/public/playlists');
            }
        }

        $playlists = $playlistModel->getAllPlaylistsWithVideoCount($me['id']);

        $this->view('playlist/index', [
            'playlists' => $playlists,
            'error' => $error
        ]);
    }

    // Equivalent to playlist.php
    public function viewPlaylist() {
        $me = user();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $playlistModel = new \App\Models\PlaylistModel();
        $playlist = $playlistModel->getPlaylistById($id);

        if (!$playlist || ($playlist['visibility'] === 'private' && (!$me || (int)$me['id'] !== (int)$playlist['user_id']))) {
            http_response_code(404);
            $this->view('playlist/not_found');
            return;
        }

        $isOwner = $me && (int)$me['id'] === (int)$playlist['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            if (!$isOwner) { http_response_code(404); exit('Playlist not found.'); }
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update') {
                $name = trim($_POST['name'] ?? '');
                $visibility = $_POST['visibility'] ?? '';
                if ($name !== '' && mb_strlen($name) <= 100 && in_array($visibility, ['private', 'public'], true)) {
                    $playlistModel->updatePlaylist($id, $me['id'], $name, $visibility);
                }
                redirect('/aurahub/public/playlist?id=' . $id);
            } elseif ($action === 'remove_video') {
                $playlistModel->removeVideoFromPlaylist($id, (int)($_POST['video_id'] ?? 0));
                redirect('/aurahub/public/playlist?id=' . $id);
            } elseif ($action === 'move_video') {
                $videoId = (int)($_POST['video_id'] ?? 0);
                $direction = $_POST['direction'] ?? '';
                if (!in_array($direction, ['up', 'down'], true)) redirect('/aurahub/public/playlist?id=' . $id);

                $playlistModel->moveVideo($id, $videoId, $direction);
                redirect('/aurahub/public/playlist?id=' . $id);
            }
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $total = $playlistModel->countPlaylistVideos($id);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        
        $items = $playlistModel->getPlaylistVideos($id, $perPage, $offset);
        $orderedReadyIds = $playlistModel->getPlaylistVideoIds($id);

        $this->view('playlist/view', [
            'id' => $id,
            'playlist' => $playlist,
            'isOwner' => $isOwner,
            'items' => $items,
            'orderedReadyIds' => $orderedReadyIds,
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ]);
    }
}
