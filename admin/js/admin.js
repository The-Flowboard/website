// Admin Dashboard JavaScript

// Get CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

// Configure jQuery AJAX to always include CSRF token
$.ajaxSetup({
    beforeSend: function(xhr, settings) {
        // Add CSRF token to POST requests
        if (settings.type === 'POST' || settings.type === 'DELETE') {
            if (settings.data instanceof FormData) {
                settings.data.append('csrf_token', csrfToken);
            } else if (settings.contentType && settings.contentType.indexOf('application/json') !== -1) {
                // For JSON requests, send token as a header to avoid corrupting the JSON body
                xhr.setRequestHeader('X-CSRF-Token', csrfToken);
            } else {
                const separator = settings.data ? '&' : '';
                settings.data = settings.data + separator + 'csrf_token=' + encodeURIComponent(csrfToken);
            }
        }
    }
});

$(document).ready(function() {
    // Load initial data
    loadStats();
    loadContacts();
    loadBlogs();
    loadCourses();
    loadAssessments();

    // Tab switching
    $('.tab').click(function() {
        const target = $(this).data('tab');
        $('.tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#' + target).addClass('active');

        // Load images when switching to images tab
        if (target === 'images') {
            loadImages();
        }
    });

    // Search functionality
    $('#contactSearch').on('keyup', function() {
        searchTable($(this).val(), 'contactsTable');
    });

    $('#blogSearch').on('keyup', function() {
        searchTable($(this).val(), 'blogsTable');
    });

    $('#courseSearch').on('keyup', function() {
        searchTable($(this).val(), 'coursesTable');
    });

    $('#searchAssessments').on('keyup', function() {
        searchTable($(this).val(), 'assessmentsTable');
    });

    $('#imageSearch').on('keyup', function() {
        filterImages($(this).val());
    });

    $('#pickerImageSearch').on('keyup', function() {
        filterPickerImages($(this).val());
    });

    // Delete buttons
    $('#deleteContactsBtn').click(() => deleteSelected('contacts'));
    $('#deleteBlogsBtn').click(() => deleteSelected('blogs'));
    $('#deleteCoursesBtn').click(() => deleteSelected('courses'));
    $('#deleteSelectedAssessments').click(() => deleteSelected('assessments'));
    $('#deleteImagesBtn').click(() => deleteSelectedImages());

    // Export buttons
    $('#exportContactsBtn').click(() => exportToExcel('contacts'));
    $('#exportBlogsBtn').click(() => exportToExcel('blogs'));
    $('#exportCoursesBtn').click(() => exportToExcel('courses'));
    $('#exportAssessments').click(() => exportToExcel('assessments'));

    // Blog actions
    $('#addBlogBtn').click(openBlogModal);

    // Image actions
    $('#uploadImageBtn').click(openUploadImageModal);
    $('#selectAllImages').change(toggleSelectAllImages);

    // Change password form
    $('#changePasswordForm').submit(function(e) {
        e.preventDefault();
        changePassword();
    });
});

// Load Statistics
function loadStats() {
    $.get('ajax/get_stats.php', function(data) {
        $('#contactCount').text(data.contacts || 0);
        $('#blogCount').text(data.blogs || 0);
        $('#coursesCount').text(data.courses || 0);
        $('#totalViews').text(data.total_views || 0);
    });
}

