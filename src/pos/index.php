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

$page_title = 'POS';
$active_nav = 'pos';
require_once __DIR__ . '/../../includes/layout/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QuickPOS — Point of Sale</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        p: {
          bg: '#0D0F13', surface: '#151821', card: '#1C1F2E', border: '#2B2F42',
          hover: '#252839', text: '#E5E7EB', muted: '#6B7094',
          accent: '#F59E0B', 'accent-h': '#FBBF24',
          success: '#10B981', danger: '#EF4444', info: '#38BDF8'
        }
      },
      fontFamily: { sans: ['DM Sans', 'sans-serif'] }
    }
  }
}
</script>
<style>
  * { box-sizing: border-box; }
  body { font-family: 'DM Sans', sans-serif; background: #0D0F13; color: #E5E7EB; overflow: scroll; }
  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #2B2F42; border-radius: 10px; }
  ::-webkit-scrollbar-thumb:hover { background: #3B3F52; }
  .no-scrollbar::-webkit-scrollbar { display: none; }
  .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

  @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
  @keyframes pop { 0% { transform: scale(0.8); opacity: 0; } 50% { transform: scale(1.05); } 100% { transform: scale(1); opacity: 1; } }
  @keyframes badgeBounce { 0%,100% { transform: scale(1); } 50% { transform: scale(1.35); } }
  @keyframes toastIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
  @keyframes toastOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
  @keyframes pulseGlow { 0%,100% { box-shadow: 0 0 20px rgba(245,158,11,0.15); } 50% { box-shadow: 0 0 35px rgba(245,158,11,0.3); } }
  @keyframes floatPlus { 0% { opacity: 1; transform: translateY(0) scale(1); } 100% { opacity: 0; transform: translateY(-40px) scale(1.5); } }

  .anim-in { animation: fadeInUp 0.4s ease both; }
  .anim-pop { animation: pop 0.3s ease both; }
  .badge-bounce { animation: badgeBounce 0.35s ease; }
  .toast-in { animation: toastIn 0.3s ease both; }
  .toast-out { animation: toastOut 0.3s ease both; }
  .pulse-glow { animation: pulseGlow 2s ease-in-out infinite; }

  .float-plus {
    position: absolute; pointer-events: none; color: #F59E0B; font-weight: 800; font-size: 18px;
    animation: floatPlus 0.6s ease forwards; z-index: 99;
  }

  .cat-btn.active { background: rgba(245,158,11,0.12); color: #F59E0B; border-color: rgba(245,158,11,0.3); }
  .cat-btn.active .cat-dot { background: #F59E0B; }
  .cat-pill.active { background: #F59E0B; color: #000; border-color: #F59E0B; font-weight: 600; }

  .product-card { transition: all 0.2s ease; }
  .product-card:hover { border-color: rgba(245,158,11,0.4); transform: translateY(-2px); }
  .product-card:active { transform: scale(0.97); }

  .pay-tab.active { background: #F59E0B; color: #000; }
  .pay-tab:not(.active):hover { background: #252839; }

  .numpad-btn { transition: all 0.1s; }
  .numpad-btn:active { transform: scale(0.92); background: #F59E0B; color: #000; }

  .cart-item { transition: all 0.2s ease; }
  .cart-item:hover { background: #252839; }

  @media print {
    body * { visibility: hidden; }
    #receiptPrint, #receiptPrint * { visibility: visible; }
    #receiptPrint { position: absolute; left: 0; top: 0; }
  }
</style>
</head>
<body>

<!-- ============ HEADER ============ -->
<header class="h-14 bg-p-surface border-b border-p-border flex items-center px-3 lg:px-5 gap-3 relative z-30">
  <!-- Logo -->
  <div class="flex items-center gap-2.5 flex-shrink-0">
    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20">
      <i class="fas fa-bolt text-black text-sm"></i>
    </div>
    <span class="font-bold text-lg text-p-text hidden sm:block">Quick<span class="text-p-accent">POS</span></span>
  </div>

  <!-- Search Desktop -->
  <div class="hidden md:flex flex-1 max-w-md mx-4">
    <div class="relative w-full">
      <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-p-muted text-sm"></i>
      <input id="searchDesktop" type="text" placeholder="Search products... (Ctrl+K)"
        class="w-full bg-p-bg border border-p-border rounded-lg pl-9 pr-4 py-2 text-sm text-p-text placeholder-p-muted focus:outline-none focus:border-p-accent/50 focus:ring-1 focus:ring-p-accent/20 transition">
    </div>
  </div>

  <div class="flex-1"></div>

  <!-- Clock -->
  <div class="hidden sm:flex flex-col items-end mr-2">
    <span id="clockTime" class="text-sm font-semibold text-p-text tabular-nums">--:--</span>
    <span id="clockDate" class="text-[10px] text-p-muted tabular-nums">---</span>
  </div>

  <!-- Actions -->
  <button onclick="openModal('historyModal'); renderOrderHistory()" class="w-9 h-9 rounded-lg border border-p-border flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition" title="Order History">
    <i class="fas fa-clock-rotate-left text-sm"></i>
  </button>
  <button onclick="openModal('heldModal'); renderHeldOrders()" class="w-9 h-9 rounded-lg border border-p-border flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition relative" title="Held Orders">
    <i class="fas fa-pause text-sm"></i>
    <span id="heldBadge" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-p-accent rounded-full text-[9px] font-bold text-black flex items-center justify-center">0</span>
  </button>
  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-xs font-bold text-black flex-shrink-0 cursor-pointer" title="Cashier">
    <?= htmlspecialchars(strtoupper(mb_substr($_SESSION['user_name'] ?? 'JD', 0, 2))) ?>
  </div>
</header>

<!-- ============ MAIN ============ -->
<main class="flex" style="height: calc(100vh - 56px);">

  <!-- Categories Sidebar (Desktop) -->
  <aside class="hidden lg:flex flex-col w-52 bg-p-surface border-r border-p-border overflow-y-auto flex-shrink-0 py-3 px-2.5 gap-1 anim-in" style="animation-delay:0.05s" id="catSidebar">
  </aside>

  <!-- Center: Products -->
  <div class="flex-1 flex flex-col overflow-hidden min-w-0">
    <!-- Mobile Search + Categories -->
    <div class="lg:hidden flex flex-col gap-2 p-3 pb-0 anim-in" style="animation-delay:0.05s">
      <div class="relative">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-p-muted text-sm"></i>
        <input id="searchMobile" type="text" placeholder="Search products..."
          class="w-full bg-p-bg border border-p-border rounded-lg pl-9 pr-4 py-2.5 text-sm text-p-text placeholder-p-muted focus:outline-none focus:border-p-accent/50 transition">
      </div>
      <div class="flex gap-2 overflow-x-auto no-scrollbar pb-2" id="catPills"></div>
    </div>

    <!-- Desktop Search -->
    <div class="hidden lg:block px-4 pt-3 anim-in" style="animation-delay:0.1s">
      <!-- handled in header on desktop -->
    </div>

    <!-- Product Grid -->
    <div class="flex-1 overflow-y-auto p-3 anim-in" style="animation-delay:0.15s" id="productsContainer">
      <div id="productGrid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-2.5"></div>
      <div id="noProducts" class="hidden flex-col items-center justify-center py-20 text-p-muted">
        <i class="fas fa-search text-4xl mb-3 opacity-30"></i>
        <p class="text-sm">No products found</p>
      </div>
    </div>
  </div>

  <!-- Cart Panel -->
  <div class="fixed inset-0 bg-black/60 z-40 hidden lg:hidden transition-opacity" id="cartBackdrop" onclick="toggleMobileCart(false)"></div>
  <aside id="cartPanel" class="
    fixed inset-x-0 bottom-0 z-50 rounded-t-2xl max-h-[92vh]
    lg:static lg:rounded-none lg:max-h-none lg:z-auto
    bg-p-surface border-t border-p-border lg:border-t-0 lg:border-l
    flex flex-col w-full lg:w-[370px] xl:w-[390px] flex-shrink-0
    transform translate-y-full lg:translate-y-0 transition-transform duration-300 ease-out
  ">
    <!-- Drag Handle (Mobile) -->
    <div class="lg:hidden flex justify-center pt-2 pb-1 cursor-grab" onclick="toggleMobileCart(false)">
      <div class="w-10 h-1 rounded-full bg-p-border"></div>
    </div>

    <!-- Cart Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-p-border">
      <div class="flex items-center gap-2.5">
        <i class="fas fa-receipt text-p-accent"></i>
        <h2 class="font-bold text-p-text">Current Order</h2>
        <span id="cartCount" class="text-xs bg-p-accent/15 text-p-accent px-2 py-0.5 rounded-full font-semibold">0 items</span>
      </div>
      <div class="flex items-center gap-1">
        <button onclick="openModal('customModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-accent hover:bg-p-accent/10 transition" title="Custom Item">
          <i class="fas fa-pen-to-square text-xs"></i>
        </button>
        <button onclick="clearCart()" class="w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-danger hover:bg-p-danger/10 transition" title="Clear Cart">
          <i class="fas fa-trash-can text-xs"></i>
        </button>
        <button onclick="toggleMobileCart(false)" class="lg:hidden w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition">
          <i class="fas fa-xmark text-sm"></i>
        </button>
      </div>
    </div>

    <!-- Cart Items -->
    <div class="flex-1 overflow-y-auto px-3 py-2 space-y-2" id="cartItems">
      <!-- empty state rendered by JS -->
    </div>

    <!-- Cart Footer -->
    <div class="border-t border-p-border px-4 py-3 space-y-2.5 flex-shrink-0" id="cartFooter">
      <!-- Discount row -->
      <div class="flex items-center justify-between">
        <button onclick="openModal('discountModal')" class="text-xs text-p-muted hover:text-p-accent transition flex items-center gap-1">
          <i class="fas fa-tag"></i> <span id="discountLabel">Add Discount</span>
        </button>
        <span id="discountAmount" class="text-xs font-semibold text-p-danger">-$0.00</span>
      </div>
      <!-- Summary -->
      <div class="space-y-1.5 text-sm">
        <div class="flex justify-between text-p-muted"><span>Subtotal</span><span id="subtotalDisplay" class="text-p-text font-medium">$0.00</span></div>
        <div class="flex justify-between text-p-muted"><span>Tax <span id="taxRateLabel">(8%)</span></span><span id="taxDisplay" class="text-p-text font-medium">$0.00</span></div>
        <div class="flex justify-between text-p-muted"><span>Discount</span><span id="discountDisplay" class="text-p-danger font-medium">-$0.00</span></div>
        <div class="h-px bg-p-border my-1"></div>
        <div class="flex justify-between items-end">
          <span class="text-p-muted font-medium">Total</span>
          <span id="totalDisplay" class="text-2xl font-black text-p-accent tabular-nums">$0.00</span>
        </div>
      </div>
      <!-- Actions -->
      <div class="flex gap-2">
        <button onclick="holdOrder()" id="holdBtn" class="flex-1 py-3 rounded-xl border border-p-border text-sm font-semibold text-p-muted hover:text-p-text hover:bg-p-hover transition flex items-center justify-center gap-2">
          <i class="fas fa-pause text-xs"></i> Hold
        </button>
        <button onclick="openCheckout()" id="payBtn" class="flex-[2] py-3 rounded-xl bg-p-accent hover:bg-p-accent-h text-black text-sm font-bold transition flex items-center justify-center gap-2 pulse-glow disabled:opacity-40 disabled:cursor-not-allowed" disabled>
          <i class="fas fa-credit-card"></i> Pay Now
        </button>
      </div>
    </div>
  </aside>
</main>

<!-- Mobile Cart FAB -->
<button id="cartFab" onclick="toggleMobileCart(true)" class="lg:hidden fixed bottom-5 right-5 z-30 w-14 h-14 rounded-2xl bg-p-accent text-black flex items-center justify-center shadow-lg shadow-amber-500/30 hover:bg-p-accent-h transition active:scale-95 hidden">
  <i class="fas fa-shopping-cart text-lg"></i>
  <span id="fabBadge" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-p-danger rounded-full text-[10px] font-bold text-white flex items-center justify-center hidden">0</span>
</button>

<!-- ============ MODALS ============ -->

<!-- Checkout Modal -->
<div id="checkoutModal" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-black/70" onclick="closeModal('checkoutModal')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative bg-p-surface rounded-2xl border border-p-border w-full max-w-md max-h-[90vh] overflow-y-auto anim-pop">
      <div class="flex items-center justify-between p-5 pb-3">
        <h3 class="text-lg font-bold text-p-text">Checkout</h3>
        <button onclick="closeModal('checkoutModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition"><i class="fas fa-xmark"></i></button>
      </div>
      <div class="px-5 pb-2 text-center">
        <p class="text-p-muted text-xs mb-1">Amount Due</p>
        <p id="checkoutTotal" class="text-3xl font-black text-p-accent tabular-nums">$0.00</p>
      </div>
      <!-- Payment Method Tabs -->
      <div class="flex gap-2 px-5 py-3">
        <button onclick="selectPayMethod('cash')" class="pay-tab active flex-1 py-2.5 rounded-xl border border-p-border text-sm font-semibold flex items-center justify-center gap-2 transition"><i class="fas fa-money-bill-wave"></i> Cash</button>
        <button onclick="selectPayMethod('card')" class="pay-tab flex-1 py-2.5 rounded-xl border border-p-border text-sm font-semibold text-p-muted flex items-center justify-center gap-2 transition"><i class="fas fa-credit-card"></i> Card</button>
        <button onclick="selectPayMethod('digital')" class="pay-tab flex-1 py-2.5 rounded-xl border border-p-border text-sm font-semibold text-p-muted flex items-center justify-center gap-2 transition"><i class="fas fa-qrcode"></i> QR</button>
      </div>
      <!-- Cash Section -->
      <div id="cashSection" class="px-5 pb-5 space-y-3">
        <div class="flex gap-1.5 flex-wrap">
          <button onclick="cashQuick(5)" class="px-3 py-2 rounded-lg bg-p-card border border-p-border text-sm font-semibold text-p-text hover:border-p-accent/40 transition">$5</button>
          <button onclick="cashQuick(10)" class="px-3 py-2 rounded-lg bg-p-card border border-p-border text-sm font-semibold text-p-text hover:border-p-accent/40 transition">$10</button>
          <button onclick="cashQuick(20)" class="px-3 py-2 rounded-lg bg-p-card border border-p-border text-sm font-semibold text-p-text hover:border-p-accent/40 transition">$20</button>
          <button onclick="cashQuick(50)" class="px-3 py-2 rounded-lg bg-p-card border border-p-border text-sm font-semibold text-p-text hover:border-p-accent/40 transition">$50</button>
          <button onclick="cashQuick(100)" class="px-3 py-2 rounded-lg bg-p-card border border-p-border text-sm font-semibold text-p-text hover:border-p-accent/40 transition">$100</button>
        </div>
        <div class="bg-p-bg rounded-xl p-4 text-right border border-p-border">
          <p class="text-[10px] text-p-muted uppercase tracking-wider mb-1">Cash Received</p>
          <p id="cashDisplay" class="text-2xl font-bold text-p-text tabular-nums">$0.00</p>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <button onclick="cashKey('1')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">1</button>
          <button onclick="cashKey('2')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">2</button>
          <button onclick="cashKey('3')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">3</button>
          <button onclick="cashKey('4')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">4</button>
          <button onclick="cashKey('5')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">5</button>
          <button onclick="cashKey('6')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">6</button>
          <button onclick="cashKey('7')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">7</button>
          <button onclick="cashKey('8')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">8</button>
          <button onclick="cashKey('9')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">9</button>
          <button onclick="cashKey('.')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">.</button>
          <button onclick="cashKey('0')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-text font-semibold text-lg">0</button>
          <button onclick="cashKey('back')" class="numpad-btn py-3 rounded-lg bg-p-card border border-p-border text-p-muted text-lg"><i class="fas fa-delete-left"></i></button>
        </div>
        <div class="text-center py-2">
          <p class="text-p-muted text-xs">Change</p>
          <p id="changeDisplay" class="text-xl font-bold text-p-muted tabular-nums">$0.00</p>
        </div>
        <div class="flex gap-2">
          <button onclick="setExactCash()" class="flex-1 py-3 rounded-xl border border-p-accent/30 text-p-accent text-sm font-semibold hover:bg-p-accent/10 transition">Exact</button>
          <button id="cashPayBtn" onclick="confirmCashPay()" class="flex-[2] py-3 rounded-xl bg-p-success text-white text-sm font-bold transition disabled:opacity-40 disabled:cursor-not-allowed" disabled>Confirm Payment</button>
        </div>
      </div>
      <!-- Card Section -->
      <div id="cardSection" class="px-5 pb-5 hidden">
        <div class="text-center py-8 space-y-4">
          <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500/20 to-purple-500/20 border border-blue-500/20 flex items-center justify-center mx-auto">
            <i class="fas fa-credit-card text-3xl text-blue-400"></i>
          </div>
          <div>
            <p class="text-p-text font-semibold">Card Payment</p>
            <p class="text-p-muted text-sm mt-1">Insert, swipe, or tap card</p>
          </div>
          <button onclick="confirmCardPay()" class="px-8 py-3 rounded-xl bg-p-success text-white font-bold transition hover:brightness-110">Confirm Payment</button>
        </div>
      </div>
      <!-- Digital Section -->
      <div id="digitalSection" class="px-5 pb-5 hidden">
        <div class="text-center py-8 space-y-4">
          <div class="w-28 h-28 rounded-2xl bg-white flex items-center justify-center mx-auto p-2">
            <svg viewBox="0 0 100 100" class="w-full h-full"><rect width="100" height="100" fill="none"/><g fill="#000"><rect x="5" y="5" width="12" height="12"/><rect x="21" y="5" width="12" height="12"/><rect x="37" y="5" width="12" height="12"/><rect x="53" y="5" width="12" height="12"/><rect x="69" y="5" width="12" height="12"/><rect x="85" y="5" width="12" height="12"/><rect x="5" y="21" width="12" height="12"/><rect x="37" y="21" width="12" height="12"/><rect x="69" y="21" width="12" height="12"/><rect x="5" y="37" width="12" height="12"/><rect x="21" y="37" width="12" height="12"/><rect x="37" y="37" width="12" height="12"/><rect x="53" y="37" width="12" height="12"/><rect x="69" y="37" width="12" height="12"/><rect x="85" y="37" width="12" height="12"/><rect x="5" y="53" width="12" height="12"/><rect x="37" y="53" width="12" height="12"/><rect x="69" y="53" width="12" height="12"/><rect x="85" y="53" width="12" height="12"/><rect x="5" y="69" width="12" height="12"/><rect x="21" y="69" width="12" height="12"/><rect x="37" y="69" width="12" height="12"/><rect x="53" y="69" width="12" height="12"/><rect x="69" y="69" width="12" height="12"/><rect x="5" y="85" width="12" height="12"/><rect x="21" y="85" width="12" height="12"/><rect x="53" y="85" width="12" height="12"/><rect x="69" y="85" width="12" height="12"/><rect x="85" y="85" width="12" height="12"/></g><rect x="42" y="42" width="16" height="16" fill="#F59E0B" rx="2"/></svg>
          </div>
          <div>
            <p class="text-p-text font-semibold">Scan to Pay</p>
            <p class="text-p-muted text-sm mt-1">Customer scans QR code with their app</p>
          </div>
          <button onclick="confirmDigitalPay()" class="px-8 py-3 rounded-xl bg-p-success text-white font-bold transition hover:brightness-110">Confirm Payment</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-black/70" onclick="closeModal('receiptModal')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl w-full max-w-xs max-h-[90vh] overflow-y-auto anim-pop">
      <div id="receiptContent" class="p-6 font-mono text-[11px] text-gray-800 leading-relaxed"></div>
      <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 flex gap-2 rounded-b-2xl">
        <button onclick="closeModal('receiptModal')" class="flex-1 py-2.5 rounded-lg border border-gray-300 text-gray-600 text-sm font-semibold transition hover:bg-gray-50">Close</button>
        <button onclick="printCurrentReceipt()" class="flex-1 py-2.5 rounded-lg bg-gray-800 text-white text-sm font-semibold transition hover:bg-gray-700 flex items-center justify-center gap-2"><i class="fas fa-print text-xs"></i> Print</button>
      </div>
    </div>
  </div>
</div>

<!-- Discount Modal -->
<div id="discountModal" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-black/70" onclick="closeModal('discountModal')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative bg-p-surface rounded-2xl border border-p-border w-full max-w-sm anim-pop">
      <div class="flex items-center justify-between p-5 pb-3">
        <h3 class="text-lg font-bold text-p-text">Apply Discount</h3>
        <button onclick="closeModal('discountModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition"><i class="fas fa-xmark"></i></button>
      </div>
      <div class="px-5 pb-5 space-y-3">
        <div class="flex gap-2">
          <button onclick="setDiscountType('percent')" id="discPercentBtn" class="disc-tab flex-1 py-2 rounded-lg border border-p-border text-sm font-semibold text-p-accent bg-p-accent/10 transition">Percentage %</button>
          <button onclick="setDiscountType('fixed')" id="discFixedBtn" class="disc-tab flex-1 py-2 rounded-lg border border-p-border text-sm font-semibold text-p-muted transition">Fixed $</button>
        </div>
        <input id="discountInput" type="number" min="0" step="any" placeholder="Enter discount value"
          class="w-full bg-p-bg border border-p-border rounded-lg px-4 py-3 text-lg font-bold text-p-text text-center focus:outline-none focus:border-p-accent/50 transition tabular-nums">
        <div class="flex gap-2">
          <button onclick="closeModal('discountModal')" class="flex-1 py-3 rounded-xl border border-p-border text-p-muted text-sm font-semibold transition hover:bg-p-hover">Cancel</button>
          <button onclick="applyDiscount()" class="flex-1 py-3 rounded-xl bg-p-accent text-black text-sm font-bold transition hover:bg-p-accent-h">Apply</button>
        </div>
        <button onclick="removeDiscount()" id="removeDiscBtn" class="w-full py-2 rounded-lg text-xs text-p-danger hover:bg-p-danger/10 transition hidden">Remove current discount</button>
      </div>
    </div>
  </div>
</div>

<!-- Custom Item Modal -->
<div id="customModal" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-black/70" onclick="closeModal('customModal')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative bg-p-surface rounded-2xl border border-p-border w-full max-w-sm anim-pop">
      <div class="flex items-center justify-between p-5 pb-3">
        <h3 class="text-lg font-bold text-p-text">Custom Item</h3>
        <button onclick="closeModal('customModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition"><i class="fas fa-xmark"></i></button>
      </div>
      <div class="px-5 pb-5 space-y-3">
        <input id="customName" type="text" placeholder="Item name" class="w-full bg-p-bg border border-p-border rounded-lg px-4 py-2.5 text-sm text-p-text placeholder-p-muted focus:outline-none focus:border-p-accent/50 transition">
        <input id="customPrice" type="number" min="0" step="0.01" placeholder="Price ($)" class="w-full bg-p-bg border border-p-border rounded-lg px-4 py-2.5 text-sm text-p-text placeholder-p-muted focus:outline-none focus:border-p-accent/50 transition tabular-nums">
        <div class="flex gap-2">
          <button onclick="closeModal('customModal')" class="flex-1 py-3 rounded-xl border border-p-border text-p-muted text-sm font-semibold transition hover:bg-p-hover">Cancel</button>
          <button onclick="addCustomItem()" class="flex-1 py-3 rounded-xl bg-p-accent text-black text-sm font-bold transition hover:bg-p-accent-h">Add to Cart</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Note Modal -->
<div id="noteModal" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-black/70" onclick="closeModal('noteModal')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative bg-p-surface rounded-2xl border border-p-border w-full max-w-sm anim-pop">
      <div class="flex items-center justify-between p-5 pb-3">
        <h3 class="text-lg font-bold text-p-text">Item Note</h3>
        <button onclick="closeModal('noteModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition"><i class="fas fa-xmark"></i></button>
      </div>
      <div class="px-5 pb-5 space-y-3">
        <p id="noteItemName" class="text-sm text-p-muted"></p>
        <textarea id="noteInput" rows="3" placeholder="e.g. No sugar, extra shot..." class="w-full bg-p-bg border border-p-border rounded-lg px-4 py-2.5 text-sm text-p-text placeholder-p-muted focus:outline-none focus:border-p-accent/50 transition resize-none"></textarea>
        <div class="flex gap-2">
          <button onclick="closeModal('noteModal')" class="flex-1 py-3 rounded-xl border border-p-border text-p-muted text-sm font-semibold transition hover:bg-p-hover">Cancel</button>
          <button onclick="saveNote()" class="flex-1 py-3 rounded-xl bg-p-accent text-black text-sm font-bold transition hover:bg-p-accent-h">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Held Orders Modal -->
<div id="heldModal" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-black/70" onclick="closeModal('heldModal')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative bg-p-surface rounded-2xl border border-p-border w-full max-w-md max-h-[80vh] overflow-hidden flex flex-col anim-pop">
      <div class="flex items-center justify-between p-5 pb-3 flex-shrink-0">
        <h3 class="text-lg font-bold text-p-text">Held Orders</h3>
        <button onclick="closeModal('heldModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition"><i class="fas fa-xmark"></i></button>
      </div>
      <div class="flex-1 overflow-y-auto px-5 pb-5" id="heldList"></div>
    </div>
  </div>
</div>

<!-- Order History Modal -->
<div id="historyModal" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-black/70" onclick="closeModal('historyModal')"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative bg-p-surface rounded-2xl border border-p-border w-full max-w-lg max-h-[80vh] overflow-hidden flex flex-col anim-pop">
      <div class="flex items-center justify-between p-5 pb-3 flex-shrink-0">
        <h3 class="text-lg font-bold text-p-text">Order History</h3>
        <button onclick="closeModal('historyModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-p-muted hover:text-p-text hover:bg-p-hover transition"><i class="fas fa-xmark"></i></button>
      </div>
      <div class="flex-1 overflow-y-auto px-5 pb-5" id="historyList"></div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="fixed top-16 right-4 z-[100] space-y-2 pointer-events-none"></div>

<!-- ============ SCRIPT ============ -->
<script>
// ============ DATA ============
let categories = [
  { id: 'all', name: 'All Items', icon: 'fa-border-all', color: '#F59E0B' },
  { id: 'coffee', name: 'Coffee & Drinks', icon: 'fa-mug-hot', color: '#D97706' },
  { id: 'bakery', name: 'Pastries', icon: 'fa-cookie-bite', color: '#EF4444' },
  { id: 'sandwiches', name: 'Sandwiches', icon: 'fa-bread-slice', color: '#059669' },
  { id: 'salads', name: 'Salads & Bowls', icon: 'fa-leaf', color: '#10B981' },
  { id: 'desserts', name: 'Desserts', icon: 'fa-ice-cream', color: '#EC4899' },
  { id: 'snacks', name: 'Quick Snacks', icon: 'fa-bolt', color: '#8B5CF6' },
];

let products = [
  { id: 1, name: 'Espresso', price: 3.50, cat: 'coffee', img: 'https://picsum.photos/seed/esp01/200/200' },
  { id: 2, name: 'Americano', price: 4.00, cat: 'coffee', img: 'https://picsum.photos/seed/ame02/200/200' },
  { id: 3, name: 'Cappuccino', price: 4.50, cat: 'coffee', img: 'https://picsum.photos/seed/cap03/200/200' },
  { id: 4, name: 'Latte', price: 5.00, cat: 'coffee', img: 'https://picsum.photos/seed/lat04/200/200' },
  { id: 5, name: 'Mocha', price: 5.50, cat: 'coffee', img: 'https://picsum.photos/seed/moc05/200/200' },
  { id: 6, name: 'Iced Coffee', price: 4.50, cat: 'coffee', img: 'https://picsum.photos/seed/icd06/200/200' },
  { id: 7, name: 'Hot Chocolate', price: 4.50, cat: 'coffee', img: 'https://picsum.photos/seed/hch07/200/200' },
  { id: 8, name: 'Fresh OJ', price: 5.00, cat: 'coffee', img: 'https://picsum.photos/seed/foj08/200/200' },
  { id: 9, name: 'Croissant', price: 3.00, cat: 'bakery', img: 'https://picsum.photos/seed/crs09/200/200' },
  { id: 10, name: 'Chocolate Muffin', price: 3.50, cat: 'bakery', img: 'https://picsum.photos/seed/muf10/200/200' },
  { id: 11, name: 'Blueberry Scone', price: 3.50, cat: 'bakery', img: 'https://picsum.photos/seed/scn11/200/200' },
  { id: 12, name: 'Cinnamon Roll', price: 4.00, cat: 'bakery', img: 'https://picsum.photos/seed/cnr12/200/200' },
  { id: 13, name: 'Bagel', price: 2.50, cat: 'bakery', img: 'https://picsum.photos/seed/bgl13/200/200' },
  { id: 14, name: 'Danish Pastry', price: 3.75, cat: 'bakery', img: 'https://picsum.photos/seed/dns14/200/200' },
  { id: 15, name: 'Club Sandwich', price: 8.50, cat: 'sandwiches', img: 'https://picsum.photos/seed/clb15/200/200' },
  { id: 16, name: 'Chicken Wrap', price: 7.50, cat: 'sandwiches', img: 'https://picsum.photos/seed/wrp16/200/200' },
  { id: 17, name: 'BLT', price: 7.00, cat: 'sandwiches', img: 'https://picsum.photos/seed/blt17/200/200' },
  { id: 18, name: 'Turkey Panini', price: 8.00, cat: 'sandwiches', img: 'https://picsum.photos/seed/pnn18/200/200' },
  { id: 19, name: 'Veggie Sandwich', price: 6.50, cat: 'sandwiches', img: 'https://picsum.photos/seed/vgs19/200/200' },
  { id: 20, name: 'Caesar Salad', price: 8.00, cat: 'salads', img: 'https://picsum.photos/seed/ces20/200/200' },
  { id: 21, name: 'Greek Salad', price: 7.50, cat: 'salads', img: 'https://picsum.photos/seed/grk21/200/200' },
  { id: 22, name: 'Quinoa Bowl', price: 9.00, cat: 'salads', img: 'https://picsum.photos/seed/qno22/200/200' },
  { id: 23, name: 'Poke Bowl', price: 11.00, cat: 'salads', img: 'https://picsum.photos/seed/pok23/200/200' },
  { id: 24, name: 'Cheesecake', price: 6.00, cat: 'desserts', img: 'https://picsum.photos/seed/chs24/200/200' },
  { id: 25, name: 'Tiramisu', price: 6.50, cat: 'desserts', img: 'https://picsum.photos/seed/tir25/200/200' },
  { id: 26, name: 'Brownie', price: 4.50, cat: 'desserts', img: 'https://picsum.photos/seed/brw26/200/200' },
  { id: 27, name: 'Ice Cream', price: 3.50, cat: 'desserts', img: 'https://picsum.photos/seed/icm27/200/200' },
  { id: 28, name: 'French Fries', price: 4.00, cat: 'snacks', img: 'https://picsum.photos/seed/frm28/200/200' },
  { id: 29, name: 'Nachos', price: 5.50, cat: 'snacks', img: 'https://picsum.photos/seed/nch29/200/200' },
  { id: 30, name: 'Chicken Nuggets', price: 5.00, cat: 'snacks', img: 'https://picsum.photos/seed/ngt30/200/200' },
  { id: 31, name: 'Onion Rings', price: 4.50, cat: 'snacks', img: 'https://picsum.photos/seed/ong31/200/200' },
  { id: 32, name: 'Mixed Nuts', price: 3.00, cat: 'snacks', img: 'https://picsum.photos/seed/mxn32/200/200' },
];

// ============ STATE ============
const state = {
  cart: [],
  activeCategory: 'all',
  searchQuery: '',
  discount: { type: 'percent', value: 0 },
  taxRate: 0.08,
  heldOrders: [],
  completedOrders: [],
  orderCounter: 1000,
  cashInput: '',
  payMethod: 'cash',
  discountType: 'percent',
  noteIndex: -1,
  mobileCartOpen: false,
  lastReceipt: null,
  isSavingOrder: false,
};

const CATEGORY_API_URL = '/src/api/pos_category.php';
const PRODUCT_API_URL = '/src/api/pos_product.php';
const ORDER_API_URL = '/src/api/orders.php';
const CASHIER_ID = <?= json_encode((int)($_SESSION['user_id'] ?? 0)) ?>;
const CASHIER_NAME = <?= json_encode($_SESSION['user_name'] ?? 'JD', JSON_UNESCAPED_UNICODE) ?>;

// ============ UTILITIES ============
function fmt(n) { return '$' + (Number(n) || 0).toFixed(2); }

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

function playSound(type) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const g = ctx.createGain();
    osc.connect(g); g.connect(ctx.destination);
    g.gain.value = 0.08;
    if (type === 'add') { osc.frequency.value = 880; osc.start(); g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08); osc.stop(ctx.currentTime + 0.08); }
    else if (type === 'remove') { osc.frequency.value = 330; osc.start(); g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1); osc.stop(ctx.currentTime + 0.1); }
    else if (type === 'success') { osc.frequency.value = 660; osc.start(); setTimeout(() => { const o2 = ctx.createOscillator(); const g2 = ctx.createGain(); o2.connect(g2); g2.connect(ctx.destination); g2.gain.value = 0.08; o2.frequency.value = 880; o2.start(); g2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15); o2.stop(ctx.currentTime + 0.15); }, 120); g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12); osc.stop(ctx.currentTime + 0.12); }
    else if (type === 'error') { osc.frequency.value = 300; osc.start(); setTimeout(() => { const o2 = ctx.createOscillator(); const g2 = ctx.createGain(); o2.connect(g2); g2.connect(ctx.destination); g2.gain.value = 0.08; o2.frequency.value = 200; o2.start(); g2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15); o2.stop(ctx.currentTime + 0.15); }, 100); g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12); osc.stop(ctx.currentTime + 0.12); }
  } catch(e) {}
}

function showToast(msg, type = 'info') {
  const c = document.getElementById('toastContainer');
  const colors = { success: 'bg-emerald-600', error: 'bg-red-600', info: 'bg-p-card border border-p-border' };
  const icons = { success: 'fa-check-circle', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
  const t = document.createElement('div');
  t.className = `pointer-events-auto ${colors[type]} text-white px-4 py-3 rounded-xl shadow-xl flex items-center gap-3 text-sm font-medium toast-in max-w-xs`;
  t.innerHTML = `<i class="fas ${icons[type]}"></i><span></span>`;
  t.querySelector('span').textContent = msg;
  c.appendChild(t);
  setTimeout(() => { t.classList.remove('toast-in'); t.classList.add('toast-out'); setTimeout(() => t.remove(), 300); }, 2500);
}

function timeStr() { return new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }); }
function dateStr() { return new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }

