const cards = document.getElementById("cards");
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const resultText = document.getElementById("resultText");
const clearBtn = document.getElementById("clearBtn");
const modal = document.getElementById("appModal");
const modalBody = document.getElementById("modalBody");
const closeModal = document.getElementById("closeModal");
const tabButtons = document.querySelectorAll(".tabs .tab");

const COLLECTIONS = {
  premium: { label: "Premium IPAs", param: "premium" },
  trollstore: { label: "TrollStore", param: "trollstore" },
};

let activeCollection = "premium";
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

function iconSrc(tool) {
  if (!tool.iconURL) {
    return "";
  }
  return `deta/${String(tool.iconURL).replace(/^\/+/, "")}`;
}

function screenshotSrc(path) {
  if (!path) {
    return "";
  }
  const clean = String(path).replace(/^\/+/, "");
  return clean.startsWith("deta/") ? clean : `deta/${clean}`;
}

function buildCategories() {
  categoryFilter.innerHTML = '<option value="all">All Categories</option>';
  const categories = [...new Set(tools.map((t) => (t.category || "Other").trim()))]
    .filter(Boolean)
    .sort();
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
  const label = COLLECTIONS[activeCollection]?.label || "Tools";
  resultText.textContent = `${filtered.length} ${label} in ${categoryTotal} categories`;

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
      const cardHtml = items
        .map(({ tool, index }) => {
          const icon = iconSrc(tool);
          const fallback = esc((tool.name || "?").slice(0, 2).toUpperCase());

          return `
          <article class="card">
            <div class="card-head">
              ${
                icon
                  ? `<img class="icon" src="${esc(icon)}" alt="${esc(tool.name)}" onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'icon fallback',textContent:'${fallback}'}))">`
                  : `<div class="icon fallback">${fallback}</div>`
              }
              <div>
                <h4 class="name">${esc(tool.name || "Unknown")}</h4>
                <p class="meta">Version: ${esc(tool.version || "N/A")}</p>
                <p class="meta">Upload Date: ${esc(tool.version_date || "N/A")}</p>
              </div>
              <button class="eye-btn" data-index="${index}" type="button" aria-label="View details">DET</button>
            </div>
          </article>`;
        })
        .join("");

      return `
        <section class="category-section">
          <h3 class="category-label">${esc(categoryName)}</h3>
          <div class="cards category-cards">${cardHtml}</div>
        </section>
      `;
    })
    .join("");
}

function showModal(tool) {
  const icon = iconSrc(tool);
  const shots = Array.isArray(tool.screenshots) ? tool.screenshots : [];
  const screenshotsHtml = shots.length
    ? `<div class="modal-shots">${shots
        .map(
          (shot) =>
            `<img src="${esc(screenshotSrc(shot))}" alt="${esc(tool.name)} screenshot" loading="lazy">`
        )
        .join("")}</div>`
    : "";

  modalBody.innerHTML = `
    <div class="card-head" style="margin-bottom: 10px;">
      ${
        icon
          ? `<img class="icon" src="${esc(icon)}" alt="${esc(tool.name)}">`
          : `<div class="icon fallback">${esc((tool.name || "?").slice(0, 2).toUpperCase())}</div>`
      }
      <div>
        <h3 style="margin:0;">${esc(tool.name || "Unknown")}</h3>
        <p class="meta">${esc(tool.subtitle || "No subtitle")}</p>
      </div>
    </div>
    <p class="meta">Developer: ${esc(tool.developer_name || "Unknown")}</p>
    <p class="meta">Category: ${esc(tool.category || "Other")}</p>
    <p class="meta">Version: ${esc(tool.version || "N/A")} (${esc(tool.version_date || "N/A")})</p>
    <p class="modal-desc">${esc(tool.description || "No description available")}</p>
    ${screenshotsHtml}
    <div class="modal-actions">
      ${
        tool.detailURL
          ? `<a href="${esc(tool.detailURL)}" target="_blank" rel="noopener">More info</a>`
          : ""
      }
      <a href="${esc(tool.downloadURL || "#")}" target="_blank" rel="noopener">Download</a>
    </div>
  `;

  if (typeof modal.showModal === "function") {
    modal.showModal();
  }
}

function setActiveCollection(collection) {
  activeCollection = collection;
  tabButtons.forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.collection === collection);
  });
}

async function loadCollection(collection) {
  setActiveCollection(collection);
  searchInput.value = "";
  categoryFilter.value = "all";
  resultText.textContent = "Loading...";
  cards.innerHTML = "";

  try {
    const response = await fetch(
      `admin_api.php?collection=${encodeURIComponent(COLLECTIONS[collection].param)}`,
      { cache: "no-store" }
    );
    const payload = await response.json();

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || "Unable to load tools");
    }

    tools = Array.isArray(payload.tools) ? payload.tools : [];
    buildCategories();
    render();
  } catch (error) {
    resultText.textContent = "Failed to load data";
    cards.innerHTML = `<article class="card"><p class="meta">${esc(error.message || "Load failed")}</p></article>`;
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

tabButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const collection = btn.dataset.collection;
    if (collection && collection !== activeCollection) {
      loadCollection(collection);
    }
  });
});

loadCollection("premium");
