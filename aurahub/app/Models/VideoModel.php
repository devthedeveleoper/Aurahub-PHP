<?php
namespace App\Models;
use Core\Model;

class VideoModel extends Model {

    public function getFeed($query = '', $creator = '', $sort = 'newest', $limit = 12, $offset = 0) {
        $orderBy = match ($sort) {
          'popular' => 'v.views DESC, v.created_at DESC, v.id DESC',
          'oldest' => 'v.created_at ASC, v.id ASC',
          default => 'v.created_at DESC, v.id DESC',
        };

        $where = "WHERE v.status = 'ready'";
        $args = [];
        if ($query !== '') {
            $like = '%' . addcslashes($query, '%_\\') . '%';
            $where .= ' AND (v.title LIKE ? OR v.description LIKE ? OR u.username LIKE ?)';
            $args = [$like, $like, $like];
        }
        if ($creator !== '') {
            $where .= ' AND u.username = ?';
            $args[] = $creator;
        }

        $sql = "SELECT v.id, v.title, v.thumbnail_url, v.views, v.created_at, u.username
                FROM videos v JOIN users u ON u.id = v.user_id $where
                ORDER BY $orderBy LIMIT " . ($limit + 1) . " OFFSET $offset";
        
        $st = static::db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    }

    public function clearHistory($userId) {
        return static::db()->prepare('DELETE FROM watch_history WHERE user_id = ?')->execute([$userId]);
    }

    public function removeFromHistory($userId, $videoId) {
        return static::db()->prepare('DELETE FROM watch_history WHERE user_id = ? AND video_id = ?')->execute([$userId, $videoId]);
    }

    public function countHistory($userId) {
        $count = static::db()->prepare("SELECT COUNT(*) FROM watch_history wh JOIN videos v ON v.id = wh.video_id WHERE wh.user_id = ? AND v.status = 'ready'");
        $count->execute([$userId]);
        return (int)$count->fetchColumn();
    }