// Load Contacts
function loadContacts() {
    $.get('ajax/get_data.php?type=contacts', function(data) {
        let html = '';
        if (data.length === 0) {
            html = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No contact submissions yet</p></div>';
        } else {
            html = `
                <table class="data-table" id="contactsTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllContacts"></th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Referral Source</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.forEach(item => {
                // Status badge styling
                const statusColors = {
                    'new': 'background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);',
                    'contacted': 'background: rgba(168, 85, 247, 0.2); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3);',
                    'qualified': 'background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);',
                    'proposal_sent': 'background: rgba(6, 182, 212, 0.2); color: #06b6d4; border: 1px solid rgba(6, 182, 212, 0.3);',
                    'won': 'background: rgba(34, 197, 94, 0.2); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);',
                    'lost': 'background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);',
                    'nurture': 'background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);'
                };
                const statusStyle = statusColors[item.status] || statusColors['new'];
                const statusLabel = (item.status || 'new').replace('_', ' ');

                html += `
                    <tr>
                        <td><input type="checkbox" class="select-row" data-id="${item.id}"></td>
                        <td>${item.first_name || '-'}</td>
                        <td>${item.last_name || '-'}</td>
                        <td>${item.email}</td>
                        <td>${item.phone}</td>
                        <td>${item.company || '-'}</td>
                        <td>${item.referral_source || '-'}</td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">${item.message}</td>
                        <td><span style="padding: 0.375rem 0.75rem; border-radius: 12px; font-size: 0.8125rem; font-weight: 600; text-transform: capitalize; ${statusStyle}">${statusLabel}</span></td>
                        <td>${new Date(item.submitted_at).toLocaleString()}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        $('#contactsTableWrapper').html(html);
        
        // Select all checkbox
        $('#selectAllContacts').change(function() {
            $('#contactsTable .select-row').prop('checked', this.checked);
        });
    });
}

// Load Blogs
function loadBlogs() {
    $.get('ajax/get_data.php?type=blogs', function(data) {
        let html = '';
        if (data.length === 0) {
            html = '<div class="empty-state"><i class="fas fa-newspaper"></i><p>No blog posts yet</p></div>';
        } else {
            html = `
                <table class="data-table" id="blogsTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllBlogs"></th>
                            <th>Title</th>
                            <th>Tags</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.forEach(item => {
                const statusColor = item.status === 'published' ? 'var(--accent-green)' : 'var(--accent-orange)';
                const tagsHtml = (item.tags || []).map(t =>
                    `<span style="display:inline-block;padding:2px 8px;border-radius:50px;font-size:0.75rem;font-weight:600;background:rgba(168,85,247,0.15);color:var(--accent-purple);border:1px solid rgba(168,85,247,0.3);margin:1px;">${t}</span>`
                ).join('') || '<span style="color:var(--text-muted);font-size:0.8rem;">—</span>';
                html += `
                    <tr>
                        <td><input type="checkbox" class="select-row" data-id="${item.id}"></td>
                        <td>${item.title}</td>
                        <td>${tagsHtml}</td>
                        <td><span style="color: ${statusColor}; font-weight: 600;">${item.status}</span></td>
                        <td>${item.views || 0}</td>
                        <td>${new Date(item.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="btn btn-primary table-btn" onclick="editBlog(${item.id})"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        $('#blogsTableWrapper').html(html);
        
        $('#selectAllBlogs').change(function() {
            $('#blogsTable .select-row').prop('checked', this.checked);
        });
    });
}

// Load Courses
function loadCourses() {
    $.get('ajax/get_data.php?type=courses', function(data) {
        let html = '';
        if (data.length === 0) {
            html = '<div class="empty-state"><i class="fas fa-graduation-cap"></i><p>No course interests yet</p></div>';
        } else {
            html = `
                <table class="data-table" id="coursesTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllCourses"></th>
                            <th>Email</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td><input type="checkbox" class="select-row" data-id="${item.id}"></td>
                        <td>${item.email}</td>
                        <td>${new Date(item.submitted_at).toLocaleString()}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        $('#coursesTableWrapper').html(html);

        $('#selectAllCourses').change(function() {
            $('#coursesTable .select-row').prop('checked', this.checked);
        });
    });
}

// Load Assessments
function loadAssessments() {
    $.get('ajax/get_data.php?type=assessments', function(data) {
        let html = '';
        if (data.length === 0) {
            html = '<div class="empty-state"><i class="fas fa-clipboard-check"></i><p>No assessments yet</p></div>';
        } else {
            html = `
                <table class="data-table" id="assessmentsTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllAssessments"></th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Industry</th>
                            <th>Company Size</th>
                            <th>Readiness</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.forEach(item => {
                // Readiness badge styling
                const readinessColors = {
                    'Advanced': 'background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);',
                    'Intermediate': 'background: rgba(6, 182, 212, 0.2); color: #06b6d4; border: 1px solid rgba(6, 182, 212, 0.3);',
                    'Beginner': 'background: rgba(168, 85, 247, 0.2); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3);'
                };
                const readinessStyle = readinessColors[item.readiness_level] || readinessColors['Intermediate'];

                html += `
                    <tr>
                        <td><input type="checkbox" class="select-row" data-id="${item.id}"></td>
                        <td>#${item.id}</td>
                        <td>${item.name}</td>
                        <td>${item.email}</td>
                        <td>${item.company_name}</td>
                        <td>${item.industry}</td>
                        <td>${item.company_size}</td>
                        <td><span style="padding: 0.375rem 0.75rem; border-radius: 12px; font-size: 0.8125rem; font-weight: 600; ${readinessStyle}">${item.readiness_level}</span></td>
                        <td>${new Date(item.submitted_at).toLocaleString()}</td>
                        <td>
                            <button class="btn btn-primary table-btn" onclick="viewAssessment(${item.id})" title="View Results">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-success table-btn" onclick="resendAssessment(${item.id})" title="Resend Results">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        $('#assessmentsTableWrapper').html(html);

        $('#selectAllAssessments').change(function() {
            $('#assessmentsTable .select-row').prop('checked', this.checked);
        });
    });
}

// View assessment results
function viewAssessment(id) {
    window.open('/assessment-results.php?id=' + id, '_blank');
}

// Resend assessment results via email
function resendAssessment(id) {
    if (!confirm('Resend assessment results to the client?')) {
        return;
    }

    $.ajax({
        url: 'ajax/resend_assessment.php',
        method: 'POST',
        data: { assessment_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Assessment results sent successfully!');
            } else {
                alert('Error: ' + (response.message || 'Failed to send assessment'));
            }
        },
        error: function() {
            alert('Failed to send assessment. Please try again.');
        }
    });
}

// Search in table
function searchTable(query, tableId) {
    const rows = $(`#${tableId} tbody tr`);
    rows.each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(query.toLowerCase()) > -1);
    });
}

// Delete selected items
function deleteSelected(type) {
    const selected = [];
    $(`#${type}Table .select-row:checked`).each(function() {
        selected.push($(this).data('id'));
    });

    if (selected.length === 0) {
        alert('Please select items to delete');
        return;
    }

    if (!confirm(`Delete ${selected.length} item(s)?`)) {
        return;
    }

    $.ajax({
        url: 'ajax/delete_data.php',
        method: 'POST',
        data: { type: type, ids: selected },
        success: function(response) {
            if (response.success) {
                if (type === 'contacts') loadContacts();
                else if (type === 'blogs') loadBlogs();
                else if (type === 'courses') loadCourses();
                else if (type === 'assessments') loadAssessments();
                loadStats();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
}

// Export to Excel
function exportToExcel(type) {
    window.location.href = `ajax/export_excel.php?type=${type}`;
}

// Blog Modal Functions
function openBlogModal() {
    $('#blogModalTitle').text('Add Blog Post');
    $('#blogForm')[0].reset();
    $('#blogId').val('');
    clearTagChips();
    resetImageUpload();
    initImageUpload();
    $('#blogModal').addClass('active');
}

function closeBlogModal() {
    $('#blogModal').removeClass('active');
    resetImageUpload();
}

function editBlog(id) {
    $.get('ajax/get_data.php?type=blogs', function(data) {
        const blog = data.find(b => b.id == id);
        if (blog) {
            $('#blogModalTitle').text('Edit Blog Post');
            $('#blogId').val(blog.id);
            $('#blogTitle').val(blog.title);
            $('#blogSlug').val(blog.slug);
            // Populate tag chips
            clearTagChips();
            (blog.tags || []).forEach(t => addTagChip(t));
            $('#blogExcerpt').val(blog.excerpt);
            $('#blogContent').val(blog.content);
            $('#blogMetaDesc').val(blog.meta_description);
            $('#blogStatus').val(blog.status);

            // Handle featured image
            if (blog.featured_image) {
                $('#featuredImagePath').val(blog.featured_image);
                showImagePreview(blog.featured_image);
                $('#imageUploadZone').hide();
            } else {
                resetImageUpload();
            }

            initImageUpload();
            $('#blogModal').addClass('active');
        }
    });
}

function saveBlog() {
    const formData = {
        id: $('#blogId').val(),
        title: $('#blogTitle').val(),
        slug: $('#blogSlug').val(),
        tags: $('#blogTags').val(),
        excerpt: $('#blogExcerpt').val(),
        content: $('#blogContent').val(),
        featured_image: $('#featuredImagePath').val(),
        meta_description: $('#blogMetaDesc').val(),
        status: $('#blogStatus').val()
    };
    
    $.ajax({
        url: 'ajax/save_blog.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                closeBlogModal();
                loadBlogs();
                loadStats();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Failed to save blog post');
        }
    });
}

// Change password
function changePassword() {
    const current = $('#current_password').val();
    const newPass = $('#new_password').val();
    const confirm = $('#confirm_password').val();
    
    if (newPass !== confirm) {
        showPasswordMessage('New passwords do not match', 'error');
        return;
    }
    
    if (newPass.length < 6) {
        showPasswordMessage('Password must be at least 6 characters', 'error');
        return;
    }
    
    $.ajax({
        url: 'ajax/change_password.php',
        method: 'POST',
        data: { current_password: current, new_password: newPass },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showPasswordMessage('Password changed successfully!', 'success');
                $('#changePasswordForm')[0].reset();
            } else {
                showPasswordMessage(response.message || 'Failed to change password', 'error');
            }
        },
        error: function() {
            showPasswordMessage('Server error. Please try again.', 'error');
        }
    });
}

function showPasswordMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    const html = `
        <div class="alert ${alertClass}">
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        </div>
    `;
    
    $('#passwordMessage').html(html);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        $('#passwordMessage').fadeOut(() => {
            $('#passwordMessage').html('').show();
        });
    }, 5000);
}

// ==============================================
// IMAGE UPLOAD FUNCTIONALITY
// ==============================================

// Initialize drag-and-drop when modal opens
function initImageUpload() {
    // Remove existing event handlers using jQuery
    $('#imageUploadZone').off('click dragover dragleave drop');
    $('#imageInput').off('change');

    // Click to upload - use native DOM click
    $('#imageUploadZone').on('click', function(e) {
        // Only trigger if clicking on the zone itself, not the hidden input
        if (e.target.id === 'imageInput') return;

        const fileInput = document.getElementById('imageInput');
        if (fileInput) {
            fileInput.click();
        }
    });

    // File input change
    $('#imageInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleImageFile(file);
        }
    });

    // Drag and drop events
    $('#imageUploadZone').on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    $('#imageUploadZone').on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    $('#imageUploadZone').on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');

        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleImageFile(files[0]);
        }
    });
}

