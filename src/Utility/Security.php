<?php

namespace App\Utility;

/**
 * Security utility — CSRF protection & rate limiting.
 */
class Security
{
    // ── CSRF ─────────────────────────────────────────────────────────────

    /**
     * Kembalikan (dan buat jika perlu) CSRF token dalam session.
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verifikasi CSRF token.
     * Cek header X-CSRF-Token terlebih dahulu (AJAX), lalu field _csrf (form).
     * Jika gagal → kirim 403 dan hentikan eksekusi.
     */
    public static function verifyCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? ($_POST['_csrf'] ?? null);

        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'CSRF token tidak valid atau kadaluarsa.',
            ]);
            exit();
        }
    }

    // ── Rate Limiting ─────────────────────────────────────────────────────

    /**
     * Rate limiter berbasis session (cocok untuk endpoint per-user/IP).
     *
     * @param string $key          Identifikasi endpoint, misal 'login' atau 'rating'
     * @param int    $maxAttempts  Maksimal request dalam window
     * @param int    $windowSec   Durasi window dalam detik
     */
    public static function rateLimit(string $key, int $maxAttempts = 10, int $windowSec = 60): void
    {
        $now = time();
        $rl  = &$_SESSION['_rl'][$key] ?? null;

        if ($rl === null || ($now - $rl['start']) > $windowSec) {
            $_SESSION['_rl'][$key] = ['count' => 1, 'start' => $now];
            return;
        }

        $_SESSION['_rl'][$key]['count']++;

        if ($_SESSION['_rl'][$key]['count'] > $maxAttempts) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
            ]);
            exit();
        }
    }
}
