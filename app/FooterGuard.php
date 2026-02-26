<?php
/**
 * FooterGuard
 *
 * Renders the sitewide Arrissa Data footer and verifies it has not been
 * tampered with. If the footer content is altered in any way the application
 * will halt with a 503 — Service Unavailable response.
 *
 * Usage (called inside app.php):
 *   FooterGuard::verify();   // early integrity check
 *   FooterGuard::render();   // output the footer
 */
class FooterGuard
{
    // ─── Protected footer HTML ────────────────────────────────────────────────
    // DO NOT EDIT — any change will break the integrity check and halt the app.
    private const FOOTER_CONTENT = <<<'HTML'
<footer id="arrissa-footer" style="border-top:1px solid var(--border);background:var(--bg-primary);padding:16px 32px;flex-shrink:0;">
    <div style="display:flex;flex-direction:column;gap:7px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span style="font-size:11px;color:var(--text-secondary);line-height:1.6;">
                &copy; 2026 <strong style="color:var(--text-primary);">Arrissa Data</strong> &middot; v1.0
                &nbsp;&middot;&nbsp; Powered by <a href="https://arrissa.trade" target="_blank" rel="noopener" style="color:var(--accent);text-decoration:none;">Arrissa</a>
                &nbsp;&middot;&nbsp; <a href="https://arrissacapital.com" target="_blank" rel="noopener" style="color:var(--accent);text-decoration:none;">arrissacapital.com</a>
                &nbsp;&middot;&nbsp; Created by <strong style="color:var(--text-primary);">Arrissa Pty Ltd</strong>
                &nbsp;&middot;&nbsp; <strong style="color:var(--text-primary);">Ngonidzashe Jiji (David Richchild)</strong>
            </span>
            <span style="display:flex;align-items:center;gap:14px;">
                <a href="https://instagram.com/davidrichchild" target="_blank" rel="noopener" title="Instagram" style="color:var(--text-secondary);display:inline-flex;align-items:center;transition:color .15s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'"><i data-feather="instagram" style="width:14px;height:14px;"></i></a>
                <a href="https://t.me/real_davidrichchild" target="_blank" rel="noopener" title="Telegram" style="color:var(--text-secondary);display:inline-flex;align-items:center;transition:color .15s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'"><i data-feather="send" style="width:14px;height:14px;"></i></a>
                <a href="https://youtube.com/@davidrichchild" target="_blank" rel="noopener" title="YouTube" style="color:var(--text-secondary);display:inline-flex;align-items:center;transition:color .15s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'"><i data-feather="youtube" style="width:14px;height:14px;"></i></a>
                <a href="https://arrissacapital.com" target="_blank" rel="noopener" title="Website" style="color:var(--text-secondary);display:inline-flex;align-items:center;transition:color .15s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'"><i data-feather="globe" style="width:14px;height:14px;"></i></a>
            </span>
        </div>
        <p style="font-size:10px;color:var(--text-secondary);line-height:1.55;opacity:.6;margin:0;">
            <strong style="color:var(--text-primary);">Disclaimer:</strong> This software is provided &quot;as is&quot; without warranty of any kind, express or implied, including but not limited to merchantability, fitness for a particular purpose, or non-infringement. It is not guaranteed to work for any specific purpose. Neither Arrissa Pty Ltd, Ngonidzashe Jiji (David Richchild), nor any of their affiliates, partners, or associates shall be held liable or accountable for any losses, damages, financial loss, or adverse outcomes of any nature arising from the use of, or reliance upon, this software. You may not redistribute, resell, or re-license this software without explicit written accreditation and permission from the author. By using this software you unconditionally agree to these terms.
        </p>
    </div>
</footer>
HTML;

    // ─── Tamper-evident hash ──────────────────────────────────────────────────
    // sha256( FOOTER_CONTENT + private salt ) — regenerate with FooterGuard::seal()
    // DO NOT EDIT this constant unless you are resealing after an authorised change.
    private const FOOTER_HASH = 'a0d8a7d743a095413808c4bb3d9f58160ce534b073f1178c82259edc2067c1db';

    // ─── Private salt (buried — changing this also invalidates the hash) ─────
    private static function salt(): string
    {
        return hash('sha256', 'ArrissaPtyLtd.NJiji.2026.arrissa.trade.footer.v1');
    }

    // ─── Compute the expected hash ────────────────────────────────────────────
    private static function expected(): string
    {
        return hash('sha256', self::FOOTER_CONTENT . self::salt());
    }

    // ─── Verify integrity ─────────────────────────────────────────────────────
    public static function verify(): void
    {
        if (!hash_equals(self::expected(), self::FOOTER_HASH)) {
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

    // ─── Render the footer ────────────────────────────────────────────────────
    public static function render(): void
    {
        self::verify();
        echo self::FOOTER_CONTENT;
    }

    // ─── Seal helper (run once after authorised edits to regenerate hash) ─────
    // Usage from CLI:  php -r "require 'app/FooterGuard.php'; echo FooterGuard::seal();"
    // Copy the output and replace the FOOTER_HASH constant above.
    public static function seal(): string
    {
        return self::expected();
    }
}
