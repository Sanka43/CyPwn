<?php
declare(strict_types=1);

require_once __DIR__ . '/json_store.php';

$counts = collectionCounts();
$premiumCount = (int)($counts['premium'] ?? 0);
$trollstoreCount = (int)($counts['trollstore'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CyPwn Admin Panel</title>
  <style>
    :root {
      --bg: #081427;
      --panel: #13213a;
      --panel-2: #1a2d4f;
      --line: #2f4875;
      --text: #eef4ff;
      --muted: #9eb0d3;
      --danger: #e65366;
      --ok: #2dad7b;
      --accent: #74a8ff;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      background: var(--bg);
      color: var(--text);
    }
    .container {
      width: min(1220px, 95%);
      margin: 24px auto;
      display: grid;
      grid-template-columns: 1.3fr 1fr;
      gap: 16px;
    }
    .panel {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 14px;
    }
    h1, h2, h3, p { margin: 0; }
    .top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      flex-wrap: wrap;
      gap: 8px;
    }
    .meta { color: var(--muted); font-size: 13px; }
    .toolbar {
      display: grid;
      grid-template-columns: 1fr 160px 120px;
      gap: 8px;
      margin: 10px 0 12px;
    }
    input, textarea, select, button {
      width: 100%;
      border: 1px solid var(--line);
      background: var(--panel-2);
      color: var(--text);
      border-radius: 7px;
      padding: 10px;
      font-size: 13px;
    }
    textarea { min-height: 90px; resize: vertical; }
    button { cursor: pointer; font-weight: 700; }
    .btn-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      margin-top: 10px;
    }
    .btn-danger { background: #4a1f29; border-color: #7b2f3f; }
    .btn-ok { background: #1f4a39; border-color: #2e7f61; }
    .small { font-size: 12px; color: var(--muted); margin-top: 8px; }
    .table-wrap {
      max-height: 70vh;
      overflow: auto;
      border: 1px solid var(--line);
      border-radius: 8px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 700px;
    }
    th, td {
      border-bottom: 1px solid #243a60;
      padding: 8px;
      text-align: left;
      font-size: 13px;
      vertical-align: top;
    }
    th { position: sticky; top: 0; background: #0e1b31; }
    tr.active { background: #1e3459; }
    .type-pill {
      border: 1px solid #4b6ea8;
      border-radius: 999px;
      padding: 2px 7px;
      font-size: 11px;
      text-transform: uppercase;
      display: inline-block;
    }
    .status {
      margin-top: 10px;
      min-height: 20px;
      font-size: 13px;
    }
    .status.ok { color: #6de2b4; }
    .status.err { color: #ff8f99; }
    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .steps {
      margin-top: 10px;
      padding: 10px;
      border: 1px solid #355585;
      border-radius: 8px;
      font-size: 12px;
      color: #b8caea;
      line-height: 1.5;
      background: #10203a;
    }
    a { color: var(--accent); }

    @media (max-width: 960px) {
      .container { grid-template-columns: 1fr; }
      .toolbar { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="container">
    <section class="panel">
      <div class="top">
        <div>
          <h1>CyPwn Tools Admin</h1>
          <p class="meta">JSON: premium <?= $premiumCount ?> · trollstore <?= $trollstoreCount ?></p>
        </div>
        <a href="index.html">Back to Library</a>
      </div>

      <div class="toolbar">
        <input id="searchInput" type="search" placeholder="Search by name, developer, category...">
        <select id="collectionSelect">
          <option value="premium">Premium IPAs</option>
          <option value="trollstore">TrollStore</option>
        </select>
        <button id="reloadBtn" type="button">Reload</button>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:70px;">ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Type</th>
              <th>Version</th>
            </tr>
          </thead>
          <tbody id="toolRows"></tbody>
        </table>
      </div>
      <p class="small" id="listMeta">Loading...</p>
      <?php if ($premiumCount === 0 && $trollstoreCount === 0): ?>
      <div class="steps">
        No data found. Add entries via this panel or place JSON files at
        <code>deta/premium_ipas.json</code> and <code>deta/trollstore_ipas.json</code>.
      </div>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2 id="formTitle">Create New Tool</h2>
      <p class="small">Select a row to edit or delete, or fill the form to create a new tool in the active collection.</p>

      <form id="toolForm">
        <input type="hidden" id="toolId">
        <div class="two-col">
          <input id="name" placeholder="Name *" required>
          <input id="developer_name" placeholder="Developer name">
        </div>
        <div class="two-col">
          <input id="subtitle" placeholder="Subtitle">
          <input id="category" placeholder="Category">
        </div>
        <div class="two-col">
          <input id="version" placeholder="Version">
          <input id="version_date" placeholder="Version date">
        </div>
        <textarea id="description" placeholder="Description"></textarea>
        <div class="two-col">
          <input id="iconURL" placeholder="iconURL (e.g. assets/icons/x.png)">
          <input id="icon_asset" placeholder="icon_asset (absolute path optional)">
        </div>
        <div class="two-col">
          <input id="detailURL" placeholder="detailURL (optional)">
          <input id="downloadURL" placeholder="downloadURL">
        </div>
        <div class="two-col">
          <input id="price" type="number" step="0.01" min="0" placeholder="Price">
          <select id="tool_type">
            <option value="free">Free</option>
            <option value="paid">Paid</option>
          </select>
        </div>
        <div class="two-col">
          <input id="screenshots" placeholder="screenshots (comma separated URLs)">
          <input id="screenshot_assets" placeholder="screenshot_assets (comma separated paths)">
        </div>

        <div class="btn-row">
          <button type="submit" class="btn-ok" id="saveBtn">Create</button>
          <button type="button" id="newBtn">New / Reset</button>
          <button type="button" class="btn-danger" id="deleteBtn">Delete</button>
        </div>
      </form>

      <div id="status" class="status"></div>
    </section>
  </div>

  <script src="admin.js"></script>
</body>
</html>
