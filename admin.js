const apiUrl = "admin_api.php";

const searchInput = document.getElementById("searchInput");
const reloadBtn = document.getElementById("reloadBtn");
const toolRows = document.getElementById("toolRows");
const listMeta = document.getElementById("listMeta");
const statusBox = document.getElementById("status");
const form = document.getElementById("toolForm");
const formTitle = document.getElementById("formTitle");
const saveBtn = document.getElementById("saveBtn");
const newBtn = document.getElementById("newBtn");
const deleteBtn = document.getElementById("deleteBtn");

const fields = [
  "toolId",
  "name",
  "developer_name",
  "subtitle",
  "category",
  "version",
  "version_date",
  "description",
  "iconURL",
  "icon_asset",
  "downloadURL",
  "price",
  "tool_type",
  "screenshots",
  "screenshot_assets",
];

const state = {
  tools: [],
  selectedId: null,
};

function esc(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function setStatus(message, isError = false) {
  statusBox.textContent = message;
  statusBox.className = `status ${isError ? "err" : "ok"}`;
}

function clearStatus() {
  statusBox.textContent = "";
  statusBox.className = "status";
}

function parseCommaList(value) {
  return String(value || "")
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

function readFormTool() {
  return {
    name: document.getElementById("name").value.trim(),
    developer_name: document.getElementById("developer_name").value.trim(),
    subtitle: document.getElementById("subtitle").value.trim(),
    category: document.getElementById("category").value.trim(),
    version: document.getElementById("version").value.trim(),
    version_date: document.getElementById("version_date").value.trim(),
    description: document.getElementById("description").value.trim(),
    iconURL: document.getElementById("iconURL").value.trim(),
    icon_asset: document.getElementById("icon_asset").value.trim(),
    downloadURL: document.getElementById("downloadURL").value.trim(),
    price: Number(document.getElementById("price").value || 0),
    tool_type: document.getElementById("tool_type").value,
    screenshots: parseCommaList(document.getElementById("screenshots").value),
    screenshot_assets: parseCommaList(document.getElementById("screenshot_assets").value),
  };
}

function resetForm() {
  fields.forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (id === "tool_type") {
      el.value = "free";
    } else {
      el.value = "";
    }
  });
  state.selectedId = null;
  formTitle.textContent = "Create New Tool";
  saveBtn.textContent = "Create";
  renderRows();
  clearStatus();
}

function fillForm(tool) {
  document.getElementById("toolId").value = String(tool._id);
  document.getElementById("name").value = tool.name || "";
  document.getElementById("developer_name").value = tool.developer_name || "";
  document.getElementById("subtitle").value = tool.subtitle || "";
  document.getElementById("category").value = tool.category || "";
  document.getElementById("version").value = tool.version || "";
  document.getElementById("version_date").value = tool.version_date || "";
  document.getElementById("description").value = tool.description || "";
  document.getElementById("iconURL").value = tool.iconURL || "";
  document.getElementById("icon_asset").value = tool.icon_asset || "";
  document.getElementById("downloadURL").value = tool.downloadURL || "";
  document.getElementById("price").value = String(tool.price ?? 0);
  document.getElementById("tool_type").value = tool.tool_type === "paid" ? "paid" : "free";
  document.getElementById("screenshots").value = Array.isArray(tool.screenshots) ? tool.screenshots.join(", ") : "";
  document.getElementById("screenshot_assets").value = Array.isArray(tool.screenshot_assets) ? tool.screenshot_assets.join(", ") : "";
}

function renderRows() {
  const q = searchInput.value.trim().toLowerCase();
  const filtered = state.tools.filter((tool) => {
    if (!q) return true;
    return [
      tool.name,
      tool.developer_name,
      tool.category,
      tool.subtitle,
      tool.version,
    ]
      .map((v) => String(v || "").toLowerCase())
      .some((v) => v.includes(q));
  });

  listMeta.textContent = `${filtered.length} / ${state.tools.length} tools`;
  if (!filtered.length) {
    toolRows.innerHTML = `<tr><td colspan="5">No tools found.</td></tr>`;
    return;
  }

  toolRows.innerHTML = filtered
    .map((tool) => {
      const active = Number(state.selectedId) === Number(tool._id) ? "active" : "";
      return `
        <tr class="${active}" data-id="${tool._id}">
          <td>${tool._id}</td>
          <td>${esc(tool.name || "")}</td>
          <td>${esc(tool.category || "")}</td>
          <td><span class="type-pill">${esc(tool.tool_type || "free")}</span></td>
          <td>${esc(tool.version || "")}</td>
        </tr>
      `;
    })
    .join("");
}

async function fetchTools() {
  clearStatus();
  try {
    const res = await fetch(apiUrl, { cache: "no-store" });
    const data = await res.json();
    if (!res.ok || !data.ok) {
      throw new Error(data.message || "Failed to load tools");
    }
    state.tools = Array.isArray(data.tools) ? data.tools : [];
    if (state.selectedId !== null) {
      const existing = state.tools.find((t) => Number(t._id) === Number(state.selectedId));
      if (existing) {
        fillForm(existing);
      } else {
        resetForm();
      }
    }
    renderRows();
  } catch (error) {
    setStatus(error.message || "Failed to load tools", true);
  }
}

async function saveTool(event) {
  event.preventDefault();
  const selectedId = document.getElementById("toolId").value;
  const tool = readFormTool();

  if (!tool.name) {
    setStatus("Tool name is required.", true);
    return;
  }

  const payload = selectedId
    ? { action: "update", id: Number(selectedId), tool }
    : { action: "create", tool };

  try {
    const res = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!res.ok || !data.ok) {
      throw new Error(data.message || "Save failed");
    }

    setStatus(data.message || "Saved");
    await fetchTools();
    if (!selectedId) {
      resetForm();
    }
  } catch (error) {
    setStatus(error.message || "Save failed", true);
  }
}

async function deleteTool() {
  const selectedId = document.getElementById("toolId").value;
  if (!selectedId) {
    setStatus("Select a tool first.", true);
    return;
  }
  if (!window.confirm("Delete this tool?")) {
    return;
  }

  try {
    const res = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "delete", id: Number(selectedId) }),
    });
    const data = await res.json();
    if (!res.ok || !data.ok) {
      throw new Error(data.message || "Delete failed");
    }
    setStatus(data.message || "Deleted");
    resetForm();
    await fetchTools();
  } catch (error) {
    setStatus(error.message || "Delete failed", true);
  }
}

toolRows.addEventListener("click", (event) => {
  const tr = event.target.closest("tr[data-id]");
  if (!tr) return;
  const id = Number(tr.dataset.id);
  const tool = state.tools.find((item) => Number(item._id) === id);
  if (!tool) return;

  state.selectedId = id;
  fillForm(tool);
  formTitle.textContent = `Edit Tool #${id}`;
  saveBtn.textContent = "Update";
  renderRows();
});

searchInput.addEventListener("input", renderRows);
reloadBtn.addEventListener("click", fetchTools);
newBtn.addEventListener("click", resetForm);
deleteBtn.addEventListener("click", deleteTool);
form.addEventListener("submit", saveTool);

fetchTools();
