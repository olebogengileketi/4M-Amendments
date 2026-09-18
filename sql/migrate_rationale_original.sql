-- ============================================================
-- Migration: Add rationale and original_text columns to amendments
-- 19th Episcopal District YPD — Quadrennial Amendment Voting
-- Run once against ame_ypd_amendments database
-- ============================================================

USE ame_ypd_amendments;

-- 1. Rationale: why this amendment is being proposed
ALTER TABLE amendments
  ADD COLUMN rationale TEXT NULL DEFAULT NULL
  AFTER proposed_amendment;

-- 2. Original Constitution: the existing text being amended
ALTER TABLE amendments
  ADD COLUMN original_text TEXT NULL DEFAULT NULL
  AFTER rationale;
