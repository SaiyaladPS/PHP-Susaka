<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/logger.php';

logVisitor($conn, $_SESSION['user_id'] ?? null);

if (!isset($_SESSION['user_id'])) {
    header('Location: /src/login/index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /src/account/index.php');
    exit;
}

$page_title = 'Category';
$active_nav = 'category';
require_once __DIR__ . '/../../includes/layout/header.php';
?>

<style>
    .category-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .category-search {
        max-width: 340px;
        min-width: 220px;
    }

    .category-preview {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 18px;
    }

    .category-icon-preview,
    .category-table-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 8px 22px rgba(0,0,0,0.2);
    }

    .category-table-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
    }

    .category-table-icon i,
    .category-icon-preview i {
        font-style: normal;
        font-weight: 800;
        font-size: 0.8rem;
    }

    .color-input-row {
        display: grid;
        grid-template-columns: 56px 1fr;
        gap: 10px;
        align-items: center;
    }

    input[type="color"] {
        width: 56px;
        height: 46px;
        padding: 4px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--surface-2);
        cursor: pointer;
    }

    .form-hint {
        margin-top: 7px;
        color: var(--text-muted);
        font-size: 0.78rem;
    }

    .muted-text {
        color: var(--text-muted);
    }

    .category-name-cell {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 220px;
    }

    .category-name-cell strong {
        display: block;
        color: var(--text);
        margin-bottom: 2px;
    }

    .category-name-cell span {
        color: var(--text-muted);
        font-size: 0.78rem;
    }

    .color-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.8rem;
    }

    .color-chip::before {
        content: "";
        width: 14px;
        height: 14px;
        border-radius: 5px;
        background: var(--chip-color);
        border: 1px solid rgba(255,255,255,0.18);
    }

    .btn[disabled] {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    @media (max-width: 760px) {
        .category-toolbar {
            align-items: stretch;
        }
        .category-search,
        .category-toolbar .btn {
            width: 100%;
            max-width: none;
            justify-content: center;
        }
    }
</style>

<div id="categoryAlert"></div>

<div class="stats-grid">
    <div class="stat-card accent">
        <div class="stat-icon accent">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">Categories</div>
            <div class="value" id="statCategories">0</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2h-3.5a2 2 0 01-1.6-.8l-.8-1.067A2 2 0 0010.5 2.333H6a2 2 0 00-2 2V13m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">Assigned Products</div>
            <div class="value" id="statProducts">0</div>
        </div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon yellow">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">In Use</div>
            <div class="value" id="statInUse">0</div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon red">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">Empty</div>
            <div class="value" id="statEmpty">0</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title" id="formTitle">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Create Category
        </div>
    </div>

    <div class="category-preview">
        <div class="category-icon-preview" id="previewIcon" style="background:#6C63FF;">
            <i>TAG</i>
        </div>
        <div>
            <strong id="previewName">New Category</strong>
            <div class="muted-text" id="previewSlug">new-category</div>
        </div>
    </div>

    <form id="categoryForm" autocomplete="off">
        <input type="hidden" id="categoryId">

        <div class="form-row">
            <div class="form-group">
                <label for="categoryName">Name</label>
                <input type="text" id="categoryName" placeholder="Coffee & Drinks" required>
            </div>
            <div class="form-group">
                <label for="categorySlug">Slug</label>
                <input type="text" id="categorySlug" placeholder="coffee" required>
                <div class="form-hint">Use lowercase letters, numbers, dash, or underscore.</div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="categoryIcon">Font Awesome Icon</label>
                <input type="text" id="categoryIcon" placeholder="fa-mug-hot" value="fa-tag">
                <div class="form-hint">Example: fa-mug-hot, fa-cookie-bite, fa-leaf.</div>
            </div>
            <div class="form-group">
                <label for="categoryColor">Color</label>
                <div class="color-input-row">
                    <input type="color" id="categoryColor" value="#6C63FF">
                    <input type="text" id="categoryColorText" value="#6C63FF" maxlength="7" placeholder="#6C63FF">
                </div>
            </div>
        </div>

        <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" id="saveBtn">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Save Category
            </button>
            <button type="button" class="btn btn-secondary" id="cancelEditBtn" hidden>
                Cancel Edit
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="category-toolbar">
        <div class="card-title" style="margin:0;">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
            Category List
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <input class="category-search" type="text" id="categorySearch" placeholder="Search category...">
            <button class="btn btn-secondary" type="button" id="refreshBtn">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M5.64 18.36A9 9 0 0020 12M18.36 5.64A9 9 0 004 12"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Icon</th>
                    <th>Color</th>
                    <th>Products</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="categoryRows">
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <p>Loading categories...</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const CATEGORY_API_URL = '/src/api/pos_category.php';
let categories = [];

const els = {
  alert: document.getElementById('categoryAlert'),
  rows: document.getElementById('categoryRows'),
  form: document.getElementById('categoryForm'),
  id: document.getElementById('categoryId'),
  name: document.getElementById('categoryName'),
  slug: document.getElementById('categorySlug'),
  icon: document.getElementById('categoryIcon'),
  color: document.getElementById('categoryColor'),
  colorText: document.getElementById('categoryColorText'),
  saveBtn: document.getElementById('saveBtn'),
  cancelBtn: document.getElementById('cancelEditBtn'),
  formTitle: document.getElementById('formTitle'),
  search: document.getElementById('categorySearch'),
  refresh: document.getElementById('refreshBtn'),
  previewIcon: document.getElementById('previewIcon'),
  previewName: document.getElementById('previewName'),
  previewSlug: document.getElementById('previewSlug'),
  statCategories: document.getElementById('statCategories'),
  statProducts: document.getElementById('statProducts'),
  statInUse: document.getElementById('statInUse'),
  statEmpty: document.getElementById('statEmpty'),
};

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

function normalizeSlug(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9_-]/g, '')
    .slice(0, 50);
}