// Validate image file
function validateImageFile(file) {
    const errors = [];

    // Check file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        errors.push('Invalid file type. Only JPG, PNG, and WebP are allowed.');
    }

    // Check file size (5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        errors.push('File size exceeds 5MB limit.');
    }

    // Check filename for suspicious patterns
    if (file.name.split('.').length > 2) {
        errors.push('Invalid filename (multiple extensions detected).');
    }

    return errors;
}

// Handle image file selection/drop
function handleImageFile(file) {
    // Reset error state
    $('#uploadError').removeClass('active').text('');

    // Validate file
    const validationErrors = validateImageFile(file);
    if (validationErrors.length > 0) {
        showUploadError(validationErrors.join(' '));
        return;
    }

    // Upload file
    uploadImage(file);
}

// Upload image to server
function uploadImage(file) {
    const formData = new FormData();
    formData.append('image', file);

    // Show progress
    $('#uploadProgress').addClass('active');
    $('#progressFill').css('width', '0%');

    $.ajax({
        url: 'ajax/upload_image.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            // Upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    $('#progressFill').css('width', percentComplete + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            $('#uploadProgress').removeClass('active');

            if (response.success) {
                // Store path in hidden input
                $('#featuredImagePath').val(response.path);

                // Show preview
                showImagePreview(response.path);

                // Hide upload zone
                $('#imageUploadZone').hide();
            } else {
                showUploadError(response.message || 'Upload failed');
            }
        },
        error: function() {
            $('#uploadProgress').removeClass('active');
            showUploadError('Failed to upload image. Please try again.');
        }
    });
}

