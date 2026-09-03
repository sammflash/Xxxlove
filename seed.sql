-- ============================================================================
-- XPORN LOVERS — optional demo data.
--
-- Safe to import after schema.sql for local development / a staging
-- preview. Remove before going to production (see DEPLOYMENT.md's
-- "Removing demo data" section) — none of this is meant to ship live.
--
-- The video/thumbnail URLs below point at royalty-free sample clips
-- (Google's public GTV test videos + Picsum placeholder images) purely
-- so the player and thumbnails have something real to render in dev.
-- ============================================================================

INSERT INTO categories (name, slug, description) VALUES
    ('Featured',      'featured',      'Hand-picked highlights'),
    ('Trending Now',  'trending-now',  'What is popular this week'),
    ('New Release',   'new-release',   'Recently added'),
    ('Editor''s Pick', 'editors-pick', 'Curated by the editorial team')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO videos (title, slug, description, category_id, video_url, thumbnail_url, duration, views, status) VALUES
    ('Sample Video Title One',
     'sample-video-title-one',
     'Placeholder demo video used to exercise the player, likes, comments, and report flow in development.',
     (SELECT id FROM categories WHERE slug = 'featured'),
     'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
     'https://picsum.photos/seed/xpl1/640/360',
     '09:56', 1280, 'published'),

    ('Sample Video Title Two',
     'sample-video-title-two',
     'Placeholder demo video used to exercise the player, likes, comments, and report flow in development.',
     (SELECT id FROM categories WHERE slug = 'trending-now'),
     'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
     'https://picsum.photos/seed/xpl2/640/360',
     '10:53', 960, 'published'),

    ('Sample Video Title Three',
     'sample-video-title-three',
     'Placeholder demo video used to exercise the player, likes, comments, and report flow in development.',
     (SELECT id FROM categories WHERE slug = 'new-release'),
     'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
     'https://picsum.photos/seed/xpl3/640/360',
     '00:15', 210, 'published'),

    ('Sample Video Title Four',
     'sample-video-title-four',
     'Placeholder demo video used to exercise the player, likes, comments, and report flow in development.',
     (SELECT id FROM categories WHERE slug = 'editors-pick'),
     'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
     'https://picsum.photos/seed/xpl4/640/360',
     '00:15', 4310, 'published')
ON DUPLICATE KEY UPDATE title = VALUES(title);
