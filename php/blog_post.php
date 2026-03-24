<?php
require_once 'db_config.php';

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /blog');
    exit;
}

// Fetch post
$stmt = $conn->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($post = $result->fetch_assoc()) {
    // Increment views
    $update = $conn->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
    $update->bind_param("i", $post['id']);
    $update->execute();

    // Fetch tags for this post
    $tag_stmt = $conn->prepare("
        SELECT bt.name, bt.slug
        FROM blog_tags bt
        JOIN blog_post_tags bpt ON bpt.tag_id = bt.id
        WHERE bpt.post_id = ?
        ORDER BY bt.name ASC
    ");
    $tag_stmt->bind_param("i", $post['id']);
    $tag_stmt->execute();
    $tag_result = $tag_stmt->get_result();
    $post_tags = [];
    while ($tag_row = $tag_result->fetch_assoc()) {
        $post_tags[] = $tag_row;
    }
} else {
    header('HTTP/1.0 404 Not Found');
    include('../404.html');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($post['meta_description'] ?? $post['excerpt']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($post['meta_keywords'] ?? ''); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($post['author']); ?>">
    <title><?php echo htmlspecialchars($post['title']); ?> | Joshi Management Consultancy</title>
    
    <!-- Same fonts and styles as other pages -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <!-- Same CSS as other pages - include the full style block from blog.html -->
    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            /* Colors - Deep Blues with Purple/Cyan Gradients */
            --primary-deep: #0a1128;
            --primary-dark: #1e2749;
            --primary-mid: #2d3561;
            --accent-purple: #a855f7;
            --accent-cyan: #06b6d4;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-orange: #f59e0b;
            --glass-white: rgba(255, 255, 255, 0.1);
            --glass-white-hover: rgba(255, 255, 255, 0.15);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-muted: rgba(255, 255, 255, 0.6);
            
            /* Typography */
            --font-display: 'Sora', sans-serif;
            --font-body: 'Outfit', sans-serif;
            
            /* Spacing */
            --section-padding: 120px;
            --container-max: 1400px;
            
            /* Effects */
            --blur-sm: blur(10px);
            --blur-md: blur(20px);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* ===== RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }
        
        body {
            font-family: var(--font-body);
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--primary-dark) 50%, var(--primary-mid) 100%);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }
        
        /* Animated background gradient mesh */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(168, 85, 247, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(6, 182, 212, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
            animation: gradient-shift 15s ease infinite;
            z-index: -1;
            pointer-events: none;
        }
        
        @keyframes gradient-shift {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(5%, -5%) rotate(5deg); }
            66% { transform: translate(-5%, 5%) rotate(-5deg); }
        }
        
        /* ===== TYPOGRAPHY ===== */
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-display);
            font-weight: 700;
            line-height: 1.2;
        }
        
        h1 {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        
        h2 {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        
        h3 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 600;
        }
        
        p {
            font-size: clamp(1rem, 1.5vw, 1.125rem);
            line-height: 1.7;
            color: var(--text-secondary);
        }
        
        /* ===== UTILITIES ===== */
        .container {
            max-width: var(--container-max);
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .section {
            padding: var(--section-padding) 0;
            position: relative;
        }
        
        /* Enhanced Glass morphism card */
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 24px;
            padding: 2.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            opacity: 0;
            transition: var(--transition);
        }
        
        .glass-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.03'/%3E%3C/svg%3E");
            border-radius: 24px;
            pointer-events: none;
        }
        
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-8px);
            border-color: rgba(255, 255, 255, 0.25);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                0 0 80px rgba(168, 85, 247, 0.15);
        }
        
        .glass-card:hover::before {
            opacity: 1;
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, var(--accent-purple) 0%, var(--accent-cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            font-family: var(--font-display);
            font-size: 1.125rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 50px;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-purple) 0%, var(--accent-cyan) 100%);
            color: white;
            box-shadow: 
                0 10px 30px rgba(168, 85, 247, 0.35),
                0 0 40px rgba(168, 85, 247, 0.15);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--accent-cyan) 0%, var(--accent-purple) 100%);
            transition: var(--transition);
            z-index: -1;
        }
        
        .btn-primary:hover::before {
            left: 0;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 15px 40px rgba(168, 85, 247, 0.45),
                0 0 60px rgba(168, 85, 247, 0.25);
        }
        
        /* ===== NAVIGATION ===== */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1.5rem 0;
            background: rgba(10, 17, 40, 0.85);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
        }
        
        nav.scrolled {
            padding: 1rem 0;
            background: rgba(10, 17, 40, 0.95);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            letter-spacing: -0.02em;
        }
        
        .nav-links {
            display: flex;
            gap: 3rem;
            list-style: none;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
            position: relative;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-purple), var(--accent-cyan));
            transition: var(--transition);
        }
        
        .nav-links a:hover {
            color: white;
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }

        /* ===== NAV DROPDOWN ===== */
        .nav-dropdown { position: relative; }
        .nav-dropdown-toggle { display: flex !important; align-items: center; gap: 0.35rem; cursor: pointer; user-select: none; }
        .nav-dropdown-toggle .dropdown-arrow { font-size: 0.55rem; transition: transform 0.3s ease; opacity: 0.6; }
        .nav-dropdown.open .nav-dropdown-toggle .dropdown-arrow { transform: rotate(180deg); }
        .nav-dropdown-menu {
            position: absolute; top: calc(100% + 1rem); left: 50%;
            transform: translateX(-50%) translateY(-8px);
            background: rgba(14, 16, 29, 0.97);
            backdrop-filter: blur(24px); border: 1px solid rgba(168, 85, 247, 0.25);
            border-radius: 12px; padding: 0.6rem 0; min-width: 280px;
            list-style: none; opacity: 0; visibility: hidden;
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
            box-shadow: 0 16px 48px rgba(0,0,0,0.5); z-index: 200;
        }
        .nav-dropdown:hover .nav-dropdown-menu,
        .nav-dropdown.open .nav-dropdown-menu { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); }
        .nav-dropdown-menu li { padding: 0; list-style: none; }
        .nav-dropdown-menu a { display: block !important; padding: 0.65rem 1.5rem !important; font-size: 0.9rem !important; color: rgba(255,255,255,0.7) !important; white-space: nowrap; font-weight: 400 !important; }
        .nav-dropdown-menu a::after { display: none !important; }
        .nav-dropdown-menu a:hover { color: white !important; background: rgba(168, 85, 247, 0.12); }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* ===== PAGE HERO ===== */
        .page-hero {
            padding: 180px 0 80px;
            text-align: center;
            position: relative;
        }
        
        .page-hero h1 {
            font-size: clamp(1.75rem, 3.5vw, 2.5rem);
            margin-bottom: 1.5rem;
            opacity: 0;
            animation: fadeInUp 0.8s ease 0.2s forwards;
        }
        
        .page-hero p {
            font-size: clamp(1.125rem, 2vw, 1.5rem);
            max-width: 800px;
            margin: 0 auto;
            opacity: 0;
            animation: fadeInUp 0.8s ease 0.4s forwards;
        }
        
        /* ===== SEARCH & FILTER BAR ===== */
        .blog-controls {
            margin-bottom: 4rem;
            opacity: 0;
            animation: fadeInUp 0.8s ease 0.6s forwards;
        }
        
        .search-bar {
            max-width: 600px;
            margin: 0 auto 2rem;
            position: relative;
        }
        
        .search-bar input {
            width: 100%;
            padding: 1.25rem 1.75rem 1.25rem 3.5rem;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 50px;
            color: white;
            font-size: 1.125rem;
            font-family: var(--font-body);
            transition: var(--transition);
            box-shadow: 
                0 4px 16px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .search-bar input::placeholder {
            color: var(--text-muted);
        }
        
        .search-bar input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--accent-cyan);
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.15),
                0 0 0 3px rgba(6, 182, 212, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }
        
        .search-icon {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem;
            color: var(--accent-cyan);
            pointer-events: none;
        }
        
        .category-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }
        
        .category-btn {
            padding: 0.75rem 1.75rem;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 50px;
            color: var(--text-secondary);
            font-family: var(--font-display);
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 
                0 4px 16px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .category-btn:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            color: white;
            transform: translateY(-2px);
        }
        
        .category-btn.active {
            background: linear-gradient(135deg, var(--accent-purple) 0%, var(--accent-cyan) 100%);
            border-color: transparent;
            color: white;
            box-shadow: 
                0 8px 24px rgba(168, 85, 247, 0.35),
                0 0 40px rgba(168, 85, 247, 0.15);
        }
        
        /* ===== BLOG GRID ===== */
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }
        
        .blog-card {
            cursor: pointer;
            opacity: 1;
            transform: scale(1);
            transition: var(--transition);
        }
        
        .blog-card.hidden {
            opacity: 0;
            transform: scale(0.9);
            height: 0;
            overflow: hidden;
            margin: 0;
            padding: 0;
            pointer-events: none;
        }
        
        .blog-image {
            height: 250px;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            overflow: hidden;
            position: relative;
            transition: var(--transition);
        }
        
        .blog-card:hover .blog-image {
            transform: scale(1.05);
            border-radius: 16px;
        }
        
        .blog-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
        }
        
        .category-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        
        .blog-card h3 {
            margin-bottom: 1rem;
            font-size: 1.75rem;
        }
        
        .blog-excerpt {
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        .blog-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9375rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .read-more {
            color: var(--accent-cyan);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }
        
        .read-more:hover {
            gap: 1rem;
            color: var(--accent-purple);
        }
        
        /* Category badge colors */
        .category-badge.ai-trends { color: var(--accent-purple); border-color: rgba(168, 85, 247, 0.3); }
        .category-badge.implementation-tips { color: var(--accent-cyan); border-color: rgba(6, 182, 212, 0.3); }
        .category-badge.case-studies { color: var(--accent-green); border-color: rgba(16, 185, 129, 0.3); }
        .category-badge.business-strategy { color: var(--accent-blue); border-color: rgba(59, 130, 246, 0.3); }
        .category-badge.ai-tools { color: var(--accent-orange); border-color: rgba(245, 158, 11, 0.3); }
        .category-badge.industry-insights { color: #ec4899; border-color: rgba(236, 72, 153, 0.3); }
        
        /* No results message */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            opacity: 0;
            display: none;
            transition: var(--transition);
        }
        
        .no-results.show {
            display: block;
            opacity: 1;
        }
        
        .no-results h3 {
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        
        /* ===== FOOTER ===== */
        footer {
            background: rgba(10, 17, 40, 0.92);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 4rem 0 2rem;
            box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.1);
            margin-top: 6rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-section h3 {
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section li {
            margin-bottom: 0.75rem;
        }
        
        .footer-section a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-section a:hover {
            color: var(--accent-cyan);
            padding-left: 5px;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        
        .social-links a:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
        }
        
        /* ===== ANIMATIONS ===== */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 1024px) {
            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                height: 100vh;
                width: 300px;
                background: rgba(10, 17, 40, 0.98);
                backdrop-filter: blur(24px) saturate(180%);
                -webkit-backdrop-filter: blur(24px) saturate(180%);
                flex-direction: column;
                padding: 6rem 2rem;
                gap: 2rem;
                transition: var(--transition);
                border-left: 1px solid rgba(255, 255, 255, 0.18);
                box-shadow: -4px 0 32px rgba(0, 0, 0, 0.3);
            }
            
            .nav-links.active {
                right: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .page-hero {
                padding: 140px 0 60px;
            }
            
            .blog-grid {
                grid-template-columns: 1fr;
            }
            
            .category-filters {
                gap: 0.75rem;
            }
            
            .category-btn {
                padding: 0.625rem 1.25rem;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 1.5rem;
            }
            
            .search-bar input {
                padding: 1rem 1.5rem 1rem 3rem;
                font-size: 1rem;
            }
        }
        /* YouTube Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(10, 17, 40, 0.95);
    backdrop-filter: blur(10px);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    position: relative;
    margin: 10% auto;
    padding: 3rem;
    max-width: 500px;
    width: 90%;
    text-align: center;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-close {
    position: absolute;
    top: 1rem;
    right: 1.5rem;
    font-size: 2rem;
    font-weight: bold;
    color: var(--text-muted);
    cursor: pointer;
    transition: var(--transition);
    line-height: 1;
}

.modal-close:hover {
    color: var(--text-primary);
    transform: scale(1.2);
}

.modal-header {
    margin-bottom: 1.5rem;
}

.modal-header h2 {
    font-size: 1.75rem;
}

.modal-form input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.08);
    border-color: var(--accent-cyan);
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
}

.modal-success {
    padding: 2rem;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 16px;
    margin: 2rem 0;
    animation: fadeInUp 0.5s ease;
}

/* Blog Content Styles */
.blog-content h1 {
    font-size: clamp(1.75rem, 3vw, 2.25rem);
    margin-top: 2.5rem;
    margin-bottom: 1.25rem;
    color: var(--text-primary);
    font-weight: 700;
    line-height: 1.3;
}

.blog-content h2 {
    font-size: clamp(1.5rem, 2.5vw, 1.875rem);
    margin-top: 2.25rem;
    margin-bottom: 1rem;
    color: var(--text-primary);
    font-weight: 600;
    line-height: 1.3;
}

.blog-content h3 {
    font-size: clamp(1.25rem, 2vw, 1.5rem);
    margin-top: 2rem;
    margin-bottom: 0.875rem;
    color: var(--text-primary);
    font-weight: 600;
    line-height: 1.3;
}

.blog-content h4 {
    font-size: clamp(1.125rem, 1.75vw, 1.25rem);
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
    font-weight: 600;
    line-height: 1.4;
}

.blog-content h5 {
    font-size: clamp(1rem, 1.5vw, 1.125rem);
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
    font-weight: 600;
    line-height: 1.4;
}

.blog-content h6 {
    font-size: 1rem;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: var(--text-secondary);
    font-weight: 600;
    line-height: 1.4;
}

.blog-content p {
    margin-bottom: 1.5rem;
    color: var(--text-secondary);
    line-height: 1.8;
    font-size: clamp(1rem, 1.5vw, 1.125rem);
}

.blog-content p:last-child {
    margin-bottom: 0;
}

.blog-content ul,
.blog-content ol {
    margin-bottom: 1.5rem;
    margin-left: 1.5rem;
    color: var(--text-secondary);
    line-height: 1.8;
}

.blog-content li {
    margin-bottom: 0.5rem;
}

.blog-content a {
    color: var(--accent-cyan);
    text-decoration: underline;
    transition: var(--transition);
}

.blog-content a:hover {
    color: var(--accent-purple);
}

.blog-content blockquote {
    border-left: 4px solid var(--accent-purple);
    padding-left: 1.5rem;
    margin: 2rem 0;
    font-style: italic;
    color: var(--text-secondary);
}

.blog-content code {
    background: rgba(255, 255, 255, 0.08);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
    color: var(--accent-cyan);
}

.blog-content pre {
    background: rgba(255, 255, 255, 0.05);
    padding: 1.5rem;
    border-radius: 12px;
    overflow-x: auto;
    margin: 1.5rem 0;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.blog-content pre code {
    background: none;
    padding: 0;
    color: var(--text-secondary);
}

.blog-content img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    margin: 2rem 0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.blog-content hr {
    border: none;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    margin: 3rem 0;
}

.blog-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 2rem 0;
}

.blog-content th,
.blog-content td {
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-align: left;
}

.blog-content th {
    background: rgba(255, 255, 255, 0.08);
    font-weight: 600;
    color: var(--text-primary);
}

.blog-content strong {
    color: var(--text-primary);
    font-weight: 600;
}

.blog-content em {
    color: var(--text-secondary);
    font-style: italic;
}
    </style>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5HH2RHZLZ7"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-5HH2RHZLZ7');
</script>
</head>
<body>
    <!-- NAVIGATION -->
    <nav id="nav">
        <div class="container">
            <div class="nav-container">
                <a href="/" class="logo"><img src="/logo.png" alt="Joshi Management Consultancy" style="height: 70px; width: auto;"></a>
                <ul class="nav-links" id="navLinks">
                    <li><a href="/services.html">Solutions</a></li>
                    <li class="nav-dropdown">
                        <a href="#" class="nav-dropdown-toggle">Industries <span class="dropdown-arrow">▼</span></a>
                        <ul class="nav-dropdown-menu">
                            <li><a href="/real-estate.html">Real Estate and Property Management</a></li>
                            <li><a href="/professional-services.html">Professional Services</a></li>
                            <li><a href="/financial-services.html">Financial Services</a></li>
                        </ul>
                    </li>
                    <li><a href="/about.html">About</a></li>
                    <li><a href="/blog.html">Blog</a></li>
                    <li><a href="/contact.html">Contact</a></li>
                    <li><a href="/assessment.html" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 0.9rem;">Free Assessment</a></li>
                </ul>
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle menu">
                    <span>☰</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- BLOG POST CONTENT -->
    <section class="page-hero" style="padding-bottom: 40px;">
        <div class="container" style="max-width: 1200px;">

            <?php if (!empty($post['featured_image'])): ?>
                <?php
                    $imagePath = $post['featured_image'];
                    $webpPath = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $imagePath);
                ?>
                <div style="margin-bottom: 1.75rem; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3); max-height: 350px;">
                    <picture>
                        <source srcset="<?php echo htmlspecialchars($webpPath); ?>" type="image/webp">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>"
                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                             style="width: 100%; height: 350px; object-fit: cover; display: block;"
                             loading="lazy">
                    </picture>
                </div>
            <?php endif; ?>

            <?php if (!empty($post_tags)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-bottom:1rem;">
                    <?php foreach ($post_tags as $tag): ?>
                        <a href="/blog.html" class="blog-category" style="text-decoration:none;"><?php echo htmlspecialchars($tag['name']); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h1><?php echo htmlspecialchars($post['title']); ?></h1>

            <div class="blog-meta" style="justify-content: center; color: var(--text-muted); margin-top: 1.5rem;">
                <span>📅 <?php echo date('M d, Y', strtotime($post['published_at'])); ?></span>
                <span>⏱️ <?php echo ceil(str_word_count($post['content']) / 200); ?> min read</span>
                <span>👁️ <?php echo number_format($post['views']); ?> views</span>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 0;">
        <div class="container" style="max-width: 1200px;">
            <article class="blog-content glass-card" style="padding: 3rem; line-height: 1.8;">
                <?php echo $post['content']; ?>
            </article>

            <div style="text-align: center; margin-top: 3rem;">
                <a href="/blog.html" class="btn btn-secondary">← Back to Blog</a>
            </div>
        </div>
    </section>
    
    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><img src="/logo.png" alt="Joshi Management Consultancy" style="height: 70px; width: auto;"></h3>
                    <p>Joshi Management Consultancy - Transforming businesses through strategic AI implementation and expert consulting services.</p>
                    <div class="social-links">
                     <a href="https://www.linkedin.com/company/joshimc/" aria-label="LinkedIn" target="_blank">in</a>
                     <a href="https://www.instagram.com/joshiconsulting" aria-label="Instagram" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/>
                         </svg>
                     </a>
                     <a href="#" aria-label="YouTube">
                           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                             <path d="M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.108-.082 2.06l-.008.105-.009.104c-.05.572-.124 1.14-.235 1.558a2.007 2.007 0 0 1-1.415 1.42c-1.16.312-5.569.334-6.18.335h-.142c-.309 0-1.587-.006-2.927-.052l-.17-.006-.087-.004-.171-.007-.171-.007c-1.11-.049-2.167-.128-2.654-.26a2.007 2.007 0 0 1-1.415-1.419c-.111-.417-.185-.986-.235-1.558L.09 9.82l-.008-.104A31.4 31.4 0 0 1 0 7.68v-.123c.002-.215.01-.958.064-1.778l.007-.103.003-.052.008-.104.022-.26.01-.104c.048-.519.119-1.023.22-1.402a2.007 2.007 0 0 1 1.415-1.42c.487-.13 1.544-.21 2.654-.26l.17-.007.172-.006.086-.003.171-.007A99.788 99.788 0 0 1 7.858 2h.193zM6.4 5.209v4.818l4.157-2.408L6.4 5.209z"/>
                            </svg>
                    </a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="/services.html#ai-implementation">AI Implementation</a></li>
                        <li><a href="/services.html#software-implementation">Software Implementation</a></li>
                        <li><a href="/services.html#opportunity-assessment">AI Opportunity Assessment</a></li>
                        <li><a href="/services.html#corporate-training">Corporate Training</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="/about.html">About Us</a></li>
                        <li><a href="/blog.html">Blog</a></li>
                        <li><a href="/courses.html">Courses</a></li>
                        <li><a href="/contact.html">Contact</a></li>
                    </ul>
                </div>
                
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Joshi Management Consultancy. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JAVASCRIPT -->
    <script>
        // ===== NAVIGATION SCROLL EFFECT =====
        const nav = document.getElementById('nav');
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });

        // ===== MOBILE MENU TOGGLE =====
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            const isActive = navLinks.classList.contains('active');
            mobileMenuBtn.innerHTML = isActive ? '<span>✕</span>' : '<span>☰</span>';
        });

        // Close mobile menu when clicking a link (but not dropdown toggles)
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                if (!link.classList.contains('nav-dropdown-toggle')) {
                    navLinks.classList.remove('active');
                    mobileMenuBtn.innerHTML = '<span>☰</span>';
                }
            });
        });

        // ===== INDUSTRIES DROPDOWN =====
        document.querySelectorAll('.nav-dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const dropdown = this.closest('.nav-dropdown');
                const isOpen = dropdown.classList.contains('open');
                document.querySelectorAll('.nav-dropdown').forEach(d => d.classList.remove('open'));
                if (!isOpen) dropdown.classList.add('open');
            });
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.nav-dropdown').forEach(d => d.classList.remove('open'));
            }
        });

        // ===== BLOG FILTERING SYSTEM =====
        const searchInput = document.getElementById('searchInput');
        const categoryButtons = document.querySelectorAll('.category-btn');
        const blogCards = document.querySelectorAll('.blog-card');
        const noResults = document.getElementById('noResults');

        let currentCategory = 'all';
        let searchTerm = '';

        // Category filter functionality
        categoryButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Update active button
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Update current category
                currentCategory = button.getAttribute('data-category');
                
                // Filter posts
                filterPosts();
            });
        });

        // Search functionality
        searchInput.addEventListener('input', (e) => {
            searchTerm = e.target.value.toLowerCase();
            filterPosts();
        });

        // Main filter function
        function filterPosts() {
            let visibleCount = 0;

            blogCards.forEach(card => {
                const category = card.getAttribute('data-category');
                const title = card.getAttribute('data-title').toLowerCase();
                const keywords = card.getAttribute('data-keywords').toLowerCase();
                
                // Check category match
                const categoryMatch = currentCategory === 'all' || category === currentCategory;
                
                // Check search match (search in title and keywords)
                const searchMatch = searchTerm === '' || 
                                   title.includes(searchTerm) || 
                                   keywords.includes(searchTerm);
                
                // Show or hide card with smooth animation
                if (categoryMatch && searchMatch) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            // Show "no results" message if no posts are visible
            if (visibleCount === 0) {
                noResults.classList.add('show');
            } else {
                noResults.classList.remove('show');
            }
        }

        // ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#' && href !== '') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });

        // ===== PREVENT DEFAULT ON READ MORE LINKS (DEMO) =====
        document.querySelectorAll('.read-more').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                alert('This is a demo. In production, this would link to the full article.');
            });
        });
       // ===== YOUTUBE MODAL =====
