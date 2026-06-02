<?php
$tools = [];
$jsonPath = __DIR__ . '/deta/premium_ipas.json';
if (file_exists($jsonPath)) {
    $json = file_get_contents($jsonPath);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $tools = $decoded;
    }
}

$totalTools = count($tools);
$freeTools = 0;
$paidTools = 0;
$categories = [];

foreach ($tools as $tool) {
    $isFree = isset($tool['tool_type']) && strtolower((string) $tool['tool_type']) === 'free';
    if ($isFree) {
        $freeTools++;
    } else {
        $paidTools++;
    }

    if (!empty($tool['category'])) {
        $categories[(string) $tool['category']] = true;
    }
}

$categoryCount = count($categories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CyPwn</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="topbar">
    <div class="brand-wrap">
      <div class="logo">CP</div>
      <div>
        <h1>CyPwn</h1>
        <p>Premium IPA collection, clean home UI</p>
      </div>
    </div>
    <div style="display:flex; gap: 10px;">
      <a class="cta" href="#toolsSection">Browse Tools</a>
      <a class="cta" href="admin.php" style="background:#1d3256; border-color:#325284;">Admin Panel</a>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-content">
        <p class="badge">Inspired by CyPwn IPA Library</p>
        <h2>Fast access to tools, tweaks and modded apps</h2>
        <p class="hero-text">
          This homepage uses PHP + JSON as data source, and JavaScript to dynamically render tools with live search and category filtering.
        </p>
      </div>
      <div class="stats-grid">
        <article class="stat-card">
          <span>Total Tools</span>
          <strong><?= $totalTools ?></strong>
        </article>
        <article class="stat-card">
          <span>Free Tools</span>
          <strong><?= $freeTools ?></strong>
        </article>
        <article class="stat-card">
          <span>Paid Tools</span>
          <strong><?= $paidTools ?></strong>
        </article>
        <article class="stat-card">
          <span>Categories</span>
          <strong><?= $categoryCount ?></strong>
        </article>
      </div>
    </section>

    <section id="toolsSection" class="tools-panel">
      <div class="tools-header">
        <h3>Tool Collection</h3>
        <p id="resultsText">Loading tools...</p>
      </div>

      <div class="controls">
        <input id="searchInput" type="search" placeholder="Search by name, developer or category...">
        <select id="categoryFilter">
          <option value="all">All Categories</option>
        </select>
        <select id="typeFilter">
          <option value="all">All Types</option>
          <option value="free">Free</option>
          <option value="paid">Paid</option>
        </select>
      </div>

      <div id="toolGrid" class="tool-grid"></div>
    </section>
  </main>

  <dialog id="toolDialog" class="tool-dialog">
    <button id="closeDialog" class="close-btn" aria-label="Close">x</button>
    <div id="dialogContent"></div>
  </dialog>

  <script>
    window.__TOOLS__ = <?= json_encode($tools, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="app.js"></script>
</body>
</html>
