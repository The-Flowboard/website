<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

requireLogin();

if (isset($_GET['logout'])) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo csrfTokenMeta(); ?>
    <title>Admin Dashboard | JMC</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-deep: #0a1128;
            --primary-dark: #1e2749;
            --primary-mid: #2d3561;
            --accent-purple: #a855f7;
            --accent-cyan: #06b6d4;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-orange: #f59e0b;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-muted: rgba(255, 255, 255, 0.6);
            --bg-glass: rgba(255, 255, 255, 0.08);
            --border-glass: rgba(255, 255, 255, 0.18);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--primary-dark) 50%, var(--primary-mid) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: rgba(10, 17, 40, 0.95);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--border-glass);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info span {
            color: var(--text-secondary);
        }
        
        .btn {
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            color: white;
        }
        
        .btn-danger {
            background: var(--accent-red);
            color: white;
        }
        
        .btn-success {
            background: var(--accent-green);
            color: white;
        }
        
        .btn-secondary {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        /* Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-glass);
            backdrop-filter: blur(24px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.purple { background: linear-gradient(135deg, var(--accent-purple), #c084fc); }
        .stat-icon.cyan { background: linear-gradient(135deg, var(--accent-cyan), #22d3ee); }
        .stat-icon.green { background: linear-gradient(135deg, var(--accent-green), #34d399); }
        .stat-icon.orange { background: linear-gradient(135deg, var(--accent-orange), #fbbf24); }
        
        .stat-info h3 {
            font-size: 2rem;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
        }
        
        .stat-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-glass);
            overflow-x: auto;
        }
        
        .tab {
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .tab:hover {
            color: var(--text-primary);
        }
        
        .tab.active {
            color: var(--accent-cyan);
            border-bottom-color: var(--accent-cyan);
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Table Container */
        .table-container {
            background: var(--bg-glass);
            backdrop-filter: blur(24px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border-bottom: 1px solid var(--border-glass);
        }
        
        .table-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        /* Search */
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            color: white;
            font-family: 'Outfit', sans-serif;
            min-width: 300px;
        }
        
        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--accent-cyan);
        }
        
        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-glass);
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
        }
        
        .data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .data-table tbody tr.selected {
            background: rgba(6, 182, 212, 0.1);
        }
        
        /* Action Buttons in Table */
        .table-btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            margin-right: 0.25rem;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--primary-dark);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border-glass);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            color: white;
            font-family: 'Outfit', sans-serif;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-cyan);
        }
        
        /* Checkbox */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: auto;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }
        
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid var(--accent-cyan);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid var(--accent-green);
            color: var(--accent-green);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
        }

        /* Image Upload Styles */
        .image-upload-container {
            margin-bottom: 1.5rem;
        }

        .image-upload-zone {
            border: 2px dashed var(--border-glass);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: rgba(255, 255, 255, 0.03);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .image-upload-zone:hover {
            border-color: var(--accent-cyan);
            background: rgba(6, 182, 212, 0.1);
        }

        .image-upload-zone.dragover {
            border-color: var(--accent-purple);
            background: rgba(168, 85, 247, 0.15);
            transform: scale(1.02);
        }

        .image-upload-zone input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--accent-cyan);
            margin-bottom: 1rem;
        }

        .upload-text {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .upload-hint {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .image-preview-container {
            display: none;
            margin-top: 1rem;
            position: relative;
        }

        .image-preview-container.active {
            display: block;
        }

        .image-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 12px;
            border: 1px solid var(--border-glass);
        }

        .image-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            justify-content: center;
        }

        .upload-progress {
            display: none;
            margin-top: 1rem;
        }

        .upload-progress.active {
            display: block;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            transition: width 0.3s ease;
            width: 0%;
        }

        .upload-error {
            color: var(--accent-red);
            margin-top: 0.5rem;
            font-size: 0.875rem;
            display: none;
        }

        .upload-error.active {
            display: block;
        }

        /* Image Gallery */
        .images-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .image-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .image-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent-cyan);
            box-shadow: 0 8px 24px rgba(6, 182, 212, 0.2);
        }

        .image-card.selected {
            border-color: var(--accent-cyan);
            background: rgba(6, 182, 212, 0.1);
        }

        .image-card-checkbox {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
            z-index: 10;
        }

        .image-card-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.02);
            position: relative;
        }

        .image-card-info {
            padding: 1rem;
        }

        .image-card-filename {
            font-size: 0.875rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            word-break: break-all;
            font-family: 'Courier New', monospace;
        }

        .image-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .image-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .image-card-btn {
            flex: 1;
            padding: 0.5rem;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }

        .no-images {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .no-images i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        /* Image Picker Modal */
        .image-picker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .picker-image-card {
            position: relative;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .picker-image-card:hover {
            border-color: var(--accent-cyan);
        }

        .picker-image-card.selected {
            border-color: var(--accent-purple);
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.3);
        }

        .picker-image-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .picker-image-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            padding: 0.5rem;
            font-size: 0.7rem;
            color: white;
            word-break: break-all;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .search-box input {
                min-width: auto;
                width: 100%;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .data-table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1><i class="fas fa-database"></i> Admin Dashboard</h1>
        <div class="user-info">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="?logout" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Stats -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-envelope"></i></div>
                <div class="stat-info">
                    <h3 id="contactCount">-</h3>
                    <p>Contact Submissions</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon cyan"><i class="fas fa-newspaper"></i></div>
                <div class="stat-info">
                    <h3 id="blogCount">-</h3>
                    <p>Blog Posts</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3 id="coursesCount">-</h3>
                    <p>Course Interests</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-eye"></i></div>
                <div class="stat-info">
                    <h3 id="totalViews">-</h3>
                    <p>Total Blog Views</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" data-tab="contacts"><i class="fas fa-envelope"></i> Contact Submissions</button>
            <button class="tab" data-tab="blogs"><i class="fas fa-newspaper"></i> Blog Posts</button>
            <button class="tab" data-tab="images"><i class="fas fa-images"></i> Images</button>
            <button class="tab" data-tab="courses"><i class="fas fa-graduation-cap"></i> Course Interests</button>
            <button class="tab" data-tab="assessments"><i class="fas fa-clipboard-check"></i> AI Assessments</button>
            <button class="tab" data-tab="settings"><i class="fas fa-cog"></i> Settings</button>
        </div>

        <!-- Tab Content: Contact Submissions -->
        <div class="tab-content active" id="contacts">
            <div class="table-container">
                <div class="table-header">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="contactSearch" placeholder="Search contacts...">
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-danger" id="deleteContactsBtn"><i class="fas fa-trash"></i> Delete Selected</button>
                        <button class="btn btn-success" id="exportContactsBtn"><i class="fas fa-file-excel"></i> Export Excel</button>
                    </div>
                </div>
                <div id="contactsTableWrapper">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Loading data...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Blog Posts -->
        <div class="tab-content" id="blogs">
            <div class="table-container">
                <div class="table-header">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="blogSearch" placeholder="Search blog posts...">
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-primary" id="addBlogBtn"><i class="fas fa-plus"></i> Add Post</button>
                        <button class="btn btn-danger" id="deleteBlogsBtn"><i class="fas fa-trash"></i> Delete Selected</button>
                        <button class="btn btn-success" id="exportBlogsBtn"><i class="fas fa-file-excel"></i> Export Excel</button>
                    </div>
                </div>
                <div id="blogsTableWrapper">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Loading data...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Images -->
        <div class="tab-content" id="images">
            <div class="table-container">
                <div class="table-header">
                    <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-secondary); white-space: nowrap;">
                            <input type="checkbox" id="selectAllImages" style="cursor: pointer;">
                            <span>Select All</span>
                        </label>
                        <div class="search-box" style="flex: 1;">
                            <i class="fas fa-search"></i>
                            <input type="text" id="imageSearch" placeholder="Search images...">
                        </div>
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-danger" id="deleteImagesBtn"><i class="fas fa-trash"></i> Delete Selected</button>
                        <button class="btn btn-primary" id="uploadImageBtn"><i class="fas fa-upload"></i> Upload Image</button>
                    </div>
                </div>
                <div id="imagesGalleryWrapper">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Loading images...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Course Interests -->
        <div class="tab-content" id="courses">
            <div class="table-container">
                <div class="table-header">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="courseSearch" placeholder="Search emails...">
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-danger" id="deleteCoursesBtn"><i class="fas fa-trash"></i> Delete Selected</button>
                        <button class="btn btn-success" id="exportCoursesBtn"><i class="fas fa-file-excel"></i> Export Excel</button>
                    </div>
                </div>
                <div id="coursesTableWrapper">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Loading data...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: AI Assessments -->
        <div class="tab-content" id="assessments">
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-clipboard-check"></i> AI Opportunity Assessments</h3>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchAssessments" placeholder="Search assessments...">
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-danger" id="deleteSelectedAssessments" disabled><i class="fas fa-trash-alt"></i> Delete Selected</button>
                        <button class="btn btn-success" id="exportAssessments"><i class="fas fa-file-excel"></i> Export to Excel</button>
                    </div>
                </div>
                <div id="assessmentsTableWrapper"></div>
            </div>
        </div>

        <!-- Tab Content: Settings -->
        <div class="tab-content" id="settings">
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-user-cog"></i> Account Settings</h3>
                </div>
                <div style="padding: 2rem;">
                    <div id="passwordMessage"></div>
                    <form id="changePasswordForm">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" id="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" id="new_password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit/Add Blog Modal -->
    <div class="modal" id="blogModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="blogModalTitle">Add Blog Post</h2>
                <button class="modal-close" onclick="closeBlogModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="blogForm">
                    <input type="hidden" id="blogId" name="id">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" id="blogTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" id="blogSlug" name="slug" required>
                        <small style="color: var(--text-muted);">URL-friendly version (e.g., "my-blog-post")</small>
                    </div>
                    <div class="form-group">
                        <label>Tags</label>
                        <!-- Tag chip input -->
                        <div id="tagInputWrapper" style="
                            display: flex;
                            flex-wrap: wrap;
                            gap: 0.5rem;
                            align-items: center;
                            padding: 0.6rem 0.75rem;
                            background: rgba(255,255,255,0.05);
                            border: 1px solid rgba(255,255,255,0.15);
                            border-radius: 8px;
                            min-height: 46px;
                            cursor: text;
                        " onclick="document.getElementById('tagTextInput').focus()">
                            <div id="tagChips" style="display: flex; flex-wrap: wrap; gap: 0.4rem;"></div>
                            <input type="text" id="tagTextInput" placeholder="Type a tag and press Enter or comma…" style="
                                border: none;
                                background: transparent;
                                outline: none;
                                color: white;
                                font-family: var(--font-body);
                                font-size: 0.9rem;
                                flex: 1;
                                min-width: 160px;
                            ">
                        </div>
                        <small style="color: var(--text-muted);">Press Enter or comma to add a tag. Click × to remove.</small>
                        <!-- Hidden CSV input sent to server -->
                        <input type="hidden" id="blogTags" name="tags" value="">
                    </div>
                    <div class="form-group">
                        <label>Excerpt</label>
                        <textarea id="blogExcerpt" name="excerpt"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Featured Image</label>
                        <div class="image-upload-container">
                            <!-- Upload Zone -->
                            <div class="image-upload-zone" id="imageUploadZone">
                                <input type="file" id="imageInput" accept="image/jpeg,image/png,image/webp">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-text">
                                    <strong>Click to upload</strong> or drag and drop
                                </div>
                                <div class="upload-hint">
                                    JPG, PNG or WebP (Max 5MB)
                                </div>
                            </div>

                            <!-- Browse Uploaded Images Button -->
                            <div style="text-align: center; margin-top: 1rem;">
                                <button type="button" class="btn btn-secondary" onclick="openImagePicker()" style="width: 100%;">
                                    <i class="fas fa-images"></i> Browse Uploaded Images
                                </button>
                            </div>

                            <!-- Progress Bar -->
                            <div class="upload-progress" id="uploadProgress">
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progressFill"></div>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div class="upload-error" id="uploadError"></div>

                            <!-- Image Preview -->
                            <div class="image-preview-container" id="imagePreviewContainer">
                                <img id="imagePreview" class="image-preview" src="" alt="Featured Image Preview">
                                <div class="image-actions">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="changeImage()">
                                        <i class="fas fa-sync-alt"></i> Change Image
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeImage()">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>

                            <!-- Hidden input to store image path -->
                            <input type="hidden" id="featuredImagePath" name="featured_image" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Content (HTML) *</label>
                        <textarea id="blogContent" name="content" required style="min-height: 200px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea id="blogMetaDesc" name="meta_description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="blogStatus" name="status">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeBlogModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveBlog()"><i class="fas fa-save"></i> Save</button>
            </div>
        </div>
    </div>

    <!-- Image Picker Modal -->
    <div class="modal" id="imagePickerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Select Featured Image</h2>
                <button class="modal-close" onclick="closeImagePicker()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="search-box" style="margin-bottom: 1rem;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="pickerImageSearch" placeholder="Search images...">
                </div>
                <div id="imagePickerGrid" class="image-picker-grid">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Loading images...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: space-between; padding: 1.5rem; border-top: 1px solid var(--border-glass);">
                <button class="btn btn-secondary" onclick="closeImagePicker()">Cancel</button>
                <button class="btn btn-primary" onclick="selectPickedImage()">
                    <i class="fas fa-check"></i> Use Selected Image
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>