function safeClass(value, fallback = '') {
  const text = String(value || '');
  return /^[a-z0-9_ -]+$/i.test(text) ? text : fallback;
}

async function parseApiResponse(res) {
  let json = null;
  try {
    json = await res.json();
  } catch (err) {
    throw new Error('Invalid API response');
  }

  if (!res.ok || !json.success) {
    throw new Error(json?.message || 'API request failed');
  }

  return json;
}

async function fetchOrders(status) {
  const res = await fetch(`${ORDER_API_URL}?status=${encodeURIComponent(status)}`, {
    headers: { Accept: 'application/json' },
  });
  const json = await parseApiResponse(res);
  return Array.isArray(json.data) ? json.data : [];
}

async function deleteOrderApi(id) {
  const res = await fetch(`${ORDER_API_URL}?id=${encodeURIComponent(id)}`, {
    method: 'DELETE',
    headers: { Accept: 'application/json' },
  });
  return parseApiResponse(res);
}

function cartProductId(item) {
  const id = Number(item?.product?.id);
  return Number.isInteger(id) && id > 0 ? id : null;
}

function buildOrderPayload(status, payment = null) {
  const payload = {
    status,
    cashier_id: CASHIER_ID || null,
    cashier_name: CASHIER_NAME || 'JD',
    tax_rate: state.taxRate,
    discount: { ...state.discount },
    items: state.cart.map(item => ({
      product_id: cartProductId(item),
      product: {
        id: item.product.id,
        name: item.product.name,
        price: Number(item.product.price) || 0,
        img: item.product.img || null,
      },
      quantity: Number(item.quantity) || 0,
      note: item.note || '',
    })),
  };

  if (payment) {
    payload.payment = payment;
  }

  return payload;
}

