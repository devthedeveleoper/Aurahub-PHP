<?php
namespace App\Models;
use Core\Model;

class ReportModel extends Model {

    public function countUserReports($userId) {
        $count = static::db()->prepare('SELECT COUNT(*) FROM video_reports WHERE reporter_id = ?');
        $count->execute([$userId]);
        return (int)$count->fetchColumn();
    }

    public function getUserReports($userId, $limit, $offset) {
        $st = static::db()->prepare("SELECT r.reason, r.details, r.status, r.moderator_note, r.created_at, r.reviewed_at,
                                            v.id AS video_id, COALESCE(v.title, NULLIF(r.video_title, ''), '[removed video]') AS title
                                     FROM video_reports r LEFT JOIN videos v ON v.id = r.video_id
                                     WHERE r.reporter_id = ?
                                     ORDER BY r.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
        $st->execute([$userId]);
        return $st->fetchAll();
    }

    public function countPendingReports($statusFilter) {
        if ($statusFilter !== 'all') {
            $count = static::db()->prepare('SELECT COUNT(*) FROM video_reports WHERE status = ?');
            $count->execute([$statusFilter]);
        } else {
            $count = static::db()->prepare('SELECT COUNT(*) FROM video_reports');
            $count->execute();
        }
        return (int)$count->fetchColumn();
    }

    public function getReports($statusFilter, $limit, $offset) {
        if ($statusFilter !== 'all') {
            $st = static::db()->prepare("SELECT r.id, r.status, r.reason, r.details, r.moderator_note, r.created_at,
                                         u.username AS reporter_name, v.id AS video_id, v.title, owner.username AS owner_name
                                         FROM video_reports r JOIN users u ON u.id = r.reporter_id
                                         LEFT JOIN videos v ON v.id = r.video_id LEFT JOIN users owner ON owner.id = v.user_id
                                         WHERE r.status = ?
                                         ORDER BY r.created_at ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
            $st->execute([$statusFilter]);
        } else {
            $st = static::db()->prepare("SELECT r.id, r.status, r.reason, r.details, r.moderator_note, r.created_at,
                                         u.username AS reporter_name, v.id AS video_id, v.title, owner.username AS owner_name
                                         FROM video_reports r JOIN users u ON u.id = r.reporter_id
                                         LEFT JOIN videos v ON v.id = r.video_id LEFT JOIN users owner ON owner.id = v.user_id
                                         ORDER BY r.created_at ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
            $st->execute();
        }
        return $st->fetchAll();
    }

    public function resolveReport($reportId, $note, $reviewerId) {
        return static::db()->prepare("UPDATE video_reports SET status = 'resolved', moderator_note = ?, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ? WHERE id = ?")
            ->execute([$note, $reviewerId, $reportId]);
    }

    public function dismissReport($reportId, $note, $reviewerId) {
        return static::db()->prepare("UPDATE video_reports SET status = 'dismissed', moderator_note = ?, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = ? WHERE id = ?")
            ->execute([$note, $reviewerId, $reportId]);
    }

    public function createReport($videoId, $reporterId, $reason, $details, $videoTitle) {
        $st = static::db()->prepare("INSERT INTO video_reports (video_id, reporter_id, reason, details, video_title) VALUES (?, ?, ?, ?, ?)");
        return $st->execute([$videoId, $reporterId, $reason, $details, $videoTitle]);
    }

    public function getReportVideoDetails($reportId) {
        $st = static::db()->prepare('SELECT r.video_id, COALESCE(v.title, r.video_title) AS title, v.stream_id
                                     FROM video_reports r LEFT JOIN videos v ON v.id = r.video_id WHERE r.id = ?');
        $st->execute([$reportId]);
        return $st->fetch();
    }

    public function removeVideoFromReport($reportId, $videoTitle, $note, $reviewerId, $videoId) {
        return static::db()->prepare("UPDATE video_reports SET video_title = ?, video_id = NULL, status = 'resolved', moderator_note = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE video_id = ?")
            ->execute([$videoTitle, $note, $reviewerId, $videoId]);
    }

    public function getAdminReports($statusFilter, $limit, $offset) {
        $where = $statusFilter === 'all' ? '' : 'WHERE r.status = ?';
        $sql = "SELECT r.id, r.reason, r.details, r.moderator_note, r.status, r.created_at,
                 v.id AS video_id, COALESCE(v.title, NULLIF(r.video_title, ''), '[removed video]') AS title,
                 owner.username AS owner_name, reporter.username AS reporter_name
                FROM video_reports r
                LEFT JOIN videos v ON v.id = r.video_id
                LEFT JOIN users owner ON owner.id = v.user_id
                JOIN users reporter ON reporter.id = r.reporter_id
                $where
                ORDER BY (r.status = 'pending') DESC, r.created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $st = static::db()->prepare($sql);
        $st->execute($statusFilter === 'all' ? [] : [$statusFilter]);
        return $st->fetchAll();
    }

    public function getTransparencyStats() {
        $st = static::db()->prepare("
            SELECT reason, COUNT(*) as count 
            FROM video_reports 
            WHERE status = 'resolved' 
            GROUP BY reason 
            ORDER BY count DESC
        ");
        $st->execute();
        return $st->fetchAll();
    }
}
