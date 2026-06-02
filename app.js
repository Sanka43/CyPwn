const cards = document.getElementById("cards");
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const resultText = document.getElementById("resultText");
const clearBtn = document.getElementById("clearBtn");
const modal = document.getElementById("appModal");
const modalBody = document.getElementById("modalBody");
const closeModal = document.getElementById("closeModal");

let tools = [];
let filtered = [];

function esc(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function buildCategories() {
  const categories = [...new Set(tools.map((t) => (t.category || "Other").trim()))].filter(Boolean).sort();
  for (const c of categories) {
    const option = document.createElement("option");
    option.value = c;
    option.textContent = c;
    categoryFilter.appendChild(option);
  }
}

function getFiltered() {
  const q = searchInput.value.trim().toLowerCase();
  const cat = categoryFilter.value;

  return tools.filter((tool) => {
    const name = (tool.name || "").toLowerCase();
    const sub = (tool.subtitle || "").toLowerCase();
    const dev = (tool.developer_name || "").toLowerCase();
    const category = (tool.category || "Other").trim();

    const searchOK = !q || name.includes(q) || sub.includes(q) || dev.includes(q);
    const categoryOK = cat === "all" || category === cat;

    return searchOK && categoryOK;
  });
}

function render() {
  filtered = getFiltered();
  const categoryTotal = new Set(filtered.map((tool) => (tool.category || "Other").trim())).size;
  resultText.textContent = `${filtered.length} items in ${categoryTotal} categories`;

  if (!filtered.length) {
    cards.innerHTML = '<article class="card"><p class="meta">No results found.</p></article>';
    return;
  }

  const grouped = new Map();
  filtered.forEach((tool, index) => {
    const category = (tool.category || "Other").trim() || "Other";
    if (!grouped.has(category)) {
      grouped.set(category, []);
    }
    grouped.get(category).push({ tool, index });
  });

  cards.innerHTML = [...grouped.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([categoryName, items]) => {
      const cardHtml = items.map(({ tool, index }) => {
        const icon = tool.iconURL ? `deta/${tool.iconURL}` : "";
        const fallback = esc((tool.name || "?").slice(0, 2).toUpperCase());

        return `
          <article class="card">
            <div class="card-head">
              ${icon
                ? `<img class="icon" src="${esc(icon)}" alt="${esc(tool.name)}" onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'icon fallback',textContent:'${fallback}'}))">`
                : `<div class="icon fallback">${fallback}</div>`}
              <div>
                <h4 class="name">${esc(tool.name || "Unknown")}</h4>
                <p class="meta">Version: ${esc(tool.version || "N/A")}</p>
                <p class="meta">Size: N/A</p>
                <p class="meta">Upload Date: ${esc(tool.version_date || "N/A")}</p>
              </div>
              <button class="eye-btn" data-index="${index}" type="button" aria-label="View details">DET</button>
            </div>
          </article>`;
      }).join("");

      return `
        <section class="category-section">
          <h3 class="category-label">${esc(categoryName)}</h3>
          <div class="cards category-cards">${cardHtml}</div>
        </section>
      `;
    }).join("");
}

function showModal(tool) {
  const icon = tool.iconURL ? `deta/${tool.iconURL}` : "";

  modalBody.innerHTML = `
    <div class="card-head" style="margin-bottom: 10px;">
      ${icon ? `<img class="icon" src="${esc(icon)}" alt="${esc(tool.name)}">` : `<div class="icon fallback">${esc((tool.name || "?").slice(0, 2).toUpperCase())}</div>`}
      <div>
        <h3 style="margin:0;">${esc(tool.name || "Unknown")}</h3>
        <p class="meta">${esc(tool.subtitle || "No subtitle")}</p>
      </div>
    </div>
    <p class="meta">Developer: ${esc(tool.developer_name || "Unknown")}</p>
    <p class="meta">Category: ${esc(tool.category || "Other")}</p>
    <p class="meta">Version: ${esc(tool.version || "N/A")} (${esc(tool.version_date || "N/A")})</p>
    <p class="modal-desc">${esc(tool.description || "No description available")}</p>
    <div class="modal-actions">
      <a href="${esc(tool.downloadURL || "#")}" target="_blank" rel="noopener">Download</a>
      <a href="${esc(tool.detailURL || "#")}" target="_blank" rel="noopener">Details</a>
    </div>
  `;

  if (typeof modal.showModal === "function") {
    modal.showModal();
  }
}

cards.addEventListener("click", (event) => {
  const btn = event.target.closest(".eye-btn");
  if (!btn) {
    return;
  }

  const index = Number(btn.dataset.index);
  if (Number.isInteger(index) && filtered[index]) {
    showModal(filtered[index]);
  }
});

searchInput.addEventListener("input", render);
categoryFilter.addEventListener("change", render);
clearBtn.addEventListener("click", () => {
  searchInput.value = "";
  categoryFilter.value = "all";
  render();
});

closeModal.addEventListener("click", () => modal.close());
modal.addEventListener("click", (event) => {
  if (event.target === modal) {
    modal.close();
  }
});

async function init() {
  try {
    const response = await fetch("deta/premium_ipas.json", { cache: "no-store" });
    if (!response.ok) {
      throw new Error("Unable to load JSON");
    }

    tools = await response.json();
    if (!Array.isArray(tools)) {
      tools = [];
    }

    buildCategories();
    render();
  } catch (error) {
    resultText.textContent = "Failed to load data";
    cards.innerHTML = '<article class="card"><p class="meta">JSON load failed. Check file path.</p></article>';
  }
}

init();
