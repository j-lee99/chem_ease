<?php
require_once '../partial/db_conn.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../signin.php');
    exit;
}
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');
// === Get profile image safely ===
$profileStmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$profileStmt->bind_param('i', $userId);
$profileStmt->execute();
$profileResult = $profileStmt->get_result();
$userRow = $profileResult->fetch_assoc();
$profileImage = $userRow['profile_image'] ?? null;
$profileStmt->close();
// === Generate initials ===
$initials = '';
if ($fullName) {
    $parts = explode(' ', trim($fullName));
    foreach ($parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
}
if (empty($initials)) $initials = 'U';
?>
<div class="forums-container">
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="page-title mb-2">Discussion Forums</h2>
                <p class="page-subtitle text-muted mb-0">Connect with peers and experts.</p>
            </div>
            <button class="btn btn-primary btn-lg new-discussion-btn" data-bs-toggle="modal" data-bs-target="#newDiscussionModal">
                New Discussion
            </button>
        </div>
    </div>
    <div class="search-section mb-4">
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="form-control search-input" placeholder="Search Discussions..." id="searchInput">
        </div>
    </div>
    <div class="topic-filters mb-4">
        <div class="filter-buttons">
            <button class="btn filter-btn active" data-category="all">All Topics</button>
            <button class="btn filter-btn" data-category="BioChemistry">BioChemistry</button>
            <button class="btn filter-btn" data-category="Organic Chemistry">Organic Chemistry</button>
            <button class="btn filter-btn" data-category="Physical Chemistry">Physical Chemistry</button>
            <button class="btn filter-btn" data-category="Analytical Chemistry">Analytical Chemistry</button>
            <button class="btn filter-btn" data-category="Inorganic Chemistry">Inorganic Chemistry</button>
            <button class="btn filter-btn" data-category="Exam Preparation">Exam Preparation</button>
            <?php if ($isAdmin): ?>
                <button class="btn filter-btn" data-category="flagged">Flagged Only</button>
                <button class="btn filter-btn" data-category="closed">Closed Only</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="discussions-list" id="discussionsList">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3 text-muted">Loading discussions...</p>
        </div>
    </div>
    <div class="text-center mt-4">
        <button class="btn btn-outline-primary btn-lg load-more-btn" id="loadMoreBtn" style="display:none;">Load More</button>
    </div>
</div>

<!-- Thread View Modal -->
<div class="modal fade" id="threadModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="threadModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="threadContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Discussion Modal -->
<div class="modal fade" id="newDiscussionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="newDiscussionForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Start New Discussion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category" required>
                            <option value="">Select category</option>
                            <option value="BioChemistry">BioChemistry</option>
                            <option value="Organic Chemistry">Organic Chemistry</option>
                            <option value="Physical Chemistry">Physical Chemistry</option>
                            <option value="Analytical Chemistry">Analytical Chemistry</option>
                            <option value="Inorganic Chemistry">Inorganic Chemistry</option>
                            <option value="Exam Preparation">Exam Preparation</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="content" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Discussion</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Thread Modal -->
<div class="modal fade" id="editThreadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="editThreadForm">
            <input type="hidden" id="editThreadId" name="thread_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Discussion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" id="editTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="editCategory" name="category" required>
                            <option value="">Select category</option>
                            <option value="BioChemistry">BioChemistry</option>
                            <option value="Organic Chemistry">Organic Chemistry</option>
                            <option value="Physical Chemistry">Physical Chemistry</option>
                            <option value="Analytical Chemistry">Analytical Chemistry</option>
                            <option value="Inorganic Chemistry">Inorganic Chemistry</option>
                            <option value="Exam Preparation">Exam Preparation</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" id="editContent" name="content" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let offset = 0;
    let currentCategory = 'all';
    let searchTerm = '';
    let currentUserId = <?php echo $userId; ?>;
    let isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    const categoryColors = {
        'BioChemistry': '#dc3545',
        'Organic Chemistry': '#28a745',
        'Physical Chemistry': '#17a2b8',
        'Analytical Chemistry': '#ffc107',
        'Inorganic Chemistry': '#6f42c1',
        'Exam Preparation': '#fd7e14'
    };

    function generateAvatar(fullName, profileImage, color) {
        const initials = fullName.split(' ').map(n => n[0]?.toUpperCase() || '').join('').substring(0, 2) || 'U';
        if (profileImage && profileImage.trim() !== '') {
            return `<img src="../${profileImage}?t=${Date.now()}" class="author-avatar-img" alt="${fullName}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">`;
        }
        return `<div class="author-avatar" style="background-color:${color}">${initials}</div>`;
    }

    function showModal(id) {
        new bootstrap.Modal(document.getElementById(id)).show();
    }

    function loadDiscussions(replace = true) {
        const url = `../partial/forum_handler.php?action=get_threads&filter=${currentCategory}&search=${encodeURIComponent(searchTerm)}&offset=${offset}`;
       
        fetch(url)
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('discussionsList');
                if (replace) {
                    list.innerHTML = '';
                    offset = 0;
                }
                if (data.length === 0 && replace) {
                    list.innerHTML = '<div class="text-center py-5"><i class="fas fa-comments fa-3x text-muted mb-3"></i><p class="text-muted">No discussions found.</p></div>';
                    document.getElementById('loadMoreBtn').style.display = 'none';
                    return;
                }
                // Sort: flagged posts first, then by created_at desc
                data.sort((a, b) => {
                    if (a.is_flagged !== b.is_flagged) {
                        return b.is_flagged - a.is_flagged; // flagged first
                    }
                    return new Date(b.created_at) - new Date(a.created_at);
                });
                data.forEach(t => {
                    const color = categoryColors[t.category] || '#6c757d';
                    const isOwn = t.user_id == currentUserId;
                    const flagged = t.is_flagged == 1;
                    const closed = t.is_closed == 1;
                    // Skip rendering flagged threads for non-owners (except admin)
                    if (flagged && !isOwn && !isAdmin) return;
                    let extraBadge = '';
                    if (flagged) extraBadge += '<span class="badge bg-danger ms-2">Flagged</span>';
                    if (closed) extraBadge += '<span class="badge bg-secondary ms-2">Closed</span>';
                    let cardHTML = `
                        <div class="discussion-card ${flagged ? 'flagged-thread' : ''}" data-id="${t.id}">
                            <div class="discussion-content">
                                <div class="discussion-header d-flex justify-content-between align-items-start">
                                    <div class="category-badges">
                                        <span class="badge topic-badge" style="background-color:${color}">${t.category}</span>
                                        ${extraBadge}
                                    </div>`;

                    // === NEW: Vertical ellipsis dropdown for actions ===
                    cardHTML += `
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-link text-muted p-0" type="button" id="threadMenu${t.id}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="threadMenu${t.id}">`;

                    // Edit (only if own and not flagged)
                    if (isOwn && !flagged) {
                        cardHTML += `<li><a class="dropdown-item" href="#" onclick="openEditThread(${t.id}, '${escapeHtml(t.title)}', '${t.category}', \`${escapeHtml(t.content)}\`)"><i class="fas fa-edit me-2"></i>Edit</a></li>`;
                    }

                    // Delete (only if own)
                    if (isOwn) {
                        cardHTML += `<li><a class="dropdown-item text-danger" href="#" onclick="deleteThread(${t.id})"><i class="fas fa-trash me-2"></i>Delete</a></li>`;
                    }

                    // Flag / Unflag (users flag others, admin can unflag)
                    if (!isOwn) {
                        if (!isAdmin && !flagged) {
                            cardHTML += `<li><a class="dropdown-item" href="#" onclick="flagThread(${t.id}, 1)"><i class="fas fa-flag me-2"></i>Flag Post</a></li>`;
                        }
                        if (isAdmin) {
                            cardHTML += `<li><a class="dropdown-item ${flagged ? 'text-success' : ''}" href="#" onclick="flagThread(${t.id}, ${flagged ? 0 : 1})"><i class="fas fa-flag me-2"></i>${flagged ? 'Unflag' : 'Flag'}</a></li>`;
                        }
                    }

                    // Close/Reopen (admin only)
                    if (isAdmin) {
                        cardHTML += `<li><a class="dropdown-item" href="#" onclick="toggleClose(${t.id}, ${closed ? 0 : 1})"><i class="fas fa-lock me-2"></i>${closed ? 'Reopen' : 'Close'}</a></li>`;
                    }

                    cardHTML += `
                                        </ul>
                                    </div>
                                </div>
                                <h5 class="discussion-title mt-3">
                                    <a href="#" onclick="viewThread(${t.id}); return false;" class="title-link">${escapeHtml(t.title)}</a>
                                </h5>`;

                    if (flagged && isOwn) {
                        cardHTML += `
                                <div class="alert alert-danger mt-2 mb-3" role="alert">
                                    <strong>YOUR POST HAS BEEN FLAGGED</strong><br>
                                    This post violates community guidelines. You can delete it permanently.
                                </div>`;
                    }

                    cardHTML += `
                                <div class="discussion-meta">
                                    <div class="author-info">
                                        <div class="avatar-wrapper">
                                            ${generateAvatar(t.full_name, t.profile_image, color)}
                                        </div>
                                        <div class="author-details">
                                            <span class="author-name">${escapeHtml(t.full_name)}</span>
                                            <span class="post-time">• ${new Date(t.created_at).toLocaleDateString()}</span>
                                        </div>
                                    </div>
                                    <div class="engagement-stats">
                                        <div class="stat-item comment-link" onclick="viewThread(${t.id})" style="cursor:pointer;">
                                            <i class="fas fa-comment"></i> ${t.replies || 0}
                                        </div>
                                        <div class="stat-item"><i class="fas fa-eye"></i> ${t.views || 0}</div>
                                        <div class="stat-item like-btn ${t.liked ? 'text-danger' : ''}" data-id="${t.id}">
                                            <i class="fa${t.liked ? 's' : 'r'} fa-heart"></i> <span class="like-count">${t.likes_count || 0}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;

                    list.innerHTML += cardHTML;
                });
                offset += data.length;
                document.getElementById('loadMoreBtn').style.display = data.length < 10 ? 'none' : 'block';
            });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function openEditThread(id, title, category, content) {
        document.getElementById('editThreadId').value = id;
        document.getElementById('editTitle').value = title;
        document.getElementById('editCategory').value = category;
        document.getElementById('editContent').value = content;
        showModal('editThreadModal');
    }

    function viewThread(threadId) {
        fetch(`../partial/forum_handler.php?action=view_thread&id=${threadId}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                // Extra check: if flagged and not owner/admin → block view
                if (data.thread.is_flagged == 1 && data.thread.user_id != currentUserId && !isAdmin) {
                    alert("This post has been removed for violating our guidelines.");
                    return;
                }
                document.getElementById('threadModalTitle').textContent = data.thread.title;
                const color = categoryColors[data.thread.category] || '#6c757d';
                const isFlagged = data.thread.is_flagged == 1;
                const isClosed = data.thread.is_closed == 1;
               
                let html = `
                    <div class="thread-detail">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h4>${escapeHtml(data.thread.title)}</h4>
                                <small class="text-muted">Posted by <strong>${escapeHtml(data.thread.full_name)}</strong> on ${new Date(data.thread.created_at).toLocaleString()}</small>
                            </div>
                        </div>`;
               
                if (isFlagged) {
                    html += `
                        <div class="alert alert-danger mb-4">
                            <strong>THIS POST HAS BEEN FLAGGED</strong><br>
                            It violates our community guidelines.
                        </div>`;
                }
               
                html += `
                        <div class="thread-content p-4 bg-light rounded mb-4">
                            ${escapeHtml(data.thread.content).replace(/\n/g, '<br>')}
                        </div>
                        <hr>
                        <h5>Replies (${data.replies.length})</h5>`;
               
                data.replies.forEach(r => {
                    const isOwnReply = r.user_id == currentUserId;
                    html += `
                        <div class="reply-item border-start border-3 border-primary ps-3 py-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-wrapper">
                                        ${generateAvatar(r.full_name, r.profile_image, color)}
                                    </div>
                                    <div>
                                        <strong>${escapeHtml(r.full_name)}</strong><br>
                                        <small class="text-muted">${new Date(r.created_at).toLocaleString()}</small>
                                    </div>
                                </div>
                                ${isOwnReply || isAdmin ? `
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReply(${r.id}, ${threadId})">Delete</button>
                                ` : ''}
                            </div>
                            <div class="mt-2">${escapeHtml(r.content).replace(/\n/g, '<br>')}</div>
                        </div>`;
                });
               
                // Allow replies if NOT flagged AND (NOT closed OR admin)
                if (!isFlagged && (!isClosed || isAdmin)) {
                    html += `
                        <div class="mt-4">
                            <textarea class="form-control" id="replyContent" rows="3" placeholder="Write a reply..."></textarea>
                            <button class="btn btn-primary mt-2" onclick="postReply(${data.thread.id})">Post Reply</button>
                        </div>`;
                } else if (isClosed) {
                    html += `<div class="alert alert-secondary mt-4">This thread is closed. No new replies allowed.</div>`;
                }
               
                html += `</div>`;
                document.getElementById('threadContent').innerHTML = html;
                showModal('threadModal');
            });
    }

    function flagThread(threadId, flagValue) {
        const message = flagValue
            ? 'Flag this post for violating community guidelines? The post owner will be notified.'
            : 'Remove flag from this post?';
       
        if (!confirm(message)) return;
       
        fetch('../partial/forum_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=flag_thread&thread_id=${threadId}&flag=${flagValue}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(flagValue ? 'Post flagged successfully.' : 'Flag removed successfully.');
                location.reload();
            } else {
                alert(data.error || 'Error flagging post');
            }
        });
    }

    function toggleClose(threadId, closeValue) {
        if (!confirm(closeValue ? 'Close this thread?' : 'Reopen this thread?')) return;
       
        fetch('../partial/forum_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_close&thread_id=${threadId}&close=${closeValue}`
        })
        .then(r => r.json())
        .then(() => loadDiscussions(true));
    }

    function deleteThread(id) {
        if (!confirm('Delete this discussion permanently? This cannot be undone.')) return;
       
        fetch('../partial/forum_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_thread&id=${id}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error deleting thread');
            }
        });
    }

    function postReply(threadId) {
        const content = document.getElementById('replyContent').value.trim();
        if (!content) {
            alert('Please enter a reply.');
            return;
        }
       
        fetch('../partial/forum_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=reply&thread_id=${threadId}&content=${encodeURIComponent(content)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('replyContent').value = '';
                viewThread(threadId);
            } else {
                alert(data.error || 'Error posting reply');
            }
        });
    }

    function deleteReply(replyId, threadId) {
        if (!confirm('Delete this reply permanently?')) return;
       
        fetch('../partial/forum_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_reply&id=${replyId}`
        })
        .then(() => viewThread(threadId));
    }

    // Like handler & comment click
    document.getElementById('discussionsList').addEventListener('click', e => {
        const likeBtn = e.target.closest('.like-btn');
        if (likeBtn) {
            e.stopPropagation();
            const id = likeBtn.dataset.id;
            fetch('../partial/forum_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=like_thread&thread_id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.liked !== undefined && data.likes !== undefined) {
                    const icon = likeBtn.querySelector('i');
                    const count = likeBtn.querySelector('.like-count');
                    icon.className = data.liked ? 'fas fa-heart' : 'far fa-heart';
                    likeBtn.classList.toggle('text-danger', data.liked);
                    count.textContent = data.likes;
                }
            });
        }
       
        const commentBtn = e.target.closest('.comment-link');
        if (commentBtn) {
            const threadId = commentBtn.closest('.discussion-card').dataset.id;
            viewThread(threadId);
        }
    });

    // Filters & Search
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCategory = btn.dataset.category;
            offset = 0;
            loadDiscussions(true);
        });
    });

    document.getElementById('searchInput').addEventListener('input', e => {
        searchTerm = e.target.value.trim();
        offset = 0;
        loadDiscussions(true);
    });

    document.getElementById('loadMoreBtn').addEventListener('click', () => loadDiscussions(false));

    // New discussion form
    document.getElementById('newDiscussionForm').addEventListener('submit', e => {
        e.preventDefault();
        const form = new FormData(e.target);
        form.append('action', 'create_thread');
       
        fetch('../partial/forum_handler.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Error creating thread');
                }
            });
    });

    // Edit thread form
    document.getElementById('editThreadForm').addEventListener('submit', e => {
        e.preventDefault();
        const form = new FormData(e.target);
        form.append('action', 'update_thread');
       
        fetch('../partial/forum_handler.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editThreadModal')).hide();
                    location.reload();
                } else {
                    alert(data.error || 'Error updating thread');
                }
            });
    });

    // Initial load
    loadDiscussions(true);
