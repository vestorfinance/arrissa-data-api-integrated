<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';
Auth::check();

$pdo = Database::getInstance()->getConnection();

// Pre-load all data for initial render
$cats = $pdo->query("
    SELECT tc.*, COUNT(t.id) AS tool_count
    FROM tool_categories tc
    LEFT JOIN tools t ON t.category_id = tc.id
    GROUP BY tc.id ORDER BY tc.id
")->fetchAll(PDO::FETCH_ASSOC);

$tools = $pdo->query("
    SELECT t.*, tc.name AS cat_name
    FROM tools t
    JOIN tool_categories tc ON tc.id = t.category_id
    ORDER BY tc.name, t.id
")->fetchAll(PDO::FETCH_ASSOC);

$totalTools  = count($tools);
$enabledCnt  = count(array_filter($tools, fn($t) => (int)$t['enabled'] === 1));
$totalCats   = count($cats);

$title = 'TMP Tool Management';
$page  = 'tmp-manage';
ob_start();
?>

<style>
/* ── Toggle switch ─────────────────────────── */
.ts-wrap { position:relative; display:inline-flex; align-items:center; width:42px; height:24px; cursor:pointer; }
.ts-wrap input { opacity:0; width:0; height:0; position:absolute; }
.ts-track { position:absolute; top:0; left:0; right:0; bottom:0; border-radius:24px; background:#404040; transition:.25s; }
.ts-thumb { position:absolute; left:3px; top:3px; width:18px; height:18px; border-radius:50%; background:#fff; transition:.25s; box-shadow:0 1px 3px rgba(0,0,0,.4); }
.ts-wrap input:checked ~ .ts-track { background:#10b981; }
.ts-wrap input:checked ~ .ts-thumb { transform:translateX(18px); }

/* ── Modal overlay ─────────────────────────── */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.75); z-index:1000; display:flex; align-items:center; justify-content:center; padding:20px; }
.modal-box { width:100%; max-width:680px; max-height:90vh; overflow-y:auto; border-radius:18px; padding:28px; background:var(--card-bg); border:1px solid var(--border); }

/* ── Toast ─────────────────────────────────── */
.tm-toast { position:fixed; bottom:24px; right:24px; z-index:9999; padding:12px 20px; border-radius:12px; font-size:13px; font-weight:500; display:flex; align-items:center; gap:10px; transform:translateY(80px); opacity:0; transition:.3s cubic-bezier(.4,0,.2,1); pointer-events:none; }
.tm-toast.show { transform:translateY(0); opacity:1; }
.tm-toast.ok  { background:#064e3b; color:#6ee7b7; border:1px solid #10b981; }
.tm-toast.err { background:#450a0a; color:#fca5a5; border:1px solid #ef4444; }

/* ── Data table ────────────────────────────── */
.dtable { width:100%; border-collapse:collapse; }
.dtable th { padding:10px 16px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); color:var(--text-secondary); white-space:nowrap; }
.dtable td { padding:11px 16px; font-size:13px; border-bottom:1px solid var(--border); color:var(--text-primary); vertical-align:middle; }
.dtable tbody tr:hover td { background:rgba(255,255,255,.025); }
.dtable tr.cat-divider td { background:rgba(79,70,229,.08); padding:7px 16px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:#818cf8; border-bottom:1px solid var(--border); }

/* ── Form inputs ────────────────────────────── */
.fi { width:100%; padding:9px 13px; border-radius:9px; font-size:13px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary); outline:none; transition:border-color .2s; font-family:inherit; }
.fi:focus { border-color:#6366f1; }
textarea.fi { resize:vertical; min-height:80px; }
select.fi { border-radius:0; border-bottom:1px solid var(--input-border); }
select.fi:focus { border-radius:0; border-color:#6366f1; }

/* ── Misc tags ──────────────────────────────── */
.badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; }
.badge-cat  { background:rgba(99,102,241,.14); color:#a5b4fc; }
.badge-on   { background:rgba(16,185,129,.14); color:#34d399; }
.badge-off  { background:rgba(239,68,68,.12);  color:#f87171; }

/* ── Btn icon ────────────────────────────────── */
.btn-edit   { display:inline-flex; align-items:center; gap:4px; padding:5px 12px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; background:rgba(79,70,229,.15); color:#a5b4fc; border:1px solid rgba(79,70,229,.3); transition:.15s; white-space:nowrap; }
.btn-edit:hover { background:rgba(79,70,229,.3); }
.btn-del    { display:inline-flex; align-items:center; justify-content:center; padding:5px 10px; border-radius:8px; font-size:12px; cursor:pointer; background:rgba(239,68,68,.1); color:#f87171; border:1px solid rgba(239,68,68,.25); transition:.15s; }
.btn-del:hover { background:rgba(239,68,68,.2); }
.btn-add    { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; background:#065f46; color:#6ee7b7; border:1px solid #10b981; transition:.15s; }
.btn-add:hover { background:#047857; }
</style>

<div class="p-6">

    <!-- ── Page Header ────────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3" style="color:var(--text-primary);">
                <i data-feather="settings" style="width:22px;height:22px;color:#6366f1;"></i>
                TMP Tool Management
            </h1>
            <p class="text-sm mt-1" style="color:var(--text-secondary);">Manage AI tool definitions, categories and availability</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <div class="px-4 py-2 rounded-xl text-sm" style="background:var(--card-bg);border:1px solid var(--border);color:var(--text-secondary);">
                <span id="stat-total" style="color:var(--text-primary);font-weight:700;"><?= $totalTools ?></span> Tools
            </div>
            <div class="px-4 py-2 rounded-xl text-sm" style="background:var(--card-bg);border:1px solid var(--border);color:var(--text-secondary);">
                <span id="stat-enabled" style="color:#34d399;font-weight:700;"><?= $enabledCnt ?></span> Active
                &nbsp;/&nbsp;
                <span id="stat-disabled" style="color:#f87171;font-weight:700;"><?= $totalTools - $enabledCnt ?></span> Off
            </div>
            <div class="px-4 py-2 rounded-xl text-sm" style="background:var(--card-bg);border:1px solid var(--border);color:var(--text-secondary);">
                <span id="stat-cats" style="color:#818cf8;font-weight:700;"><?= $totalCats ?></span> Categories
            </div>
        </div>
    </div>

    <!-- ── Tabs ────────────────────────────────────────────────────────────── -->
    <div class="flex gap-1 mb-5 p-1 rounded-full w-fit" style="background:var(--bg-secondary);border:1px solid var(--border);">
        <button id="tab-tools" onclick="switchTab('tools')" class="px-5 py-2 rounded-full text-sm font-semibold transition-all"
                style="background:var(--card-bg);color:var(--text-primary);border:1px solid var(--border);">
            <i data-feather="tool" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:5px;"></i>Tools <span id="tab-tools-count" class="ml-1 text-xs opacity-60">(<?= $totalTools ?>)</span>
        </button>
        <button id="tab-cats" onclick="switchTab('cats')" class="px-5 py-2 rounded-full text-sm font-semibold transition-all"
                style="color:var(--text-secondary);border:1px solid transparent;">
            <i data-feather="folder" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:5px;"></i>Categories <span id="tab-cats-count" class="ml-1 text-xs opacity-60">(<?= $totalCats ?>)</span>
        </button>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- TOOLS PANEL                                                         -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <div id="panel-tools">

        <!-- Toolbar -->
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <input type="text" id="tool-search" placeholder="Search by name or phrase…"
                   oninput="filterTools()" class="fi" style="max-width:280px;">
            <select id="cat-filter" onchange="filterTools()" class="fi" style="max-width:220px;">
                <option value="">All Categories</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex-1"></div>
            <button onclick="openAddTool()" class="btn-add">
                <i data-feather="plus" style="width:14px;height:14px;"></i> Add Tool
            </button>
        </div>

        <!-- Table -->
        <div class="rounded-2xl overflow-hidden" style="border:1px solid var(--border);background:var(--card-bg);">
            <div style="overflow-x:auto;">
                <table class="dtable">
                    <thead>
                        <tr>
                            <th style="width:44px;">#</th>
                            <th>Tool Name</th>
                            <th>Search Phrase</th>
                            <th>Category</th>
                            <th style="width:80px;">Enabled</th>
                            <th style="text-align:right;width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tools-tbody">
                        <?php
                        $prevCat = null;
                        $rowNum  = 0;
                        foreach ($tools as $t):
                            $rowNum++;
                            if ($t['cat_name'] !== $prevCat):
                                $prevCat = $t['cat_name'];
                        ?>
                        <tr class="cat-divider" data-cat-name="<?= htmlspecialchars($t['cat_name']) ?>">
                            <td colspan="6"><i data-feather="folder" style="width:11px;height:11px;display:inline;vertical-align:middle;margin-right:6px;opacity:.7;"></i><?= htmlspecialchars($t['cat_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr data-tool-id="<?= $t['id'] ?>"
                            data-cat="<?= htmlspecialchars($t['cat_name']) ?>"
                            data-name="<?= htmlspecialchars(strtolower($t['tool_name'])) ?>"
                            data-phrase="<?= htmlspecialchars(strtolower($t['search_phrase'] ?? '')) ?>">
                            <td style="color:var(--text-secondary);"><?= $rowNum ?></td>
                            <td>
                                <code style="font-size:12px;color:#a5b4fc;background:rgba(99,102,241,.08);padding:2px 8px;border-radius:6px;">
                                    <?= htmlspecialchars($t['tool_name']) ?>
                                </code>
                            </td>
                            <td style="color:var(--text-secondary);font-size:12px;">
                                <?= htmlspecialchars($t['search_phrase'] ?? '') ?>
                            </td>
                            <td>
                                <span class="badge badge-cat"><?= htmlspecialchars($t['cat_name']) ?></span>
                            </td>
                            <td>
                                <label class="ts-wrap" title="Click to toggle">
                                    <input type="checkbox" <?= $t['enabled'] ? 'checked' : '' ?>
                                           onchange="toggleTool(<?= $t['id'] ?>, this)">
                                    <div class="ts-track"></div>
                                    <div class="ts-thumb"></div>
                                </label>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <button class="btn-edit"
                                            onclick='openEditTool(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)'>
                                        <i data-feather="edit-2" style="width:12px;height:12px;"></i>Edit
                                    </button>
                                    <button class="btn-del"
                                            onclick="confirmDeleteTool(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['tool_name'])) ?>')">
                                        <i data-feather="trash-2" style="width:13px;height:13px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- CATEGORIES PANEL                                                    -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <div id="panel-cats" style="display:none;">

        <div class="flex items-center justify-end mb-4">
            <button onclick="openAddCat()" class="btn-add">
                <i data-feather="plus" style="width:14px;height:14px;"></i> Add Category
            </button>
        </div>

        <div class="rounded-2xl overflow-hidden" style="border:1px solid var(--border);background:var(--card-bg);">
            <div style="overflow-x:auto;">
                <table class="dtable">
                    <thead>
                        <tr>
                            <th style="width:44px;">#</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Endpoint Base</th>
                            <th style="width:100px;">EA Required</th>
                            <th>EA Name</th>
                            <th style="width:60px;">Tools</th>
                            <th style="text-align:right;width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cats-tbody">
                        <?php foreach ($cats as $idx => $c): ?>
                        <tr data-cat-id="<?= $c['id'] ?>">
                            <td style="color:var(--text-secondary);"><?= $idx + 1 ?></td>
                            <td>
                                <strong style="color:var(--text-primary);"><?= htmlspecialchars($c['name']) ?></strong>
                            </td>
                            <td style="color:var(--text-secondary);font-size:12px;max-width:220px;">
                                <?= htmlspecialchars($c['description'] ?? '—') ?>
                            </td>
                            <td style="font-size:11px;color:#a5b4fc;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="<?= htmlspecialchars($c['endpoint_base'] ?? '') ?>">
                                <?= htmlspecialchars($c['endpoint_base'] ?? '—') ?>
                            </td>
                            <td>
                                <?php if ((int)$c['requires_ea']): ?>
                                <span class="badge badge-on" style="display:inline-flex;align-items:center;gap:3px;"><i data-feather="check" style="width:10px;height:10px;"></i>Yes</span>
                                <?php else: ?>
                                <span class="badge badge-off">No</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px;color:var(--text-secondary);">
                                <?= htmlspecialchars($c['ea_name'] ?? '—') ?>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-cat"><?= (int)$c['tool_count'] ?></span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <button class="btn-edit"
                                            onclick='openEditCat(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)'>
                                        <i data-feather="edit-2" style="width:12px;height:12px;"></i>Edit
                                    </button>
                                    <button class="btn-del"
                                            onclick="confirmDeleteCat(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>', <?= (int)$c['tool_count'] ?>)">
                                        <i data-feather="trash-2" style="width:13px;height:13px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /p-6 -->

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!--  MODAL — Add / Edit Tool                                              -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div id="modal-tool" class="modal-overlay" style="display:none;" onclick="backdropClose(event,'modal-tool')">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <span id="modal-tool-icon" class="flex items-center" style="color:#6366f1;"></span>
                <h2 id="modal-tool-title" class="text-lg font-semibold" style="color:var(--text-primary);">Add Tool</h2>
            </div>
            <button onclick="closeModal('modal-tool')" style="color:var(--text-secondary);cursor:pointer;display:flex;align-items:center;padding:4px;border-radius:6px;background:none;border:none;">
                <i data-feather="x" style="width:18px;height:18px;"></i>
            </button>
        </div>
        <form id="form-tool" onsubmit="submitToolForm(event)">
            <input type="hidden" id="tool-edit-id" value="">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Tool Name *</label>
                    <input type="text" id="f-tool-name" class="fi" placeholder="e.g. close_profitable_positions" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Category *</label>
                    <select id="f-cat-id" class="fi" required>
                        <?php foreach ($cats as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Search Phrase *</label>
                <input type="text" id="f-search-phrase" class="fi" placeholder="e.g. close all profitable positions" required>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Tool Format URL *</label>
                <input type="text" id="f-tool-format" class="fi" placeholder="{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=..." required>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Inputs Explanation</label>
                <textarea id="f-inputs-exp" class="fi" style="min-height:100px;font-family:monospace;font-size:12px;"
                          placeholder="symbol = ALL  OR  a specific symbol&#10;volume = lot size  e.g. 0.01"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Description</label>
                <textarea id="f-description" class="fi" placeholder="What this tool does…"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Auth Method</label>
                    <input type="text" id="f-auth-method" class="fi" placeholder="api_key">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Response Type</label>
                    <input type="text" id="f-response-type" class="fi" placeholder="JSON">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('modal-tool')"
                        style="padding:9px 20px;border-radius:9px;font-size:13px;font-weight:500;background:var(--bg-secondary);color:var(--text-secondary);border:1px solid var(--border);cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" id="btn-tool-submit"
                        style="padding:9px 22px;border-radius:9px;font-size:13px;font-weight:600;background:#4f46e5;color:#fff;border:none;cursor:pointer;">
                    Save Tool
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!--  MODAL — Add / Edit Category                                          -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div id="modal-cat" class="modal-overlay" style="display:none;" onclick="backdropClose(event,'modal-cat')">
    <div class="modal-box" style="max-width:560px;">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <span id="modal-cat-icon" class="flex items-center" style="color:#6366f1;"></span>
                <h2 id="modal-cat-title" class="text-lg font-semibold" style="color:var(--text-primary);">Add Category</h2>
            </div>
            <button onclick="closeModal('modal-cat')" style="color:var(--text-secondary);cursor:pointer;display:flex;align-items:center;padding:4px;border-radius:6px;background:none;border:none;">
                <i data-feather="x" style="width:18px;height:18px;"></i>
            </button>
        </div>
        <form id="form-cat" onsubmit="submitCatForm(event)">
            <input type="hidden" id="cat-edit-id" value="">

            <div class="mb-4">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Category Name *</label>
                <input type="text" id="f-cat-name" class="fi" placeholder="e.g. orders" required>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Description</label>
                <textarea id="f-cat-desc" class="fi" placeholder="What this category covers…"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">Endpoint Base URL</label>
                <input type="text" id="f-cat-endpoint" class="fi"
                       placeholder="{base_url}/orders-api-v1/orders-api.php">
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--text-secondary);">EA Name</label>
                <input type="text" id="f-cat-ea" class="fi"
                       placeholder="e.g. Arrissa Data MT5 Orders API.ex5">
            </div>

            <div class="flex items-center gap-3 mb-5">
                <label class="ts-wrap">
                    <input type="checkbox" id="f-cat-requires-ea">
                    <div class="ts-track"></div>
                    <div class="ts-thumb"></div>
                </label>
                <span class="text-sm" style="color:var(--text-secondary);">Requires MT5 EA</span>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('modal-cat')"
                        style="padding:9px 20px;border-radius:9px;font-size:13px;font-weight:500;background:var(--bg-secondary);color:var(--text-secondary);border:1px solid var(--border);cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" id="btn-cat-submit"
                        style="padding:9px 22px;border-radius:9px;font-size:13px;font-weight:600;background:#4f46e5;color:#fff;border:none;cursor:pointer;">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!--  MODAL — Delete Confirmation                                          -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div id="modal-delete" class="modal-overlay" style="display:none;" onclick="backdropClose(event,'modal-delete')">
    <div class="modal-box" style="max-width:420px;text-align:center;">
        <div style="margin-bottom:16px;display:flex;justify-content:center;"><i data-feather="trash-2" style="width:42px;height:42px;color:#f87171;"></i></div>
        <h2 class="text-lg font-semibold mb-2" style="color:var(--text-primary);">Confirm Delete</h2>
        <p class="text-sm mb-1" style="color:var(--text-secondary);">
            You are about to delete <strong id="del-item-name" style="color:var(--text-primary);"></strong>.
        </p>
        <p id="del-extra-note" class="text-xs mb-5" style="color:#f87171;display:none;">
            <i data-feather="alert-triangle" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-right:4px;"></i>This will also delete all tools in this category!
        </p>
        <p id="del-no-note" class="text-xs mb-5" style="color:var(--text-secondary);">This cannot be undone.</p>
        <div class="flex gap-3">
            <button onclick="closeModal('modal-delete')"
                    style="flex:1;padding:10px;border-radius:9px;font-size:13px;font-weight:500;background:var(--bg-secondary);color:var(--text-secondary);border:1px solid var(--border);cursor:pointer;">
                Cancel
            </button>
            <button id="btn-del-confirm" onclick="executeDelete()"
                    style="flex:1;padding:10px;border-radius:9px;font-size:13px;font-weight:600;background:#7f1d1d;color:#fca5a5;border:1px solid #ef4444;cursor:pointer;">
                Delete
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="tm-toast" class="tm-toast"></div>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!--  JAVASCRIPT                                                           -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<script>
// ── State ──────────────────────────────────────────────────────────────────
let deleteCtx = null;   // {type:'tool'|'cat', id:N}
let enabledCount = <?= $enabledCnt ?>;
let totalCount   = <?= $totalTools ?>;

// ── Tab Switch ─────────────────────────────────────────────────────────────
function switchTab(tab) {
    const isTools = (tab === 'tools');
    document.getElementById('panel-tools').style.display = isTools ? '' : 'none';
    document.getElementById('panel-cats').style.display  = isTools ? 'none' : '';

    const tBtn  = document.getElementById('tab-tools');
    const cBtn  = document.getElementById('tab-cats');
    const onStyle  = 'background:var(--card-bg);color:var(--text-primary);border:1px solid var(--border);border-radius:9999px;';
    const offStyle = 'color:var(--text-secondary);border:1px solid transparent;border-radius:9999px;';
    tBtn.style.cssText += isTools  ? onStyle : offStyle;
    cBtn.style.cssText += !isTools ? onStyle : offStyle;
    feather.replace();
}

// ── Filter tools table ─────────────────────────────────────────────────────
function filterTools() {
    const q   = document.getElementById('tool-search').value.toLowerCase().trim();
    const cat = document.getElementById('cat-filter').value;
    const rows    = document.querySelectorAll('#tools-tbody tr[data-tool-id]');
    const dividers= document.querySelectorAll('#tools-tbody tr.cat-divider');
    const visibleCats = new Set();

    rows.forEach(r => {
        const nm = r.dataset.name   || '';
        const ph = r.dataset.phrase || '';
        const rc = r.dataset.cat    || '';
        const ok = (!q || nm.includes(q) || ph.includes(q)) && (!cat || rc === cat);
        r.style.display = ok ? '' : 'none';
        if (ok) visibleCats.add(rc);
    });

    dividers.forEach(d => {
        d.style.display = visibleCats.has(d.dataset.catName) ? '' : 'none';
    });
}

// ── Toggle enabled/disabled ────────────────────────────────────────────────
async function toggleTool(id, checkbox) {
    const prev = checkbox.checked;
    try {
        const res = await apiPost({ action: 'toggle_tool', id });
        const nowEnabled = res.enabled === 1;
        checkbox.checked = nowEnabled;
        enabledCount += nowEnabled ? 1 : -1;
        refreshStats();
        showToast(nowEnabled ? 'Tool enabled' : 'Tool disabled', nowEnabled ? 'ok' : 'err');
    } catch (e) {
        checkbox.checked = prev;
        showToast('Failed: ' + e.message, 'err');
    }
}

// ── Add Tool ───────────────────────────────────────────────────────────────
function openAddTool() {
    document.getElementById('modal-tool-title').textContent = 'Add Tool';
    document.getElementById('modal-tool-icon').innerHTML = feather.icons['plus'].toSvg({width:18,height:18});
    document.getElementById('tool-edit-id').value = '';
    document.getElementById('form-tool').reset();
    document.getElementById('f-auth-method').value   = 'api_key';
    document.getElementById('f-response-type').value = 'JSON';
    openModal('modal-tool');
}

// ── Edit Tool ──────────────────────────────────────────────────────────────
function openEditTool(t) {
    document.getElementById('modal-tool-title').textContent = 'Edit Tool';
    document.getElementById('modal-tool-icon').innerHTML = feather.icons['edit-2'].toSvg({width:18,height:18});
    document.getElementById('tool-edit-id').value       = t.id;
    document.getElementById('f-tool-name').value        = t.tool_name        || '';
    document.getElementById('f-cat-id').value           = t.category_id      || '';
    document.getElementById('f-search-phrase').value    = t.search_phrase    || '';
    document.getElementById('f-tool-format').value      = t.tool_format      || '';
    document.getElementById('f-inputs-exp').value       = t.inputs_explanation|| '';
    document.getElementById('f-description').value      = t.description      || '';
    document.getElementById('f-auth-method').value      = t.auth_method      || 'api_key';
    document.getElementById('f-response-type').value    = t.response_type    || 'JSON';
    openModal('modal-tool');
}

async function submitToolForm(e) {
    e.preventDefault();
    const id  = document.getElementById('tool-edit-id').value;
    const btn = document.getElementById('btn-tool-submit');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    try {
        await apiPost({
            action:             id ? 'update_tool' : 'add_tool',
            id:                 id ? parseInt(id) : undefined,
            tool_name:          document.getElementById('f-tool-name').value.trim(),
            category_id:        parseInt(document.getElementById('f-cat-id').value),
            search_phrase:      document.getElementById('f-search-phrase').value.trim(),
            tool_format:        document.getElementById('f-tool-format').value.trim(),
            inputs_explanation: document.getElementById('f-inputs-exp').value.trim(),
            description:        document.getElementById('f-description').value.trim(),
            auth_method:        document.getElementById('f-auth-method').value.trim(),
            response_type:      document.getElementById('f-response-type').value.trim(),
        });
        showToast(id ? 'Tool updated' : 'Tool added', 'ok');
        closeModal('modal-tool');
        if (!id) totalCount++;
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        showToast('Error: ' + err.message, 'err');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Save Tool';
    }
}

// ── Delete Tool ────────────────────────────────────────────────────────────
function confirmDeleteTool(id, name) {
    deleteCtx = { type: 'tool', id };
    document.getElementById('del-item-name').textContent = name;
    document.getElementById('del-extra-note').style.display = 'none';
    document.getElementById('del-no-note').style.display    = '';
    openModal('modal-delete');
}

// ── Add Category ───────────────────────────────────────────────────────────
function openAddCat() {
    document.getElementById('modal-cat-title').textContent = 'Add Category';
    document.getElementById('modal-cat-icon').innerHTML = feather.icons['plus'].toSvg({width:18,height:18});
    document.getElementById('cat-edit-id').value = '';
    document.getElementById('form-cat').reset();
    document.getElementById('f-cat-requires-ea').checked = false;
    openModal('modal-cat');
}

// ── Edit Category ──────────────────────────────────────────────────────────
function openEditCat(c) {
    document.getElementById('modal-cat-title').textContent = 'Edit Category';
    document.getElementById('modal-cat-icon').innerHTML = feather.icons['edit-2'].toSvg({width:18,height:18});
    document.getElementById('cat-edit-id').value           = c.id;
    document.getElementById('f-cat-name').value            = c.name          || '';
    document.getElementById('f-cat-desc').value            = c.description   || '';
    document.getElementById('f-cat-endpoint').value        = c.endpoint_base || '';
    document.getElementById('f-cat-ea').value              = c.ea_name       || '';
    document.getElementById('f-cat-requires-ea').checked   = !!parseInt(c.requires_ea || 0);
    openModal('modal-cat');
}

async function submitCatForm(e) {
    e.preventDefault();
    const id  = document.getElementById('cat-edit-id').value;
    const btn = document.getElementById('btn-cat-submit');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    try {
        await apiPost({
            action:        id ? 'update_category' : 'add_category',
            id:            id ? parseInt(id) : undefined,
            name:          document.getElementById('f-cat-name').value.trim(),
            description:   document.getElementById('f-cat-desc').value.trim(),
            endpoint_base: document.getElementById('f-cat-endpoint').value.trim(),
            ea_name:       document.getElementById('f-cat-ea').value.trim(),
            requires_ea:   document.getElementById('f-cat-requires-ea').checked ? 1 : 0,
        });
        showToast(id ? 'Category updated' : 'Category added', 'ok');
        closeModal('modal-cat');
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        showToast('Error: ' + err.message, 'err');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Save Category';
    }
}

// ── Delete Category ────────────────────────────────────────────────────────
function confirmDeleteCat(id, name, toolCount) {
    deleteCtx = { type: 'cat', id };
    document.getElementById('del-item-name').textContent = name;
    const hasTools = toolCount > 0;
    document.getElementById('del-extra-note').style.display = hasTools ? '' : 'none';
    document.getElementById('del-no-note').style.display    = hasTools ? 'none' : '';
    openModal('modal-delete');
}

// ── Execute Delete ─────────────────────────────────────────────────────────
async function executeDelete() {
    if (!deleteCtx) return;
    const btn = document.getElementById('btn-del-confirm');
    btn.disabled = true;
    btn.textContent = 'Deleting…';
    try {
        const action = deleteCtx.type === 'tool' ? 'delete_tool' : 'delete_category';
        await apiPost({ action, id: deleteCtx.id });
        showToast('Deleted successfully', 'ok');
        closeModal('modal-delete');
        setTimeout(() => location.reload(), 700);
    } catch (err) {
        showToast('Delete failed: ' + err.message, 'err');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Delete';
        deleteCtx = null;
    }
}

// ── API Helper ─────────────────────────────────────────────────────────────
async function apiPost(data) {
    const res = await fetch('/api/tmp-admin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    const json = await res.json();
    if (!res.ok || json.error) throw new Error(json.error || 'Request failed');
    return json;
}

// ── Modal helpers ──────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function backdropClose(e, id) { if (e.target.id === id) closeModal(id); }

// ── Stats update ───────────────────────────────────────────────────────────
function refreshStats() {
    document.getElementById('stat-total').textContent    = totalCount;
    document.getElementById('stat-enabled').textContent  = enabledCount;
    document.getElementById('stat-disabled').textContent = totalCount - enabledCount;
}

// ── Toast ──────────────────────────────────────────────────────────────────
let toastTimer;
function showToast(msg, type = 'ok') {
    const el = document.getElementById('tm-toast');
    el.textContent = msg;
    el.className = 'tm-toast ' + type + ' show';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 3000);
}

// Init
feather.replace();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