async function saveOrderApi(status, payment = null) {
  const res = await fetch(ORDER_API_URL, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(buildOrderPayload(status, payment)),
  });
  const json = await parseApiResponse(res);
  return json.data;
}

function setOrderSaving(isSaving) {
  state.isSavingOrder = isSaving;
  const payBtn = document.getElementById('payBtn');
  const holdBtn = document.getElementById('holdBtn');
  if (payBtn) payBtn.disabled = isSaving || state.cart.length === 0;
  if (holdBtn) holdBtn.disabled = isSaving || state.cart.length === 0;
  if (document.getElementById('cashPayBtn')) updateCashDisplay();
}

// ============ CATEGORY API ============
async function loadCategories() {
  try {
    const res = await fetch(CATEGORY_API_URL, { headers: { Accept: 'application/json' } });
    const json = await res.json();
    if (!res.ok || !json.success || !Array.isArray(json.data)) {
      throw new Error(json.message || 'Failed to load categories');
    }

    categories = json.data.map(c => ({
      id: c.id || c.slug,
      dbId: Number(c.db_id) || null,
      name: c.name,
      icon: c.icon || 'fa-tag',
      color: c.color || '#F59E0B',
      productCount: Number(c.product_count) || 0,
    }));

    if (!categories.some(c => c.id === state.activeCategory)) {
      state.activeCategory = categories[0]?.id || 'all';
    }

    renderCategories();
    renderProducts();
  } catch (err) {
    showToast('Using local categories - API unavailable', 'info');
  }
}