</script>

<script>
document.getElementById('threadModal').addEventListener('hidden.bs.modal', function () {
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
});
</script>

<style>
    .flagged-thread {
        border-left: 5px solid #dc3545 !important;
        background: #fff5f5 !important;
    }
    .flagged-thread .discussion-title {
        color: #6c757d;
    }
    .dropdown-menu {
        min-width: 140px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .dropdown-item {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    .dropdown-item i {
        width: 20px;
        text-align: center;
    }
    .avatar-wrapper {
        position: relative;
        width: 40px;
        height: 40px;
    }
    .author-avatar, .author-avatar-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.9rem;
    }
    .author-avatar-img {
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .author-avatar {
        background-color: #6c757d;
    }
    :root {
        --primary-blue: #17a2b8;
        --gradient-end: #20c997;
        --dark-text: #2c3e50;
        --success-color: #28a745;
        --danger-color: #dc3545;
    }
    .forums-container {
padding: 1rem; max-width: 1400px; margin: 0 auto; 
    }
    .page-header {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(255, 255, 255, 0.8));
        border-radius: 15px;
        padding: 2rem;
        border: 1px solid rgba(23, 162, 184, 0.1);
        margin-top: -3.5%;
    }
    .page-title {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 0.8rem 0;
            line-height: 1.2;
    }
    .page-subtitle {
        color: #6c757d;
        font-size: 1.1rem;
    }
    .new-discussion-btn {
        background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
        border: none;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
    }
    .new-discussion-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(23, 162, 184, 0.4);
    }
    .search-box {
        position: relative;
        max-width: 500px;
    }
    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }
    .search-input {
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid rgba(23, 162, 184, 0.1);
        border-radius: 12px;
        padding: 0.75rem 1rem 0.75rem 3rem;
        box-shadow: 0 2px 10px rgba(23, 162, 184, 0.1);
    }
    .search-input:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 4px 20px rgba(23, 162, 184, 0.2);
        background: white;
    }
    .topic-filters {
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    .filter-buttons {
        display: flex;
        gap: 0.75rem;
        min-width: max-content;
    }
    .filter-btn {
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid rgba(23, 162, 184, 0.1);
        color: var(--dark-text);
        border-radius: 25px;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    .filter-btn:hover {
        background: var(--primary-blue);
        color: white;
        border-color: var(--primary-blue);
        transform: translateY(-1px);
    }
    .filter-btn.active {
        background: var(--primary-blue);
        color: white;
        border-color: var(--primary-blue);
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
    }
    .discussions-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .discussion-card {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(23, 162, 184, 0.1);
        border-radius: 15px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }
    .discussion-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(23, 162, 184, 0.15);
        border-color: var(--primary-blue);
    }
    .discussion-header .d-flex {
        justify-content: space-between;
        align-items: start;
    }
    .topic-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        color: white;
    }
    .discussion-title {
        margin: 0.75rem 0;
        font-weight: 600;
        color: var(--dark-text);
    }
    .title-link {
        color: inherit;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .title-link:hover {
        color: var(--primary-blue);
    }
    .discussion-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .author-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .author-details {
        display: flex;
        flex-direction: column;
    }
    .author-name {
        font-weight: 600;
        color: var(--dark-text);
        font-size: 0.9rem;
    }
    .post-time {
        color: #6c757d;
        font-size: 0.8rem;
    }
    .engagement-stats {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: #6c757d;
        font-size: 0.9rem;
        cursor: pointer;
    }
    .stat-item i {
        font-size: 0.8rem;
    }
    .like-btn {
        cursor: pointer;
        transition: all 0.3s;
    }
    .like-btn.text-danger i {
        color: #dc3545;
    }
    .comment-link {
        color: var(--primary-blue);
        font-weight: 600;
    }
    .comment-link:hover {
        color: var(--gradient-end);
    }
    .load-more-btn {
        border-radius: 12px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        border: 2px solid var(--primary-blue);
        color: var(--primary-blue);
        transition: all 0.3s ease;
    }
    .load-more-btn:hover {
        background: var(--primary-blue);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
    }
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    .modal-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
        color: white;
        border-radius: 15px 15px 0 0;
    }
    .modal-header .btn-close {
        filter: invert(1);
    }
    @media(max-width:768px) {
        .page-header {
             text-align: center;
             marigin-top: 5% !important;
         }

        .page-header .d-flex {
            flex-direction: column;
            gap: 1rem;
        }
        .discussion-meta {
            flex-direction: column;
            align-items: start;
            gap: 0.75rem;
        }
        .engagement-stats {
            width: 100%;
            justify-content: space-around;
        }
        .dropdown-menu {
            min-width: 160px;
        }
        .dropdown-item {
            padding: 0.6rem 1.25rem;
            font-size: 1rem;
        }
    }
</style>