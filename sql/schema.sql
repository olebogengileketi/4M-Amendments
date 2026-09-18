-- ============================================================
-- 19th Episcopal District YPD — Quadrennial Amendment Voting
-- MySQL schema
-- Designed & built for the 19th Episcopal District YPD
-- by Olebogeng Leketi, Historiographer/Statistician
-- ============================================================

CREATE DATABASE IF NOT EXISTS ame_ypd_amendments
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ame_ypd_amendments;

-- ------------------------------------------------------------
-- settings: singleton row holding convention name + vote gate
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  id                TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  convention_name   VARCHAR(255) NOT NULL DEFAULT 'Quadrennial Convention',
  voting_open       TINYINT(1) NOT NULL DEFAULT 1,
  results_public    TINYINT(1) NOT NULL DEFAULT 1,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_settings_singleton CHECK (id = 1)
) ENGINE=InnoDB;

INSERT INTO settings (id, convention_name, voting_open, results_public)
VALUES (1, 'Quadrennial Convention', 1, 1)
ON DUPLICATE KEY UPDATE id = id;

-- ------------------------------------------------------------
-- checkins: one row per delegate check-in
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS checkins (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  checkin_token     CHAR(36) NOT NULL,
  full_name         VARCHAR(150) NOT NULL,
  local_church      VARCHAR(150) NOT NULL,
  area              VARCHAR(100) NOT NULL,
  name_norm         VARCHAR(150) NOT NULL,      -- lowercased/trimmed, for soft dup match
  church_norm       VARCHAR(150) NOT NULL,
  area_norm         VARCHAR(100) NOT NULL,
  ballot_submitted  TINYINT(1) NOT NULL DEFAULT 0,
  checked_in_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at      DATETIME NULL,
  UNIQUE KEY uniq_token (checkin_token),
  KEY idx_soft_match (name_norm, church_norm, area_norm)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- amendments: the proposed amendments delegates vote on
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS amendments (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  proposal_no         VARCHAR(30)  NOT NULL,
  article_no          VARCHAR(30)  NOT NULL,
  section             VARCHAR(60)  NOT NULL,
  page_no             VARCHAR(20)  NOT NULL,
  proposed_amendment  TEXT         NOT NULL,
  rationale           TEXT NULL,                 -- why this amendment is being proposed
  original_text       TEXT NULL,                 -- the existing text being amended
  summary             VARCHAR(255) NULL,          -- optional short title shown in lists/charts
  display_order       INT NOT NULL DEFAULT 0,
  is_active           TINYINT(1) NOT NULL DEFAULT 1,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- votes: one row per (delegate, amendment)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS votes (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  checkin_token     CHAR(36) NOT NULL,
  amendment_id      INT UNSIGNED NOT NULL,
  vote_choice       ENUM('yes','no','abstain','comment') NOT NULL,
  comment_text      TEXT NULL DEFAULT NULL,     -- only populated when vote_choice = 'comment'
  voted_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_vote (checkin_token, amendment_id),
  KEY idx_amendment (amendment_id),
  CONSTRAINT fk_votes_amendment FOREIGN KEY (amendment_id) REFERENCES amendments(id) ON DELETE CASCADE,
  CONSTRAINT fk_votes_checkin FOREIGN KEY (checkin_token) REFERENCES checkins(checkin_token) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- sample seed data (safe to delete from admin panel)
-- ------------------------------------------------------------
INSERT INTO amendments (proposal_no, article_no, section, page_no, proposed_amendment, summary, display_order, is_active) VALUES
('P-001', 'Article IV', 'Sec. 2', '14', 'Amend Article IV, Section 2 to change the term of the YPD District President from two (2) years to four (4) years, aligned with the Episcopal quadrennial.', 'District President term: 2 → 4 years', 1, 1),
('P-002', 'Article VI', 'Sec. 5', '22', 'Amend Article VI, Section 5 to require that each Local YPD submit an annual financial report to the District Treasurer no later than January 31st.', 'Require annual Local YPD financial report', 2, 1),
('P-003', 'Article IX', 'Sec. 1', '31', 'Amend Article IX, Section 1 to establish a standing Youth Advocacy Committee within the District YPD structure.', 'Establish Youth Advocacy Committee', 3, 1)
ON DUPLICATE KEY UPDATE proposal_no = proposal_no;
