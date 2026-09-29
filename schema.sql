CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(30)  NOT NULL UNIQUE,
  email         VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  account_status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  bio           VARCHAR(1000) NOT NULL DEFAULT '',
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE videos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  title         VARCHAR(150) NOT NULL,
  description   TEXT NULL,
  stream_id     VARCHAR(64)  NULL,              -- hosted file id (NULL while a remote upload is processing)
  status        ENUM('processing','ready','failed') NOT NULL DEFAULT 'ready',
  remote_id     VARCHAR(64)  NULL,              -- remote upload id while processing
  status_msg    VARCHAR(255) NULL,
  checked_at    TIMESTAMP    NULL,
  thumbnail_url VARCHAR(500) NULL,              -- Freeimage.host URL
  views         INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at),
  KEY idx_user (user_id),
  CONSTRAINT fk_v_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE video_reports (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT UNSIGNED NOT NULL,
  video_id    INT UNSIGNED NULL,
  video_title VARCHAR(150) NOT NULL,
  reason      ENUM('spam','harassment','copyright','misleading','other') NOT NULL,
  details     VARCHAR(1000) NOT NULL DEFAULT '',
  moderator_note VARCHAR(1000) NOT NULL DEFAULT '',
  status      ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  UNIQUE KEY uq_video_reporter (reporter_id, video_id),
  KEY idx_video_reports_status (status, created_at),
  CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_video_reports_video_keep FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE SET NULL,
  CONSTRAINT fk_reports_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE likes (
  user_id  INT UNSIGNED NOT NULL,
  video_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, video_id),
  CONSTRAINT fk_l_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_l_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE watch_later (
  user_id    INT UNSIGNED NOT NULL,
  video_id   INT UNSIGNED NOT NULL,
  saved_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, video_id),
  KEY idx_watch_later_saved (user_id, saved_at),
  CONSTRAINT fk_wl_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_wl_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE watch_history (
  user_id    INT UNSIGNED NOT NULL,
  video_id   INT UNSIGNED NOT NULL,
  watched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, video_id),
  KEY idx_watch_history_recent (user_id, watched_at),
  CONSTRAINT fk_wh_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wh_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE playlists (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  visibility  ENUM('private','public') NOT NULL DEFAULT 'private',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_playlists_user (user_id, created_at),
  CONSTRAINT fk_playlists_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE playlist_videos (
  playlist_id INT UNSIGNED NOT NULL,
  video_id    INT UNSIGNED NOT NULL,
  position    INT UNSIGNED NOT NULL DEFAULT 0,
  added_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (playlist_id, video_id),
  KEY idx_playlist_videos_video (video_id),
  CONSTRAINT fk_pv_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
  CONSTRAINT fk_pv_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  video_id   INT UNSIGNED NOT NULL,
  body       VARCHAR(1000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_video (video_id, created_at),
  CONSTRAINT fk_c_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_c_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comment_likes (
  user_id    INT UNSIGNED NOT NULL,
  comment_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, comment_id),
  KEY idx_comment_likes_comment (comment_id),
  CONSTRAINT fk_cl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cl_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB;
