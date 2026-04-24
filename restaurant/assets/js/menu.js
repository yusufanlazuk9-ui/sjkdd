// =========================================
// RESTAURANT DIGITAL MENU - MAIN JS
// =========================================

const Cart = (() => {
  let items = [];

  const getItems = () => items;

  const add = (product, qty, notes) => {
    const existing = items.find(i => i.id === product.id && i.notes === notes);
    if (existing) {
      existing.qty += qty;
    } else {
      items.push({ ...product, qty, notes });
    }
    save();
    render();
  };

  const remove = (index) => {
    items.splice(index, 1);
    save();
    render();
  };

  const updateQty = (index, delta) => {
    items[index].qty += delta;
    if (items[index].qty <= 0) {
      items.splice(index, 1);
    }
    save();
    render();
  };

  const total = () => items.reduce((sum, i) => sum + (i.price * i.qty), 0);
  const count = () => items.reduce((sum, i) => sum + i.qty, 0);

  const clear = () => {
    items = [];
    save();
    render();
  };

  const save = () => {
    try {
      sessionStorage.setItem('cart', JSON.stringify(items));
    } catch(e) {}
  };

  const load = () => {
    try {
      const saved = sessionStorage.getItem('cart');
      if (saved) items = JSON.parse(saved);
    } catch(e) { items = []; }
  };

  const render = () => {
    const count_ = count();
    const badge = document.getElementById('cart-badge');
    if (badge) {
      badge.textContent = count_;
      badge.parentElement.style.display = count_ > 0 ? 'flex' : 'flex';
    }

    const cartItems = document.getElementById('cart-items');
    const cartEmpty = document.getElementById('cart-empty');
    const cartFooter = document.getElementById('cart-footer');
    if (!cartItems) return;

    if (items.length === 0) {
      cartItems.innerHTML = '';
      if (cartEmpty) cartEmpty.style.display = 'block';
      if (cartFooter) cartFooter.style.display = 'none';
    } else {
      if (cartEmpty) cartEmpty.style.display = 'none';
      if (cartFooter) cartFooter.style.display = 'block';
      cartItems.innerHTML = items.map((item, idx) => `
        <div class="cart-item">
          <div class="cart-item-name">${escHtml(item.name)}</div>
          <div class="qty-control">
            <button class="qty-btn" onclick="Cart.updateQty(${idx}, -1)">−</button>
            <span class="qty-num">${item.qty}</span>
            <button class="qty-btn" onclick="Cart.updateQty(${idx}, 1)">+</button>
          </div>
          <div class="cart-item-price">${(item.price * item.qty).toFixed(2)} ₺</div>
        </div>
      `).join('');
    }

    const totalEl = document.getElementById('cart-total');
    if (totalEl) totalEl.textContent = total().toFixed(2) + ' ₺';
  };

  return { getItems, add, remove, updateQty, total, count, clear, save, load, render };
})();

const Modal = (() => {
  let currentProduct = null;
  let currentQty = 1;

  const open = (product) => {
    currentProduct = product;
    currentQty = 1;

    document.getElementById('modal-title').textContent = product.name;
    document.getElementById('modal-desc').textContent = product.description || '';
    document.getElementById('modal-price').textContent = parseFloat(product.price).toFixed(2) + ' ₺';
    document.getElementById('modal-qty').textContent = currentQty;
    document.getElementById('modal-notes').value = '';

    const imgEl = document.getElementById('modal-img');
    if (product.image) {
      imgEl.innerHTML = `<img src="/restaurant/assets/img/${escHtml(product.image)}" alt="${escHtml(product.name)}" onerror="this.parentElement.innerHTML='<span>${getIcon(product.category_icon)}</span>'">`;
    } else {
      imgEl.innerHTML = `<span>${getIcon(product.category_icon)}</span>`;
    }

    document.getElementById('modal-overlay').classList.add('open');
    document.getElementById('product-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  const close = () => {
    document.getElementById('modal-overlay').classList.remove('open');
    document.getElementById('product-modal').classList.remove('open');
    document.body.style.overflow = '';
    currentProduct = null;
  };

  const changeQty = (delta) => {
    currentQty = Math.max(1, currentQty + delta);
    document.getElementById('modal-qty').textContent = currentQty;
  };

  const addToCart = () => {
    if (!currentProduct) return;
    const notes = document.getElementById('modal-notes').value.trim();
    Cart.add(currentProduct, currentQty, notes);
    close();
    showToast(currentProduct.name + ' sepete eklendi!', 'success');
  };

  return { open, close, changeQty, addToCart };
})();

function getIcon(icon) {
  return icon || '🍽️';
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = '') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const toast = document.createElement('div');
  toast.className = 'toast' + (type ? ' ' + type : '');
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'toastOut 0.3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, 2500);
}

function openCart() {
  document.getElementById('cart-overlay').classList.add('open');
  document.getElementById('cart-drawer').classList.add('open');
  document.body.style.overflow = 'hidden';
  Cart.render();
}

function closeCart() {
  document.getElementById('cart-overlay').classList.remove('open');
  document.getElementById('cart-drawer').classList.remove('open');
  document.body.style.overflow = '';
}

function scrollToCategory(id) {
  const el = document.getElementById('cat-' + id);
  if (el) {
    const offset = 130;
    const top = el.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top, behavior: 'smooth' });
  }

  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.id == id);
  });
}

function setActiveTabOnScroll() {
  const sections = document.querySelectorAll('.category-section');
  const offset = 150;
  let active = null;

  sections.forEach(sec => {
    const top = sec.getBoundingClientRect().top;
    if (top <= offset) active = sec.id.replace('cat-', '');
  });

  if (active) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.id == active);
    });
  }
}

async function submitOrder() {
  const items = Cart.getItems();
  if (items.length === 0) {
    showToast('Sepetiniz boş!', 'error');
    return;
  }

  const notes = document.getElementById('cart-notes')?.value || '';
  const tableId = document.getElementById('table-id')?.value;
  const sessionToken = document.getElementById('session-token')?.value;
  const submitBtn = document.getElementById('submit-order-btn');

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Gönderiliyor...';
  }

  try {
    const res = await fetch('/restaurant/api/order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ table_id: tableId, session_token: sessionToken, items, notes })
    });
    const data = await res.json();

    if (data.success) {
      Cart.clear();
      closeCart();
      window.location.href = '/restaurant/order-status.php?order_id=' + data.order_id;
    } else {
      showToast(data.error || 'Bir hata oluştu.', 'error');
    }
  } catch(e) {
    showToast('Bağlantı hatası.', 'error');
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Siparişi Gönder';
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  Cart.load();
  Cart.render();

  window.addEventListener('scroll', setActiveTabOnScroll);

  const firstTab = document.querySelector('.tab-btn');
  if (firstTab) firstTab.classList.add('active');
});
