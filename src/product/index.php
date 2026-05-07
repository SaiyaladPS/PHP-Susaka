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

$page_title = 'Product';
$active_nav = 'product';
require_once __DIR__ . '/../../includes/layout/header.php';
?>

<style>
    .product-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .product-filters {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .product-search {
        max-width: 340px;
        min-width: 220px;
    }

    .product-preview {
        display: grid;
        grid-template-columns: 118px 1fr;
        gap: 18px;
        align-items: center;
        padding: 14px;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 18px;
    }

    .product-preview-media,
    .product-thumb {
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        flex-shrink: 0;
    }

    .product-preview-media {
        width: 118px;
        height: 118px;
        border-radius: 16px;
    }

    .product-thumb {
        width: 52px;
        height: 52px;
        border-radius: 12px;
    }

    .product-preview-media img,
    .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-fallback {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-align: center;
    }

    .product-preview h2 {
        font-size: 1.35rem;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .product-preview-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .product-name-cell {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 260px;
    }

    .product-name-cell strong {
        display: block;
        color: var(--text);
        margin-bottom: 2px;
    }

    .product-name-cell span {
        display: block;
        color: var(--text-muted);
        font-size: 0.78rem;
        max-width: 330px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .muted-text {
        color: var(--text-muted);
    }

    .money-cell {
        font-variant-numeric: tabular-nums;
        font-weight: 700;
        color: var(--success);
    }

    .form-hint {
        margin-top: 7px;
        color: var(--text-muted);
        font-size: 0.78rem;
    }

    .btn[disabled] {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    @media (max-width: 760px) {
        .product-preview {
            grid-template-columns: 1fr;
        }
        .product-preview-media {
            width: 100%;
            aspect-ratio: 1 / 1;
            height: auto;
        }
        .product-filters,
        .product-search,
        .product-filters select,
        .product-filters .btn {
            width: 100%;
            max-width: none;
        }
        .product-filters .btn {
            justify-content: center;
        }
    }
</style>

<div id="productAlert"></div>

<div class="stats-grid">
    <div class="stat-card accent">
        <div class="stat-icon accent">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4m16 0v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7m16 0l-2-4H6L4 7"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">Products</div>
            <div class="value" id="statProducts">0</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.761 0-5 1.343-5 3s2.239 3 5 3 5-1.343 5-3-2.239-3-5-3zm0 0V4m0 10v6"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">Average Price</div>
            <div class="value" id="statAverage">$0.00</div>
        </div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon yellow">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">Categories Used</div>
            <div class="value" id="statCategories">0</div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon red">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="label">With Image</div>
            <div class="value" id="statImages">0</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title" id="formTitle">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Create Product
        </div>
    </div>

    <div class="product-preview">
        <div class="product-preview-media" id="previewImage">
            <span class="product-fallback">NO IMAGE</span>
        </div>
        <div>
            <h2 id="previewName">New Product</h2>
            <div class="product-preview-meta">
                <span class="badge badge-accent" id="previewCategory">No category</span>
                <span class="badge badge-success" id="previewPrice">$0.00</span>
            </div>
            <div class="form-hint" id="previewImageUrl">Image preview appears when an image URL is provided.</div>
        </div>
    </div>

    <form id="productForm" autocomplete="off">
        <input type="hidden" id="productId">

        <div class="form-row">
            <div class="form-group">
                <label for="productName">Product Name</label>
                <input type="text" id="productName" placeholder="Latte" required>
            </div>
            <div class="form-group">
                <label for="productPrice">Price</label>
                <input type="number" id="productPrice" min="0.01" step="0.01" placeholder="5.00" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="productCategory">Category</label>
                <select id="productCategory" required>
                    <option value="">Loading categories...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="productImage">Image URL</label>
                <input type="text" id="productImage" placeholder="https://picsum.photos/seed/latte/200/200">
                <div class="form-hint">Paste a product image URL. Empty is allowed.</div>
            </div>
        </div>

        <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" id="saveBtn">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Save Product
            </button>
            <button type="button" class="btn btn-secondary" id="cancelEditBtn" hidden>
                Cancel Edit
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="product-toolbar">
        <div class="card-title" style="margin:0;">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4m16 0v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7m16 0l-2-4H6L4 7"/>
            </svg>
            Product List
        </div>
        <div class="product-filters">
            <input class="product-search" type="text" id="productSearch" placeholder="Search product...">
            <select id="categoryFilter">
                <option value="all">All categories</option>
            </select>
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
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="productRows">
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <p>Loading products...</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const PRODUCT_API_URL = '/src/api/pos_product.php';
const CATEGORY_API_URL = '/src/api/pos_category.php';
let products = [];
let categories = [];

const els = {
  alert: document.getElementById('productAlert'),
  rows: document.getElementById('productRows'),
  form: document.getElementById('productForm'),
  id: document.getElementById('productId'),
  name: document.getElementById('productName'),
  price: document.getElementById('productPrice'),
  category: document.getElementById('productCategory'),
  image: document.getElementById('productImage'),
  saveBtn: document.getElementById('saveBtn'),
  cancelBtn: document.getElementById('cancelEditBtn'),
  formTitle: document.getElementById('formTitle'),
  search: document.getElementById('productSearch'),
  filter: document.getElementById('categoryFilter'),
  refresh: document.getElementById('refreshBtn'),
  previewImage: document.getElementById('previewImage'),
  previewName: document.getElementById('previewName'),
  previewCategory: document.getElementById('previewCategory'),
  previewPrice: document.getElementById('previewPrice'),
  previewImageUrl: document.getElementById('previewImageUrl'),
  statProducts: document.getElementById('statProducts'),
  statAverage: document.getElementById('statAverage'),
  statCategories: document.getElementById('statCategories'),
  statImages: document.getElementById('statImages'),
};

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

function fmt(value) {
  return '$' + (Number(value) || 0).toFixed(2);
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

function productFallback(name) {
  return String(name || 'Product')
    .trim()
    .split(/\s+/)
    .map(part => part[0] || '')
    .join('')
    .slice(0, 3)
    .toUpperCase() || 'PRD';
}

function renderImage(url, name, className = '') {
  const safeUrl = String(url || '').trim();
  if (!safeUrl) {
    return `<span class="product-fallback">${escapeHtml(productFallback(name))}</span>`;
  }
  return `<img ${className ? `class="${className}"` : ''} src="${escapeHtml(safeUrl)}" alt="${escapeHtml(name)}" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'product-fallback',textContent:'IMG'}))">`;
}

function updatePreview() {
  const name = els.name.value.trim() || 'New Product';
  const price = Number(els.price.value) || 0;
  const image = els.image.value.trim();
  const selected = els.category.options[els.category.selectedIndex];
  const categoryName = selected && selected.value ? selected.textContent : 'No category';

  els.previewName.textContent = name;
  els.previewPrice.textContent = fmt(price);
  els.previewCategory.textContent = categoryName;
  els.previewImage.innerHTML = renderImage(image, name);
  els.previewImageUrl.textContent = image || 'Image preview appears when an image URL is provided.';
}

function resetForm() {
  els.form.reset();
  els.id.value = '';
  els.cancelBtn.hidden = true;
  els.saveBtn.innerHTML = `
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    Save Product
  `;
  els.formTitle.lastChild.textContent = ' Create Product';
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

function usableCategories() {
  return categories.filter(category => category.slug !== 'all');
}

function renderCategoryOptions() {
  const options = usableCategories().map(category => `
    <option value="${category.db_id}" data-slug="${escapeHtml(category.slug)}">
      ${escapeHtml(category.name)}
    </option>
  `).join('');

  els.category.innerHTML = `<option value="">Select category</option>${options}`;
  els.filter.innerHTML = `<option value="all">All categories</option>${usableCategories().map(category => `
    <option value="${escapeHtml(category.slug)}">${escapeHtml(category.name)}</option>
  `).join('')}`;
}

async function loadCategories() {
  try {
    const json = await apiRequest(CATEGORY_API_URL, { method: 'GET' });
    categories = Array.isArray(json.data) ? json.data : [];
    renderCategoryOptions();
    updatePreview();
  } catch (err) {
    showAlert(err.message, 'error');
  }
}

async function loadProducts() {
  els.rows.innerHTML = `
    <tr><td colspan="6"><div class="empty-state"><p>Loading products...</p></div></td></tr>
  `;
  try {
    const json = await apiRequest(`${PRODUCT_API_URL}?limit=200`, { method: 'GET' });
    products = Array.isArray(json.data) ? json.data : [];
    renderStats();
    renderTable();
  } catch (err) {
    els.rows.innerHTML = `
      <tr><td colspan="6"><div class="empty-state"><p>${escapeHtml(err.message)}</p></div></td></tr>
    `;
    showAlert(err.message, 'error');
  }
}

function filteredProducts() {
  const q = els.search.value.trim().toLowerCase();
  const category = els.filter.value;
  return products.filter(product => {
    const matchesSearch = !q ||
      String(product.name).toLowerCase().includes(q) ||
      String(product.category_name || '').toLowerCase().includes(q) ||
      String(product.category_slug || product.cat || '').toLowerCase().includes(q);
    const matchesCategory = category === 'all' || String(product.category_slug || product.cat) === category;
    return matchesSearch && matchesCategory;
  });
}

function renderStats() {
  const count = products.length;
  const average = count
    ? products.reduce((sum, product) => sum + (Number(product.price) || 0), 0) / count
    : 0;
  const usedCategories = new Set(products.map(product => product.category_slug || product.cat).filter(Boolean));
  const withImage = products.filter(product => String(product.image_url || product.img || '').trim()).length;

  els.statProducts.textContent = count;
  els.statAverage.textContent = fmt(average);
  els.statCategories.textContent = usedCategories.size;
  els.statImages.textContent = withImage;
}

function renderTable() {
  const list = filteredProducts();
  if (list.length === 0) {
    els.rows.innerHTML = `
      <tr><td colspan="6"><div class="empty-state"><p>No products found</p></div></td></tr>
    `;
    return;
  }

  els.rows.innerHTML = list.map(product => {
    const id = Number(product.id);
    const imageUrl = product.image_url || product.img || '';
    const categoryName = product.category_name || product.category_slug || 'Uncategorized';

    return `
      <tr>
        <td>${id}</td>
        <td>
          <div class="product-name-cell">
            <span class="product-thumb">${renderImage(imageUrl, product.name)}</span>
            <div>
              <strong>${escapeHtml(product.name)}</strong>
              <span>${escapeHtml(imageUrl || 'No image URL')}</span>
            </div>
          </div>
        </td>
        <td><span class="badge badge-accent">${escapeHtml(categoryName)}</span></td>
        <td class="money-cell">${fmt(product.price)}</td>
        <td>
          ${imageUrl
            ? `<a href="${escapeHtml(imageUrl)}" target="_blank" rel="noreferrer" class="badge badge-muted">Open image</a>`
            : '<span class="badge badge-muted">No image</span>'}
        </td>
        <td class="actions">
          <button type="button" class="btn btn-warning btn-sm" onclick="editProduct(${id})">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit
          </button>
          <button type="button" class="btn btn-danger btn-sm" onclick="deleteProduct(${id})">
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

window.editProduct = function editProduct(id) {
  const product = products.find(item => Number(item.id) === Number(id));
  if (!product) return;

  els.id.value = product.id;
  els.name.value = product.name;
  els.price.value = Number(product.price).toFixed(2);
  els.category.value = product.category_id || '';
  els.image.value = product.image_url || product.img || '';
  els.cancelBtn.hidden = false;
  els.saveBtn.innerHTML = `
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    Update Product
  `;
  els.formTitle.lastChild.textContent = ' Edit Product';
  updatePreview();
  els.name.focus();
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

window.deleteProduct = async function deleteProduct(id) {
  const product = products.find(item => Number(item.id) === Number(id));
  if (!product) return;
  if (!confirm(`Delete product "${product.name}"?`)) return;

  try {
    await apiRequest(`${PRODUCT_API_URL}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    showAlert('Product deleted successfully');
    if (Number(els.id.value) === Number(id)) resetForm();
    await Promise.all([loadCategories(), loadProducts()]);
  } catch (err) {
    showAlert(err.message, 'error');
  }
};

els.form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const id = Number(els.id.value);
  const payload = {
    name: els.name.value.trim(),
    price: Number(els.price.value),
    category_id: Number(els.category.value),
    image_url: els.image.value.trim(),
  };

  if (!payload.name) {
    showAlert('Product name is required', 'error');
    return;
  }
  if (!payload.price || payload.price <= 0) {
    showAlert('Product price must be greater than 0', 'error');
    return;
  }
  if (!payload.category_id) {
    showAlert('Please select a category', 'error');
    return;
  }
  if (id > 0) {
    payload.id = id;
  }

  els.saveBtn.disabled = true;
  try {
    await apiRequest(PRODUCT_API_URL, {
      method: id > 0 ? 'PUT' : 'POST',
      body: JSON.stringify(payload),
    });
    showAlert(id > 0 ? 'Product updated successfully' : 'Product created successfully');
    resetForm();
    await Promise.all([loadCategories(), loadProducts()]);
  } catch (err) {
    showAlert(err.message, 'error');
  } finally {
    els.saveBtn.disabled = false;
  }
});

els.name.addEventListener('input', updatePreview);
els.price.addEventListener('input', updatePreview);
els.category.addEventListener('change', updatePreview);
els.image.addEventListener('input', updatePreview);
els.search.addEventListener('input', renderTable);
els.filter.addEventListener('change', renderTable);
els.refresh.addEventListener('click', () => Promise.all([loadCategories(), loadProducts()]));
els.cancelBtn.addEventListener('click', resetForm);

resetForm();
Promise.all([loadCategories(), loadProducts()]);
</script>

<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
