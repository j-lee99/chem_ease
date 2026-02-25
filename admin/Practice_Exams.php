<?php
session_start();
require_once '../partial/db_conn.php';


$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'super_admin');
// If NOT logged in OR not admin → back to login
if (!isset($_SESSION['user_id']) || !in_array(($_SESSION['role'] ?? ''), ['admin', 'super_admin'], true)) {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        if ($isSuperAdmin) {
            echo "ChemEase Super Admin Panel - Users";
        } elseif ($isAdmin) {
            echo "ChemEase Admin Panel - Users";
        } else {
            echo "ChemEase - Users";
        }
        ?>
    </title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="pe.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon.ico">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
    <link rel="apple-touch-icon" href="/apple-icon.png">
    <link rel="apple-touch-icon-precomposed" href="/apple-icon-precomposed.png">

    <!-- Android Icons -->
    <link rel="icon" type="image/png" sizes="36x36" href="/android-icon-36x36.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/android-icon-48x48.png">
    <link rel="icon" type="image/png" sizes="72x72" href="/android-icon-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/android-icon-96x96.png">
    <link rel="icon" type="image/png" sizes="144x144" href="/android-icon-144x144.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/android-icon-192x192.png">

    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileColor" content="#0d6efd">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="msapplication-square70x70logo" content="/ms-icon-70x70.png">
    <meta name="msapplication-square150x150logo" content="/ms-icon-150x150.png">
    <meta name="msapplication-square310x310logo" content="/ms-icon-310x310.png">
    <style>
        .question-block {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            position: relative;
        }

        .question-block:hover {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .choice-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 8px;
            position: relative;
        }

        .choice-row input[type=text] {
            flex: 1;
        }

        .remove-choice {
            cursor: pointer;
            color: #dc3545;
            padding: 5px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }

        .remove-choice:hover {
            background-color: #f8d7da;
        }

        .step {
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .step:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .step.completed {
            color: #28a745;
            background-color: #d4edda;
        }

        .step.active {
            font-weight: bold;
            color: #0d6efd;
            background-color: #cfe2ff;
        }

        .media-upload {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 0;
        }

        .media-upload:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }

        .media-upload.dragover {
            border-color: #0d6efd;
            background-color: #e3f2fd;
        }

        .media-preview {
            max-width: 100px;
            max-height: 100px;
            margin: 5px;
            border-radius: 4px;
        }

        .choice-preview {
            padding: 8px 12px;
            margin: 2px 0;
            border-radius: 4px;
            font-size: 14px;
        }

        .choice-preview.correct {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }

        .choice-preview.incorrect {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .truefalse-template {
            display: none;
        }

        .multiple-template {
            display: block;
        }

        .question-image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .attachment-preview {
            display: inline-block;
            padding: 5px 10px;
            background: #e9ecef;
            border-radius: 4px;
            margin: 5px 0;
            font-size: 12px;
        }

        .actions-cell {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
        }

        .action-btn.view {
            background-color: #007bff;
            color: white;
        }

        .action-btn.view:hover {
            background-color: #0056b3;
        }

        .action-btn.edit {
            background-color: #28a745;
            color: white;
        }

        .action-btn.edit:hover {
            background-color: #1e7e34;
        }

        .action-btn.delete {
            background-color: #dc3545;
            color: white;
        }

        .action-btn.delete:hover {
            background-color: #c82333;
        }

        .modal-xl {
            max-width: 90%;
        }

        .review-question {
            border: 1px solid #dee2e6;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <img src="../images/logo.png" alt="ChemEase Logo">
            <span>ChemEase</span>
            <button class="collapse-btn ms-auto" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="index.php" class="nav-link" data-section="dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Users.php" class="nav-link" data-section="users">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </div>
            <?php if ($isAdmin): ?>
                <div class="nav-item">
                    <a href="Learning_Material.php" class="nav-link" data-section="learning">
                        <i class="fas fa-book"></i>
                        <span>Learning Materials</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="Practice_Exams.php" class="nav-link active" data-section="exams">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Practice Exams</span>
                    </a>
                </div>
            <? endif; ?>
            <?php if ($isSuperAdmin): ?>
                <div class="nav-item">
                    <a href="Discussion_Forums.php" class="nav-link" data-section="forums">
                        <i class="fas fa-comments"></i>
                        <span>Discussion Forums</span>
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($isSuperAdmin): ?>
                <div class="nav-item">
                    <a href="Generate_Reports.php" class="nav-link" data-section="reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Generate Reports</span>
                    </a>
                </div>
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
    <!-- Main Content -->
    <div class="main-content">
        <div class="practice-exams-container">
            <div class="page-header">
                <h1 class="page-title">Practice Exams</h1>
            </div>
            <!-- Stats -->
            <div class="stats-row" id="statsRow">
                <div class="stat-box total-exams">
                    <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                    <h2 class="stat-number">0</h2>
                    <p class="stat-label">Total Exams</p>
                    <p class="stat-subtitle">Across 5 categories</p>
                </div>
                <div class="stat-box total-attempts">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <h2 class="stat-number">0</h2>
                    <p class="stat-label">Total Attempts</p>
                    <p class="stat-subtitle"></p>
                </div>
                <div class="stat-box average-score">
                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                    <h2 class="stat-number">0%</h2>
                    <p class="stat-label">Average Score</p>
                    <p class="stat-subtitle">Based on 0 attempts</p>
                </div>
            </div>
            <!-- Controls -->
            <div class="exam-controls">
                <div class="controls-row">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search exams...">
                    </div>
                    <div class="controls-buttons">
                        <button class="btn-control btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">
                            <i class="fas fa-plus"></i> ADD EXAMS
                        </button>
                    </div>
                </div>
            </div>
            <!-- Table -->
            <div class="exams-table-container">
                <div class="table-responsive">
                    <table class="exams-table">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Topic</th>
                                <th>Category</th>
                                <th>Difficulty</th>
                                <th>Questions</th>
                                <th>Attempts</th>
                                <th>Average Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="examsTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- ADD/EDIT EXAM MODAL -->
    <div class="modal fade" id="addExamModal" tabindex="-1"
        data-bs-backdrop="static"
        data-bs-keyboard="false">


        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Create New Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="progress-step d-flex justify-content-between mb-4">
                        <div class="step" onclick="showStep(1)">Basic Information</div>
                        <div class="step" onclick="showStep(2)">Add Questions</div>
                        <div class="step" onclick="showStep(3)">Review &amp; Create</div>
                    </div>
                    <!-- STEP 1: Basic Information -->
                    <div id="step1" class="step-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Exam Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="examTitle" placeholder="Enter exam title">
                                    <div class="error-message" id="titleError"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Topic <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="examTopic" placeholder="Enter exam topic">
                                    <div class="error-message" id="topicError"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="examCategory">
                                        <option value="">Select category</option>
                                        <option value="Analytical Chemistry">Analytical Chemistry</option>
                                        <option value="Organic Chemistry">Organic Chemistry</option>
                                        <option value="Physical Chemistry">Physical Chemistry</option>
                                        <option value="Inorganic Chemistry">Inorganic Chemistry</option>
                                        <option value="BioChemistry">BioChemistry</option>
                                    </select>
                                    <div class="error-message" id="categoryError"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Difficulty <span class="text-danger">*</span></label>
                                    <select class="form-control" id="examDifficulty">
                                        <option value="">Select difficulty</option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                    </select>
                                    <div class="error-message" id="difficultyError"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="examDuration" min="1" placeholder="30">
                                    <div class="error-message" id="durationError"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Total Questions <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="totalItems" min="1" placeholder="10">
                                    <div class="error-message" id="itemsError"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Passing Score (%) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="passingScore" min="0" max="100" placeholder="70">
                                    <div class="error-message" id="passingError"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="examDescription" rows="4" placeholder="Enter exam description..."></textarea>
                        </div>
                        <button class="btn btn-primary float-end" onclick="nextStep(2)">
                            <i class="fas fa-arrow-right"></i> Next
                        </button>
                    </div>
                    <!-- STEP 2: Add Questions -->
                    <div id="step2" class="step-content" style="display:none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Add <strong id="requiredQuestions">0</strong> questions for this exam.
                            <span id="remainingQuestions" class="text-muted float-end"></span>
                        </div>
                        <div id="questionsContainer"></div>
                        <div class="text-center mt-3">
                            <button class="btn btn-success btn-lg add-question" onclick="addQuestion()">
                                <i class="fas fa-plus"></i> Add Question
                            </button>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button class="btn btn-secondary" onclick="prevStep(1)">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button class="btn btn-primary" onclick="nextStep(3)">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    <!-- STEP 3: Review & Create -->
                    <div id="step3" class="step-content" style="display:none;">
                        <h5 class="mb-3"><i class="fas fa-clipboard-check"></i> Review Exam</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Title:</strong> <span id="reviewTitle"></span></p>
                                        <p><strong>Topic:</strong> <span id="reviewTopic"></span></p>
                                        <p><strong>Category:</strong> <span class="badge bg-primary" id="reviewCategory"></span></p>
                                        <p><strong>Difficulty:</strong> <span class="badge bg-warning" id="reviewDifficulty"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Duration:</strong> <span id="reviewDuration"></span> minutes</p>
                                        <p><strong>Total Questions:</strong> <span id="reviewTotalItems"></span></p>
                                        <p><strong>Passing Score:</strong> <span id="reviewPassingScore"></span>%</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <strong>Description:</strong>
                                    <p class="mt-1" id="reviewDescription"></p>
                                </div>
                            </div>
                        </div>
                        <h6 class="mb-3">Questions Preview:</h6>
                        <div id="reviewQuestions" class="review-section"></div>
                        <div class="d-flex justify-content-between mt-4">
                            <button class="btn btn-secondary" onclick="prevStep(2)">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button class="btn btn-success" id="createExamBtn" onclick="createExam()">
                                <i class="fas fa-check"></i> Create Exam
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
        <div id="toastContainer"></div>
    </div>
    <!-- VIEW EXAM MODAL -->
    <div class="modal fade" id="viewExamModal" tabindex="-1"
        data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye"></i> Exam Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="viewExamContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle
        document.querySelector('.collapse-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.top-navbar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');
            this.querySelector('i').classList.toggle('fa-chevron-left');
            this.querySelector('i').classList.toggle('fa-chevron-right');
        });
        /* ==== GLOBAL VARIABLES ==== */
        let currentStep = 1;
        let questions = [];
        let currentExamId = 0;
        let isEditMode = false;
        let dragCounter = 0;
        /* ==== UTILITY FUNCTIONS ==== */
        function showError(id, message) {
            const errorEl = document.getElementById(id);
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = message ? 'block' : 'none';
            }
        }

        function clearErrors() {
            ['titleError', 'topicError', 'categoryError', 'difficultyError', 'durationError', 'itemsError', 'passingError'].forEach(id => {
                showError(id, '');
            });
        }

        function validateStep1() {
            clearErrors();
            let isValid = true;
            const title = document.getElementById('examTitle').value.trim();
            if (!title) {
                showError('titleError', 'Exam title is required');
                isValid = false;
            }
            const topic = document.getElementById('examTopic').value.trim();
            if (!topic) {
                showError('topicError', 'Topic is required');
                isValid = false;
            }
            const category = document.getElementById('examCategory').value;
            if (!category) {
                showError('categoryError', 'Category is required');
                isValid = false;
            }
            const difficulty = document.getElementById('examDifficulty').value;
            if (!difficulty) {
                showError('difficultyError', 'Difficulty is required');
                isValid = false;
            }
            const duration = parseInt(document.getElementById('examDuration').value);
            if (!duration || duration < 1) {
                showError('durationError', 'Duration must be at least 1 minute');
                isValid = false;
            }
            const items = parseInt(document.getElementById('totalItems').value);
            if (!items || items < 1) {
                showError('itemsError', 'At least 1 question is required');
                isValid = false;
            }
            const passing = parseInt(document.getElementById('passingScore').value);
            if (!passing || passing < 0 || passing > 100) {
                showError('passingError', 'Passing score must be between 0-100');
                isValid = false;
            }
            return isValid;
        }
        /* ==== SIDEBAR ==== */
        function toggleSidebar() {
            document.querySelectorAll('.sidebar, .top-navbar, .main-content').forEach(el => {
                el.classList.toggle('collapsed');
            });
            const i = document.querySelector('.collapse-btn i');
            i.classList.toggle('fa-chevron-left');
            i.classList.toggle('fa-chevron-right');
        }
        /* ==== LOAD EXAMS ==== */
        // function loadExams() {
        //     fetch('../partial/exam_list.php')
        //         .then(r => r.json())
        //         .then(data => {
        //             // Update stats
        //             const totalExams = data.length;
        //             const totalAttempts = data.reduce((a, e) => a + (parseInt(e.completions) || 0), 0);
        //             const avgScoreTotal = data.reduce((a, e) => a + ((parseInt(e.avg_score) || 0) * (parseInt(e.completions) || 0)), 0);
        //             const avgScore = totalAttempts > 0 ? Math.round(avgScoreTotal / totalAttempts) : 0;

        //             // Update DOM values
        //             document.querySelector('.total-exams .stat-number').textContent = totalExams;
        //             document.querySelector('.total-attempts .stat-number').textContent = totalAttempts > 999 ? (totalAttempts / 1000).toFixed(1) + 'k' : totalAttempts;
        //             document.querySelector('.average-score .stat-number').textContent = avgScore + '%';

        //             // Update subtitles
        //             document.querySelector('.total-exams .stat-subtitle').textContent = 'Across 5 categories';
        //             document.querySelector('.total-attempts .stat-subtitle').textContent = ''; // Could be enhanced later
        //             document.querySelector('.average-score .stat-subtitle').textContent = 'Based on ' + totalAttempts + ' attempts';

        //             // ALWAYS animate after updating numbers
        //             animateStats();

        //             // Populate table
        //             const tbody = document.getElementById('examsTbody');
        //             tbody.innerHTML = '';
        //             if (data.length === 0) {
        //                 tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No exams found. Create your first exam!</td></tr>';
        //                 return;
        //             }
        //             data.forEach(e => {
        //                 const tr = document.createElement('tr');
        //                 const categoryClass = e.category.toLowerCase().replace(/\s+/g, '-');
        //                 const difficultyLower = e.difficulty.toLowerCase();
        //                 const difficultyBadgeClass = difficultyLower === 'beginner' ? 'bg-success' :
        //                                               difficultyLower === 'intermediate' ? 'bg-warning' :
        //                                               'bg-danger';
        //                 tr.innerHTML = `
        //                     <td>
        //                         <div class="exam-title">
        //                             <i class="fas fa-clipboard-list me-2"></i>
        //                             ${e.title}
        //                         </div>
        //                     </td>
        //                     <td>
        //                         <div class="exam-topic">${e.topic || 'General'}</div>
        //                     </td>
        //                     <td>
        //                         <span class="category-badge ${categoryClass}">${e.category}</span>
        //                     </td>
        //                     <td>
        //                         <span class="badge ${difficultyBadgeClass}">${e.difficulty}</span>
        //                     </td>
        //                     <td>
        //                         <div class="attempts-count">
        //                             <i class="fas fa-question-circle me-1"></i>
        //                             ${e.actual_questions}
        //                         </div>
        //                     </td>
        //                     <td>
        //                         <div class="attempts-count">
        //                             <i class="fas fa-users me-1"></i>
        //                             ${e.completions}
        //                         </div>
        //                     </td>
        //                     <td>
        //                         <div class="score-progress">
        //                             <div class="progress-bar-container">
        //                                 <div class="progress-bar progress-${e.avg_score}"></div>
        //                             </div>
        //                             <span class="score-text">${e.avg_score}%</span>
        //                         </div>
        //                     </td>
        //                     <td>
        //                         <div class="actions-cell">
        //                             <button class="action-btn view" title="View Details" onclick="viewExam(${e.id})">
        //                                 <i class="fas fa-eye"></i>
        //                             </button>
        //                             <button class="action-btn edit" title="Edit Exam" onclick="editExam(${e.id})">
        //                                 <i class="fas fa-edit"></i>
        //                             </button>
        //                             <button class="action-btn delete" title="Delete Exam" onclick="deleteExam(${e.id})">
        //                                 <i class="fas fa-trash"></i>
        //                             </button>
        //                         </div>
        //                     </td>`;
        //                 tbody.appendChild(tr);
        //             });
        //             animateProgressBars();
        //             attachRowHover();
        //         })
        //         .catch(err => {
        //             console.error('Error loading exams:', err);
        //             document.getElementById('examsTbody').innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger">Error loading exams. Please try again.</td></tr>';
        //         });
        // }

        function loadExams() {
            fetch('../partial/exam_list.php')
                .then(r => r.json())
                .then(({
                    data
                }) => {
                    data = Array.isArray(data) ? data : [];

                    /* ---------- Stats ---------- */

                    const totalExams = data.length;

                    let totalAttempts = 0;
                    let weightedScoreSum = 0;

                    for (const e of data) {
                        const completions = Number(e.completions) || 0;
                        const avgScore = Number(e.avg_score) || 0;

                        totalAttempts += completions;
                        weightedScoreSum += avgScore * completions;
                    }

                    const avgScore = totalAttempts ?
                        Math.round(weightedScoreSum / totalAttempts) :
                        0;

                    /* ---------- DOM Cache ---------- */

                    const $totalExams = document.querySelector('.total-exams .stat-number');
                    const $totalAttempts = document.querySelector('.total-attempts .stat-number');
                    const $averageScore = document.querySelector('.average-score .stat-number');

                    const $subtitleExams = document.querySelector('.total-exams .stat-subtitle');
                    const $subtitleAttempts = document.querySelector('.total-attempts .stat-subtitle');
                    const $subtitleScore = document.querySelector('.average-score .stat-subtitle');

                    /* ---------- Update Stats ---------- */

                    $totalExams.textContent = totalExams;
                    $totalAttempts.textContent = totalAttempts > 999 ?
                        (totalAttempts / 1000).toFixed(1) + 'k' :
                        totalAttempts;
                    $averageScore.textContent = avgScore + '%';

                    $subtitleExams.textContent = 'Across 5 categories';
                    $subtitleAttempts.textContent = '';
                    $subtitleScore.textContent = `Based on ${totalAttempts} attempts`;

                    animateStats();

                    /* ---------- Populate Table ---------- */

                    const tbody = document.getElementById('examsTbody');
                    tbody.innerHTML = '';

                    if (!data.length) {
                        tbody.innerHTML = `
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              No exams found. Create your first exam!
            </td>
          </tr>`;
                        return;
                    }

                    const fragment = document.createDocumentFragment();

                    for (const e of data) {
                        const categoryClass = (e.category || 'general')
                            .toLowerCase()
                            .replace(/\s+/g, '-');

                        const difficulty = (e.difficulty || 'beginner').toLowerCase();
                        const difficultyBadgeClass =
                            difficulty === 'beginner' ? 'bg-success' :
                            difficulty === 'intermediate' ? 'bg-warning' :
                            'bg-danger';

                        const tr = document.createElement('tr');

                        tr.innerHTML = `
          <td>
            <div class="exam-title">
              <i class="fas fa-clipboard-list me-2"></i>
              ${e.title}
            </div>
          </td>
          <td>
            <div class="exam-topic">${e.topic || 'General'}</div>
          </td>
          <td>
            <span class="category-badge ${categoryClass}">${e.category}</span>
          </td>
          <td>
            <span class="badge ${difficultyBadgeClass}">${e.difficulty}</span>
          </td>
          <td>
            <div class="attempts-count">
              <i class="fas fa-question-circle me-1"></i>
              ${e.actual_questions}
            </div>
          </td>
          <td>
            <div class="attempts-count">
              <i class="fas fa-users me-1"></i>
              ${e.completions}
            </div>
          </td>
          <td>
            <div class="score-progress">
              <div class="progress-bar-container">
                <div class="progress-bar progress-${e.avg_score}"></div>
              </div>
              <span class="score-text">${e.avg_score}%</span>
            </div>
          </td>
          <td>
            <div class="actions-cell">
              <button class="action-btn view" title="View Details" onclick="viewExam(${e.id})">
                <i class="fas fa-eye"></i>
              </button>
              <button class="action-btn edit" title="Edit Exam" onclick="editExam(${e.id})">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn delete" title="Delete Exam" onclick="deleteExam(${e.id})">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        `;

                        fragment.appendChild(tr);
                    }

                    tbody.appendChild(fragment);

                    animateProgressBars();
                    attachRowHover();
                })
                .catch(err => {
                    console.error('Error loading exams:', err);
                    document.getElementById('examsTbody').innerHTML = `
        <tr>
          <td colspan="8" class="text-center py-4 text-danger">
            Error loading exams. Please try again.
          </td>
        </tr>`;
                });
        }

        /* ==== ANIMATIONS ==== */
        function animateStats() {
            document.querySelectorAll('.stat-number').forEach(el => {
                const text = el.textContent.trim();
                let target = 0;
                let suffix = '';

                if (text.includes('%')) {
                    target = parseInt(text.replace('%', ''));
                    suffix = '%';
                } else if (text.includes('k')) {
                    target = parseFloat(text.replace('k', '')) * 1000;
                    suffix = text.includes('.') ? (target / 1000).toFixed(1) + 'k' : Math.floor(target / 1000) + 'k';
                } else {
                    target = parseInt(text.replace(/[^\d]/g, '')) || 0;
                    suffix = '';
                }

                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }

                    let displayValue;
                    if (suffix === '%') {
                        displayValue = Math.floor(current) + '%';
                    } else if (suffix.includes('k')) {
                        displayValue = (current / 1000).toFixed(1) + 'k';
                    } else {
                        displayValue = Math.floor(current);
                    }

                    el.textContent = displayValue;
                }, 30);
            });
        }

        function animateProgressBars() {
            document.querySelectorAll('.progress-bar').forEach(bar => {
                const match = bar.className.match(/progress-(\d+)/);
                if (match) {
                    const width = match[1] + '%';
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 500);
                }
            });
        }

        function attachRowHover() {
            document.querySelectorAll('.exams-table tbody tr').forEach(r => {
                r.addEventListener('mouseenter', () => {
                    r.style.transform = 'translateX(2px)';
                    r.style.boxShadow = '2px 0 8px rgba(0,0,0,0.1)';
                });
                r.addEventListener('mouseleave', () => {
                    r.style.transform = '';
                    r.style.boxShadow = '';
                });
            });
        }
        /* ==== SEARCH ==== */
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', e => {
                    const term = e.target.value.toLowerCase();
                    document.querySelectorAll('.exams-table tbody tr').forEach(tr => {
                        tr.style.display = tr.textContent.toLowerCase().includes(term) ? '' : 'none';
                    });
                });
            }
        });
        /* ==== MODAL NAVIGATION ==== */
        function showStep(s) {
            if (s === 2 && currentStep === 1 && !validateStep1()) return;
            document.querySelectorAll('.step-content').forEach(c => c.style.display = 'none');
            document.getElementById('step' + s).style.display = 'block';
            currentStep = s;
            // Update step indicators
            document.querySelectorAll('.step').forEach((st, i) => {
                st.classList.toggle('completed', i < s - 1);
                st.classList.toggle('active', i === s - 1);
            });
            // Update questions counter for step 2
            if (s === 2) {
                const total = parseInt(document.getElementById('totalItems').value) || 0;
                document.getElementById('requiredQuestions').textContent = total;
                updateRemainingQuestions();
            }
        }

        function nextStep(s) {
            if (s === 2 && currentStep === 1) {
                if (!validateStep1()) return;
                updateRequiredQuestions();
            }
            if (s === 3 && currentStep === 2) {
                if (questions.length < parseInt(document.getElementById('totalItems').value)) {
                    alert(`Please add all ${document.getElementById('totalItems').value} questions`);
                    return;
                }
                populateReview();
            }
            showStep(s);
        }

        function prevStep(s) {
            showStep(s);
        }
        /* ==== QUESTIONS MANAGEMENT ==== */
        function addQuestion() {
            const max = parseInt(document.getElementById('totalItems').value) || 0;
            if (questions.length >= max) {
                alert('Maximum number of questions reached');
                return;
            }
            const idx = questions.length;
            const div = document.createElement('div');
            div.className = 'question-block mb-4';
            div.id = `question-block-${idx}`;
            div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fas fa-question-circle text-primary me-2"></i>Question ${idx + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuestion(${idx})">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
         
            <div class="form-group mb-3">
                <label class="form-label">Question Text <span class="text-danger">*</span></label>
                <textarea class="form-control question-text" rows="2" placeholder="Enter the question text here..." required></textarea>
                <div class="error-message" id="question-error-${idx}"></div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">Question Type <span class="text-danger">*</span></label>
                <select class="form-control question-type" onchange="typeChanged(this, ${idx})">
                    <option value="multiple">Multiple Choice</option>
                    <option value="truefalse">True/False</option>
                </select>
            </div>
            <!-- Media Upload -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="media-upload" onclick="document.getElementById('imageUpload-${idx}').click()">
                        <i class="fas fa-image fa-2x text-muted mb-2"></i>
                        <p class="mb-1"><strong>Add Image</strong></p>
                        <small class="text-muted">JPG, PNG, GIF (Max 5MB)</small>
                        <input type="file" id="imageUpload-${idx}" accept="image/*" style="display: none;" onchange="handleImageUpload(event, ${idx})">
                        <div id="image-preview-${idx}"></div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Answer Choices <span class="text-danger">*</span></label>
                <div class="choices-container" id="choices-${idx}"></div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addChoice(${idx})">
                    <i class="fas fa-plus"></i> Add Choice
                </button>
            </div>
         
            <hr class="my-3">
        `;
            document.getElementById('questionsContainer').appendChild(div);
            // Initialize question data
            questions.push({
                id: null,
                text: '',
                type: 'multiple',
                image_path: '',
                choices: []
            });
            // Add initial choices
            if (questions[idx].type === 'multiple') {
                addChoice(idx, 4);
            } else {
                addChoice(idx, 2); // True/False
                setupTrueFalseChoices(idx);
            }
            attachQuestionListeners(div, idx);
            updateRemainingQuestions();
        }

        function removeQuestion(idx) {
            if (confirm('Are you sure you want to remove this question?')) {
                document.getElementById(`question-block-${idx}`).remove();
                questions.splice(idx, 1);
                // Re-index remaining questions
                updateQuestionNumbers();
                updateRemainingQuestions();
            }
        }

        function updateQuestionNumbers() {
            document.querySelectorAll('.question-block').forEach((block, index) => {
                const title = block.querySelector('h6');
                if (title) {
                    title.innerHTML = `<i class="fas fa-question-circle text-primary me-2"></i>Question ${index + 1}`;
                }
                // Update question-text label
                const label = block.querySelector('.question-text').parentElement.querySelector('.form-label');
                if (label) {
                    label.innerHTML = `Question Text <span class="text-danger">*</span>`;
                }
                // Update error id
                const error = block.querySelector('.error-message');
                if (error) {
                    error.id = `question-error-${index}`;
                }
            });
        }

        function addChoice(qidx, count = 1) {
            const container = document.getElementById(`choices-${qidx}`);
            if (!container) return;
            for (let i = 0; i < count; i++) {
                const cidx = questions[qidx].choices.length;
                const row = document.createElement('div');
                row.className = 'choice-row';
                row.innerHTML = `
                <input type="text" class="form-control choice-input" placeholder="Enter choice ${String.fromCharCode(65 + cidx)}..." required>
                <div class="form-check form-switch">
                    <input class="form-check-input correct-choice" type="checkbox" id="correct-${qidx}-${cidx}">
                    <label class="form-check-label" for="correct-${qidx}-${cidx}">Correct</label>
                </div>
                ${cidx > 1 ? `<span class="remove-choice" onclick="removeChoice(this, ${qidx}, ${cidx})" title="Remove choice">
                    <i class="fas fa-trash"></i>
                </span>` : ''}
            `;
                container.appendChild(row);
                questions[qidx].choices.push({
                    text: '',
                    correct: false
                });
                // Attach listeners to new choice
                const choiceInput = row.querySelector('.choice-input');
                const correctCheck = row.querySelector('.correct-choice');
                choiceInput.addEventListener('input', function() {
                    const choiceIndex = Array.from(container.querySelectorAll('.choice-input')).indexOf(this);
                    questions[qidx].choices[choiceIndex].text = this.value;
                });
                correctCheck.addEventListener('change', function() {
                    const choiceIndex = Array.from(container.querySelectorAll('.correct-choice')).indexOf(this);
                    questions[qidx].choices[choiceIndex].correct = this.checked;
                    if (questions[qidx].type === 'truefalse') {
                        // Ensure only one correct answer for true/false
                        container.querySelectorAll('.correct-choice').forEach((cb, ci) => {
                            if (ci !== choiceIndex) {
                                cb.checked = false;
                                questions[qidx].choices[ci].correct = false;
                            }
                        });
                    }
                });
            }
        }

        function removeChoice(element, qidx, cidx) {
            if (questions[qidx].choices.length <= 2) {
                alert('At least 2 choices are required');
                return;
            }
            element.closest('.choice-row').remove();
            questions[qidx].choices.splice(cidx, 1);
        }

        function setupTrueFalseChoices(qidx) {
            const container = document.getElementById(`choices-${qidx}`);
            if (!container) return;
            // Clear existing choices
            container.innerHTML = '';
            questions[qidx].choices = []; // Reset choices in data
            // Add two choices immediately
            addChoice(qidx, 2);
            // Immediately set text and correct status in data and DOM
            const inputs = container.querySelectorAll('.choice-input');
            const checks = container.querySelectorAll('.correct-choice');
            if (inputs[0]) inputs[0].value = 'True';
            if (inputs[1]) inputs[1].value = 'False';
            if (checks[0]) checks[0].checked = true;
            if (checks[1]) checks[1].checked = false;
            // Sync to questions array immediately
            questions[qidx].choices[0] = {
                text: 'True',
                correct: true
            };
            questions[qidx].choices[1] = {
                text: 'False',
                correct: false
            };
        }

        function typeChanged(select, qidx) {
            const newType = select.value;
            questions[qidx].type = newType;
            const container = document.getElementById(`choices-${qidx}`);
            if (!container) return;
            // Clear existing choices
            container.innerHTML = '';
            questions[qidx].choices = [];
            if (newType === 'multiple') {
                addChoice(qidx, 4);
            } else {
                setupTrueFalseChoices(qidx);
            }
        }

        function attachQuestionListeners(block, qidx) {
            const textArea = block.querySelector('.question-text');
            const typeSelect = block.querySelector('.question-type');
            if (textArea) {
                textArea.addEventListener('input', function() {
                    questions[qidx].text = this.value;
                    validateQuestionText(qidx);
                });
                textArea.addEventListener('blur', function() {
                    validateQuestionText(qidx);
                });
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    typeChanged(this, qidx);
                });
            }
        }

        function validateQuestionText(qidx) {
            const text = questions[qidx].text.trim();
            const errorEl = document.getElementById(`question-error-${qidx}`);
            if (errorEl) {
                if (!text) {
                    errorEl.textContent = 'Question text is required';
                    errorEl.style.display = 'block';
                } else {
                    errorEl.style.display = 'none';
                }
            }
        }

        function handleImageUpload(event, qidx) {
            const file = event.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            // Show loading
            const preview = document.getElementById(`image-preview-${qidx}`);
            preview.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
            fetch('../partial/upload_question_media.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        questions[qidx].image_path = data.path;
                        showImagePreview(preview, data.path, data.filename);
                    } else {
                        alert('Error uploading image: ' + data.msg);
                        preview.innerHTML = '';
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    alert('Upload failed');
                    preview.innerHTML = '';
                });
        }

        function showImagePreview(container, path, filename) {
            container.innerHTML = `
            <img src="../${path}" class="question-image-preview" alt="${filename}" onerror="this.style.display='none'">
            <div class="mt-2">
                <small class="text-muted">${filename}</small>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeMedia(${container.id.match(/image-preview-(\d+)/)[1]}, 'image')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        }

        function removeMedia(qidx, type) {
            if (type === 'image') {
                questions[qidx].image_path = '';
                document.getElementById(`image-preview-${qidx}`).innerHTML = '';
                document.getElementById(`imageUpload-${qidx}`).value = '';
            }
        }

        function updateRemainingQuestions() {
            const total = parseInt(document.getElementById('totalItems').value) || 0;
            const remaining = total - questions.length;
            const span = document.getElementById('remainingQuestions');
            if (span) {
                if (remaining > 0) {
                    span.innerHTML = `<span class="text-warning">${remaining} more needed</span>`;
                } else if (remaining === 0) {
                    span.innerHTML = '<span class="text-success">All questions added</span>';
                } else {
                    span.innerHTML = '<span class="text-danger">Too many questions</span>';
                }
            }
        }

        function updateRequiredQuestions() {
            const total = parseInt(document.getElementById('totalItems').value) || 0;
            document.getElementById('requiredQuestions').textContent = total;
            updateRemainingQuestions();
        }
        /* ==== REVIEW POPULATION ==== */
        function populateReview() {
            // Basic info
            document.getElementById('reviewTitle').textContent = document.getElementById('examTitle').value;
            document.getElementById('reviewTopic').textContent = document.getElementById('examTopic').value;
            document.getElementById('reviewCategory').textContent = document.getElementById('examCategory').value;
            document.getElementById('reviewDuration').textContent = document.getElementById('examDuration').value;
            document.getElementById('reviewTotalItems').textContent = document.getElementById('totalItems').value;
            document.getElementById('reviewDescription').textContent = document.getElementById('examDescription').value || 'No description provided';
            document.getElementById('reviewDifficulty').textContent = document.getElementById('examDifficulty').value;
            document.getElementById('reviewPassingScore').textContent = document.getElementById('passingScore').value;
            // Questions review
            const reviewContainer = document.getElementById('reviewQuestions');
            reviewContainer.innerHTML = '';
            questions.forEach((q, qidx) => {
                const questionDiv = document.createElement('div');
                questionDiv.className = 'review-question';
                let mediaHtml = '';
                if (q.image_path) {
                    mediaHtml += `<img src="../${q.image_path}" class="question-image-preview mb-2" alt="Question image">`;
                }
                questionDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">Question ${qidx + 1} (${q.type === 'multiple' ? 'Multiple Choice' : 'True/False'})</h6>
                    ${mediaHtml ? `<span class="badge bg-info">Has Media</span>` : ''}
                </div>
                <p class="mb-2"><strong>Q:</strong> ${q.text || '<em>(No text)</em>'}</p>
                ${mediaHtml}
                <div class="mt-3">
                    <strong>Choices:</strong>
                    ${q.choices.map((c, cidx) => {
                        const letter = String.fromCharCode(65 + cidx);
                        const status = c.correct ? 'correct' : 'incorrect';
                        const indicator = c.correct ? '<i class="fas fa-check text-success ms-2"></i>' : '';
                        return `
                            <div class="choice-preview ${status} mt-1">
                                <strong>${letter}.</strong> ${c.text || '<em>(No text)</em>'}${indicator}
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
                reviewContainer.appendChild(questionDiv);
            });
        }
        /* ==== EXAM OPERATIONS ==== */
        function createExam() {
            if (!validateStep1()) return;
            const totalQuestions = parseInt(document.getElementById('totalItems').value);
            if (questions.length !== totalQuestions) {
                alert(`Please add exactly ${totalQuestions} questions`);
                return;
            }
            // Validate all questions have text
            for (let i = 0; i < questions.length; i++) {
                if (!questions[i].text.trim()) {
                    alert(`Question ${i + 1} needs text`);
                    showStep(2);
                    return;
                }
                // Validate choices
                for (let j = 0; j < questions[i].choices.length; j++) {
                    if (!questions[i].choices[j].text.trim()) {
                        alert(`Choice ${String.fromCharCode(65 + j)} for question ${i + 1} needs text`);
                        showStep(2);
                        return;
                    }
                }
                // Ensure at least one correct answer
                if (!questions[i].choices.some(c => c.correct)) {
                    alert(`Question ${i + 1} needs at least one correct answer`);
                    showStep(2);
                    return;
                }
            }
            const payload = {
                exam_id: currentExamId,
                title: document.getElementById('examTitle').value,
                topic: document.getElementById('examTopic').value,
                description: document.getElementById('examDescription').value,
                category: document.getElementById('examCategory').value,
                difficulty: document.getElementById('examDifficulty').value,
                total_questions: totalQuestions,
                duration_minutes: parseInt(document.getElementById('examDuration').value),
                passing_score: parseInt(document.getElementById('passingScore').value),
                questions: questions.map(q => ({
                    text: q.text,
                    type: q.type,
                    image_path: q.image_path || null,
                    choices: q.choices.map(c => ({
                        text: c.text,
                        correct: c.correct ? 1 : 0
                    }))
                }))
            };
            const url = isEditMode ? '../partial/exam_update.php' : '../partial/exam_create.php';
            const btn = document.getElementById('createExamBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
            btn.disabled = true;
            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addExamModal')).hide();
                        resetForm();
                        loadExams();
                        if (isEditMode) {
                            showToast('Exam updated successfully!', 'success');
                        } else {
                            showToast('Exam created successfully!', 'success');
                        }
                    } else {
                        alert('Error: ' + (res.msg || 'Failed to save exam'));
                        showStep(2);
                    }
                })
                .catch(err => {
                    console.error('Save error:', err);
                    alert('Error saving exam. Please try again.');
                    showStep(2);
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        function resetForm() {
            questions = [];
            currentExamId = 0;
            isEditMode = false;
            document.getElementById('addExamModal').querySelectorAll('input, textarea, select').forEach(el => {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = false;
                } else {
                    el.value = '';
                }
            });
            document.getElementById('questionsContainer').innerHTML = '';
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            document.getElementById('modalTitle').textContent = 'Create New Exam';
            showStep(1);
        }
        /* ==== VIEW EXAM ==== */
        function viewExam(id) {
            currentExamId = id;
            const modal = new bootstrap.Modal(document.getElementById('viewExamModal'));
            modal.show();
            fetch(`../partial/exam_get.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const exam = data.exam;
                        const content = document.getElementById('viewExamContent');

                        // Safely handle avg_score and completions
                        const avgScore = parseInt(exam.avg_score) || 0;
                        const completions = parseInt(exam.completions) || 0;
                        let html = `
                        <div class="row">
                            <div class="col-md-8">
                                <h5><i class="fas fa-clipboard-list me-2"></i>${exam.title || 'Untitled'}</h5>
                                <p class="text-muted mb-1"><strong>Topic:</strong> ${exam.topic || 'General'}</p>
                                <p class="text-muted mb-1"><strong>Category:</strong>
                                    <span class="badge bg-primary">${exam.category || ''}</span>
                                </p>
                                <p class="text-muted mb-3"><strong>Difficulty:</strong>
                                    <span class="badge bg-warning">${exam.difficulty || ''}</span>
                                </p>
                                <div class="mb-3">
                                    <h6><i class="fas fa-align-left me-2"></i>Description</h6>
                                    <p>${exam.description || 'No description provided'}</p>
                                </div>
                                <h6><i class="fas fa-list me-2"></i>Questions (${exam.questions?.length || 0})</h6>
                    `;
                        if (exam.questions && Array.isArray(exam.questions)) {
                            exam.questions.forEach((q, qidx) => {
                                let mediaHtml = '';
                                if (q.image_path) {
                                    mediaHtml = `<img src="../${q.image_path}" class="img-fluid rounded mb-2" style="max-width: 300px;" alt="Question image" onerror="this.style.display='none'">`;
                                }
                                html += `
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title mb-2">
                                            <strong>Question ${qidx + 1}:</strong>
                                            <span class="badge bg-${q.type === 'multiple' ? 'info' : 'secondary'}">
                                                ${q.type === 'multiple' ? 'Multiple Choice' : 'True/False'}
                                            </span>
                                        </h6>
                                        <p class="card-text mb-2">${q.text || '(No text)'}</p>
                                        ${mediaHtml}
                                        <h6 class="mt-3 mb-2">Choices:</h6>
                                        <ul class="list-unstyled">
                            `;
                                if (q.choices && Array.isArray(q.choices)) {
                                    q.choices.forEach((c, cidx) => {
                                        const letter = String.fromCharCode(65 + cidx);
                                        const correctMark = c.correct ? ' <span class="text-success"><i class="fas fa-check-circle"></i> (Correct)</span>' : '';
                                        html += `<li class="mb-1"><strong>${letter}.</strong> ${c.text || '(No text)'}${correctMark}</li>`;
                                    });
                                }
                                html += `
                                        </ul>
                                    </div>
                                </div>
                            `;
                            });
                        }
                        html += `
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Exam Info</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Duration:</strong> ${exam.duration_minutes || 0} minutes</p>
                                        <p class="mb-1"><strong>Passing Score:</strong> ${exam.passing_score || 0}%</p>
                                        <p class="mb-1"><strong>Total Questions:</strong> ${exam.total_questions || 0}</p>
                                        <p class="mb-1"><strong>Created:</strong> ${exam.created_at ? new Date(exam.created_at).toLocaleDateString() : 'N/A'}</p>
                                        <hr>
                                        <h6 class="mb-2">Stats</h6>
                                        <div class="progress mb-2" style="height: 20px;">
                                            <div class="progress-bar" style="width: ${avgScore}%"></div>
                                        </div>
                                        <small class="text-muted">Average Score: ${avgScore}%</small>
                                        <p class="mb-0 mt-2"><small class="text-muted">Attempts: ${completions}</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                        content.innerHTML = html;
                    } else {
                        document.getElementById('viewExamContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading exam details: ${data.msg || 'Unknown error'}
                        </div>
                    `;
                    }
                })
                .catch(err => {
                    console.error('View exam error:', err);
                    document.getElementById('viewExamContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load exam details
                    </div>
                `;
                });
        }
        /* ==== EDIT EXAM ==== */
        function editExam(id) {
            currentExamId = id;
            isEditMode = true;
            document.getElementById('modalTitle').textContent = 'Edit Exam';
            fetch(`../partial/exam_get.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error loading exam: ' + (data.msg || 'Unknown'));
                        return;
                    }
                    const exam = data.exam;
                    // Populate basic info
                    document.getElementById('examTitle').value = exam.title || '';
                    document.getElementById('examTopic').value = exam.topic || '';
                    document.getElementById('examCategory').value = exam.category || '';
                    document.getElementById('examDifficulty').value = exam.difficulty || '';
                    document.getElementById('examDuration').value = exam.duration_minutes || '';
                    document.getElementById('totalItems').value = exam.total_questions || '';
                    document.getElementById('passingScore').value = exam.passing_score || '';
                    document.getElementById('examDescription').value = exam.description || '';
                    // Reset questions
                    questions = [];
                    document.getElementById('questionsContainer').innerHTML = '';
                    if (!exam.questions || exam.questions.length === 0) {
                        alert('This exam has no questions.');
                        return;
                    }
                    exam.questions.forEach((q, qidx) => {
                        addQuestion(); // Creates empty question block
                        const block = document.getElementById(`question-block-${qidx}`);
                        if (!block) return;
                        // Set question text
                        const textArea = block.querySelector('.question-text');
                        textArea.value = q.text || '';
                        questions[qidx].text = q.text || '';
                        // Set type
                        const typeSelect = block.querySelector('.question-type');
                        typeSelect.value = q.type || 'multiple';
                        questions[qidx].type = q.type || 'multiple';
                        // Rebuild choices correctly
                        typeChanged(typeSelect, qidx);
                        setTimeout(() => {
                            const container = document.getElementById(`choices-${qidx}`);
                            if (!container) return;
                            container.innerHTML = '';
                            questions[qidx].choices = [];
                            (q.choices || []).forEach((c, cidx) => {
                                const row = document.createElement('div');
                                row.className = 'choice-row';
                                const choiceText = (c.text || '').replace(/"/g, '&quot;');
                                row.innerHTML = `
                                <input type="text" class="form-control choice-input" value="${choiceText}" required>
                                <div class="form-check form-switch">
                                    <input class="form-check-input correct-choice" type="checkbox" id="correct-${qidx}-${cidx}" ${c.correct ? 'checked' : ''}>
                                    <label class="form-check-label" for="correct-${qidx}-${cidx}">Correct</label>
                                </div>
                                ${cidx > 1 ? `<span class="remove-choice" onclick="removeChoice(this, ${qidx}, ${cidx})">
                                    <i class="fas fa-trash"></i>
                                </span>` : ''}
                            `;
                                container.appendChild(row);
                                // === CRITICAL FIX: Re-sync data from input ===
                                questions[qidx].choices.push({
                                    text: c.text || '',
                                    correct: !!c.correct
                                });
                                // Re-attach input listener to keep sync
                                const input = row.querySelector('.choice-input');
                                input.addEventListener('input', function() {
                                    questions[qidx].choices[cidx].text = this.value;
                                });
                                // Re-attach checkbox listener
                                const checkbox = row.querySelector('.correct-choice');
                                checkbox.addEventListener('change', function() {
                                    questions[qidx].choices[cidx].correct = this.checked;
                                    if (questions[qidx].type === 'truefalse') {
                                        container.querySelectorAll('.correct-choice').forEach((cb, ci) => {
                                            if (ci !== cidx) {
                                                cb.checked = false;
                                                questions[qidx].choices[ci].correct = false;
                                            }
                                        });
                                    }
                                });
                            });
                            // Restore image
                            if (q.image_path) {
                                questions[qidx].image_path = q.image_path;
                                const preview = document.getElementById(`image-preview-${qidx}`);
                                if (preview) {
                                    showImagePreview(preview, q.image_path, q.image_path.split('/').pop());
                                }
                            }
                        }, 200);
                    });
                    updateRequiredQuestions();
                    updateRemainingQuestions();
                    const modal = new bootstrap.Modal(document.getElementById('addExamModal'));
                    modal.show();
                    showStep(1);
                })
                .catch(err => {
                    console.error(err);
                    alert('Failed to load exam');
                });
        }
        /* ==== DELETE EXAM ==== */
        function deleteExam(id) {
            if (confirm('Are you sure you want to delete this exam? This action cannot be undone.')) {
                fetch(`../partial/exam_delete.php?id=${id}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            loadExams();
                            showToast('Exam deleted successfully!', 'success');
                        } else {
                            alert('Error deleting exam: ' + res.msg);
                        }
                    })
                    .catch(err => {
                        console.error('Delete error:', err);
                        alert('Failed to delete exam');
                    });
            }
        }
        /* ==== TOAST NOTIFICATIONS ==== */
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) return;
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'danger' ? 'danger' : 'primary'} border-0 mb-2`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, {
                delay: 5000 // auto-dismiss after 5 seconds
            });
            bsToast.show();
            // Clean up when hidden
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
        /* ==== EVENT LISTENERS ==== */
        // Total items change handler
        document.addEventListener('DOMContentLoaded', function() {
            const totalItemsInput = document.getElementById('totalItems');
            if (totalItemsInput) {
                totalItemsInput.addEventListener('input', updateRequiredQuestions);
            }
            // File drag and drop (only for image now)
            document.querySelectorAll('.media-upload').forEach(uploadArea => {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                ['dragenter', 'dragover'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
                });
                uploadArea.addEventListener('drop', handleDrop, false);
            });

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length > 0) {
                    const fileInput = uploadArea.querySelector('input[type="file"]');
                    const dataTransfer = new DataTransfer();
                    files.forEach(file => dataTransfer.items.add(file));
                    fileInput.files = dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            }
            // Sidebar collapse
            const collapseBtn = document.querySelector('.collapse-btn');
            if (collapseBtn) {
                collapseBtn.addEventListener('click', toggleSidebar);
            }
            // Initial load
            loadExams();
        });
        /* ==== MODAL EVENTS ==== */
        document.getElementById('addExamModal').addEventListener('hidden.bs.modal', function() {
            resetForm();
        });
        // Auto-save draft every 30 seconds (optional)
        setInterval(() => {
            if (currentStep > 1 && questions.length > 0) {
                localStorage.setItem('exam_draft', JSON.stringify({
                    questions: questions,
                    basic: {
                        title: document.getElementById('examTitle')?.value,
                        topic: document.getElementById('examTopic')?.value,
                        category: document.getElementById('examCategory')?.value,
                        difficulty: document.getElementById('examDifficulty')?.value,
                        duration: document.getElementById('examDuration')?.value,
                        total: document.getElementById('totalItems')?.value,
                        passing: document.getElementById('passingScore')?.value,
                        description: document.getElementById('examDescription')?.value
                    }
                }));
            }
        }, 30000);
    </script>

</html>