    public function getHistory($userId, $limit, $offset) {
        $st = static::db()->prepare("SELECT v.id, v.title, v.thumbnail_url, v.views, v.created_at, u.username, wh.watched_at
                                     FROM watch_history wh JOIN videos v ON v.id = wh.video_id JOIN users u ON u.id = v.user_id
                                     WHERE wh.user_id = ? AND v.status = 'ready'
                                     ORDER BY wh.watched_at DESC LIMIT $limit OFFSET $offset");
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function removeFromWatchLater($userId, $videoId) {
        return static::db()->prepare('DELETE FROM watch_later WHERE user_id = ? AND video_id = ?')->execute([$userId, $videoId]);
    }

    public function countWatchLater($userId) {
        $count = static::db()->prepare('SELECT COUNT(*) FROM watch_later WHERE user_id = ?');
        $count->execute([$userId]);
        return (int)$count->fetchColumn();
    }

    public function getWatchLater($userId, $limit, $offset) {
        $st = static::db()->prepare("SELECT v.id, v.title, v.thumbnail_url, v.views, v.created_at, u.username, wl.saved_at
                                     FROM watch_later wl
                                     JOIN videos v ON v.id = wl.video_id
                                     JOIN users u ON u.id = v.user_id
                                     WHERE wl.user_id = ? AND v.status = 'ready'
                                     ORDER BY wl.saved_at DESC, v.id DESC LIMIT $limit OFFSET $offset");
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function getAnalyticsSummary($userId) {
        $summary = static::db()->prepare("SELECT COUNT(*) AS video_count, COALESCE(SUM(views), 0) AS total_views,
                                          COALESCE(AVG(views), 0) AS average_views
                                   FROM videos WHERE user_id = ? AND status = 'ready'");
        $summary->execute([$userId]);
        return $summary->fetch();
    }

    public function getAnalyticsVideos($userId, $limit = 100) {
        $st = static::db()->prepare("SELECT id, title, thumbnail_url, views, created_at
                             FROM videos WHERE user_id = ? AND status = 'ready'
                             ORDER BY views DESC, created_at DESC, id DESC LIMIT $limit");
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function deleteVideo($videoId) {
        return static::db()->prepare('DELETE FROM videos WHERE id = ?')->execute([$videoId]);
    }

    public function createDirectVideo($userId, $title, $description, $fileId, $thumbUrl) {
        static::db()->prepare("INSERT INTO videos (user_id, title, description, stream_id, status, thumbnail_url) VALUES (?,?,?,?, 'ready', ?)")
            ->execute([$userId, $title, $description, $fileId, $thumbUrl]);
        return (int)static::db()->lastInsertId();
    }

    public function createProcessingVideo($userId, $title, $description, $remoteId, $thumbUrl) {
        static::db()->prepare("INSERT INTO videos (user_id, title, description, status, remote_id, thumbnail_url) VALUES (?,?,?, 'processing', ?, ?)")
            ->execute([$userId, $title, $description, $remoteId, $thumbUrl]);
        return (int)static::db()->lastInsertId();
    }

    public function getVideoById($id) {
        $st = static::db()->prepare('SELECT v.*, u.username FROM videos v JOIN users u ON u.id = v.user_id WHERE v.id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public function updateThumbnail($videoId, $thumbnailUrl) {
        static::db()->prepare('UPDATE videos SET thumbnail_url = ? WHERE id = ?')->execute([$thumbnailUrl, $videoId]);
    }

    public function incrementViewCount($videoId) {
        static::db()->prepare('UPDATE videos SET views = views + 1 WHERE id = ?')->execute([$videoId]);
    }

    public function saveToHistory($userId, $videoId) {
        static::db()->prepare('INSERT INTO watch_history (user_id, video_id, watched_at) VALUES (?,?,NOW())
                    ON DUPLICATE KEY UPDATE watched_at = NOW()')
            ->execute([$userId, $videoId]);
    }

    public function toggleLike($userId, $videoId) {
        $d = static::db()->prepare('DELETE FROM likes WHERE user_id = ? AND video_id = ?');
        $d->execute([$userId, $videoId]);
        if ($d->rowCount() === 0) static::db()->prepare('INSERT IGNORE INTO likes (user_id, video_id) VALUES (?,?)')->execute([$userId, $videoId]);
    }

    public function toggleWatchLater($userId, $videoId) {
        $d = static::db()->prepare('DELETE FROM watch_later WHERE user_id = ? AND video_id = ?');
        $d->execute([$userId, $videoId]);
        if ($d->rowCount() === 0) static::db()->prepare('INSERT IGNORE INTO watch_later (user_id, video_id) VALUES (?,?)')->execute([$userId, $videoId]);
    }

    public function likeCount($videoId) {
        return (int)static::db()->query('SELECT COUNT(*) FROM likes WHERE video_id = ' . (int)$videoId)->fetchColumn();
    }

    public function hasLiked($userId, $videoId) {
        $l = static::db()->prepare('SELECT 1 FROM likes WHERE user_id = ? AND video_id = ?');
        $l->execute([$userId, $videoId]);
        return (bool)$l->fetchColumn();
    }

    public function hasWatchLater($userId, $videoId) {
        $s = static::db()->prepare('SELECT 1 FROM watch_later WHERE user_id = ? AND video_id = ?');
        $s->execute([$userId, $videoId]);
        return (bool)$s->fetchColumn();
    }

    public function getRelatedVideos($videoId, $limit = 8) {
        $rs = static::db()->prepare("SELECT v.id, v.title, v.thumbnail_url, v.views, u.username FROM videos v JOIN users u ON u.id = v.user_id
                             WHERE v.id <> ? AND v.status = 'ready' ORDER BY v.created_at DESC LIMIT $limit");
        $rs->execute([$videoId]);
        return $rs->fetchAll();
    }

    public function getVideoForEdit($videoId, $userId) {
        $st = static::db()->prepare('SELECT id, title, description, thumbnail_url FROM videos WHERE id = ? AND user_id = ?');
        $st->execute([$videoId, $userId]);
        return $st->fetch();
    }

    public function updateVideoDetails($videoId, $userId, $title, $description, $thumbnailUrl = null) {
        if ($thumbnailUrl !== null) {
            static::db()->prepare('UPDATE videos SET title = ?, description = ?, thumbnail_url = ? WHERE id = ? AND user_id = ?')
                ->execute([$title, $description, $thumbnailUrl, $videoId, $userId]);
        } else {
            static::db()->prepare('UPDATE videos SET title = ?, description = ? WHERE id = ? AND user_id = ?')
                ->execute([$title, $description, $videoId, $userId]);
        }
    }

    public function getVideoForReport($videoId) {
        $st = static::db()->prepare("SELECT id, user_id, title FROM videos WHERE id = ? AND status = 'ready'");
        $st->execute([$videoId]);
        return $st->fetch();
    }

    public function hasExistingReport($reporterId, $videoId) {
        $existing = static::db()->prepare('SELECT status FROM video_reports WHERE reporter_id = ? AND video_id = ?');
        $existing->execute([$reporterId, $videoId]);
        return $existing->fetchColumn();
    }

    public function getVideoForStatus($videoId) {
        $st = static::db()->prepare('SELECT id, user_id, remote_id, status, status_msg, checked_at FROM videos WHERE id = ?');
        $st->execute([$videoId]);
        return $st->fetch();
    }

    public function updateVideoStatusReady($videoId, $streamId, $thumbnailUrl) {
        static::db()->prepare("UPDATE videos SET status='ready', stream_id=?, thumbnail_url=COALESCE(thumbnail_url, ?), remote_id=NULL, status_msg=NULL WHERE id=? AND status='processing'")
            ->execute([$streamId, $thumbnailUrl, $videoId]);
    }

    public function updateVideoStatusFailed($videoId, $message) {
        static::db()->prepare("UPDATE videos SET status='failed', status_msg=? WHERE id=? AND status='processing'")
            ->execute([mb_substr((string)$message, 0, 250), $videoId]);
    }

    public function updateVideoCheckedAt($videoId) {
        static::db()->prepare('UPDATE videos SET checked_at = NOW() WHERE id = ?')->execute([$videoId]);
    }
}
