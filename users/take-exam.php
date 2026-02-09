<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../signin.php');
    exit;
}

require_once '../partial/db_conn.php';

$page = 'practical-exams';

if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    die("Invalid exam.");
}

$examId = intval($_GET['exam_id']);
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
        /* --- Exam top bar --- */
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

        /* --- Navbar (copied-minimal) --- */
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
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
            padding-top: 0px;

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
            /* visible blue for review */
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

        /* Review Modal */
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

        /* Score Circle */
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 8px solid #e5e7eb;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .score-pass {
            border-color: #14b8a6;
            color: #14b8a6;
        }

        .score-fail {
            border-color: #f43f5e;
            color: #f43f5e;
        }

        .stats-grid {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-box {
            text-align: center;
            padding: 10px 15px;
        }

        .stat-value {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
        }
    </style>

</head>

<body>

    <div class="exam-topbar">
        <a href="practical-exams.php" class="exam-logo">
            <img src="../images/logo.png" alt="ChemEase Logo">
            <span>ChemEase</span>
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
                            <div class="stat-label">Unanswered</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="statTime">00:00</div>
                            <div class="stat-label">Time Taken</div>
                        </div>
                    </div>
                    <div id="detailedResults"></div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button class="btn btn-primary btn-lg" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Your Exam History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="historyBody">
                    <p class="text-center">Loading your history...</p>
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
                    <button class="btn btn-danger" data-bs-dismiss="modal" onclick="finalSubmit()">Submit Now</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let examData = null;
        let originalQuestions = [];
        let questionMapping = [];
        let currentQ = 0;
        let responses = {}; // key = question_id, value = choice_id
        let startTime = null;
        let timerInterval = null;
        let examEnded = false;
        let isGoingToReview = false;

        // =======================
        // START EXAM
        // =======================
        function startExam(examId) {
            fetch(`../partial/exam_start.php?exam_id=${examId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.exam) {
                        alert("Exam not found.");
                        return;
                    }

                    originalQuestions = data.questions;

                    const shuffledQuestions = shuffleArray(data.questions);

                    questionMapping = shuffledQuestions.map(q => {
                        const originalIndex = originalQuestions.findIndex(oq => oq.id === q.id);
                        return {
                            shuffledQuestion: {
                                ...q,
                                choices: shuffleArray(q.choices)
                            },
                            originalIndex
                        };
                    });

                    examData = {
                        ...data,
                        questions: questionMapping.map(qm => qm.shuffledQuestion)
                    };

                    responses = {}; // initialize as object keyed by question_id

                    document.getElementById('examTitle').textContent = examData.exam.title;
                    document.getElementById('qTotal').textContent = examData.questions.length;

                    startTime = Date.now();
                    startTimer(examData.exam.duration_minutes * 60);

                    showQuestion();
                    updateButtons();
                });
        }

        // =======================
        // SHOW QUESTION
        // =======================
        function showQuestion() {
            const q = examData.questions[currentQ];
            const container = document.getElementById('questionContainer');

            document.getElementById('qCurrent').textContent = currentQ + 1;

            let html = `<h5 class="mb-3">${q.text}</h5>`;

            q.choices.forEach(choice => {
                html += `
        <div class="form-check mb-2">
            <input class="form-check-input"
                   type="radio"
                   name="choice_q_${currentQ}"
                   id="choice_${choice.id}"
                   value="${choice.id}">
            <label class="form-check-label" for="choice_${choice.id}">
                ${choice.text}
            </label>
        </div>
        `;
            });

            container.innerHTML = html;

            // preselect if already answered
            if (responses[q.id] !== undefined) {
                const selected = container.querySelector(`input[value="${responses[q.id]}"]`);
                if (selected) selected.checked = true;
            }

            container.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    responses[q.id] = Number(radio.value); // store by question ID
                    updateButtons();
                });
            });
        }

        // =======================
        // NAVIGATION
        // =======================
        function nextQuestion() {
            if (currentQ < examData.questions.length - 1) {
                currentQ++;
                showQuestion();
                updateButtons();
            }
        }

        function prevQuestion() {
            if (currentQ > 0) {
                currentQ--;
                showQuestion();
                updateButtons();
            }
        }

        function updateButtons() {
            const q = examData.questions[currentQ];
            const isLast = currentQ === examData.questions.length - 1;

            // Previous button
            document.getElementById('prevBtn').disabled = currentQ === 0;

            // Next button
            document.getElementById('nextBtn').disabled = responses[q.id] === undefined;
            document.getElementById('nextBtn').classList.toggle('d-none', isLast);

            // Review button
            document.getElementById('reviewBtn').classList.toggle('d-none', !isLast);
        }


        // =======================
        // EXIT
        // =======================
        function showExitModal() {
            const modal = new bootstrap.Modal(document.getElementById('exitExamModal'));
            modal.show();
        }

        function exitExam() {
            window.location.href = 'practical-exams.php';
        }

        // =======================
        // REVIEW MODAL
        // =======================
        function showReviewModal() {
            isGoingToReview = true;
            let reviewHtml = '';

            examData.questions.forEach((q, i) => {
                // console.log(q.choices)
                // console.log(responses)
                const userAnswerId = responses[q.id]; // lookup by question ID
                const userAnswer = q.choices.find(c => c.id == userAnswerId);
                const correctAnswer = q.choices.find(c => c.correct);

                const isCorrect = userAnswerId && userAnswer && userAnswer.correct;

                let cleanUserText = userAnswer ? userAnswer.text.replace(/^[A-D]\.\s*/i, '').trim() : 'Not answered';
                console.log(userAnswer)
                let cleanCorrectText = correctAnswer ? correctAnswer.text.replace(/^[A-D]\.\s*/i, '').trim() : '';

                reviewHtml += `<div class="mb-4 p-3 border rounded">
            <div class="fw-bold mb-2">Question ${i + 1}</div>
            <div class="mb-2">${q.text}</div>
            <div class="review-answer">
                <strong>Your Answer:</strong> ${cleanUserText}
            </div>
        </div>`;
            });

            document.getElementById('reviewContainer').innerHTML = reviewHtml;

            const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
            reviewModal.show();
        }

        function backToExam() {
            isGoingToReview = false;
            showQuestion();
            updateButtons();
        }

        // =======================
        // TIMER
        // =======================
        function startTimer(seconds) {
            let remaining = seconds;

            timerInterval = setInterval(() => {
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;

                document.getElementById('timerDisplay').textContent =
                    `${String(mins).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;

                if (remaining <= 0) {
                    clearInterval(timerInterval);
                    timeUp();
                }

                remaining--;
            }, 1000);
        }

        function timeUp() {
            // Do NOT set examEnded here; finalSubmit() will bail out if examEnded is true.
            const modal = new bootstrap.Modal(document.getElementById('timeUpModal'));
            modal.show();
        }

        // =======================
        // SUBMISSION
        // =======================
        function confirmSubmit() {
            if (confirm("Submit your exam now?")) finalSubmit();
        }

        function finalSubmit() {
            if (examEnded) return;
            clearInterval(timerInterval);
            examEnded = true;

            const timeTaken = Math.floor((Date.now() - startTime) / 1000);
            const minutes = String(Math.floor(timeTaken / 60)).padStart(2, '0');
            const seconds = String(timeTaken % 60).padStart(2, '0');

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
                    if (res.success) showResults(res, minutes + ':' + seconds);
                    else alert('Error submitting exam. Please try again.');
                });
        }

        // =======================
        // SHOW RESULTS
        // =======================
        function showResults(data, timeTaken) {
            const passed = data.score >= data.passing_score;
            const statusText = passed ? 'Passed' : 'Failed';
            const passingItems = Math.ceil((data.passing_score / 100) * data.total);

            document.getElementById('finalScore').innerHTML = `
        ${data.score}%<div style="font-size: 1rem; margin-top: 0.5rem; font-weight: 600;">
        ${statusText} (Passing Score: ${passingItems}/${data.total})
        </div>
    `;

            document.getElementById('scoreCircle').className = 'score-circle ' + (passed ? 'score-pass' : 'score-fail');
            document.getElementById('resultsTitle').textContent = passed ? 'Congratulations! You Passed!' : 'Exam Completed';

            document.getElementById('statCorrect').textContent = data.correct;
            const answered = Object.keys(responses).length;
            const incorrect = Math.max(0, answered - data.correct);

            document.getElementById('statIncorrect').textContent = incorrect;
            document.getElementById('statUnanswered').textContent = Math.max(0, data.total - answered);
            document.getElementById('statTime').textContent = timeTaken;

            // detailed results handled in Review modal
            showModal('resultsModal');
        }

        // =======================
        // UTILITIES
        // =======================
        function shuffleArray(arr) {
            return [...arr].sort(() => Math.random() - 0.5);
        }

        function showModal(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }

        // =======================
        // AUTO START
        // =======================
        document.addEventListener("DOMContentLoaded", () => {
            startExam(<?= $examId ?>);
        });
    </script>



    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Defensive cleanup: remove any stuck modal backdrop that could block clicks
            document.querySelectorAll(".modal-backdrop").forEach(b => b.remove());
            document.body.classList.remove("modal-open");
            document.body.style.removeProperty("padding-right");

            // Redirect back to Practice Exams after closing results
            const resultsModal = document.getElementById('resultsModal');
            if (resultsModal) {
                resultsModal.addEventListener('hidden.bs.modal', () => {
                    window.location.href = 'practical-exams.php';
                });
            }
        });
    </script>

</body>

</html>