// ============ PRODUCT API ============
async function loadProducts() {
  try {
    const res = await fetch(PRODUCT_API_URL, { headers: { Accept: 'application/json' } });
    const json = await res.json();
    if (!res.ok || !json.success || !Array.isArray(json.data)) {
      throw new Error(json.message || 'Failed to load products');
    }

    products = json.data.map(p => ({
      id: Number(p.id),
      name: p.name,
      price: Number(p.price) || 0,
      cat: p.cat || p.category_slug || '',
      img: p.img || p.image_url || '',
      categoryId: Number(p.category_id) || null,
    }));

    renderCategories();
    renderProducts();
  } catch (err) {
    showToast('Using local products - API unavailable', 'info');
  }
}

// ============ CART LOGIC ============
function addToCart(productId, btnEl) {
  const prod = products.find(p => p.id === productId);
  if (!prod) return;
  const existing = state.cart.find(i => i.product.id === productId && !i.note);
  if (existing) { existing.quantity++; existing.lineTotal = existing.quantity * existing.product.price; }
  else { state.cart.push({ product: prod, quantity: 1, note: '', lineTotal: prod.price }); }
  playSound('add');
  // Float +1 animation
  if (btnEl) {
    const r = btnEl.getBoundingClientRect();
    const f = document.createElement('div');
    f.className = 'float-plus'; f.textContent = '+1';
    f.style.left = r.left + r.width / 2 - 10 + 'px';
    f.style.top = r.top - 5 + 'px';
    document.body.appendChild(f);
    setTimeout(() => f.remove(), 600);
  }
  bounceBadge();
  renderCart();
}