document.addEventListener('DOMContentLoaded', function() {
    const youtubeModal = document.getElementById('youtubeModal');
    const closeModal = document.getElementById('closeModal');
    const youtubeSubscribeForm = document.getElementById('youtubeSubscribeForm');
    const youtubeSuccessMessage = document.getElementById('youtubeSuccessMessage');

    // Check if modal exists (it should)
    if (!youtubeModal) {
        console.error('YouTube modal not found');
        return;
    }

    // Find all YouTube social links
    const youtubeLinks = document.querySelectorAll('a[aria-label="YouTube"]');
    youtubeLinks.forEach(link => {
        link.onclick = function(e) {
            e.preventDefault();
            youtubeModal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        };
    });

    // Close modal
    closeModal.onclick = function() {
        youtubeModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    };

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == youtubeModal) {
            youtubeModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    };

    // Handle form submission
    youtubeSubscribeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = document.getElementById('youtubeEmail').value;
        const submitBtn = youtubeSubscribeForm.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Subscribing...';
        
        const formData = new FormData();
        formData.append('email', email);
        
        fetch('php/youtube_subscribe.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                youtubeSubscribeForm.style.display = 'none';
                youtubeSuccessMessage.style.display = 'block';
                
                // Close modal after 3 seconds
                setTimeout(() => {
                    youtubeModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    
                    // Reset form for next time
                    setTimeout(() => {
                        youtubeSubscribeForm.style.display = 'block';
                        youtubeSuccessMessage.style.display = 'none';
                        youtubeSubscribeForm.reset();
                    }, 500);
                }, 3000);
            } else {
                alert(data.message);
            }
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Subscribe for Updates';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Subscribe for Updates';
        });
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && youtubeModal.style.display === 'block') {
            youtubeModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
});
    </script>