function safeIconClass(value) {
  const icon = String(value || 'fa-tag').trim();
  return /^[a-z0-9_ -]+$/i.test(icon) ? icon : 'fa-tag';
}

function iconLabel(icon) {
  return safeIconClass(icon).replace(/^fa-/, '').split('-').map(part => part[0] || '').join('').slice(0, 3).toUpperCase() || 'TAG';
}

function isHexColor(value) {
  return /^#[0-9A-Fa-f]{6}$/.test(String(value || '').trim());
}

function showAlert(message, type = 'success') {
  els.alert.innerHTML = `
    <div class="alert ${type === 'success' ? 'alert-success' : 'alert-error'}">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        ${type === 'success'
          ? '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>'
          : '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12A9 9 0 113 12a9 9 0 0118 0z"/>'}
      </svg>
      <span>${escapeHtml(message)}</span>
    </div>
  `;
  window.setTimeout(() => { els.alert.innerHTML = ''; }, 3500);
}

function updatePreview() {
  const name = els.name.value.trim() || 'New Category';
  const slug = normalizeSlug(els.slug.value || name) || 'new-category';
  const icon = safeIconClass(els.icon.value);
  const color = isHexColor(els.colorText.value) ? els.colorText.value : els.color.value;

  els.previewName.textContent = name;
  els.previewSlug.textContent = slug;
  els.previewIcon.style.background = color;
  els.previewIcon.innerHTML = `<i class="fa-solid ${icon}">${iconLabel(icon)}</i>`;
}

function resetForm() {
  els.form.reset();
  els.id.value = '';
  els.icon.value = 'fa-tag';
  els.color.value = '#6C63FF';
  els.colorText.value = '#6C63FF';
  els.slug.readOnly = false;
  els.cancelBtn.hidden = true;
  els.saveBtn.innerHTML = `
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    Save Category
  `;
  els.formTitle.lastChild.textContent = ' Create Category';
  updatePreview();
}

async function apiRequest(url, options = {}) {
  const res = await fetch(url, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
    ...options,
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok || !json.success) {
    throw new Error(json.message || 'Request failed');
  }
  return json;
}

async function loadCategories() {
  els.rows.innerHTML = `
    <tr><td colspan="6"><div class="empty-state"><p>Loading categories...</p></div></td></tr>
  `;
  try {
    const json = await apiRequest(CATEGORY_API_URL, { method: 'GET' });
    categories = Array.isArray(json.data) ? json.data : [];
    renderStats();
    renderTable();
  } catch (err) {
    els.rows.innerHTML = `
      <tr><td colspan="6"><div class="empty-state"><p>${escapeHtml(err.message)}</p></div></td></tr>
    `;
    showAlert(err.message, 'error');
  }
}

function filteredCategories() {
  const q = els.search.value.trim().toLowerCase();
  if (!q) return categories;
  return categories.filter(cat =>
    String(cat.name).toLowerCase().includes(q) ||
    String(cat.slug).toLowerCase().includes(q) ||
    String(cat.icon).toLowerCase().includes(q)
  );
}

function renderStats() {
  const realCategories = categories.filter(cat => cat.slug !== 'all');
  const allCategory = categories.find(cat => cat.slug === 'all');
  const totalProducts = allCategory
    ? Number(allCategory.product_count) || 0
    : realCategories.reduce((sum, cat) => sum + (Number(cat.product_count) || 0), 0);

  els.statCategories.textContent = realCategories.length;
  els.statProducts.textContent = totalProducts;
  els.statInUse.textContent = realCategories.filter(cat => Number(cat.product_count) > 0).length;
  els.statEmpty.textContent = realCategories.filter(cat => Number(cat.product_count) === 0).length;
}