function addCustomItem() {
  const name = document.getElementById('customName').value.trim();
  const price = parseFloat(document.getElementById('customPrice').value);
  if (!name) { showToast('Enter item name', 'error'); return; }
  if (isNaN(price) || price <= 0) { showToast('Enter valid price', 'error'); return; }
  state.cart.push({ product: { id: 'custom_' + Date.now(), name, price, cat: 'custom', img: null }, quantity: 1, note: '', lineTotal: price });
  closeModal('customModal');
  document.getElementById('customName').value = '';
  document.getElementById('customPrice').value = '';
  playSound('add');
  bounceBadge();
  renderCart();
  showToast(`"${name}" added`, 'success');
}

function updateQuantity(idx, delta) {
  if (idx < 0 || idx >= state.cart.length) return;
  state.cart[idx].quantity += delta;
  if (state.cart[idx].quantity <= 0) { state.cart.splice(idx, 1); playSound('remove'); }
  else { state.cart[idx].lineTotal = state.cart[idx].quantity * state.cart[idx].product.price; }
  renderCart();
}

function removeFromCart(idx) {
  if (idx < 0 || idx >= state.cart.length) return;
  state.cart.splice(idx, 1);
  playSound('remove');
  renderCart();
}

function clearCart() {
  if (state.cart.length === 0) return;
  state.cart = [];
  state.discount = { type: 'percent', value: 0 };
  playSound('remove');
  renderCart();
  showToast('Cart cleared', 'info');
}

function calcTotals() {
  const subtotal = state.cart.reduce((s, i) => s + (Number(i.lineTotal) || 0), 0);
  let discAmt = 0;
  if (state.discount.value > 0) {
    discAmt = state.discount.type === 'percent' ? subtotal * (state.discount.value / 100) : state.discount.value;
    discAmt = Math.min(discAmt, subtotal);
  }
  const tax = (subtotal - discAmt) * state.taxRate;
  const total = subtotal - discAmt + tax;
  return { subtotal, discAmt, tax, total };
}

function totalItems() { return state.cart.reduce((s, i) => s + (Number(i.quantity) || 0), 0); }

// ============ DISCOUNT ============
function setDiscountType(t) {
  state.discountType = t;
  document.getElementById('discPercentBtn').className = `disc-tab flex-1 py-2 rounded-lg border text-sm font-semibold transition ${t === 'percent' ? 'text-p-accent bg-p-accent/10 border-p-accent/30' : 'text-p-muted border-p-border hover:bg-p-hover'}`;
  document.getElementById('discFixedBtn').className = `disc-tab flex-1 py-2 rounded-lg border text-sm font-semibold transition ${t === 'fixed' ? 'text-p-accent bg-p-accent/10 border-p-accent/30' : 'text-p-muted border-p-border hover:bg-p-hover'}`;
}

