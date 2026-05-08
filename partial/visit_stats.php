<?php
// admin/partial/visit_stats.php

session_start();
header('Content-Type: application/json');

require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

$range = $_GET['range'] ?? '7d';

switch ($range) {
    case '24h':
        $days = 1;
        break;
    case '30d':
        $days = 30;
        break;
    case '90d':
        $days = 90;
        break;
    case '7d':
    default:
        $days = 7;
        break;
}
$response = [
    'success' => true,
    'range' => $range,
    'summary' => [
        'total_visits' => 0,
        'unique_sessions' => 0,
        'unique_users' => 0,
        'guest_visits' => 0,
        'user_visits' => 0,
    ],
    'by_page' => [],
    'daily' => [],
    'by_role' => [],
    'recent' => [],
];

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_visits,
        COUNT(DISTINCT session_id) AS unique_sessions,
        COUNT(DISTINCT user_id) AS unique_users,
        SUM(CASE WHEN role = 'guest' THEN 1 ELSE 0 END) AS guest_visits,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) AS user_visits
    FROM user_visits
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
");

$stmt->bind_param('i', $days);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($summary) {
    $response['summary'] = [
        'total_visits' => (int)($summary['total_visits'] ?? 0),
        'unique_sessions' => (int)($summary['unique_sessions'] ?? 0),
        'unique_users' => (int)($summary['unique_users'] ?? 0),
        'guest_visits' => (int)($summary['guest_visits'] ?? 0),
        'user_visits' => (int)($summary['user_visits'] ?? 0),
    ];
}

/*
|--------------------------------------------------------------------------
| Visits by Page
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        page,
        COUNT(*) AS visits,
        COUNT(DISTINCT session_id) AS unique_sessions
    FROM user_visits
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY page
    ORDER BY visits DESC
");

$stmt->bind_param('i', $days);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $response['by_page'][] = [
        'page' => $row['page'],
        'visits' => (int)$row['visits'],
        'unique_sessions' => (int)$row['unique_sessions'],
    ];
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Daily Visits
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        DATE(visited_at) AS visit_date,
        COUNT(*) AS visits,
        COUNT(DISTINCT session_id) AS unique_sessions
    FROM user_visits
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY DATE(visited_at)
    ORDER BY visit_date ASC
");

$stmt->bind_param('i', $days);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $response['daily'][] = [
        'date' => $row['visit_date'],
        'visits' => (int)$row['visits'],
        'unique_sessions' => (int)$row['unique_sessions'],
    ];
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Visits by Role
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        COALESCE(role, 'unknown') AS role,
        COUNT(*) AS visits
    FROM user_visits
    WHERE visited_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY role
    ORDER BY visits DESC
");

$stmt->bind_param('i', $days);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $response['by_role'][] = [
        'role' => $row['role'],
        'visits' => (int)$row['visits'],
    ];
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Recent Visits
|--------------------------------------------------------------------------
*/
$res = $conn->query("
    SELECT
        uv.id,
        uv.user_id,
        uv.role,
        uv.page,
        uv.visited_at,
        u.full_name
    FROM user_visits uv
    LEFT JOIN users u ON uv.user_id = u.id
    ORDER BY uv.visited_at DESC
    LIMIT 15
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $response['recent'][] = [
            'id' => (int)$row['id'],
            'user_id' => $row['user_id'] !== null ? (int)$row['user_id'] : null,
            'role' => $row['role'] ?? 'unknown',
            'name' => $row['full_name'] ?: ($row['role'] === 'guest' ? 'Guest Visitor' : 'Unknown User'),
            'page' => $row['page'],
            'visited_at' => $row['visited_at'],
        ];
    }

    $res->free();
}

echo json_encode($response);