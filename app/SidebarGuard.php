<?php
/**
 * SidebarGuard
 *
 * Renders the tamper-proof "Other Products" external sidebar link.
 * If the link URL or label is altered in any way the application will
 * halt with a 503 — Service Unavailable response.
 *
 * Usage (called inside app.php):
 *   SidebarGuard::verify();   // early integrity check
 *   SidebarGuard::render();   // output the sidebar link
 *   SidebarGuard::seal();     // regenerate LINK_HASH after an authorised change
 */
class SidebarGuard
{
    // ─── Protected link HTML ──────────────────────────────────────────────────
    // DO NOT EDIT — any change will break the integrity check and halt the app.
    private const LINK_CONTENT = '<a href="https://flowbase.store" target="_blank" rel="noopener" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: #fff; background: var(--accent);"><i data-feather="shopping-bag" style="width: 20px; height: 20px;"></i><span class="text-base font-medium">Other Products</span></a>';

    // ─── Tamper-evident hash ──────────────────────────────────────────────────
    // sha256( LINK_CONTENT + private salt ) — regenerate with SidebarGuard::seal()
    // DO NOT EDIT this constant unless you are resealing after an authorised change.
    private const LINK_HASH = '3d81a7ed78784eebf14b3269c1a0b023df92083e66c52c414d1a07bca52650ca';

    // ─── Private salt (buried — changing this also invalidates the hash) ─────
    private static function salt(): string
    {
        return hash('sha256', 'ArrissaPtyLtd.NJiji.2026.flowbase.store.sidebar.v1');
    }

    // ─── Compute the expected hash ────────────────────────────────────────────
    private static function expected(): string
    {
        return hash('sha256', self::LINK_CONTENT . self::salt());
    }

    // ─── Verify integrity ─────────────────────────────────────────────────────
    public static function verify(): void
    {
        if (!hash_equals(self::expected(), self::LINK_HASH)) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            die('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>503</title>'
              . '<style>*{margin:0;padding:0;box-sizing:border-box}body{background:#0f0f0f;color:#a0a0a0;'
              . 'font-family:monospace;display:flex;align-items:center;justify-content:center;height:100vh;}'
              . 'h2{color:#ef4444;margin-bottom:8px;font-size:1.2rem;}</style></head>'
              . '<body><div style="text-align:center">'
              . '<h2>503 — Service Unavailable</h2>'
              . '<p>Application integrity check failed. Contact the administrator.</p>'
              . '</div></body></html>');
        }
    }

    // ─── Render the link ──────────────────────────────────────────────────────
    public static function render(): void
    {
        self::verify();
        echo self::LINK_CONTENT;
    }

    // ─── Seal: regenerate the hash after an authorised content change ─────────
    // Run: php -r "require 'app/SidebarGuard.php'; echo SidebarGuard::seal();"
    // Then paste the output into LINK_HASH above.
    public static function seal(): string
    {
        return hash('sha256', self::LINK_CONTENT . self::salt());
    }
}
