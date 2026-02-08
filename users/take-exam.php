<?php
require_once '../partial/db_conn.php';

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
        body {
            /* background: #020617; */
            background: #e5e7eb;
            /* color: #e5e7eb; */
            color: #020617;
        }

        .exam-wrapper {
            max-width: 1100px;
            margin: 20px auto;
            padding: 15px;
        }

        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .exam-body {
            min-height: 350px;
        }

        .choice-btn {
            width: 100%;
            text-align: left;
            margin-bottom: 8px;
        }

        .exam-footer {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #1e293b;
            padding-top: 10px;
        }
    </style>

</head>

<body>

    <div class="exam-wrapper">

        <div class="exam-header">
            <h4 id="examTitle"></h4>

            <div>
                <span>Question <span id="qCurrent">1</span>/<span id="qTotal">0</span></span>
                <span class="ms-3 badge bg-danger" id="timerDisplay"></span>
            </div>
        </div>

        <div id="questionContainer" class="exam-body"></div>

        <div class="exam-footer">
            <button class="btn btn-outline-secondary" onclick="showExitModal()">
                <i class="fa fa-arrow-left"></i> Go Back
            </button>

            <div class="d-flex gap-2">
                <button id="prevBtn" class="btn btn-secondary" onclick="prevQuestion()">Previous</button>
                <button id="nextBtn" class="btn btn-primary" onclick="nextQuestion()">Next</button>
                <button id="submitBtn" class="btn btn-danger d-none" onclick="confirmSubmit()">Submit</button>
            </div>
        </div>

    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        // =======================
        // GLOBAL VARIABLES
        // =======================

        let examData = null;
        let originalQuestions = [];
        let questionMapping = [];
        let currentQ = 0;
        let responses = [];
        let startTime = null;
        let timerInterval = null;

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

                    responses = Array(examData.questions.length).fill(null);

                    document.getElementById('examTitle').textContent = examData.exam.title;
                    document.getElementById('qTotal').textContent = examData.questions.length;

                    startTime = Date.now();
                    startTimer(examData.exam.duration_minutes * 60);

                    showQuestion();
                    updateButtons();
                });
        }


        // =======================
        // QUESTION DISPLAY
        // =======================

        function showQuestion() {
            const q = examData.questions[currentQ];

            document.getElementById('qCurrent').textContent = currentQ + 1;

            let html = `<h5 class="mb-3">${q.text}</h5>`;

            q.choices.forEach(choice => {
                const checked = responses[currentQ] === choice.id ? 'checked' : '';

                html += `
            <div class="form-check mb-2">
                <input class="form-check-input"
                       type="radio"
                       name="choice"
                       id="choice_${choice.id}"
                       value="${choice.id}"
                       ${checked}
                       onchange="saveAnswer(${choice.id})">

                <label class="form-check-label" for="choice_${choice.id}">
                    ${choice.text}
                </label>
            </div>
        `;
            });

            document.getElementById('questionContainer').innerHTML = html;
        }


        function saveAnswer(choiceId) {
            responses[currentQ] = choiceId;
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
            const isLast = currentQ === examData.questions.length - 1;

            document.getElementById('nextBtn').classList.toggle('d-none', isLast);
            document.getElementById('submitBtn').classList.toggle('d-none', !isLast);

            document.getElementById('prevBtn').disabled = currentQ === 0;
        }

        function showExitModal() {
            const modal = new bootstrap.Modal(document.getElementById('exitExamModal'));
            modal.show();
        }

        function exitExam() {
            window.location.href = 'practical-exams.php';
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
                    submitExam();
                }

                remaining--;
            }, 1000);
        }


        // =======================
        // SUBMISSION
        // =======================

        function confirmSubmit() {
            if (confirm("Submit your exam now?")) submitExam();
        }

        function submitExam() {
            fetch('../partial/exam_submit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        exam_id: examData.exam.id,
                        attempt_id: examData.attempt_id,
                        answers: responses,
                        mapping: questionMapping
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert("Exam submitted successfully!");
                        window.location.href = 'practical-exams.php';
                    } else {
                        alert(res.message || "Submission failed.");
                    }
                });
        }



        // =======================
        // UTILITIES
        // =======================

        function shuffleArray(arr) {
            return [...arr].sort(() => Math.random() - 0.5);
        }


        // =======================
        // AUTO START
        // =======================

        document.addEventListener("DOMContentLoaded", () => {
            startExam(<?= $examId ?>);
        });
    </script>

    <!-- <div class="modal fade" id="exitExamModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Leave Exam?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>
                        You are about to leave the exam.<br>
                        <strong>Your progress will be lost.</strong><br><br>
                        Are you sure you want to go back?
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" onclick="exitExam()">Yes, Leave</button>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Exit Confirmation Modal -->
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

</body>

</html>