function applyDiscount() {
  const val = parseFloat(document.getElementById('discountInput').value);
  if (isNaN(val) || val <= 0) { showToast('Enter a valid discount', 'error'); return; }
  if (state.discountType === 'percent' && val > 100) { showToast('Max 100%', 'error'); return; }
  if (state.discountType === 'fixed' && val > calcTotals().subtotal) { showToast('Discount exceeds subtotal', 'error'); return; }
  state.discount = { type: state.discountType, value: val };
  document.getElementById('discountInput').value = '';
  closeModal('discountModal');
  renderCart();
  showToast(`Discount applied: ${state.discountType === 'percent' ? val + '%' : fmt(val)}`, 'success');
}

function removeDiscount() {
  state.discount = { type: 'percent', value: 0 };
  closeModal('discountModal');
  renderCart();
  showToast('Discount removed', 'info');
}

// ============ NOTES ============
function openNote(idx) {
  state.noteIndex = idx;
  document.getElementById('noteItemName').textContent = state.cart[idx].product.name;
  document.getElementById('noteInput').value = state.cart[idx].note || '';
  openModal('noteModal');
  setTimeout(() => document.getElementById('noteInput').focus(), 100);
}

function saveNote() {
  if (state.noteIndex < 0) return;
  state.cart[state.noteIndex].note = document.getElementById('noteInput').value.trim();
  closeModal('noteModal');
  renderCart();
  showToast('Note saved', 'success');
}

// ============ ORDERS ============
async function loadHeldOrders() {
  state.heldOrders = await fetchOrders('held');
  updateHeldBadge();
  return state.heldOrders;
}

async function loadCompletedOrders() {
  state.completedOrders = await fetchOrders('completed');
  return state.completedOrders;
}

async function holdOrder() {
  if (state.cart.length === 0) { showToast('Cart is empty', 'error'); return; }
  if (state.isSavingOrder) return;

  setOrderSaving(true);
  try {
    const order = await saveOrderApi('held');
    state.heldOrders.unshift(order);
    state.cart = [];
    state.discount = { type: 'percent', value: 0 };
    renderCart();
    updateHeldBadge();
    showToast(`Order #${order.id} held`, 'success');
  } catch (err) {
    playSound('error');
    showToast(err.message || 'Failed to hold order', 'error');
  } finally {
    setOrderSaving(false);
  }
}

async function recallOrder(id) {
  const idx = state.heldOrders.findIndex(o => o.id === id);
  if (idx < 0) return;
  if (state.cart.length > 0) { showToast('Clear current cart first', 'error'); return; }
  if (state.isSavingOrder) return;

  setOrderSaving(true);
  try {
    await deleteOrderApi(id);
    const order = state.heldOrders.splice(idx, 1)[0];
    state.cart = order.items;
    state.discount = order.discount;
    closeModal('heldModal');
    renderCart();
    updateHeldBadge();
    showToast(`Order #${order.id} recalled`, 'success');
  } catch (err) {
    playSound('error');
    showToast(err.message || 'Failed to recall order', 'error');
  } finally {
    setOrderSaving(false);
  }
}

async function deleteHeldOrder(id) {
  if (state.isSavingOrder) return;

  setOrderSaving(true);
  try {
    await deleteOrderApi(id);
    state.heldOrders = state.heldOrders.filter(o => o.id !== id);
    renderHeldOrdersList();
    updateHeldBadge();
    showToast('Held order deleted', 'info');
  } catch (err) {
    playSound('error');
    showToast(err.message || 'Failed to delete held order', 'error');
  } finally {
    setOrderSaving(false);
  }
}

async function completeOrder(payment) {
  if (state.cart.length === 0) { showToast('Cart is empty', 'error'); return; }
  if (state.isSavingOrder) return;

  setOrderSaving(true);
  try {
    const order = await saveOrderApi('completed', payment);
    state.completedOrders.unshift(order);
    state.lastReceipt = order;
    state.cart = [];
    state.discount = { type: 'percent', value: 0 };
    playSound('success');
    renderCart();
    closeModal('checkoutModal');
    showReceipt(order);
    showToast(`Payment successful — #${order.id}`, 'success');
  } catch (err) {
    playSound('error');
    showToast(err.message || 'Failed to save order', 'error');
  } finally {
    setOrderSaving(false);
  }
}

// ============ CHECKOUT ============
function openCheckout() {
  if (state.cart.length === 0) { showToast('Cart is empty', 'error'); return; }
  if (state.isSavingOrder) return;
  const t = calcTotals();
  document.getElementById('checkoutTotal').textContent = fmt(t.total);
  state.cashInput = '';
  state.payMethod = 'cash';
  selectPayMethod('cash');
  openModal('checkoutModal');
}

function selectPayMethod(m) {
  state.payMethod = m;
  document.querySelectorAll('.pay-tab').forEach((b, i) => {
    b.classList.toggle('active', ['cash', 'card', 'digital'][i] === m);
  });
  document.getElementById('cashSection').classList.toggle('hidden', m !== 'cash');
  document.getElementById('cardSection').classList.toggle('hidden', m !== 'card');
  document.getElementById('digitalSection').classList.toggle('hidden', m !== 'digital');
  updateCashDisplay();
}

function cashQuick(amt) {
  state.cashInput = amt.toFixed(2);
  updateCashDisplay();
}

function cashKey(k) {
  if (k === 'back') { state.cashInput = state.cashInput.slice(0, -1); }
  else if (k === '.') { if (!state.cashInput.includes('.')) state.cashInput += state.cashInput ? '.' : '0.'; }
  else { if (state.cashInput.includes('.') && state.cashInput.split('.')[1].length >= 2) return; state.cashInput += k; }
  updateCashDisplay();
}

function setExactCash() {
  state.cashInput = calcTotals().total.toFixed(2);
  updateCashDisplay();
}

function updateCashDisplay() {
  const val = parseFloat(state.cashInput) || 0;
  const total = calcTotals().total;
  document.getElementById('cashDisplay').textContent = fmt(val);
  const change = val - total;
  const cd = document.getElementById('changeDisplay');
  cd.textContent = fmt(Math.max(0, change));
  cd.className = `text-xl font-bold tabular-nums ${change >= 0 ? 'text-p-success' : 'text-p-danger'}`;
  document.getElementById('cashPayBtn').disabled = state.isSavingOrder || state.cart.length === 0 || val < total;
}

function confirmCashPay() {
  if (state.isSavingOrder) return;
  const val = parseFloat(state.cashInput) || 0;
  if (val < calcTotals().total) return;
  completeOrder({ method: 'cash', amount: val, change: val - calcTotals().total });
}

function confirmCardPay() { if (!state.isSavingOrder) completeOrder({ method: 'card' }); }
function confirmDigitalPay() { if (!state.isSavingOrder) completeOrder({ method: 'digital' }); }

// ============ RECEIPT ============
function buildReceiptHTML(order) {
  const t = order.totals;
  const payment = order.payment || { method: 'digital' };
  const payLabel = payment.method === 'cash' ? `Cash — Received: ${fmt(payment.amount)} | Change: ${fmt(payment.change)}` : payment.method === 'card' ? 'Card Payment' : 'Digital / QR Payment';
  let items = order.items.map(i => {
    const noteLine = i.note ? ` <span style="color:#999">[${escapeHtml(i.note)}]</span>` : '';
    return `<div style="display:flex;justify-content:space-between"><span>${i.quantity}x ${escapeHtml(i.product.name)}${noteLine}</span><span>${fmt(i.lineTotal)}</span></div>`;
  }).join('');
  return `
    <div style="text-align:center;margin-bottom:8px"><b style="font-size:14px">QuickPOS</b><br><span style="color:#888;font-size:10px">123 Main Street &bull; (555) 123-4567</span></div>
    <div style="border-top:1px dashed #ccc;border-bottom:1px dashed #ccc;padding:6px 0;margin:6px 0;font-size:10px">
      <div style="display:flex;justify-content:space-between"><span>Order #${order.id}</span><span>${escapeHtml(order.time)}</span></div>
      <div>${escapeHtml(order.date)} &bull; Cashier: ${escapeHtml(order.cashier_name || CASHIER_NAME || 'JD')}</div>
    </div>
    <div style="margin:8px 0">${items}</div>
    <div style="border-top:1px dashed #ccc;padding-top:8px">
      <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>${fmt(t.subtotal)}</span></div>
      <div style="display:flex;justify-content:space-between"><span>Tax (${(state.taxRate * 100).toFixed(0)}%)</span><span>${fmt(t.tax)}</span></div>
      ${t.discAmt > 0 ? `<div style="display:flex;justify-content:space-between;color:#dc2626"><span>Discount</span><span>-${fmt(t.discAmt)}</span></div>` : ''}
      <div style="border-top:1px dashed #ccc;margin-top:6px;padding-top:6px;display:flex;justify-content:space-between;font-weight:bold;font-size:13px"><span>TOTAL</span><span>${fmt(t.total)}</span></div>
    </div>
    <div style="border-top:1px dashed #ccc;border-bottom:1px dashed #ccc;padding:6px 0;margin:8px 0;font-size:10px;text-align:center">${payLabel}</div>
    <div style="text-align:center;margin-top:10px;font-size:10px;color:#888">Thank you for your purchase!<br>Please come again</div>
  `;
}