// Show image preview
function showImagePreview(imagePath) {
    $('#imagePreview').attr('src', imagePath);
    $('#imagePreviewContainer').addClass('active');
}

// Show upload error
function showUploadError(message) {
    $('#uploadError').addClass('active').text(message);
}

// Change image (show upload zone again)
function changeImage() {
    $('#imageUploadZone').show();
    $('#imagePreviewContainer').removeClass('active');
    $('#imageInput').val('');
}

// Remove image
function removeImage() {
    const imagePath = $('#featuredImagePath').val();

    if (!imagePath) {
        changeImage();
        return;
    }

    if (!confirm('Remove this image?')) {
        return;
    }

    // Optional: Delete from server
    $.ajax({
        url: 'ajax/delete_image.php',
        method: 'POST',
        data: { path: imagePath },
        success: function(response) {
            if (response.success) {
                resetImageUpload();
            } else {
                alert('Failed to delete image: ' + response.message);
            }
        },
        error: function() {
            // Still reset UI even if deletion fails
            resetImageUpload();
        }
    });
}

// Reset image upload UI
function resetImageUpload() {
    $('#featuredImagePath').val('');
    $('#imageInput').val('');
    $('#imagePreview').attr('src', '');
    $('#imagePreviewContainer').removeClass('active');
    $('#imageUploadZone').show();
    $('#uploadError').removeClass('active').text('');
}

