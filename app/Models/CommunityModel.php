<?php
namespace App\Models;
use Core\Model;

class CommunityModel extends Model {

    public function createCommunity($name, $description, $creatorId, $visibility = 'public') {
        static::db()->prepare('INSERT INTO communities (name, description, creator_id, visibility) VALUES (?,?,?,?)')
            ->execute([$name, $description, $creatorId, $visibility]);
        $communityId = (int)static::db()->lastInsertId();
        
        // Creator is automatically an admin
        $this->joinCommunity($communityId, $creatorId, 'admin');
        
        return $communityId;
    }

    public function joinCommunity($communityId, $userId, $role = 'member') {
        static::db()->prepare('INSERT IGNORE INTO community_members (community_id, user_id, role) VALUES (?,?,?)')
            ->execute([$communityId, $userId, $role]);
    }

    public function leaveCommunity($communityId, $userId) {
        static::db()->prepare('DELETE FROM community_members WHERE community_id = ? AND user_id = ?')
            ->execute([$communityId, $userId]);
    }

    public function getCommunityById($id) {
        $st = static::db()->prepare('SELECT c.*, u.username as creator_name FROM communities c JOIN users u ON u.id = c.creator_id WHERE c.id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public function getPublicCommunities($limit = 24, $offset = 0) {
        $st = static::db()->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count
            FROM communities c 
            WHERE c.visibility = 'public' 
            ORDER BY member_count DESC, c.created_at DESC 
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $st->execute();
        return $st->fetchAll();
    }

    public function getUserCommunities($userId) {
        $st = static::db()->prepare("
            SELECT c.*, m.role,
                   (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as member_count
            FROM communities c 
            JOIN community_members m ON m.community_id = c.id 
            WHERE m.user_id = ?
            ORDER BY c.name ASC
        ");
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function isMember($communityId, $userId) {
        $st = static::db()->prepare('SELECT role FROM community_members WHERE community_id = ? AND user_id = ?');
        $st->execute([$communityId, $userId]);
        return $st->fetchColumn();
    }

    public function getPosts($communityId, $limit = 50) {
        $st = static::db()->prepare("
            SELECT cp.*, u.username, u.avatar_url,
                   v.title as video_title, v.thumbnail_url as video_thumbnail
            FROM community_posts cp
            JOIN users u ON u.id = cp.user_id
            LEFT JOIN videos v ON v.id = cp.video_id
            WHERE cp.community_id = ?
            ORDER BY cp.created_at DESC
            LIMIT " . (int)$limit
        );
        $st->execute([$communityId]);
        return $st->fetchAll();
    }

    public function createPost($communityId, $userId, $body, $videoId = null) {
        static::db()->prepare('INSERT INTO community_posts (community_id, user_id, body, video_id) VALUES (?,?,?,?)')
            ->execute([$communityId, $userId, $body, $videoId]);
    }
}
