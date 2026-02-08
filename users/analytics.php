<style>
.analytics-container {
    padding: 2rem 0;
}
.page-header {
            background: linear-gradient(135deg, rgba(23,162,184,0.12) 0%, rgba(255,255,255,0.97) 100%);
            padding: 3rem 2rem;
            border-radius: 24px;
            margin: 1.5rem auto 2.5rem;
            box-shadow: 0 12px 45px rgba(23,162,184,0.14);
            backdrop-filter: blur(14px);
            text-align: center;
            border: 1px solid rgba(23,162,184,0.08);
            max-width: 1200px;
                        margin-top: -5%;
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
            font-size: 1.2rem;
            color: #5c6b7f;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
            font-weight: 400;
        }
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-bottom: 3rem;
}
.stats-card {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 6px 25px rgba(23, 162, 184, 0.12);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(23, 162, 184, 0.08);
    position: relative;
    overflow: hidden;
}
.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-blue), var(--gradient-end));
    transition: all 0.4s ease;
}
.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(23, 162, 184, 0.2);
}
.stats-card:hover::before {
    height: 6px;
}
.stats-header {
    color: #6c757d;
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stats-value {
    color: var(--dark-text);
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 1rem;
}
.stats-change {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
}
.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }
.analytics-nav {
    display: flex;
    justify-content: center;
    gap: 0;
    margin-bottom: 3rem;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 0.5rem;
    box-shadow: 0 4px 20px rgba(23, 162, 184, 0.1);
    border: 1px solid rgba(23, 162, 184, 0.1);
}
.nav-tab {
    background: transparent;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 1;
    text-align: center;
}
.nav-tab.active {
    background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
    color: white;
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
}
.nav-tab:hover:not(.active) {
    background: rgba(23, 162, 184, 0.1);
    color: var(--primary-blue);
}
.content-section {
    display: none;
}
.content-section.active {
    display: block;
    animation: fadeIn 0.5s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.performance-card {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 6px 25px rgba(23, 162, 184, 0.12);
    border: 1px solid rgba(23, 162, 184, 0.08);
}
.section-title {
    color: var(--dark-text);
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.section-description {
    color: #6c757d;
    font-size: 1rem;
    margin-bottom: 2.5rem;
}
.topic-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.topic-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.topic-info {
    flex: 1;
    margin-right: 2rem;
}
.topic-name {
    color: var(--dark-text);
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.75rem;
}
.progress-container {
    position: relative;
    width: 100%;
    height: 12px;
    background: rgba(108, 117, 125, 0.1);
    border-radius: 10px;
    overflow: hidden;
}
.progress-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 1.2s ease-out;
    width: 0%;
}
.progress-success { background: linear-gradient(135deg, #28a745, #20c997); }
.progress-warning { background: linear-gradient(135deg, #ffc107, #ffb300); }
.progress-danger { background: linear-gradient(135deg, #dc3545, #e74c3c); }
.progress-secondary { background: #6c757d; }
.topic-score {
    color: var(--dark-text);
    font-weight: 700;
    font-size: 1.3rem;
    min-width: 60px;
    text-align: right;
}
@media (max-width: 992px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
}
@media (max-width: 768px) {
    .analytics-container { padding: 1.5rem 1rem; }
    .page-title { font-size: 2.2rem; }
    .stats-grid { grid-template-columns: 1fr; }
    .stats-card { padding: 1.75rem; }
    .stats-value { font-size: 2.7rem; }
    .analytics-nav { flex-direction: column; gap: 0.5rem; }
    .nav-tab { padding: 1rem; }
    .performance-card { padding: 2rem; }
    .topic-item { flex-direction: column; align-items: stretch; gap: 1rem; }
    .topic-info { margin-right: 0; }
    .topic-score { text-align: center; font-size: 1.6rem; }
               .page-header {
 
                        margin-top: -20%;

        }
}
</style>

<div class="analytics-container">
    <div class="page-header">
        <h1 class="page-title">Performance Analytics</h1>
        <p class="page-subtitle">Track your progress and identify areas for improvement</p>
    </div>
    <!-- Stats Cards -->
    <div class="stats-grid" id="statsGrid"></div>
    <!-- Navigation Tabs -->
    <div class="analytics-nav">
        <button class="nav-tab active" onclick="switchTab('topic-performance', this)">Topic Performance</button>
        <button class="nav-tab" onclick="switchTab('exam-history', this)">Exam History</button>
        <button class="nav-tab" onclick="switchTab('recommendations', this)">Recommendations</button>
    </div>
    <!-- Topic Performance -->
    <div id="topic-performance" class="content-section active">
        <div class="performance-card">
            <h2 class="section-title">Topic Performance Analysis</h2>
            <p class="section-description">Your average score per chemistry category (0–100%)</p>
            <div class="topic-list" id="topicList"></div>
        </div>
    </div>
    <!-- Exam History -->
    <div id="exam-history" class="content-section">
        <div class="performance-card">
            <h2 class="section-title">Recent Exam History</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Exam</th>
                            <th>Category</th>
                            <th>Score</th>
                            <th>Date</th>
                            <th>Time Taken</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody"></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Recommendations -->
    <div id="recommendations" class="content-section">
        <div class="performance-card">
            <h2 class="section-title">Personalized Study Tips</h2>
            <div id="recommendationsList"></div>
        </div>
    </div>
</div>

<script>
let analyticsData = {};
fetch('../partial/fetch_analytics.php')
    .then(r => r.json())
    .then(data => {
        analyticsData = data;
        renderStats();
        renderTopics();
        renderHistory();
        renderRecommendations();
        animateProgressBars();
    })
    .catch(err => console.error('Analytics fetch failed:', err));

function renderStats() {
    const grid = document.getElementById('statsGrid');
    const stats = analyticsData.stats || {};
    const icons = ['fa-chart-line', 'fa-clipboard-check', 'fa-book-reader'];
    const titles = ['Overall Score', 'Exams Completed', 'Materials Completed'];
    grid.innerHTML = Object.keys(stats).map((key, i) => {
        const s = stats[key];
        const title = titles[i];
        let valueDisplay = s.value;
        if (key === 'overall_score') valueDisplay += '%';
        // No unit/word at all for Materials Completed – just the number
        return `
        <div class="stats-card">
            <div class="stats-header">${title}</div>
            <div class="stats-value">
                ${valueDisplay}
            </div>
        </div>`;
    }).join('');
}

function renderTopics() {
    const list = document.getElementById('topicList');
    const items = analyticsData.topic_performance || [];
    list.innerHTML = items.map(t => `
        <div class="topic-item">
            <div class="topic-info">
                <div class="topic-name">${t.topic}</div>
                <div class="progress-container">
                    <div class="progress-bar progress-${t.color}"
                         style="--progress-width: ${t.score}%;"></div>
                </div>
            </div>
            <div class="topic-score">${t.score}%</div>
        </div>
    `).join('');
}

function renderHistory() {
    const tbody = document.getElementById('historyBody');
    const rows = analyticsData.history || [];
    tbody.innerHTML = rows.map(h => {
        const badge = h.score >= 80 ? 'success' : (h.score >= 70 ? 'warning' : 'danger');
        return `
        <tr>
            <td><strong>${h.title}</strong></td>
            <td><span class="badge bg-secondary">${h.category}</span></td>
            <td><span class="badge bg-${badge}">${h.score}%</span></td>
            <td>${h.date}</td>
            <td>${h.time_taken}</td>
        </tr>`;
    }).join('');
}

function renderRecommendations() {
    const container = document.getElementById('recommendationsList');
    const recs = analyticsData.recommendations || [];
    container.innerHTML = recs.map(r => `
        <div class="alert alert-info mb-3">
            <i class="fas fa-lightbulb me-2"></i> ${r}
        </div>
    `).join('');
}

function switchTab(targetId, btn) {
    document.querySelectorAll('.nav-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(targetId).classList.add('active');
}

function animateProgressBars() {
    document.querySelectorAll('.progress-bar').forEach(bar => {
        setTimeout(() => {
            bar.style.width = bar.getAttribute('style').match(/--progress-width:\s*([^;]+)/)?.[1] || '0%';
        }, 400);
    });
}
</script>