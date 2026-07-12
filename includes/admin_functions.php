<?php

function is_admin($user_id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user && $user['role'] === 'admin';
}

function require_admin() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: /accounts/login/");
        exit;
    }
    if (!is_admin($_SESSION['user_id'])) {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>Access Denied</title><link rel='stylesheet' href='/style.css'></head><body>";
        echo "<div style='display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:20px;'>";
        echo "<div><h1>403 - Access Denied</h1><p style='color:var(--text-muted);'>You do not have permission to access this area.</p>";
        echo "<a href='/accounts/login/' class='btn btn-primary' style='margin-top:20px;display:inline-block;'>Go Back</a></div></div></body></html>";
        exit;
    }
}

function is_banned($user_id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT banned FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user && $user['banned'] == 1;
}

function get_ban_info($user_id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT banned, ban_reason, banned_at, banned_by FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function ban_user($user_id, $reason, $admin_id) {
    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE users SET banned = 1, ban_reason = ?, banned_at = NOW(), banned_by = ? WHERE id = ?");
    $stmt->bind_param("sis", $reason, $admin_id, $user_id);
    return $stmt->execute();
}

function unban_user($user_id) {
    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE users SET banned = 0, ban_reason = NULL, banned_at = NULL, banned_by = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

function get_user_count() {
    global $mysqli;
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
    return $result->fetch_assoc()['count'];
}

function get_banned_count() {
    global $mysqli;
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE banned = 1");
    return $result->fetch_assoc()['count'];
}

function get_unverified_count() {
    global $mysqli;
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE is_verified = 0");
    return $result->fetch_assoc()['count'];
}

function get_admin_count() {
    global $mysqli;
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    return $result->fetch_assoc()['count'];
}

function get_recent_users($limit = 10) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT id, username, email, role, banned, is_verified, created_at FROM users ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function search_users($search, $page = 1, $per_page = 20) {
    global $mysqli;
    $offset = ($page - 1) * $per_page;
    $search_param = "%$search%";
    $stmt = $mysqli->prepare("SELECT id, username, email, role, banned, ban_reason, is_verified, created_at FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $search_param, $search_param, $per_page, $offset);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $count_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM users WHERE username LIKE ? OR email LIKE ?");
    $count_stmt->bind_param("ss", $search_param, $search_param);
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['count'];

    return ['users' => $users, 'total' => $total, 'page' => $page, 'per_page' => $per_page];
}
