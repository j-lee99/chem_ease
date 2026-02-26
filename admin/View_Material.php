<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'super_admin');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') === 'user') {
    header("Location: ../index.php");
    exit();
}

$materialId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($materialId <= 0) {
    http_response_code(400);
    echo "Invalid material id.";
    exit();
}

// Fetch material
$stmt = $conn->prepare("SELECT id, title, description, category, created_at FROM study_materials WHERE id = ?");
$stmt->bind_param("i", $materialId);
$stmt->execute();
$material = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$material) {
    http_response_code(404);
    echo "Material not found.";
    exit();
}

// Fetch files
$stmt = $conn->prepare("SELECT id, path FROM study_material_files WHERE material_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $materialId);
$stmt->execute();
$res = $stmt->get_result();

$pdfs = [];
$videos = [];

while ($row = $res->fetch_assoc()) {
    $path = trim($row['path'] ?? '');
    if ($path === '') continue;

    $lower = strtolower($path);
    $isUrl = preg_match('/^https?:\/\//i', $path) === 1;

    if ($isUrl) {
        $videos[] = $row;
    } else if (str_ends_with($lower, '.pdf')) {
        $pdfs[] = $row;
    } else {
        // treat unknown local as file
        $pdfs[] = $row;
    }
}
$stmt->close();

function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function youtube_embed_url($url)
{
    $url = trim($url);
    // Handle youtu.be/<id>
    if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    // Handle youtube.com/watch?v=<id>
    if (preg_match('~v=([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    // Handle youtube.com/embed/<id>
    if (preg_match('~youtube\.com/embed/([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    return $url; // fallback
}

// Choose default preview
$defaultType = null;
$defaultSrc = null;
if (count($pdfs) > 0) {
    $defaultType = 'pdf';
    $defaultSrc = '../' . ltrim($pdfs[0]['path'], '/');
} else if (count($videos) > 0) {
    $defaultType = 'video';
    $defaultSrc = youtube_embed_url($videos[0]['path']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase Admin - View Material</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="learning_material.css">

    <style>
        :root {
            --primary: #17a2b8;
        }

        body {
            background: #eef1f4;
        }

        .viewer-shell {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .left-pane {
            border-right: 1px solid #e5e7eb;
            background: #fbfdff;
        }

        .file-link {
            display: flex;
            gap: .75rem;
            align-items: center;
            padding: .75rem .9rem;
            border-radius: 12px;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .file-link:hover {
            background: #f1f8ff;
            border-color: #dbeafe;
        }

        .file-link.active {
            background: #e3f2fd;
            border-color: #b6d4fe;
        }

        .file-meta {
            font-size: .85rem;
            color: #6c757d;
        }

        .modal-header {
            background: var(--primary);
            color: white;
        }

        .modal-title {
            font-weight: 700;
        }

        .editable-file-name {
            flex: 1;
            margin: 0 0.5rem;
            padding: 0.4rem 0.8rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .delete-file-btn {
            color: var(--danger);
            background: none;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.3rem 0.6rem;
        }

        .delete-file-btn:hover {
            color: #c82333;
        }


        .viewer-area {
            background: #fff;
            min-height: 70vh;
        }

        .viewer-frame {
            width: 100%;
            height: 72vh;
            border: 0;
        }

        .top-actions a {
            text-decoration: none;
        }

        .badge-cat {
            background: rgba(23, 162, 184, .12);
            color: #0b7285;
            border: 1px solid rgba(23, 162, 184, .25);
        }
    </style>
</head>

<body>

    <!-- Sidebar (reuse your existing layout styles) -->
    <div class="sidebar">
        <div class="brand">
            <img src="../images/logo.png" alt="ChemEase Logo">
            <span>ChemEase</span>
            <button class="collapse-btn ms-auto" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a></div>
            <div class="nav-item"><a href="Users.php" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a></div>
            <div class="nav-item"><a href="Learning_Material.php" class="nav-link active"><i class="fas fa-book"></i><span>Learning Materials</span></a></div>
            <div class="nav-item"><a href="Practice_Exams.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Practice Exams</span></a></div>
            <?php if ($isSuperAdmin): ?>
                <div class="nav-item"><a href="Discussion_Forums.php" class="nav-link"><i class="fas fa-comments"></i><span>Discussion Forums</span></a></div>
                <div class="nav-item"><a href="Generate_Reports.php" class="nav-link"><i class="fas fa-file-alt"></i><span>Generate Reports</span></a></div>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4>ADMIN PANEL</h4>
        <div class="navbar-actions">
            <a href="https://chemease.site/" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
        </div>
    </div>

    <div class="main-content">
        <div class="materials-container">

            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h1 class="materials-title mb-1"><?php echo e($material['title']); ?></h1>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge badge-cat"><?php echo e($material['category']); ?></span>
                        <span class="text-muted small"><i class="fas fa-clock me-1"></i><?php echo e($material['created_at']); ?></span>
                    </div>
                    <?php if (!empty($material['description'])): ?>
                        <p class="text-muted mt-2 mb-0"><?php echo e($material['description']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="top-actions d-flex gap-2">
                    <a href="Learning_Material.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <?php if ($isAdmin || $isSuperAdmin): ?>
                        <button type="button" class="btn btn-outline-primary" id="editMaterialBtn">
                            <i class="fas fa-pen me-2"></i>Edit
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="deleteMaterialBtn">
                            <i class="fas fa-trash me-2"></i>Delete
                        </button>
                    <?php endif; ?>
                    <!-- <button class="btn btn-outline-primary" onclick="openFirst()">
                        <i class="fas fa-eye me-2"></i>Open First Item
                    </button> -->
                </div>
            </div>

            <div class="viewer-shell">
                <div class="row g-0">
                    <div class="col-lg-4 col-xl-3 left-pane p-3">
                        <h6 class="fw-bold mb-3"><i class="fas fa-paperclip me-2"></i>Files & Videos</h6>

                        <?php if (count($pdfs) === 0 && count($videos) === 0): ?>
                            <div class="text-muted">No files or videos uploaded yet.</div>
                        <?php endif; ?>

                        <?php if (count($pdfs) > 0): ?>
                            <div class="mb-2 text-muted small fw-semibold">PDF Files</div>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php foreach ($pdfs as $i => $f):
                                    $fileName = basename($f['path']);
                                    $src = '../' . ltrim($f['path'], '/');
                                ?>
                                    <div class="file-link"
                                        data-type="pdf"
                                        data-src="<?php echo e($src); ?>"
                                        onclick="selectItem(this)">
                                        <i class="fas fa-file-pdf text-danger fs-4"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo e($fileName); ?></div>
                                            <div class="file-meta">PDF</div>
                                        </div>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo e($src); ?>" target="_blank" onclick="event.stopPropagation();">
                                            Open
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (count($videos) > 0): ?>
                            <div class="mb-2 text-muted small fw-semibold">YouTube / Links</div>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($videos as $v):
                                    $embed = youtube_embed_url($v['path']);
                                ?>
                                    <div class="file-link"
                                        data-type="video"
                                        data-src="<?php echo e($embed); ?>"
                                        onclick="selectItem(this)">
                                        <i class="fas fa-play-circle text-danger fs-4"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo e($v['path']); ?></div>
                                            <div class="file-meta">Video/Link</div>
                                        </div>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo e($v['path']); ?>" target="_blank" onclick="event.stopPropagation();">
                                            Open
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-8 col-xl-9 p-3 viewer-area">
                        <div id="viewerEmpty" class="text-center text-muted py-5" style="<?php echo $defaultSrc ? 'display:none;' : ''; ?>">
                            <i class="fas fa-eye-slash fs-1 mb-3"></i>
                            <div>No preview selected.</div>
                        </div>

                        <iframe id="pdfFrame" class="viewer-frame" style="<?php echo $defaultType === 'pdf' ? '' : 'display:none;'; ?>"
                            src="<?php echo $defaultType === 'pdf' ? e($defaultSrc) . '#toolbar=1&navpanes=0' : ''; ?>"></iframe>

                        <div id="videoWrap" style="<?php echo $defaultType === 'video' ? '' : 'display:none;'; ?>">
                            <div class="ratio ratio-16x9">
                                <iframe id="videoFrame" src="<?php echo $defaultType === 'video' ? e($defaultSrc) : ''; ?>" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>



    <!-- Edit Modal (unchanged) -->
    <div class="modal fade" id="editModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm" enctype="multipart/form-data">
                    <input type="hidden" name="material_id" id="edit_material_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Title *</label>
                                    <input type="text" name="title" id="edit_title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="category" id="edit_category" class="form-select" required>
                                        <option value="Analytical Chemistry">Analytical Chemistry</option>
                                        <option value="Organic Chemistry">Organic Chemistry</option>
                                        <option value="Physical Chemistry">Physical Chemistry</option>
                                        <option value="Inorganic Chemistry">Inorganic Chemistry</option>
                                        <option value="BioChemistry">BioChemistry</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6>Upload New PDF Files</h6>
                                <div class="mb-3">
                                    <input type="file" name="new_pdfs[]" accept=".pdf" class="form-control" multiple>
                                </div>
                                <h6>Add New YouTube URLs</h6>
                                <div class="mb-3">
                                    <textarea name="new_youtube_urls" class="form-control" rows="4" placeholder="One URL per line..."></textarea>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h5>Existing Files</h5>
                        <div id="edit_file_list" class="file-list mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const CAN_MANAGE_MATERIALS = <?= ($isAdmin || $isSuperAdmin) ? 'true' : 'false' ?>;
        const MATERIAL_ID = <?= (int)$materialId ?>;

        async function loadEditModal(materialId) {
            try {
                const resp = await fetch(`../partial/get_material_details.php?id=${materialId}`);
                const data = await resp.json();
                if (data.status !== 'success') {
                    alert('Failed to load material details');
                    return;
                }
                document.getElementById('edit_material_id').value = materialId;
                document.getElementById('edit_title').value = data.title || '';
                document.getElementById('edit_description').value = data.description || '';
                document.getElementById('edit_category').value = data.category || '';
                const fileList = document.getElementById('edit_file_list');
                let html = '';
                if (data.pdfs?.length) {
                    html += '<h6 class="mt-3">Existing PDF Files</h6>';
                    data.pdfs.forEach(f => {
                        const currentName = (f.path || '').split('/').pop();
                        html += `
                            <div class="file-item pdf d-flex align-items-center mb-2" data-file-id="${f.id}">
                                <i class="fas fa-file-pdf me-3"></i>
                                <input type="text" class="editable-file-name" value="${currentName}" name="pdf_names[${f.id}]">
                                <button class="delete-file-btn" type="button" data-file-id="${f.id}" data-type="pdf">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>`;
                    });
                }
                if (data.videos?.length) {
                    html += '<h6 class="mt-3">Existing YouTube Links</h6>';
                    data.videos.forEach(v => {
                        html += `
                            <div class="file-item youtube d-flex align-items-center mb-2" data-file-id="${v.id}">
                                <i class="fas fa-play-circle me-3"></i>
                                <input type="text" class="editable-file-name" value="${v.path || ''}" name="youtube_urls[${v.id}]">
                                <button class="delete-file-btn" type="button" data-file-id="${v.id}" data-type="youtube">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>`;
                    });
                }
                if (!data.pdfs?.length && !data.videos?.length) {
                    html += '<p class="text-muted text-center mt-3">No files attached yet.</p>';
                }
                fileList.innerHTML = html;

                // clear old delete_* hidden inputs from previous opens
                document.querySelectorAll('#editForm input[type="hidden"][name^="delete_"]').forEach(el => el.remove());

                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();

                document.querySelectorAll('.delete-file-btn').forEach(btn => {
                    btn.onclick = () => {
                        if (!confirm('Remove this file/link?')) return;
                        const fileId = btn.dataset.fileId;
                        const type = btn.dataset.type;
                        btn.closest('.file-item')?.remove();

                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = `delete_${type}[]`;
                        hidden.value = fileId;
                        document.getElementById('editForm').appendChild(hidden);
                    };
                });
            } catch (err) {
                alert('Error loading edit data');
                console.error(err);
            }
        }

        async function deleteMaterial(materialId) {
            if (!confirm('Delete this title and all attached files? This cannot be undone.')) return;
            try {
                const resp = await fetch('../partial/delete_material_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(materialId)
                });
                const data = await resp.json();
                if (data.status === 'success') {
                    window.location.href = 'Learning_Material.php';
                } else {
                    alert('Error: ' + (data.error || 'Unknown'));
                }
            } catch (err) {
                alert('Network error');
            }
        }


        function toggleSidebar() {
            document.querySelectorAll('.sidebar, .top-navbar, .main-content').forEach(el => el.classList.toggle('collapsed'));
            const i = document.querySelector('.collapse-btn i');
            i.classList.toggle('fa-chevron-left');
            i.classList.toggle('fa-chevron-right');
        }

        function clearActive() {
            document.querySelectorAll('.file-link').forEach(el => el.classList.remove('active'));
        }

        function selectItem(el) {
            clearActive();
            el.classList.add('active');

            const type = el.dataset.type;
            const src = el.dataset.src;

            const viewerEmpty = document.getElementById('viewerEmpty');
            const pdfFrame = document.getElementById('pdfFrame');
            const videoWrap = document.getElementById('videoWrap');
            const videoFrame = document.getElementById('videoFrame');

            viewerEmpty.style.display = 'none';

            if (type === 'pdf') {
                videoWrap.style.display = 'none';
                pdfFrame.style.display = '';
                pdfFrame.src = src + '#toolbar=1&navpanes=0';
            } else {
                pdfFrame.style.display = 'none';
                videoWrap.style.display = '';
                videoFrame.src = src;
            }
        }

        function openFirst() {
            const first = document.querySelector('.file-link');
            if (first) selectItem(first);
        }

        // auto-select first item if available
        window.addEventListener('DOMContentLoaded', () => {
            const first = document.querySelector('.file-link');
            if (first) selectItem(first);

            if (CAN_MANAGE_MATERIALS) {
                document.getElementById('editMaterialBtn')?.addEventListener('click', () => loadEditModal(MATERIAL_ID));
                document.getElementById('deleteMaterialBtn')?.addEventListener('click', () => deleteMaterial(MATERIAL_ID));
            }
        });

        document.getElementById('editForm')?.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const resp = await fetch('../partial/update_material_api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await resp.json();
                alert(data.message || data.error || 'Unknown response');
                if (data.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                    // Refresh to update title/description list
                    window.location.reload();
                }
            } catch (err) {
                alert('Update failed');
            }
        });
    </script>
</body>

</html>