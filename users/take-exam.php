<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['user', 'guest'], true)) {
    header('Location: ../signin.php');
    exit;
}

$isGuestUser = ($_SESSION['role'] === 'guest');
if (!$isGuestUser && !isset($_SESSION['user_id'])) {
    header('Location: ../signin.php');
    exit;
}

require_once '../partial/db_conn.php';
require_once '../partial/system_settings_bootstrap.php';

$page = 'practical-exams';

if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    die("Invalid exam.");
}

$examId = intval($_GET['exam_id']);

if ($isGuestUser) {
    $stmt = $conn->prepare("SELECT title FROM exams WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $examId);
    $stmt->execute();
    $examRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$examRow || !preg_match('/POST TEST\s*\(Module\s+/i', (string)$examRow['title'])) {
        header('Location: index.php?page=practical-exams');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    :root {
            --primary-blue: #17a2b8;
            --light-blue: #e8f4f8;
            --dark-text: #2c3e50;
            --light-gray: #f8f9fa;
            --input-bg: rgba(255, 255, 255, 0.9);
            --success-color: #28A745;
            --warning-color: #FFC107;
            --danger-color: #DC3545;
            --purple-color: #6F42C1;
            --gradient-start: #17a2b8;
            --gradient-end: #20c5d4;
        }
        
        .exam-topbar {
            position: sticky;
            top: 0;
            z-index: 1050;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .exam-back {
            text-decoration: none;
            color: #111827;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .exam-back:hover {
            color: #0d6efd;
        }

        .exam-logo {
            text-decoration: none;
            color: #111827;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .exam-logo img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
        }
        
        .navbar-brand {
            color: var(--primary-blue) !important;
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        .navbar-brand img {
            transition: all 0.3s ease;
        }
        .navbar-brand:hover img {
            transform: rotate(360deg) scale(1.1);
        }

        .profile-dropdown .profile-trigger {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 999px;
            overflow: hidden;
        }

        .profile-dropdown .profile-img {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 999px;
            display: block;
        }

        .profile-dropdown .profile-initials {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background: #e9ecef;
            color: #111827;
        }

        body {
            padding-top: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .exam-wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .exam-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .exam-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #14b8a6;
        }

        .exam-header p {
            color: #4b5563;
            font-size: 1rem;
        }

        #questionContainer {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .form-check-label {
            font-size: 1rem;
            padding-left: 8px;
        }

        .exam-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .exam-footer button {
            border-radius: 10px;
            padding: 10px 22px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        #prevBtn {
            background: #e5e7eb;
            color: #111827;
        }

        #nextBtn {
            background: #14b8a6;
            color: white;
        }

        #reviewBtn {
            background: #3b82f6;
            color: white;
        }

        #submitBtn {
            background: #f43f5e;
            border: none;
            color: white;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.35em 0.6em;
            border-radius: 8px;
        }

        #timerDisplay {
            background: #fef3c7;
            color: #111827;
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        #reviewContainer {
            max-height: 65vh;
            overflow-y: auto;
            padding: 10px;
        }

        .review-answer {
            background: #f3f4f6;
            padding: 12px 15px;
            border-radius: 8px;
            margin-top: 8px;
        }

        .results-modal {
            border: 0;
            overflow: hidden;
            border-radius: 18px;
        }

        .results-header {
            background: linear-gradient(90deg, #14b8a6, #06b6d4);
            color: #fff;
            padding: 18px 20px;
            text-align: center;
            font-weight: 700;
        }

        .results-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .results-body {
            padding: 18px 16px 8px;
        }

        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 999px;
            border: none;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
            margin: 6px auto 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            font-weight: 900;
            color: #fff;
        }

        .score-pass {
            background: #14b8a6;
        }

        .score-fail {
            background: #ef4444;
        }

        .stats-grid {
            display: flex;
            justify-content: space-around;
            gap: 10px;
            margin: 0 auto 18px;
            flex-wrap: wrap;
        }

        .stat-box {
            text-align: center;
            background: #f3f4f6;
            border-radius: 12px;
            min-width: 120px;
            flex: 1 1 120px;
            padding: 12px 10px;
        }

        .stat-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: #0ea5e9;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .details-title {
            text-align: left;
            font-weight: 800;
            margin: 12px 0 10px;
            font-size: 1.05rem;
        }

        .details-scroll {
            max-height: 55vh;
            overflow-y: auto;
            padding-right: 4px;
        }

        .q-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 14px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.04);
            text-align: left;
        }

        .q-card .q-number {
            font-weight: 800;
            margin-bottom: 6px;
            color: #111827;
        }

        .q-card .q-text {
            color: #374151;
            margin-bottom: 10px;
            line-height: 1.35;
        }

        .answer-pill {
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 8px;
            border-left: 4px solid transparent;
            font-size: 0.95rem;
        }

        .answer-pill strong {
            font-weight: 800;
        }

        .answer-pill .pill-text {
            flex: 1;
        }

        .answer-pill .pill-icon {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .pill-correct {
            background: #dcfce7;
            border-left-color: #16a34a;
            color: #166534;
        }

        .pill-wrong {
            background: #fee2e2;
            border-left-color: #dc2626;
            color: #991b1b;
        }

        .pill-unanswered {
            background: #fef3c7;
            border-left-color: #f59e0b;
            color: #92400e;
        }


        .form-check-label {
            display: inline-flex;
            align-items: flex-start;
            gap: 0.25rem;
            line-height: 1.45;
        }
    </style>
</head>

<body>
    
    <?php if (!empty($systemSettings['site_banner_enabled']) && !empty($systemSettings['site_banner_message'])): ?>
    <div class="alert alert-warning text-center mb-0 rounded-0">
        <i class="fas fa-bullhorn me-2"></i>
        <?= htmlspecialchars($systemSettings['site_banner_message']) ?>
    </div>
<?php endif; ?>

    <div class="exam-topbar">
        <!--<a href="practical-exams.php" class="exam-logo">-->
        <!--    <img src="../images/logo.png" alt="ChemEase Logo">-->
        <!--    <span>ChemEase</span>-->
        <!--</a>-->
        <a class="navbar-brand d-flex align-items-center" href="practical-exams.php">
                <img src="../images/logo.png" alt="ChemEase Logo" width="35" height="35" class="me-2">
                ChemEase
            </a>
    </div>

    <div class="exam-wrapper">
        <div class="d-flex justify-content-start mb-3">
            <button class="btn btn-outline-danger" onclick="showExitModal()">
                <i class="fa fa-arrow-left"></i> Go Back
            </button>
        </div>

        <div class="exam-header">
            <h4 id="examTitle"></h4>
            <div>
                <span>Question <span id="qCurrent">1</span>/<span id="qTotal">0</span></span>
                <span class="ms-3 badge bg-danger" id="timerDisplay"></span>
            </div>
        </div>

        <div id="questionContainer" class="exam-body"></div>

        <div class="exam-footer d-flex justify-content-between align-items-center">
            <button id="prevBtn" class="btn btn-secondary" onclick="prevQuestion()">Previous</button>
            <div>
                <button id="reviewBtn" class="btn btn-info d-none" onclick="showReviewModal()">Review</button>
                <button id="nextBtn" class="btn btn-primary" onclick="nextQuestion()">Next</button>
            </div>
        </div>
    </div>

    <!-- Exit Exam Modal -->
    <div class="modal fade" id="exitExamModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Leave Exam?</h5>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <p>
                        You are about to leave the exam.<br>
                        <strong>Your progress will be lost.</strong><br><br>
                        Are you sure you want to go back?
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="exitExam()">Yes, Leave</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Review Your Answers</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewContainer" style="max-height:70vh;overflow-y:auto"></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" onclick="backToExam()">Back to Exam</button>
                    <button class="btn btn-success" onclick="finalSubmit()">Submit Exam</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div class="modal fade" id="resultsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content results-modal">
                <div class="results-header">
                    <h3 id="resultsTitle">Exam Complete!</h3>
                </div>
                <div class="results-body text-center">
                    <div class="score-circle" id="scoreCircle">
                        <div id="finalScore">0%</div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-value" id="statCorrect">0</div>
                            <div class="stat-label">Correct</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="statIncorrect">0</div>
                            <div class="stat-label">Incorrect</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="statUnanswered">0</div>
                            <div class="stat-label">Questions</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="statTime">00:00</div>
                            <div class="stat-label">Time Taken</div>
                        </div>
                    </div>

                    <div id="detailedResults"></div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button class="btn btn-primary btn-lg" type="button" onclick="closeResultsModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Up Modal -->
    <div class="modal fade" id="timeUpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Time's Up!</h5>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-clock fa-3x text-danger mb-3"></i>
                    <p>Your time has ended. The exam will now be submitted automatically.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button class="btn btn-danger" type="button" onclick="finalSubmit()">Submit Now</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        const IS_GUEST = <?php echo $isGuestUser ? 'true' : 'false'; ?>;
        const PAGE_EXAM_ID = <?= $examId ?>;

        function buildGuestExamUrl(action) {
            const u = new URL('practical-exams.php', window.location.href);
            u.searchParams.set('guest_exam_action', action);
            u.searchParams.set('exam_id', String(PAGE_EXAM_ID));
            return u.toString();
        }

        let examData = null;
        let currentQ = 0;
        let responses = {};
        let startTime = null;
        let timerInterval = null;
        let examEnded = false;
        let isSubmitting = false;

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function cleanAnswerText(text) {
            return (text || '').replace(/^[A-D]\.\s*/i, '').trim();
        }


        function shuffleArrayCopy(items) {
            const arr = Array.isArray(items) ? [...items] : [];

            for (let i = arr.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [arr[i], arr[j]] = [arr[j], arr[i]];
            }

            return arr;
        }

        function normalizeExamQuestionChoices(examPayload) {
            if (!examPayload || !Array.isArray(examPayload.questions)) return examPayload;

            examPayload.questions = examPayload.questions.map(question => {
                const shuffledChoices = shuffleArrayCopy(question.choices || []).map(choice => ({
                    ...choice,
                    display_text: cleanAnswerText(choice.text || choice.answer_text || '')
                }));

                return {
                    ...question,
                    choices: shuffledChoices
                };
            });

            return examPayload;
        }

        function getModalInstance(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return null;

            return bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        }

        function showModal(modalId) {
            const modal = getModalInstance(modalId);
            if (!modal) return;
            modal.show();
        }

        function hideModal(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;

            const instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
            }
        }

        function cleanupModalState() {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        }

        function closeResultsModal() {
            const modalEl = document.getElementById('resultsModal');
            if (!modalEl) {
                window.location.href = 'index.php?page=practical-exams';
                return;
            }

            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            } else {
                window.location.href = 'index.php?page=practical-exams';
            }
        }

        function startExam(examId) {
            fetch(IS_GUEST ? buildGuestExamUrl('start') : `../partial/exam_start.php?exam_id=${examId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.exam || !Array.isArray(data.questions)) {
                        console.error(data);
                        alert('Failed to load exam.');
                        return;
                    }

                    examData = normalizeExamQuestionChoices(data);
                    responses = {};
                    currentQ = 0;
                    examEnded = false;
                    isSubmitting = false;

                    document.getElementById('examTitle').textContent = examData.exam.title || 'Exam';
                    document.getElementById('qTotal').textContent = examData.questions.length;

                    startTime = Date.now();

                    const durationMinutes = Number(examData.exam.duration_minutes) || 0;
                    startTimer(durationMinutes * 60);

                    showQuestion();
                    updateButtons();
                })
                .catch(error => {
                    console.error('Failed to start exam:', error);
                    alert('Failed to load exam.');
                });
        }

        function showQuestion() {
            if (!examData || !examData.questions || !examData.questions[currentQ]) return;

            const q = examData.questions[currentQ];
            const container = document.getElementById('questionContainer');

            document.getElementById('qCurrent').textContent = currentQ + 1;

            let html = `
                <h5 class="mb-3">${escapeHtml(q.text)}</h5>
            `;

            if (q.image_path) {
                html += `
                    <div class="mb-3">
                        <img src="../${escapeHtml(q.image_path)}" alt="Question Image" class="img-fluid rounded">
                    </div>
                `;
            }

            if (q.attachment_path) {
                html += `
                    <div class="mb-3">
                        <a href="../${escapeHtml(q.attachment_path)}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            View Attachment
                        </a>
                    </div>
                `;
            }

            if (Array.isArray(q.choices) && q.choices.length > 0) {
                q.choices.forEach(choice => {
                    const checked = responses[q.id] === Number(choice.id) ? 'checked' : '';

                    html += `
                        <div class="form-check mb-2">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="choice_q_${q.id}"
                                id="choice_${choice.id}"
                                value="${choice.id}"
                                ${checked}
                            >
                            <label class="form-check-label" for="choice_${choice.id}">
                                ${escapeHtml(choice.display_text || cleanAnswerText(choice.text))}
                            </label>
                        </div>
                    `;
                });
            } else {
                html += `<div class="text-muted">No choices available for this question.</div>`;
            }

            container.innerHTML = html;

            container.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    responses[q.id] = Number(radio.value);
                    updateButtons();
                });
            });
        }

        function nextQuestion() {
            if (!examData) return;

            if (currentQ < examData.questions.length - 1) {
                currentQ++;
                showQuestion();
                updateButtons();
            }
        }

        function prevQuestion() {
            if (!examData) return;

            if (currentQ > 0) {
                currentQ--;
                showQuestion();
                updateButtons();
            }
        }

        function updateButtons() {
            if (!examData || !examData.questions[currentQ]) return;

            const q = examData.questions[currentQ];
            const isLast = currentQ === examData.questions.length - 1;
            const isAnswered = responses[q.id] !== undefined;

            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const reviewBtn = document.getElementById('reviewBtn');

            prevBtn.disabled = currentQ === 0;

            if (isLast) {
                nextBtn.classList.add('d-none');
                reviewBtn.classList.remove('d-none');
                reviewBtn.disabled = !isAnswered;
            } else {
                nextBtn.classList.remove('d-none');
                reviewBtn.classList.add('d-none');
                nextBtn.disabled = !isAnswered;
                reviewBtn.disabled = true;
            }
        }

        function showExitModal() {
            showModal('exitExamModal');
        }

        function exitExam() {
            window.location.href = 'index.php?page=practical-exams';
        }

        function showReviewModal() {
            if (!examData) return;

            const lastQuestion = examData.questions[examData.questions.length - 1];
            if (lastQuestion && responses[lastQuestion.id] === undefined) {
                alert('Please answer the last question before reviewing your exam.');
                return;
            }

            let reviewHtml = '';

            examData.questions.forEach((q, index) => {
                const userAnswerId = responses[q.id];
                const userAnswer = q.choices.find(c => Number(c.id) === Number(userAnswerId));

                const cleanUserText = userAnswer
                    ? cleanAnswerText(userAnswer.text)
                    : 'Not answered';

                reviewHtml += `
                    <div class="mb-4 p-3 border rounded">
                        <div class="fw-bold mb-2">Question ${index + 1}</div>
                        <div class="mb-2">${escapeHtml(q.text)}</div>
                        <div class="review-answer">
                            <strong>Your Answer:</strong> ${escapeHtml(cleanUserText)}
                        </div>
                    </div>
                `;
            });

            document.getElementById('reviewContainer').innerHTML = reviewHtml;
            showModal('reviewModal');
        }

        function backToExam() {
            showQuestion();
            updateButtons();
        }

        function startTimer(seconds) {
            clearInterval(timerInterval);

            let remaining = Math.max(0, Number(seconds) || 0);
            const display = document.getElementById('timerDisplay');

            if (remaining <= 0) {
                display.textContent = 'No Time Limit';
                return;
            }

            timerInterval = setInterval(() => {
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;

                display.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

                if (remaining <= 0) {
                    clearInterval(timerInterval);
                    timeUp();
                    return;
                }

                remaining--;
            }, 1000);
        }

        function timeUp() {
            showModal('timeUpModal');
        }

        function finalSubmit() {
            if (examEnded || isSubmitting || !examData) return;

            const unanswered = examData.questions.filter(q => responses[q.id] === undefined);
            if (unanswered.length > 0) {
                alert(`Please answer all questions before submitting. ${unanswered.length} question(s) are still unanswered.`);
                return;
            }

            isSubmitting = true;
            examEnded = true;
            clearInterval(timerInterval);

            const timeTakenInSeconds = Math.floor((Date.now() - startTime) / 1000);
            const minutes = String(Math.floor(timeTakenInSeconds / 60)).padStart(2, '0');
            const seconds = String(timeTakenInSeconds % 60).padStart(2, '0');

            if (IS_GUEST) {
                const totalQ = Array.isArray(examData.questions) ? examData.questions.length : 0;
                let totalCorrect = 0;

                examData.questions.forEach(q => {
                    const userAnswerId = responses[q.id];
                    const userAnswer = Array.isArray(q.choices) ? q.choices.find(c => Number(c.id) === Number(userAnswerId)) : null;
                    if (userAnswer && userAnswer.correct) totalCorrect++;
                });

                const scorePct = totalQ > 0 ? Math.round((totalCorrect / totalQ) * 100) : 0;
                const passingScore = Number(examData?.exam?.passing_score || 0);
                const moduleMatch = String(examData?.exam?.title || '').match(/POST TEST\s*\(Module\s+([A-Za-z0-9IVXLCDM]+)\)/i);
                const guestPayload = new URLSearchParams({
                    exam_id: String(examData?.exam?.id || PAGE_EXAM_ID),
                    title: String(examData?.exam?.title || ''),
                    category: String(examData?.exam?.category || ''),
                    module_code: String(moduleMatch ? moduleMatch[1] : ''),
                    score: String(scorePct),
                    correct: String(totalCorrect),
                    total: String(totalQ),
                    passing_score: String(passingScore),
                    time_taken: `${minutes}:${seconds}`
                });

                fetch(buildGuestExamUrl('save'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: guestPayload.toString()
                })
                .then(r => r.json())
                .then(() => {
                    showResults({
                        success: true,
                        raw_percent: scorePct,
                        grade: scorePct,
                        total: totalQ,
                        correct: totalCorrect,
                        incorrect: Math.max(0, totalQ - totalCorrect),
                        unanswered: 0,
                        passing_score: passingScore,
                        details: []
                    }, `${minutes}:${seconds}`);
                })
                .catch(error => {
                    console.error('Guest submit failed:', error);
                    examEnded = false;
                    isSubmitting = false;
                    alert('Error submitting guest exam. Please try again.');
                });
                return;
            }

            const payload = {
                attempt_id: examData.attempt_id,
                responses: examData.questions.map(q => ({
                    question_id: q.id,
                    answer_id: responses[q.id] || null
                }))
            };

            fetch('../partial/exam_submit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showResults(res, `${minutes}:${seconds}`);
                } else {
                    examEnded = false;
                    isSubmitting = false;
                    alert(res.error || res.message || 'Error submitting exam. Please try again.');
                }
            })
            .catch(error => {
                console.error('Submit failed:', error);
                examEnded = false;
                isSubmitting = false;
                alert('Error submitting exam. Please try again.');
            });
        }

        function showResults(data, timeTaken) {
            hideModal('reviewModal');
            hideModal('timeUpModal');
            hideModal('exitExamModal');
            cleanupModalState();

            const rawPercent = Number(data.raw_percent ?? 0);
            const grade = Number(data.grade ?? 0);
            const total = Number(data.total ?? 0);
            const correct = Number(data.correct ?? 0);
            const answered = Number(data.total_answered ?? 0);
            const passingGrade = Number(data.passing_score ?? 0);
            const details = Array.isArray(data.details) ? data.details : [];

            const passed = (data.passed !== null && data.passed !== undefined)
                ? !!data.passed
                : (passingGrade > 0 ? grade >= passingGrade : false);

            document.getElementById('finalScore').innerHTML = `<div>${Math.round(grade)}%</div>`;
            document.getElementById('scoreCircle').className = 'score-circle ' + (passed ? 'score-pass' : 'score-fail');
            document.getElementById('resultsTitle').textContent = 'Exam Completed';

            document.getElementById('statCorrect').textContent = correct;
            document.getElementById('statIncorrect').textContent = Math.max(0, answered - correct);
            document.getElementById('statUnanswered').textContent = total ?? 0;
            document.getElementById('statTime').textContent = timeTaken;

            const oldPassingLine = document.getElementById('passingLine');
            if (oldPassingLine) oldPassingLine.remove();

            const passingLine = document.createElement('div');
            passingLine.id = 'passingLine';
            passingLine.style.marginTop = '-6px';
            passingLine.style.marginBottom = '14px';
            passingLine.style.fontWeight = '700';
            passingLine.style.color = '#6b7280';
            passingLine.style.fontSize = '0.9rem';
            passingLine.innerHTML = passingGrade > 0
                ? `Passing Grade: ${passingGrade}%`
                : `Passing: N/A`;

            const scoreCircleEl = document.getElementById('scoreCircle');
            scoreCircleEl.after(passingLine);

            let detailsHtml = `
                <div class="details-title">Answer Summary</div>
                <div class="details-scroll">
            `;

            details.forEach((item, i) => {
                let userPillClass = 'pill-unanswered';
                let userIcon = '<i class="fa-solid fa-circle-question pill-icon"></i>';
                let userAnswerText = item.user_answer_text ? cleanAnswerText(item.user_answer_text) : 'Not answered';

                if (item.is_answered) {
                    if (item.is_correct) {
                        userPillClass = 'pill-correct';
                        userIcon = '<i class="fa-solid fa-circle-check pill-icon"></i>';
                    } else {
                        userPillClass = 'pill-wrong';
                        userIcon = '<i class="fa-solid fa-circle-xmark pill-icon"></i>';
                    }
                }

                const showCorrectAnswer = !item.is_correct;
                const correctText = item.correct_answer_text
                    ? cleanAnswerText(item.correct_answer_text)
                    : 'N/A';

                detailsHtml += `
                    <div class="q-card">
                        <div class="q-number">Question ${item.order_index ?? (i + 1)}</div>
                        <div class="q-text">${escapeHtml(item.question_text ?? '')}</div>

                        <div class="answer-pill ${userPillClass}">
                            <div class="pill-text">
                                <strong>Your Answer:</strong> ${escapeHtml(userAnswerText)}
                            </div>
                            ${userIcon}
                        </div>

                        ${
                            showCorrectAnswer
                                ? `
                                <div class="answer-pill pill-correct mt-2">
                                    <div class="pill-text">
                                        <strong>Correct Answer:</strong> ${escapeHtml(correctText)}
                                    </div>
                                    <i class="fa-solid fa-check pill-icon"></i>
                                </div>
                                `
                                : ''
                        }
                    </div>
                `;
            });

            detailsHtml += `
                </div>
                <div class="mt-3 text-muted small">
                    Correct and incorrect checking is validated securely on the server after submission.
                </div>
            `;

            document.getElementById('detailedResults').innerHTML = detailsHtml;
            showModal('resultsModal');
        }

        document.addEventListener('DOMContentLoaded', () => {
            cleanupModalState();

            const resultsModal = document.getElementById('resultsModal');
            if (resultsModal) {
                resultsModal.addEventListener('hidden.bs.modal', () => {
                    cleanupModalState();
                    window.location.href = 'index.php?page=practical-exams';
                });
            }

            startExam(<?= $examId ?>);
        });
    </script>
</body>
</html>