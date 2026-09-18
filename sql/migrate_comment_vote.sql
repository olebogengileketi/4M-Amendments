-- ============================================================
-- Migration: Add "comment" vote choice + comment_text column
-- 19th Episcopal District YPD — Quadrennial Amendment Voting
-- Run once against ame_ypd_amendments database
-- ============================================================

USE ame_ypd_amendments;

-- 1. Expand vote_choice ENUM to include 'comment'
ALTER TABLE votes
  MODIFY COLUMN vote_choice
    ENUM('yes','no','abstain','comment') NOT NULL;

-- 2. Add comment_text column (nullable — only populated when vote_choice = 'comment')
ALTER TABLE votes
  ADD COLUMN comment_text TEXT NULL DEFAULT NULL
  AFTER vote_choice;
