<?php
// users/dashboard.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../signin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase - Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

    .welcome-section {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.1);
    }

    .progress-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(23, 162, 184, 0.05);
        border: none;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        border: 1px solid rgba(23, 162, 184, 0.1);
        height: 100%;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .stat-icon {
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: var(--primary-blue);
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--dark-text);
        font-weight: 500;
        opacity: 0.8;
        margin-top: auto;
    }

    .progress-item {
        margin: 1rem 0;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        border: 1px solid rgba(23, 162, 184, 0.1);
    }

    .progress {
        background-color: rgba(23, 162, 184, 0.1);
        border-radius: 8px;
        height: 6px;
    }

    .progress-bar {
        border-radius: 8px;
        background: var(--primary-blue);
        transition: width 1s ease;
    }

    .activity-item {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(23, 162, 184, 0.1);
        position: relative;
    }

    .activity-item h6 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .activity-item p {
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }

    .performance-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        margin-bottom: 1rem;
        background: white;
        border-radius: 8px;
        border: 1px solid rgba(23, 162, 184, 0.1);
    }

    .subject-icon {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .analytical-chemistry { background: var(--primary-blue); }
    .organic-chemistry { background: var(--success-color); }
    .physical-chemistry { background: var(--purple-color); }
    .inorganic-chemistry { background: var(--warning-color); }
    .biochemistry { background: var(--danger-color); }

    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 4px;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    @media (max-width: 768px) {
        .welcome-section { padding: 1.5rem; }
        .progress-card { padding: 1rem; }
        .stat-card {
            min-height: 130px;
            padding: 1rem 0.75rem;
        }
        .stat-number { font-size: 1.6rem; }
        .stat-label { font-size: 0.85rem; }
    }
</style>
</head>
<body>
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Student'); ?>!</h1>
        <p class="mb-0 opacity-75">Track your progress and continue your chemistry learning journey.</p>
    </div>

    <!-- Progress Overview -->
    <div class="card progress-card">
        <div class="card-body">
            <h4 class="card-title mb-4">Your Progress Overview</h4>
           
            <div class="row mb-4" id="statsContainer">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <i class="fas fa-trophy text-warning mb-2" style="font-size: 1.5rem;"></i>
                        <div class="stat-number text-warning" id="overallCompletion">--</div>
                        <div class="stat-label">Overall Completion</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <i class="fas fa-fire text-danger mb-2" style="font-size: 1.5rem;"></i>
                        <div class="stat-number text-danger" id="studyStreak">--</div>
                        <div class="stat-label">Study Streak</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <i class="fas fa-clock text-info mb-2" style="font-size: 1.5rem;"></i>
                        <div class="stat-number text-info" id="daysActive">--</div>
                        <div class="stat-label">Days Active</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 1.5rem;"></i>
                        <div class="stat-number text-success" id="topicsCompleted">--</div>
                        <div class="stat-label">Topics Completed</div>
                    </div>
                </div>
            </div>

            <h6 class="mb-3">Progress by Category</h6>
            <div id="progressContainer">
                <div class="skeleton" style="height: 60px; margin-bottom: 1rem;"></div>
                <div class="skeleton" style="height: 60px; margin-bottom: 1rem;"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activities -->
        <div class="col-lg-6">
            <div class="card progress-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Recent Activities</h5>
                    <p class="text-muted small mb-3">Your learning progress over the past few days</p>
                    <div id="activitiesContainer">
                        <div class="skeleton" style="height: 80px; margin-bottom: 1rem;"></div>
                        <div class="skeleton" style="height: 80px; margin-bottom: 1rem;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="col-lg-6">
            <div class="card progress-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Performance Metrics</h5>
                    <p class="text-muted small mb-3">Your exam scores by category</p>
                    <div id="performanceContainer">
                        <div class="skeleton" style="height: 70px; margin-bottom: 1rem;"></div>
                        <div class="skeleton" style="height: 70px; margin-bottom: 1rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadDashboardData() {
            try {
                const response = await fetch('../partial/get_dashboard_data.php');
                const data = await response.json();

                if (!data.success) {
                    console.error('Error:', data.error);
                    return;
                }

                // Update stats
                document.getElementById('overallCompletion').textContent = data.stats.overall_completion + '%';
                document.getElementById('studyStreak').textContent = data.stats.study_streak + ' days';
                document.getElementById('daysActive').textContent = data.stats.days_active;
                document.getElementById('topicsCompleted').textContent = data.stats.topics_completed;

                // Shared icon/category mapping (used in both sections for consistency)
                const categoryIcons = {
                    'Analytical Chemistry': 'microscope',
                    'Organic Chemistry': 'leaf',
                    'Physical Chemistry': 'calculator',
                    'Inorganic Chemistry': 'atom',
                    'BioChemistry': 'dna'
                };
                const categoryColors = {
                    'Analytical Chemistry': 'primary',
                    'Organic Chemistry': 'success',
                    'Physical Chemistry': 'warning',
                    'Inorganic Chemistry': 'info',
                    'BioChemistry': 'danger'
                };

                // Progress by category
                const progressContainer = document.getElementById('progressContainer');
                if (data.progress.length === 0) {
                    progressContainer.innerHTML = '<div class="empty-state"><i class="fas fa-chart-line"></i><p>No progress data yet. Start studying!</p></div>';
                } else {
                    progressContainer.innerHTML = data.progress.map(prog => {
                        const category = prog.category;
                        const icon = categoryIcons[category] || 'flask';
                        const color = categoryColors[category] || 'primary';

                        return `
                            <div class="progress-item">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-${icon} text-${color} me-2"></i>${category}</span>
                                    <strong>${prog.percentage}%</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-${color}" style="width: ${prog.percentage}%"></div>
                                </div>
                                <small class="text-muted">${prog.completed}/${prog.total} topics</small>
                            </div>
                        `;
                    }).join('');
                }

                // Recent Activities
                const activitiesContainer = document.getElementById('activitiesContainer');
                if (data.recent_activities.length === 0) {
                    activitiesContainer.innerHTML = '<div class="empty-state"><i class="fas fa-history"></i><p>No recent activity</p></div>';
                } else {
                    activitiesContainer.innerHTML = data.recent_activities.map(a => `
                        <div class="activity-item">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-${a.icon} text-${a.color} me-3" style="font-size:1.2rem;"></i>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${a.title}</h6>
                                    <p class="text-muted small mb-1">${a.description}</p>
                                    <small class="text-${a.color} fw-bold">${a.status}</small>
                                </div>
                                <small class="text-muted">${a.time_ago}</small>
                            </div>
                        </div>
                    `).join('');
                }

                // Performance Metrics – now using SAME icons & colors as Progress by Category
                const performanceContainer = document.getElementById('performanceContainer');
                if (data.performance.length === 0) {
                    performanceContainer.innerHTML = '<div class="empty-state"><i class="fas fa-chart-bar"></i><p>No exams taken yet</p></div>';
                } else {
                    performanceContainer.innerHTML = data.performance.map(p => {
                        const category = p.category;
                        const icon = categoryIcons[category] || 'flask';
                        const color = categoryColors[category] || 'primary';

                        return `
                            <div class="performance-item">
                                <div class="subject-icon ${category.toLowerCase().replace(/\s+/g, '-')}">
                                    <i class="fas fa-${icon}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>${category}</strong>
                                        <span class="fw-bold">${p.avg_score}%</span>
                                    </div>
                                    <div class="progress mt-2" style="height:6px;">
                                        <div class="progress-bar bg-${color}" style="width:${p.avg_score}%"></div>
                                    </div>
                                    <small class="text-muted">${p.total_exams} exam${p.total_exams!==1?'s':''} • ${p.total_correct}/${p.total_answered} correct</small>
                                </div>
                            </div>
                        `;
                    }).join('');
                }

                // Animate progress bars
                setTimeout(() => {
                    document.querySelectorAll('.progress-bar').forEach((bar, i) => {
                        const w = bar.style.width;
                        bar.style.width = '0%';
                        setTimeout(() => bar.style.width = w, i * 100);
                    });
                }, 200);

            } catch (err) {
                console.error('Fetch error:', err);
            }
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadDashboardData);

        // Scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.style.opacity = '1';
                    e.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.card, .activity-item, .performance-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>