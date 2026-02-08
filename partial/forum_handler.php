<?php
// partial/forum_handler.php
require_once 'db_conn.php';
session_start();
header('Content-Type: application/json');
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
$is_admin = ($user_role === 'admin');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function query($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

switch ($action) {
case 'get_threads':
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $offset = (int)($_GET['offset'] ?? 0);
    $limit = 10;
    $where = ['t.is_closed = 0'];
    $params = [];
    
    if ($filter !== 'all') {
        // Normalize both sides: remove spaces for comparison
        $normalizedFilter = str_replace(' ', '', $filter);
        $where[] = "REPLACE(t.category, ' ', '') = ?";
        $params[] = $normalizedFilter;
    }
    
    if ($search) {
        $where[] = "(t.title LIKE ? OR t.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereSql = implode(' AND ', $where);
    $sql = "SELECT
                t.*,
                u.full_name,
                u.profile_image,
                (SELECT COUNT(*) FROM forum_replies r WHERE r.thread_id = t.id) as replies,
                (SELECT COUNT(*) FROM forum_likes l WHERE l.thread_id = t.id) as likes_count
            FROM forum_threads t
            JOIN users u ON t.user_id = u.id
            WHERE $whereSql
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $result = query($sql, $params);
    $threads = [];
    while ($row = $result->fetch_assoc()) {
        $row['liked'] = false;
        if ($user_id) {
            $like = $conn->query("SELECT 1 FROM forum_likes WHERE thread_id = {$row['id']} AND user_id = $user_id")->fetch_row();
            if ($like) $row['liked'] = true;
        }
        $threads[] = $row;
    }
    echo json_encode($threads);
    break;

case 'view_thread':
    $thread_id = (int)$_GET['id'];
    
    // Fetch thread with profile_image
    $thread = $conn->query("
        SELECT t.*, u.full_name, u.profile_image 
        FROM forum_threads t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = $thread_id
    ")->fetch_assoc();
    
    if (!$thread) {
        echo json_encode(['error' => 'Thread not found']);
        exit;
    }
    
    // Increment view count (only if not owner and not admin)
    if ($user_id && $user_id != $thread['user_id'] && !$is_admin) {
        $conn->query("UPDATE forum_threads SET views = views + 1 WHERE id = $thread_id");
    }
    
    // Fetch replies with profile_image
    $replies = $conn->query("
        SELECT r.*, u.full_name, u.profile_image 
        FROM forum_replies r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.thread_id = $thread_id 
        ORDER BY r.created_at
    ")->fetch_all(MYSQLI_ASSOC);
    
    $liked = $conn->query("SELECT 1 FROM forum_likes WHERE thread_id = $thread_id AND user_id = $user_id")->fetch_row();
    
    echo json_encode([
        'thread'  => array_merge($thread, ['liked' => (bool)$liked]),
        'replies' => $replies
    ]);
    break;

case 'create_thread':
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = $_POST['category'];
    
    if (!$title || !$content || !$category) {
        echo json_encode(['error' => 'All fields required']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO forum_threads (title, content, category, user_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sssi', $title, $content, $category, $user_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'thread_id' => $stmt->insert_id]);
    break;

case 'update_thread':
    $thread_id = (int)$_POST['thread_id'];
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? '';
    
    if (!$thread_id || !$title || !$content || !$category) {
        echo json_encode(['error' => 'All fields are required.']);
        exit;
    }
    
    // Validate thread owner
    $stmt = $conn->prepare("SELECT user_id, is_closed, is_flagged FROM forum_threads WHERE id = ?");
    $stmt->bind_param("i", $thread_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        echo json_encode(['error' => 'Thread not found']);
        exit;
    }
    
    if ($result['is_flagged'] == 1) {
        echo json_encode(['error' => 'Flagged threads cannot be edited.']);
        exit;
    }
    
    if ($result['is_closed'] == 1) {
        echo json_encode(['error' => 'Closed threads cannot be edited.']);
        exit;
    }
    
    if ($result['user_id'] != $user_id) {
        echo json_encode(['error' => 'You are not allowed to edit this thread.']);
        exit;
    }
    
    // Update thread
    $stmt = $conn->prepare("UPDATE forum_threads SET title = ?, content = ?, category = ? WHERE id = ?");
    $stmt->bind_param("sssi", $title, $content, $category, $thread_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    break;

case 'delete_thread':
    $thread_id = (int)$_POST['id'];
    $check = $conn->query("SELECT user_id FROM forum_threads WHERE id = $thread_id")->fetch_assoc();
    
    if (!$check || $check['user_id'] != $user_id) {
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }
    
    // Mark as closed (soft delete)
    $conn->query("UPDATE forum_threads SET is_closed = 1 WHERE id = $thread_id");
    echo json_encode(['success' => true]);
    break;

case 'flag_thread':
    $thread_id = (int)$_POST['thread_id'];
    $flag = (int)$_POST['flag'];
    
    if (!$user_id) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    
    // Get thread owner
    $thread = $conn->query("SELECT user_id FROM forum_threads WHERE id = $thread_id")->fetch_assoc();
    if (!$thread) {
        echo json_encode(['error' => 'Thread not found']);
        exit;
    }
    
    // Users cannot flag their own posts
    if ($thread['user_id'] == $user_id) {
        echo json_encode(['error' => 'You cannot flag your own post']);
        exit;
    }
    
    // Only admins can unflag posts
    if (!$is_admin && $flag == 0) {
        echo json_encode(['error' => 'Only admins can unflag posts']);
        exit;
    }
    
    // Update flag status
    $conn->query("UPDATE forum_threads SET is_flagged = $flag WHERE id = $thread_id");
    echo json_encode(['success' => true]);
    break;

case 'toggle_close':
    if (!$is_admin) { 
        echo json_encode(['error' => 'Unauthorized']); 
        exit; 
    }
    $thread_id = (int)$_POST['thread_id'];
    $close = (int)$_POST['close'];
    $conn->query("UPDATE forum_threads SET is_closed = $close WHERE id = $thread_id");
    echo json_encode(['success' => true]);
    break;

case 'like_thread':
    $thread_id = (int)$_POST['thread_id'];
    
    if (!$user_id) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    
    $check = $conn->query("SELECT 1 FROM forum_likes WHERE thread_id = $thread_id AND user_id = $user_id")->fetch_row();
    if ($check) {
        // Unlike
        $conn->query("DELETE FROM forum_likes WHERE thread_id = $thread_id AND user_id = $user_id");
        $conn->query("UPDATE forum_threads SET likes = likes - 1 WHERE id = $thread_id");
        $liked = false;
    } else {
        // Like
        $conn->query("INSERT INTO forum_likes (thread_id, user_id) VALUES ($thread_id, $user_id)");
        $conn->query("UPDATE forum_threads SET likes = likes + 1 WHERE id = $thread_id");
        $liked = true;
    }
    
    $new_count = $conn->query("SELECT likes FROM forum_threads WHERE id = $thread_id")->fetch_row()[0];
    echo json_encode(['liked' => $liked, 'likes' => (int)$new_count]);
    break;

case 'reply':
    $thread_id = (int)$_POST['thread_id'];
    $content = trim($_POST['content']);
    
    if (!$content) {
        echo json_encode(['error' => 'Reply required']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO forum_replies (thread_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $thread_id, $user_id, $content);
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

case 'delete_reply':
    $reply_id = (int)$_POST['id'];
    $check = $conn->query("SELECT user_id FROM forum_replies WHERE id = $reply_id")->fetch_assoc();
    
    if (!$check || ($check['user_id'] != $user_id && !$is_admin)) {
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }
    
    $conn->query("DELETE FROM forum_replies WHERE id = $reply_id");
    echo json_encode(['success' => true]);
    break;

case 'get_threads_admin':
    if (!$is_admin) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $offset = (int)($_GET['offset'] ?? 0);
    $limit = 15;
    $where = [];
    $params = [];
    $types = '';
    
    if ($filter === 'flagged') {
        $where[] = "t.is_flagged = 1";
    } elseif ($filter === 'closed') {
        $where[] = "t.is_closed = 1";
    } elseif ($filter !== 'all') {
        $where[] = "t.category = ?";
        $params[] = $filter;
        $types .= 's';
    }
    
    if ($search) {
        $where[] = "(t.title LIKE ? OR t.content LIKE ? OR u.full_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'sss';
    }
    
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT 
                t.*, 
                u.full_name,
                u.profile_image,
                (SELECT COUNT(*) FROM forum_replies r WHERE r.thread_id = t.id) as replies,
                (SELECT COUNT(*) FROM forum_likes l WHERE l.thread_id = t.id) as likes_count
            FROM forum_threads t
            JOIN users u ON t.user_id = u.id
            $whereSql
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $threads = [];
    while ($row = $result->fetch_assoc()) {
        $threads[] = $row;
    }
    
    echo json_encode($threads);
    break;

default:
    echo json_encode(['error' => 'Invalid action']);
}
?>