function renderTable() {
  const list = filteredCategories();
  if (list.length === 0) {
    els.rows.innerHTML = `
      <tr><td colspan="6"><div class="empty-state"><p>No categories found</p></div></td></tr>
    `;
    return;
  }

  els.rows.innerHTML = list.map(cat => {
    const dbId = Number(cat.db_id);
    const icon = safeIconClass(cat.icon);
    const color = isHexColor(cat.color) ? cat.color : '#6C63FF';
    const productCount = Number(cat.product_count) || 0;
    const isAll = cat.slug === 'all';
    const canDelete = !isAll && productCount === 0;

    return `
      <tr>
        <td>${dbId}</td>
        <td>
          <div class="category-name-cell">
            <span class="category-table-icon" style="background:${color};">
              <i class="fa-solid ${icon}">${iconLabel(icon)}</i>
            </span>
            <div>
              <strong>${escapeHtml(cat.name)}</strong>
              <span>${escapeHtml(cat.slug)}</span>
            </div>
          </div>
        </td>
        <td><span class="badge badge-muted">${escapeHtml(icon)}</span></td>
        <td><span class="color-chip" style="--chip-color:${color};">${escapeHtml(color)}</span></td>
        <td>
          <span class="badge ${productCount > 0 ? 'badge-accent' : 'badge-muted'}">
            ${productCount} products
          </span>
        </td>
        <td class="actions">
          <button type="button" class="btn btn-warning btn-sm" onclick="editCategory(${dbId})">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit
          </button>
          <button type="button" class="btn btn-danger btn-sm" ${canDelete ? '' : 'disabled'} onclick="deleteCategory(${dbId})" title="${canDelete ? 'Delete category' : 'Only empty non-system categories can be deleted'}">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

window.editCategory = function editCategory(id) {
  const cat = categories.find(item => Number(item.db_id) === Number(id));
  if (!cat) return;

  els.id.value = cat.db_id;
  els.name.value = cat.name;
  els.slug.value = cat.slug;
  els.icon.value = cat.icon || 'fa-tag';
  els.color.value = isHexColor(cat.color) ? cat.color : '#6C63FF';
  els.colorText.value = els.color.value;
  els.slug.readOnly = cat.slug === 'all';
  els.cancelBtn.hidden = false;
  els.saveBtn.innerHTML = `
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    Update Category
  `;
  els.formTitle.lastChild.textContent = ' Edit Category';
  updatePreview();
  els.name.focus();
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

window.deleteCategory = async function deleteCategory(id) {
  const cat = categories.find(item => Number(item.db_id) === Number(id));
  if (!cat) return;
  if (!confirm(`Delete category "${cat.name}"?`)) return;

  try {
    await apiRequest(`${CATEGORY_API_URL}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    showAlert('Category deleted successfully');
    if (Number(els.id.value) === Number(id)) resetForm();
    await loadCategories();
  } catch (err) {
    showAlert(err.message, 'error');
  }
};

els.form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const id = Number(els.id.value);
  const payload = {
    name: els.name.value.trim(),
    slug: normalizeSlug(els.slug.value),
    icon: safeIconClass(els.icon.value),
    color: els.colorText.value.trim().toUpperCase(),
  };

  if (!payload.name) {
    showAlert('Category name is required', 'error');
    return;
  }
  if (!payload.slug || payload.slug.length < 2) {
    showAlert('Slug must be at least 2 characters', 'error');
    return;
  }
  if (!isHexColor(payload.color)) {
    showAlert('Color must be a valid hex color, example #6C63FF', 'error');
    return;
  }
  if (id > 0) {
    payload.id = id;
  }

  els.saveBtn.disabled = true;
  try {
    await apiRequest(CATEGORY_API_URL, {
      method: id > 0 ? 'PUT' : 'POST',
      body: JSON.stringify(payload),
    });
    showAlert(id > 0 ? 'Category updated successfully' : 'Category created successfully');
    resetForm();
    await loadCategories();
  } catch (err) {
    showAlert(err.message, 'error');
  } finally {
    els.saveBtn.disabled = false;
  }
});

els.name.addEventListener('input', () => {
  if (!els.id.value) {
    els.slug.value = normalizeSlug(els.name.value);
  }
  updatePreview();
});
els.slug.addEventListener('input', () => {
  els.slug.value = normalizeSlug(els.slug.value);
  updatePreview();
});
els.icon.addEventListener('input', updatePreview);
els.color.addEventListener('input', () => {
  els.colorText.value = els.color.value.toUpperCase();
  updatePreview();
});
els.colorText.addEventListener('input', () => {
  const value = els.colorText.value.trim();
  if (isHexColor(value)) els.color.value = value;
  updatePreview();
});
els.search.addEventListener('input', renderTable);
els.refresh.addEventListener('click', loadCategories);
els.cancelBtn.addEventListener('click', resetForm);

resetForm();
loadCategories();
</script>

<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
