<?php
namespace App\Models;
use Core\Model;
use PDO;
use Throwable;

class PlaylistModel extends Model {

    public function getPublicPlaylistsForUser($userId) {
        $st = static::db()->prepare("SELECT p.id, p.name, COUNT(pv.video_id) AS video_count
                                         FROM playlists p LEFT JOIN playlist_videos pv ON pv.playlist_id = p.id
                                         WHERE p.user_id = ? AND p.visibility = 'public'
                                         GROUP BY p.id ORDER BY p.created_at DESC");
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function getAllPlaylistsWithVideoCount($userId) {
        $st = static::db()->prepare('SELECT p.id, p.name, p.visibility, p.created_at, COUNT(pv.video_id) AS video_count
                                     FROM playlists p LEFT JOIN playlist_videos pv ON pv.playlist_id = p.id
                                     WHERE p.user_id = ? GROUP BY p.id ORDER BY p.created_at DESC');
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function getPlaylistById($playlistId) {
        $st = static::db()->prepare('SELECT p.*, u.username FROM playlists p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
        $st->execute([$playlistId]);
        return $st->fetch();
    }

    public function createPlaylist($userId, $name, $visibility) {
        static::db()->prepare('INSERT INTO playlists (user_id, name, visibility) VALUES (?, ?, ?)')
            ->execute([$userId, $name, $visibility]);
        return (int)static::db()->lastInsertId();
    }

    public function updatePlaylist($playlistId, $userId, $name, $visibility) {
        static::db()->prepare('UPDATE playlists SET name = ?, visibility = ? WHERE id = ? AND user_id = ?')
            ->execute([$name, $visibility, $playlistId, $userId]);
    }

    public function deletePlaylist($playlistId, $userId) {
        static::db()->prepare('DELETE FROM playlists WHERE id = ? AND user_id = ?')
            ->execute([$playlistId, $userId]);
    }

    public function removeVideoFromPlaylist($playlistId, $videoId) {
        static::db()->prepare('DELETE FROM playlist_videos WHERE playlist_id = ? AND video_id = ?')
            ->execute([$playlistId, $videoId]);
    }

    public function addVideoToPlaylist($playlistId, $videoId) {
        $count = static::db()->prepare('SELECT COUNT(*) FROM playlist_videos WHERE playlist_id = ? AND video_id = ?');
        $count->execute([$playlistId, $videoId]);
        if ($count->fetchColumn() > 0) return false;
        
        static::db()->prepare('INSERT INTO playlist_videos (playlist_id, video_id, position) SELECT ?, ?, COALESCE(MAX(position),0)+1 FROM playlist_videos WHERE playlist_id = ?')
            ->execute([$playlistId, $videoId, $playlistId]);
        return true;
    }

    public function moveVideo($playlistId, $videoId, $direction) {
        $db = static::db();
        $db->beginTransaction();
        try {
            $orderQuery = $db->prepare('SELECT video_id, position FROM playlist_videos WHERE playlist_id = ? ORDER BY position ASC, added_at DESC, video_id ASC FOR UPDATE');
            $orderQuery->execute([$playlistId]);
            $orderedIds = array_map('intval', array_column($orderQuery->fetchAll(), 'video_id'));
            foreach ($orderedIds as $index => $orderedId) {
                $db->prepare('UPDATE playlist_videos SET position = ? WHERE playlist_id = ? AND video_id = ?')
                    ->execute([$index, $playlistId, $orderedId]);
            }

            $currentIndex = array_search($videoId, $orderedIds, true);
            $neighborIndex = $currentIndex === false ? false : ($direction === 'up' ? $currentIndex - 1 : $currentIndex + 1);
            if ($currentIndex !== false && isset($orderedIds[$neighborIndex])) {
                $neighborId = $orderedIds[$neighborIndex];
                $db->prepare('UPDATE playlist_videos SET position = ? WHERE playlist_id = ? AND video_id = ?')
                    ->execute([$neighborIndex, $playlistId, $videoId]);
                $db->prepare('UPDATE playlist_videos SET position = ? WHERE playlist_id = ? AND video_id = ?')
                    ->execute([$currentIndex, $playlistId, $neighborId]);
            }
            $db->commit();
        } catch (Throwable $ex) {
            if ($db->inTransaction()) $db->rollBack();
            throw $ex;
        }
    }

    public function countPlaylistVideos($playlistId) {
        $count = static::db()->prepare("SELECT COUNT(*) FROM playlist_videos pv JOIN videos v ON v.id = pv.video_id WHERE pv.playlist_id = ? AND v.status = 'ready'");
        $count->execute([$playlistId]);
        return (int)$count->fetchColumn();
    }

    public function getPlaylistVideos($playlistId, $limit, $offset) {
        $items = static::db()->prepare("SELECT v.id, v.title, v.thumbnail_url, v.views, v.created_at, u.username, pv.added_at
                                        FROM playlist_videos pv JOIN videos v ON v.id = pv.video_id JOIN users u ON u.id = v.user_id
                                        WHERE pv.playlist_id = ? AND v.status = 'ready'
                                        ORDER BY pv.position ASC, pv.added_at DESC, pv.video_id ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
        $items->execute([$playlistId]);
        return $items->fetchAll();
    }

    public function getPlaylistVideoIds($playlistId) {
        $orderedReady = static::db()->prepare("SELECT pv.video_id FROM playlist_videos pv JOIN videos v ON v.id = pv.video_id
                                               WHERE pv.playlist_id = ? AND v.status = 'ready'
                                               ORDER BY pv.position ASC, pv.added_at DESC, pv.video_id ASC");
        $orderedReady->execute([$playlistId]);
        return array_map('intval', $orderedReady->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getUserPlaylistsForVideo($userId, $videoId) {
        $st = static::db()->prepare('SELECT p.id, p.name, p.visibility,
                                     (SELECT 1 FROM playlist_videos pv WHERE pv.playlist_id = p.id AND pv.video_id = ?) AS in_playlist
                                     FROM playlists p WHERE p.user_id = ? ORDER BY p.created_at DESC');
        $st->execute([$videoId, $userId]);
        return $st->fetchAll();
    }
}
