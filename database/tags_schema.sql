-- ============================================================
-- Tags System Migration
-- Run this ONCE on the production database.
-- Replaces the blog_posts.category column with a proper
-- many-to-many tags system.
-- ============================================================

-- 1. Drop pivot first (FK constraint), then lookup table
DROP TABLE IF EXISTS blog_post_tags;
DROP TABLE IF EXISTS blog_tags;

-- 2. Create blog_tags lookup table (match blog_posts collation)
CREATE TABLE blog_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create pivot table (post ↔ tag many-to-many)
CREATE TABLE blog_post_tags (
    post_id INT NOT NULL,
    tag_id  INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)  REFERENCES blog_tags(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Migrate existing category values into blog_tags
INSERT IGNORE INTO blog_tags (name, slug)
SELECT DISTINCT
    category,
    LOWER(REPLACE(REPLACE(category, ' & ', '-'), ' ', '-'))
FROM blog_posts
WHERE category IS NOT NULL AND category != '';

-- 5. Populate pivot from existing category column
INSERT IGNORE INTO blog_post_tags (post_id, tag_id)
SELECT bp.id, bt.id
FROM blog_posts bp
JOIN blog_tags bt
  ON bt.slug = LOWER(REPLACE(REPLACE(bp.category, ' & ', '-'), ' ', '-'))
WHERE bp.category IS NOT NULL AND bp.category != '';

-- 6. Drop the old category column
ALTER TABLE blog_posts DROP COLUMN category;