<!-- YouTube Subscribe Modal -->
<div id="youtubeModal" class="modal">
    <div class="modal-content glass-card">
        <span class="modal-close" id="closeModal">&times;</span>
        <div class="modal-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16" style="color: #FF0000;">
                <path d="M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.108-.082 2.06l-.008.105-.009.104c-.05.572-.124 1.14-.235 1.558a2.007 2.007 0 0 1-1.415 1.42c-1.16.312-5.569.334-6.18.335h-.142c-.309 0-1.587-.006-2.927-.052l-.17-.006-.087-.004-.171-.007-.171-.007c-1.11-.049-2.167-.128-2.654-.26a2.007 2.007 0 0 1-1.415-1.419c-.111-.417-.185-.986-.235-1.558L.09 9.82l-.008-.104A31.4 31.4 0 0 1 0 7.68v-.123c.002-.215.01-.958.064-1.778l.007-.103.003-.052.008-.104.022-.26.01-.104c.048-.519.119-1.023.22-1.402a2.007 2.007 0 0 1 1.415-1.42c.487-.13 1.544-.21 2.654-.26l.17-.007.172-.006.086-.003.171-.007A99.788 99.788 0 0 1 7.858 2h.193zM6.4 5.209v4.818l4.157-2.408L6.4 5.209z"/>
            </svg>
            <h2 style="margin-top: 1.5rem;">YouTube Channel Coming Soon!</h2>
        </div>
        <p style="margin: 1.5rem 0; font-size: 1.125rem; line-height: 1.6;">
            We're launching our YouTube channel with exclusive AI tutorials, case studies, and industry insights. Be the first to know when we go live!
        </p>
        
        <div id="youtubeSuccessMessage" class="modal-success" style="display: none;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">✓</div>
            <h3 style="color: var(--accent-green); margin-bottom: 0.5rem;">You're Subscribed!</h3>
            <p>We'll send you an email as soon as our channel launches.</p>
        </div>
        
        <form id="youtubeSubscribeForm" class="modal-form">
            <input 
                type="email" 
                name="email" 
                id="youtubeEmail" 
                placeholder="Enter your email address" 
                required
                style="width: 100%; padding: 1rem 1.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.18); border-radius: 12px; color: white; font-size: 1rem; font-family: var(--font-body); margin-bottom: 1.5rem;"
            >
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Subscribe for Updates
            </button>
        </form>
        
        <p style="margin-top: 1.5rem; font-size: 0.875rem; color: var(--text-muted); text-align: center;">
            🔒 We respect your privacy. Unsubscribe anytime.
        </p>
    </div>
</div>
</body>
</html>
