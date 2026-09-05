<?php
/**
 * Session-based authentication for BOTH the admin (teacher) and learners
 * (registered site visitors). The two identities live in the same PHP
 * session under different keys and never overlap.
 */

// =======================================================================
// Admin auth
// =======================================================================

function is_admin_logged_in(): bool
{
    if (empty($_SESSION['admin_id'])) {
        return false;
    }

    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > 8 * 3600) {
        admin_logout();
        return false;
    }

    $_SESSION['admin_last_activity'] = time();
    return true;
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect(base_url('/admin/index.php'));
    }
}

function current_admin(): ?array
{
    static $admin = null;
    static $loaded = false;

    if (!is_admin_logged_in()) {
        return null;
    }

    if (!$loaded) {
        $stmt = db()->prepare('SELECT id, username, display_name FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
        $loaded = true;
    }

    return $admin;
}

/**
 * @return array{ok:bool,error?:string}
 */
function admin_login_attempt(string $username, string $password): array
{
    $ip = client_ip();

    if (is_ip_locked_out($ip)) {
        return ['ok' => false, 'error' => 'Too many failed attempts from this location. Please try again in 15 minutes.'];
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $ok = $user && password_verify($password, $user['password_hash']);
    record_login_attempt($ip, (bool) $ok);

    if (!$ok) {
        return ['ok' => false, 'error' => 'Incorrect username or password.'];
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return ['ok' => true];
}

function admin_logout(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_last_activity']);
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function record_login_attempt(string $ip, bool $success): void
{
    $stmt = db()->prepare('INSERT INTO login_attempts (ip_address, success) VALUES (?, ?)');
    $stmt->execute([$ip, $success ? 1 : 0]);
}

function is_ip_locked_out(string $ip): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = ? AND success = 0 AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
    );
    $stmt->execute([$ip]);
    return (int) $stmt->fetchColumn() >= 5;
}

// =======================================================================
// Learner auth
// =======================================================================

function is_learner_logged_in(): bool
{
    return !empty($_SESSION['learner_id']);
}

function require_learner_login(string $redirectTo = '/my-progress.php'): void
{
    if (!is_learner_logged_in()) {
        flash_set('info', 'Please log in to view this page.');
        redirect(base_url('/login.php?redirect=' . urlencode($redirectTo)));
    }
}

function current_learner(): ?array
{
    static $learner = null;
    static $loaded = false;

    if (!is_learner_logged_in()) {
        return null;
    }

    if (!$loaded) {
        $stmt = db()->prepare('SELECT id, name, email, created_at FROM learners WHERE id = ?');
        $stmt->execute([$_SESSION['learner_id']]);
        $learner = $stmt->fetch() ?: null;
        $loaded = true;
    }

    return $learner;
}

/**
 * @return array{ok:bool,error?:string}
 */
function learner_login(string $email, string $password): array
{
    $stmt = db()->prepare('SELECT * FROM learners WHERE email = ?');
    $stmt->execute([$email]);
    $learner = $stmt->fetch();

    if (!$learner || !password_verify($password, $learner['password_hash'])) {
        return ['ok' => false, 'error' => 'Incorrect email or password.'];
    }

    session_regenerate_id(true);
    $_SESSION['learner_id'] = (int) $learner['id'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return ['ok' => true];
}

function learner_logout(): void
{
    unset($_SESSION['learner_id']);
}

/**
 * @return array{ok:bool,error?:string,id?:int}
 */
function learner_register(string $name, string $email, string $password): array
{
    $name = trim($name);
    $email = trim(mb_strtolower($email, 'UTF-8'));

    if ($name === '' || mb_strlen($name) > 150) {
        return ['ok' => false, 'error' => 'Please enter your name.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please enter a valid email address.'];
    }
    if (mb_strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Your password must be at least 6 characters.'];
    }

    $stmt = db()->prepare('SELECT id FROM learners WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'An account with that email already exists. Try logging in instead.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = db()->prepare('INSERT INTO learners (name, email, password_hash, email_verified) VALUES (?, ?, ?, 0)');
    $stmt->execute([$name, $email, $hash]);
    $id = (int) db()->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['learner_id'] = $id;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return ['ok' => true, 'id' => $id];
}

function create_password_reset_token(int $learnerId): string
{
    $token = bin2hex(random_bytes(32));
    $stmt = db()->prepare(
        'INSERT INTO password_resets (learner_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
    );
    $stmt->execute([$learnerId, hash('sha256', $token)]);
    return $token;
}

function verify_password_reset_token(string $token): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()'
    );
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetch() ?: null;
}

function consume_password_reset_token(int $resetId, int $learnerId, string $newPassword): void
{
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $db = db();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('UPDATE learners SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $learnerId]);

        $stmt = $db->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
        $stmt->execute([$resetId]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}