function showReceipt(order) {
  document.getElementById('receiptContent').innerHTML = buildReceiptHTML(order);
  openModal('receiptModal');
}

function printCurrentReceipt() {
  if (!state.lastReceipt) return;
  const html = `<!DOCTYPE html><html><head><title>Receipt #${state.lastReceipt.id}</title><style>body{font-family:'Courier New',monospace;font-size:12px;padding:15px;width:280px;margin:0 auto;color:#000}b{font-weight:700}.center{text-align:center}.line{border-top:1px dashed #000;margin:6px 0}.row{display:flex;justify-content:space-between}</style></head><body>${buildReceiptHTML(state.lastReceipt)}</body></html>`;
  const w = window.open('', '_blank', 'width=320,height=600');
  w.document.write(html); w.document.close();
  setTimeout(() => { w.print(); w.close(); }, 300);
}

// ============ RENDER ============
function renderCategories() {
  // Desktop sidebar
  const sb = document.getElementById('catSidebar');
  sb.innerHTML = categories.map(c => {
    const count = c.productCount ?? (c.id !== 'all' ? products.filter(p => p.cat === c.id).length : products.length);
    return `
    <button onclick='setCategory(${JSON.stringify(c.id)})' class="cat-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left text-sm transition ${state.activeCategory === c.id ? 'active' : 'text-p-muted hover:text-p-text hover:bg-p-hover'}">
      <div class="cat-dot w-2 h-2 rounded-full flex-shrink-0 transition" style="background:${state.activeCategory === c.id ? c.color : 'transparent'}"></div>
      <i class="fas ${safeClass(c.icon, 'fa-tag')} w-4 text-center text-xs"></i>
      <span class="truncate">${escapeHtml(c.name)}</span>
      <span class="ml-auto text-[10px] text-p-muted">${count}</span>
    </button>
  `}).join('');
  // Mobile pills
  const pills = document.getElementById('catPills');
  pills.innerHTML = categories.map(c => `
    <button onclick='setCategory(${JSON.stringify(c.id)})' class="cat-pill flex-shrink-0 px-4 py-2 rounded-full text-xs border border-p-border text-p-muted transition whitespace-nowrap ${state.activeCategory === c.id ? 'active' : 'hover:bg-p-hover hover:text-p-text'}">
      <i class="fas ${safeClass(c.icon, 'fa-tag')} mr-1"></i>${escapeHtml(c.name)}
    </button>
  `).join('');
}

function renderProducts() {
  const q = state.searchQuery.toLowerCase().trim();
  let list = products;
  if (state.activeCategory !== 'all') list = list.filter(p => p.cat === state.activeCategory);
  if (q) list = list.filter(p => p.name.toLowerCase().includes(q));
  const grid = document.getElementById('productGrid');
  const empty = document.getElementById('noProducts');
  if (list.length === 0) { grid.classList.add('hidden'); empty.classList.remove('hidden'); empty.classList.add('flex'); return; }
  grid.classList.remove('hidden'); empty.classList.add('hidden'); empty.classList.remove('flex');
  grid.innerHTML = list.map((p, i) => {
    const productId = JSON.stringify(p.id);
    const productName = escapeHtml(p.name);
    const image = p.img
      ? `<img src="${escapeHtml(p.img)}" alt="${productName}" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105" loading="lazy" onerror="this.style.display='none'">`
      : `<div class="w-full h-full flex items-center justify-center text-p-muted"><i class="fas fa-image text-3xl opacity-30"></i></div>`;
    return `
    <div class="product-card bg-p-card rounded-xl border border-p-border overflow-hidden cursor-pointer anim-in" style="animation-delay:${Math.min(i * 0.03, 0.3)}s">
      <div class="aspect-square bg-p-bg relative overflow-hidden" onclick="addToCart(${productId}, this.querySelector('.add-btn'))">
        ${image}
        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent pointer-events-none"></div>
        <button class="add-btn absolute bottom-2 right-2 w-10 h-10 bg-p-accent hover:bg-p-accent-h rounded-full flex items-center justify-center shadow-lg shadow-amber-500/25 transition-all active:scale-90" onclick="event.stopPropagation(); addToCart(${productId}, this)">
          <i class="fas fa-plus text-black text-sm font-bold"></i>
        </button>
      </div>
      <div class="p-2.5" onclick="addToCart(${productId}, this.parentElement.querySelector('.add-btn'))">
        <p class="text-[13px] font-semibold text-p-text truncate leading-tight">${productName}</p>
        <p class="text-p-accent font-bold text-sm mt-1">${fmt(p.price)}</p>
      </div>
    </div>
  `}).join('');
}

function renderCart() {
  const container = document.getElementById('cartItems');
  const t = calcTotals();
  // Cart items
  if (state.cart.length === 0) {
    container.innerHTML = `
      <div class="flex flex-col items-center justify-center py-12 text-p-muted">
        <i class="fas fa-bag-shopping text-3xl mb-3 opacity-30"></i>
        <p class="text-sm font-medium">Cart is empty</p>
        <p class="text-xs mt-1 opacity-60">Tap products to add</p>
      </div>`;
  } else {
    container.innerHTML = state.cart.map((item, i) => `
      <div class="cart-item flex items-start gap-2.5 p-2.5 rounded-lg bg-p-card/50 border border-p-border/50">
        ${item.product.img ? `<img src="${escapeHtml(item.product.img)}" class="w-11 h-11 rounded-lg object-cover flex-shrink-0" onerror="this.style.display='none'">` : `<div class="w-11 h-11 rounded-lg bg-p-bg flex items-center justify-center flex-shrink-0"><i class="fas fa-receipt text-p-muted text-xs"></i></div>`}
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-1">
            <p class="text-[13px] font-semibold text-p-text truncate leading-tight">${escapeHtml(item.product.name)}</p>
            <button onclick="removeFromCart(${i})" class="w-6 h-6 rounded flex items-center justify-center text-p-muted hover:text-p-danger hover:bg-p-danger/10 transition flex-shrink-0"><i class="fas fa-xmark text-[10px]"></i></button>
          </div>
          ${item.note ? `<p class="text-[10px] text-p-accent mt-0.5 truncate"><i class="fas fa-sticky-note mr-0.5"></i>${escapeHtml(item.note)}</p>` : ''}
          <div class="flex items-center justify-between mt-1.5">
            <div class="flex items-center gap-1">
              <button onclick="updateQuantity(${i}, -1)" class="w-7 h-7 rounded-md bg-p-bg border border-p-border flex items-center justify-center text-p-muted hover:text-p-text hover:border-p-accent/30 transition active:scale-90"><i class="fas fa-minus text-[9px]"></i></button>
              <span class="w-7 text-center text-sm font-bold text-p-text tabular-nums">${item.quantity}</span>
              <button onclick="updateQuantity(${i}, 1)" class="w-7 h-7 rounded-md bg-p-bg border border-p-border flex items-center justify-center text-p-muted hover:text-p-text hover:border-p-accent/30 transition active:scale-90"><i class="fas fa-plus text-[9px]"></i></button>
            </div>
            <div class="flex items-center gap-1.5">
              <button onclick="openNote(${i})" class="w-6 h-6 rounded flex items-center justify-center text-p-muted hover:text-p-accent hover:bg-p-accent/10 transition" title="Add note"><i class="fas fa-pen text-[9px]"></i></button>
              <span class="text-sm font-bold text-p-text tabular-nums">${fmt(item.lineTotal)}</span>
            </div>
          </div>
        </div>
      </div>
    `).join('');
  }
  // Summary
  document.getElementById('subtotalDisplay').textContent = fmt(t.subtotal);
  document.getElementById('taxDisplay').textContent = fmt(t.tax);
  document.getElementById('discountDisplay').textContent = '-' + fmt(t.discAmt);
  document.getElementById('discountAmount').textContent = '-' + fmt(t.discAmt);
  document.getElementById('totalDisplay').textContent = fmt(t.total);
  document.getElementById('cartCount').textContent = totalItems() + ' item' + (totalItems() !== 1 ? 's' : '');
  // Discount label
  const dl = document.getElementById('discountLabel');
  if (state.discount.value > 0) { dl.textContent = `Discount: ${state.discount.type === 'percent' ? state.discount.value + '%' : fmt(state.discount.value)}`; dl.classList.add('text-p-accent'); dl.classList.remove('text-p-muted'); }
  else { dl.textContent = 'Add Discount'; dl.classList.remove('text-p-accent'); dl.classList.add('text-p-muted'); }
  // Buttons
  document.getElementById('payBtn').disabled = state.isSavingOrder || state.cart.length === 0;
  document.getElementById('holdBtn').disabled = state.isSavingOrder || state.cart.length === 0;
  // Badge
  updateFabBadge();
}

