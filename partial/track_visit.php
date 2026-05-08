<?php
// partial/track_visit.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn) || !$conn instanceof mysqli) {
    require_once __DIR__ . '/db_conn.php';
}

if (!function_exists('visitTableExists')) {
    function visitTableExists(mysqli $conn): bool
    {
        $res = $conn->query("SHOW TABLES LIKE 'user_visits'");
        if (!$res) {
            error_log('visitTableExists failed: ' . $conn->error);
            return false;
        }

        $exists = $res->num_rows > 0;
        $res->free();

        return $exists;
    }
}

if (!function_exists('getClientIpForVisit')) {
    function getClientIpForVisit(): string
    {
        $keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $value = trim((string)$_SERVER[$key]);

                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $parts = explode(',', $value);
                    return trim($parts[0] ?? '');
                }

                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('trackVisit')) {
    function trackVisit(mysqli $conn, string $page): void
    {
        try {
            if (!visitTableExists($conn)) {
                error_log('trackVisit skipped: user_visits table does not exist.');
                return;
            }

            $page = trim($page);
            if ($page === '') {
                $page = 'unknown';
            }

            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if (str_contains($scriptName, '/partial/')) {
                return;
            }

            $sessionId = session_id();
            if (!$sessionId) {
                return;
            }

            $userId = null;
            if (
                isset($_SESSION['user_id']) &&
                is_numeric($_SESSION['user_id']) &&
                (int)$_SESSION['user_id'] > 0
            ) {
                $userId = (int)$_SESSION['user_id'];
            }

            $role = isset($_SESSION['role']) ? (string)$_SESSION['role'] : 'guest';

            $ip = getClientIpForVisit();
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $ipHash = $ip !== '' ? hash('sha256', $ip) : null;
            $uaHash = $ua !== '' ? hash('sha256', $ua) : null;

            $page = substr($page, 0, 120);
            $role = substr($role, 0, 30);

            $checkStmt = $conn->prepare("
                SELECT id
                FROM user_visits
                WHERE session_id = ?
                  AND page = ?
                  AND visited_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                LIMIT 1
            ");

            if (!$checkStmt) {
                error_log('trackVisit check prepare failed: ' . $conn->error);
                return;
            }

            $checkStmt->bind_param('ss', $sessionId, $page);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if ($exists) {
                return;
            }

            /*
             * Use explicit NULL handling for user_id.
             * This avoids nullable integer binding issues.
             */
            if ($userId === null) {
                $insertStmt = $conn->prepare("
                    INSERT INTO user_visits (
                        user_id,
                        role,
                        session_id,
                        page,
                        ip_hash,
                        user_agent_hash,
                        visited_at
                    )
                    VALUES (
                        NULL,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        NOW()
                    )
                ");

                if (!$insertStmt) {
                    error_log('trackVisit guest insert prepare failed: ' . $conn->error);
                    return;
                }

                $insertStmt->bind_param(
                    'sssss',
                    $role,
                    $sessionId,
                    $page,
                    $ipHash,
                    $uaHash
                );
            } else {
                $insertStmt = $conn->prepare("
                    INSERT INTO user_visits (
                        user_id,
                        role,
                        session_id,
                        page,
                        ip_hash,
                        user_agent_hash,
                        visited_at
                    )
                    VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        NOW()
                    )
                ");

                if (!$insertStmt) {
                    error_log('trackVisit user insert prepare failed: ' . $conn->error);
                    return;
                }

                $insertStmt->bind_param(
                    'isssss',
                    $userId,
                    $role,
                    $sessionId,
                    $page,
                    $ipHash,
                    $uaHash
                );
            }

            if (!$insertStmt->execute()) {
                error_log('trackVisit execute failed: ' . $insertStmt->error);
            }

            $insertStmt->close();
        } catch (Throwable $e) {
            error_log('trackVisit fatal skipped: ' . $e->getMessage());
            return;
        }
    }
}