// =========================================
// IMAGE MANAGEMENT FUNCTIONS
// =========================================

// Global variable to store all images
let allImages = [];
let selectedPickerImage = null;

// Load images for the Images tab
function loadImages() {
    $.ajax({
        url: 'ajax/get_images.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                allImages = response.images;
                renderImageGallery(allImages);
            } else {
                $('#imagesGalleryWrapper').html('<div class="no-images"><i class="fas fa-exclamation-circle"></i><p>' + response.message + '</p></div>');
            }
        },
        error: function() {
            $('#imagesGalleryWrapper').html('<div class="no-images"><i class="fas fa-exclamation-circle"></i><p>Failed to load images</p></div>');
        }
    });
}

// Render image gallery
function renderImageGallery(images) {
    if (!images || images.length === 0) {
        $('#imagesGalleryWrapper').html(`
            <div class="no-images">
                <i class="fas fa-images"></i>
                <p>No images uploaded yet</p>
                <button class="btn btn-primary" onclick="openUploadImageModal()">
                    <i class="fas fa-upload"></i> Upload Your First Image
                </button>
            </div>
        `);
        return;
    }

    let html = '<div class="images-gallery">';

    images.forEach(image => {
        // Build blog associations display
        let blogAssociations = '';
        if (image.usedCount > 0) {
            const blogTitles = image.usedInBlogs.map(blog => blog.title).join(', ');
            blogAssociations = `
                <div class="image-card-meta" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <span style="color: var(--accent-cyan); font-size: 0.75rem;">
                        <i class="fas fa-link"></i> Used in ${image.usedCount} blog${image.usedCount > 1 ? 's' : ''}: ${blogTitles}
                    </span>
                </div>
            `;
        } else {
            blogAssociations = `
                <div class="image-card-meta" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <span style="color: var(--text-muted); font-size: 0.75rem;">
                        <i class="fas fa-unlink"></i> Not used in any blog
                    </span>
                </div>
            `;
        }

        html += `
            <div class="image-card" data-filename="${image.filename}" data-path="${image.path}">
                <input type="checkbox" class="image-card-checkbox" value="${image.path}">
                <img src="${image.url}" alt="${image.filename}" class="image-card-thumbnail"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'200\\' height=\\'200\\'%3E%3Crect fill=\\'%23ccc\\' width=\\'200\\' height=\\'200\\'/%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' text-anchor=\\'middle\\' dy=\\'.3em\\' fill=\\'%23333\\' font-family=\\'sans-serif\\'%3ENo Preview%3C/text%3E%3C/svg%3E'">
                <div class="image-card-info">
                    <div class="image-card-filename">${image.filename}</div>
                    <div class="image-card-meta">
                        <span><i class="fas fa-calendar"></i> ${image.uploadedAtFormatted}</span>
                    </div>
                    <div class="image-card-meta">
                        <span><i class="fas fa-hdd"></i> ${image.sizeFormatted}</span>
                        ${image.dimensions ? `<span>${image.dimensions.width}×${image.dimensions.height}</span>` : ''}
                    </div>
                    ${blogAssociations}
                    <div class="image-card-actions">
                        <button class="btn btn-secondary btn-sm image-card-btn" onclick="renameImage('${image.path}', '${image.filename.replace(/'/g, "\\'")}')">
                            <i class="fas fa-edit"></i> Rename
                        </button>
                        <button class="btn btn-secondary btn-sm image-card-btn" onclick="copyImageUrl('${image.url}')">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="btn btn-danger btn-sm image-card-btn" onclick="deleteSingleImage('${image.path}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    $('#imagesGalleryWrapper').html(html);

    // Add click handler for cards
    $('.image-card').click(function(e) {
        if (!$(e.target).is('input, button')) {
            const checkbox = $(this).find('.image-card-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked'));
            $(this).toggleClass('selected', checkbox.prop('checked'));
        }
    });

    // Add change handler for checkboxes
    $('.image-card-checkbox').change(function() {
        $(this).closest('.image-card').toggleClass('selected', this.checked);
    });
}

// Filter images in the gallery
function filterImages(searchTerm) {
    const filtered = allImages.filter(img =>
        img.filename.toLowerCase().includes(searchTerm.toLowerCase())
    );
    renderImageGallery(filtered);
}

// Copy image URL to clipboard
function copyImageUrl(url) {
    const fullUrl = url.startsWith('http') ? url : 'https://joshimc.com' + url;
    navigator.clipboard.writeText(fullUrl).then(function() {
        alert('Image URL copied to clipboard!');
    }, function() {
        prompt('Copy this URL:', fullUrl);
    });
}

// Delete selected images
function deleteSelectedImages() {
    const selected = $('.image-card-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select at least one image to delete');
        return;
    }

    if (!confirm(`Are you sure you want to delete ${selected.length} image(s)?`)) {
        return;
    }

    const paths = selected.map(function() { return this.value; }).get();

    // Delete each image
    let completed = 0;
    let errors = 0;

    paths.forEach(path => {
        $.ajax({
            url: 'ajax/delete_image.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ path: path }),
            success: function(response) {
                completed++;
                if (!response.success) {
                    errors++;
                }
                if (completed === paths.length) {
                    if (errors > 0) {
                        alert(`Deleted ${completed - errors} images. ${errors} failed.`);
                    } else {
                        alert('Images deleted successfully');
                    }
                    loadImages(); // Reload gallery
                }
            },
            error: function() {
                completed++;
                errors++;
                if (completed === paths.length) {
                    alert(`Deleted ${completed - errors} images. ${errors} failed.`);
                    loadImages(); // Reload gallery
                }
            }
        });
    });
}

// Delete single image
function deleteSingleImage(path) {
    if (!confirm('Are you sure you want to delete this image?')) {
        return;
    }

    $.ajax({
        url: 'ajax/delete_image.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ path: path }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Image deleted successfully');
                loadImages(); // Reload gallery
            } else {
                alert('Failed to delete image: ' + response.message);
            }
        },
        error: function() {
            alert('Failed to delete image');
        }
    });
}

// Open upload image modal (for Images tab)
function openUploadImageModal() {
    // Open the blog modal just for image upload
    // Or you could create a separate upload modal
    alert('Use the "Browse Uploaded Images" button in the blog editor to upload images, or drag and drop images in the blog editor.');
}

// =========================================
// IMAGE PICKER MODAL FUNCTIONS
// =========================================

// Open image picker modal
function openImagePicker() {
    selectedPickerImage = null;
    $('#imagePickerModal').addClass('active');
    loadImagePicker();
}

// Close image picker modal
function closeImagePicker() {
    $('#imagePickerModal').removeClass('active');
    selectedPickerImage = null;
}

// Load images in the picker
function loadImagePicker() {
    $.ajax({
        url: 'ajax/get_images.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderImagePicker(response.images);
            } else {
                $('#imagePickerGrid').html('<div class="no-images"><p>' + response.message + '</p></div>');
            }
        },
        error: function() {
            $('#imagePickerGrid').html('<div class="no-images"><p>Failed to load images</p></div>');
        }
    });
}

// Render images in picker grid
function renderImagePicker(images) {
    if (!images || images.length === 0) {
        $('#imagePickerGrid').html(`
            <div class="no-images">
                <i class="fas fa-images"></i>
                <p>No images available. Upload images from the Images tab.</p>
            </div>
        `);
        return;
    }

    let html = '';
    images.forEach(image => {
        html += `
            <div class="picker-image-card" data-path="${image.path}" data-url="${image.url}" onclick="selectImageCard(this)">
                <img src="${image.url}" alt="${image.filename}" class="picker-image-thumbnail"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'150\\' height=\\'150\\'%3E%3Crect fill=\\'%23ccc\\' width=\\'150\\' height=\\'150\\'/%3E%3C/svg%3E'">
                <div class="picker-image-name">${image.filename}</div>
            </div>
        `;
    });

    $('#imagePickerGrid').html(html);
}

// Select an image card in the picker
function selectImageCard(element) {
    $('.picker-image-card').removeClass('selected');
    $(element).addClass('selected');
    selectedPickerImage = {
        path: $(element).data('path'),
        url: $(element).data('url')
    };
}

// Use the selected image from picker
function selectPickedImage() {
    if (!selectedPickerImage) {
        alert('Please select an image');
        return;
    }

    // Set the featured image
    $('#featuredImagePath').val(selectedPickerImage.path);
    $('#imagePreview').attr('src', selectedPickerImage.url);
    $('#imagePreviewContainer').addClass('active');
    $('#imageUploadZone').hide();

    // Close the picker
    closeImagePicker();
}

// Filter images in picker
function filterPickerImages(searchTerm) {
    const cards = $('.picker-image-card');
    cards.each(function() {
        const filename = $(this).find('.picker-image-name').text().toLowerCase();
        if (filename.includes(searchTerm.toLowerCase())) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Toggle select all images
function toggleSelectAllImages() {
    const isChecked = $('#selectAllImages').prop('checked');
    $('.image-card-checkbox').prop('checked', isChecked);
    $('.image-card').toggleClass('selected', isChecked);
}

// Rename image
function renameImage(oldPath, currentFilename) {
    const newFilename = prompt('Enter new filename:', currentFilename);

    if (!newFilename || newFilename === currentFilename) {
        return; // User cancelled or no change
    }

    $.ajax({
        url: 'ajax/rename_image.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            oldPath: oldPath,
            newFilename: newFilename
        }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let message = 'Image renamed successfully';
                if (response.updatedBlogs > 0) {
                    message += `\nUpdated ${response.updatedBlogs} blog post(s) with new path`;
                }
                alert(message);
                loadImages(); // Reload gallery
            } else {
                alert('Failed to rename image: ' + response.message);
            }
        },
        error: function() {
            alert('Failed to rename image');
        }
    });
}

// ===== TAG CHIP INPUT =====

function syncTagsInput() {
    const chips = document.querySelectorAll('#tagChips .tag-chip');
    const names = Array.from(chips).map(c => c.dataset.name);
    document.getElementById('blogTags').value = names.join(',');
}

function addTagChip(name) {
    name = name.trim();
    if (!name) return;

    // Prevent duplicates (case-insensitive)
    const existing = Array.from(document.querySelectorAll('#tagChips .tag-chip'))
        .map(c => c.dataset.name.toLowerCase());
    if (existing.includes(name.toLowerCase())) return;

    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.dataset.name = name;
    chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:50px;font-size:0.8rem;font-weight:600;background:rgba(168,85,247,0.18);color:var(--accent-purple);border:1px solid rgba(168,85,247,0.35);';
    chip.innerHTML = `${name} <button type="button" onclick="removeTagChip(this)" style="background:none;border:none;color:var(--accent-purple);cursor:pointer;padding:0;line-height:1;font-size:1rem;">&times;</button>`;
    document.getElementById('tagChips').appendChild(chip);
    syncTagsInput();
}

function removeTagChip(btn) {
    btn.parentElement.remove();
    syncTagsInput();
}

function clearTagChips() {
    document.getElementById('tagChips').innerHTML = '';
    document.getElementById('blogTags').value = '';
    const input = document.getElementById('tagTextInput');
    if (input) input.value = '';
}

// Wire up the tag text input once DOM is ready
$(document).ready(function() {
    $(document).on('keydown', '#tagTextInput', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const val = this.value.replace(/,/g, '').trim();
            if (val) addTagChip(val);
            this.value = '';
        } else if (e.key === 'Backspace' && this.value === '') {
            // Remove last chip on backspace when input is empty
            const chips = document.querySelectorAll('#tagChips .tag-chip');
            if (chips.length > 0) chips[chips.length - 1].remove();
            syncTagsInput();
        }
    });

    // Also handle paste
    $(document).on('paste', '#tagTextInput', function(e) {
        e.preventDefault();
        const pasted = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        pasted.split(/[,\n]+/).forEach(t => { if (t.trim()) addTagChip(t.trim()); });
    });
});