function renderHeldOrdersList() {
  const el = document.getElementById('heldList');
  if (state.heldOrders.length === 0) {
    el.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-p-muted"><i class="fas fa-pause text-3xl mb-3 opacity-30"></i><p class="text-sm">No held orders</p></div>`;
    return;
  }
  el.innerHTML = state.heldOrders.map(o => `
    <div class="bg-p-card border border-p-border rounded-xl p-4 mb-2">
      <div class="flex items-center justify-between mb-2">
        <div>
          <span class="font-bold text-p-text">#${o.id}</span>
          <span class="text-xs text-p-muted ml-2">${o.time}</span>
        </div>
        <span class="text-p-accent font-bold text-sm">${fmt(o.totals.total)}</span>
      </div>
      <p class="text-xs text-p-muted mb-3">${o.items.map(i => `${i.quantity}x ${escapeHtml(i.product.name)}`).join(', ')}</p>
      <div class="flex gap-2">
        <button onclick="recallOrder(${o.id})" class="flex-1 py-2 rounded-lg bg-p-accent/10 text-p-accent text-xs font-semibold hover:bg-p-accent/20 transition"><i class="fas fa-rotate-left mr-1"></i>Recall</button>
        <button onclick="deleteHeldOrder(${o.id})" class="py-2 px-3 rounded-lg bg-p-danger/10 text-p-danger text-xs font-semibold hover:bg-p-danger/20 transition"><i class="fas fa-trash"></i></button>
      </div>
    </div>
  `).join('');
}

async function renderHeldOrders() {
  const el = document.getElementById('heldList');
  el.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-p-muted"><i class="fas fa-spinner fa-spin text-3xl mb-3 opacity-50"></i><p class="text-sm">Loading held orders...</p></div>`;
  try {
    await loadHeldOrders();
    renderHeldOrdersList();
  } catch (err) {
    el.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-p-muted"><i class="fas fa-circle-exclamation text-3xl mb-3 text-p-danger"></i><p class="text-sm">${escapeHtml(err.message || 'Failed to load held orders')}</p></div>`;
    showToast(err.message || 'Failed to load held orders', 'error');
  }
}

function showOrderReceipt(id) {
  const order = state.completedOrders.find(o => o.id === id);
  if (!order) return;
  state.lastReceipt = order;
  showReceipt(order);
}

function renderOrderHistoryList() {
  const el = document.getElementById('historyList');
  if (state.completedOrders.length === 0) {
    el.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-p-muted"><i class="fas fa-clock-rotate-left text-3xl mb-3 opacity-30"></i><p class="text-sm">No order history</p></div>`;
    return;
  }
  el.innerHTML = state.completedOrders.map(o => {
    const method = o.payment?.method || 'unknown';
    const payIcon = method === 'cash' ? 'fa-money-bill-wave text-emerald-400' : method === 'card' ? 'fa-credit-card text-blue-400' : 'fa-qrcode text-purple-400';
    return `
    <div class="bg-p-card border border-p-border rounded-xl p-4 mb-2">
      <div class="flex items-center justify-between mb-1.5">
        <div class="flex items-center gap-2">
          <span class="font-bold text-p-text">#${o.id}</span>
          <i class="fas ${payIcon} text-xs"></i>
          <span class="text-[10px] px-1.5 py-0.5 rounded bg-p-success/10 text-p-success font-semibold">${method.toUpperCase()}</span>
        </div>
        <span class="text-p-accent font-bold">${fmt(o.totals.total)}</span>
      </div>
      <div class="flex items-center justify-between">
        <p class="text-xs text-p-muted">${o.date} ${o.time} &bull; ${o.items.reduce((s,i)=>s+i.quantity,0)} items</p>
        <button onclick="showOrderReceipt(${o.id})" class="text-xs text-p-muted hover:text-p-accent transition"><i class="fas fa-receipt"></i></button>
      </div>
    </div>`;
  }).join('');
}

async function renderOrderHistory() {
  const el = document.getElementById('historyList');
  el.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-p-muted"><i class="fas fa-spinner fa-spin text-3xl mb-3 opacity-50"></i><p class="text-sm">Loading order history...</p></div>`;
  try {
    await loadCompletedOrders();
    renderOrderHistoryList();
  } catch (err) {
    el.innerHTML = `<div class="flex flex-col items-center justify-center py-12 text-p-muted"><i class="fas fa-circle-exclamation text-3xl mb-3 text-p-danger"></i><p class="text-sm">${escapeHtml(err.message || 'Failed to load order history')}</p></div>`;
    showToast(err.message || 'Failed to load order history', 'error');
  }
}

// ============ UI HELPERS ============
function setCategory(id) {
  state.activeCategory = id;
  renderCategories();
  renderProducts();
  // Scroll mobile categories to show active
  const pills = document.getElementById('catPills');
  const active = pills.querySelector('.active');
  if (active) active.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
}

function bounceBadge() {
  const b = document.getElementById('fabBadge');
  b.classList.remove('badge-bounce');
  void b.offsetWidth;
  b.classList.add('badge-bounce');
}

function updateFabBadge() {
  const cnt = totalItems();
  const fab = document.getElementById('cartFab');
  const badge = document.getElementById('fabBadge');
  if (cnt > 0) { fab.classList.remove('hidden'); badge.classList.remove('hidden'); badge.textContent = cnt; }
  else { fab.classList.add('hidden'); badge.classList.add('hidden'); }
}

function updateHeldBadge() {
  const b = document.getElementById('heldBadge');
  if (state.heldOrders.length > 0) { b.classList.remove('hidden'); b.classList.add('flex'); b.textContent = state.heldOrders.length; }
  else { b.classList.add('hidden'); b.classList.remove('flex'); }
}

function toggleMobileCart(open) {
  state.mobileCartOpen = open;
  const panel = document.getElementById('cartPanel');
  const backdrop = document.getElementById('cartBackdrop');
  if (open) { backdrop.classList.remove('hidden'); requestAnimationFrame(() => { panel.style.transform = 'translateY(0)'; }); }
  else { panel.style.transform = ''; backdrop.classList.add('hidden'); }
}

function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function updateClock() {
  document.getElementById('clockTime').textContent = timeStr();
  document.getElementById('clockDate').textContent = dateStr();
}

// ============ SEARCH ============
function handleSearch(val) {
  state.searchQuery = val;
  renderProducts();
}

// ============ INIT ============
function init() {
  renderCategories();
  renderProducts();
  renderCart();
  loadCategories();
  loadProducts();
  loadHeldOrders().catch(() => {});
  updateClock();
  setInterval(updateClock, 1000);

  // Search listeners
  const sd = document.getElementById('searchDesktop');
  const sm = document.getElementById('searchMobile');
  sd.addEventListener('input', e => { sm.value = e.target.value; handleSearch(e.target.value); });
  sm.addEventListener('input', e => { sd.value = e.target.value; handleSearch(e.target.value); });

  // Keyboard shortcuts
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('#checkoutModal,#receiptModal,#discountModal,#customModal,#noteModal,#heldModal,#historyModal').forEach(m => { if (!m.classList.contains('hidden')) m.classList.add('hidden'); });
      if (state.mobileCartOpen) toggleMobileCart(false);
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); sd.focus(); sm.focus(); }
  });

  // Discount modal state
  if (state.discount.value > 0) {
    document.getElementById('removeDiscBtn').classList.remove('hidden');
  }
  const discountObs = new MutationObserver(() => {
    const hasDisc = state.discount.value > 0;
    document.getElementById('removeDiscBtn').classList.toggle('hidden', !hasDisc);
    if (hasDisc) { setDiscountType(state.discount.type); document.getElementById('discountInput').value = state.discount.value; }
  });

  // Handle window resize — close mobile cart if resizing to desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024 && state.mobileCartOpen) toggleMobileCart(false);
  });
}

document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
