<?php
namespace App\Models;
use Core\Model;

class CommentModel extends Model {

    public function addComment($userId, $videoId, $body, $parentId = null) {
        static::db()->prepare('INSERT INTO comments (user_id, video_id, body, parent_id) VALUES (?,?,?,?)')->execute([$userId, $videoId, $body, $parentId]);
    }

    public function editComment($commentId, $userId, $videoId, $body) {
        static::db()->prepare('UPDATE comments SET body = ? WHERE id = ? AND video_id = ? AND user_id = ?')
            ->execute([$body, $commentId, $videoId, $userId]);
    }

    public function deleteComment($commentId, $userId, $videoId, $videoOwnerId) {
        static::db()->prepare('DELETE c FROM comments c JOIN videos v ON v.id = c.video_id
                               WHERE c.id = ? AND c.video_id = ? AND (c.user_id = ? OR v.user_id = ?)')
            ->execute([$commentId, $videoId, $userId, $videoOwnerId]);
    }

    public function toggleCommentLike($userId, $commentId, $videoId) {
        $exists = static::db()->prepare('SELECT 1 FROM comments WHERE id = ? AND video_id = ?');
        $exists->execute([$commentId, $videoId]);
        if ($exists->fetchColumn()) {
            $delete = static::db()->prepare('DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?');
            $delete->execute([$userId, $commentId]);
            if ($delete->rowCount() === 0) {
                static::db()->prepare('INSERT IGNORE INTO comment_likes (user_id, comment_id) VALUES (?,?)')->execute([$userId, $commentId]);
            }
        }
    }

    public function getCommentsForVideo($videoId, $userId, $sort = 'newest') {
        $commentOrderBy = $sort === 'popular' ? 'like_count DESC, c.created_at DESC' : 'c.created_at DESC';

        $cs = static::db()->prepare("SELECT c.id, c.parent_id, c.user_id, c.body, c.created_at, u.username, u.avatar_url,
                                    COALESCE(cl.like_count, 0) AS like_count, mine.comment_id AS liked_by_me
                            FROM comments c JOIN users u ON u.id = c.user_id
                            LEFT JOIN (SELECT comment_id, COUNT(*) AS like_count FROM comment_likes GROUP BY comment_id) cl ON cl.comment_id = c.id
                            LEFT JOIN comment_likes mine ON mine.comment_id = c.id AND mine.user_id = ?
                            WHERE c.video_id = ? ORDER BY $commentOrderBy LIMIT 200");
        $cs->execute([$userId ?? 0, $videoId]);
        $all = $cs->fetchAll();
        
        $parents = [];
        $replies = [];
        foreach ($all as $c) {
            if ($c['parent_id']) {
                $replies[$c['parent_id']][] = $c;
            } else {
                $parents[] = $c;
            }
        }
        
        // Sort replies chronologically regardless of parent sort
        foreach ($replies as &$reps) {
            usort($reps, fn($a, $b) => $a['created_at'] <=> $b['created_at']);
        }
        
        return ['parents' => $parents, 'replies' => $replies];
    }
}
