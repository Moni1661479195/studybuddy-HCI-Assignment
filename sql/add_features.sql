-- Interests, study sessions, badges, quick-match queue, recommendations, resources
CREATE TABLE IF NOT EXISTS interests (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS user_interests (
  user_id INT NOT NULL,
  interest_id INT NOT NULL,
  weight FLOAT DEFAULT 1.0,
  PRIMARY KEY(user_id, interest_id),
  INDEX (interest_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS study_sessions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  started_at DATETIME NOT NULL,
  ended_at DATETIME NULL,
  is_group TINYINT(1) DEFAULT 0,
  metadata JSON DEFAULT NULL,
  INDEX(user_id, started_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badges (
  id INT PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(80) NOT NULL UNIQUE,
  title VARCHAR(120) NOT NULL,
  description TEXT
);

CREATE TABLE IF NOT EXISTS user_badges (
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  awarded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(user_id,badge_id),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quick_match_queue (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  skill_level VARCHAR(32) NULL,
  availability_window JSON NULL,
  requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('open','matched','cancelled') DEFAULT 'open',
  matched_with INT NULL,
  INDEX(status,requested_at),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS recommendations (
  user_id INT NOT NULL,
  candidate_user_id INT NOT NULL,
  score FLOAT NOT NULL,
  computed_at DATETIME NOT NULL,
  PRIMARY KEY(user_id, candidate_user_id),
  INDEX (user_id),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(candidate_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS resources (
  id INT PRIMARY KEY AUTO_INCREMENT,
  uploader_id INT NOT NULL,
  filename VARCHAR(255),
  mime VARCHAR(100),
  path VARCHAR(255),
  title VARCHAR(255),
  description TEXT,
  tags JSON DEFAULT '[]',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(uploader_id) REFERENCES users(id) ON DELETE CASCADE
);