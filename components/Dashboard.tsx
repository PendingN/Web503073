"use client";

import Link from "next/link";
import { FormEvent, useEffect, useMemo, useRef, useState } from "react";
import PlantIllustration from "./PlantIllustration";
import { collections, formatPrice, products, type DemoCartItem, type Product } from "../shared/content";

type SectionId = "overview" | "collections" | "products" | "care";

const navItems: Array<{ id: SectionId; label: string; icon: typeof HomeIcon }> = [
  { id: "overview", label: "Tổng quan", icon: HomeIcon },
  { id: "products", label: "Sản phẩm", icon: ShopIcon },
  { id: "care", label: "Chăm sóc cây", icon: LeafIcon },
  { id: "collections", label: "Bộ sưu tập", icon: SearchNavIcon },
];

const sectionLabels: Record<SectionId, string> = {
  overview: "Tổng quan",
  products: "Sản phẩm",
  care: "Chăm sóc cây",
  collections: "Bộ sưu tập",
};

export default function Dashboard() {
  const [active, setActive] = useState<SectionId>("overview");
  const [searchFocused, setSearchFocused] = useState(false);
  const [notice, setNotice] = useState("");
  const [cartItems, setCartItems] = useState<DemoCartItem[]>([]);
  const [query, setQuery] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("");
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
  const [cartOpen, setCartOpen] = useState(false);
  const mainRef = useRef<HTMLElement>(null);
  const noticeTimer = useRef<number | undefined>(undefined);

  const filteredProducts = useMemo(() => products.filter(product => {
    const matchesQuery = product.name.toLocaleLowerCase("vi").includes(query.toLocaleLowerCase("vi"));
    const matchesCategory = !categoryFilter || product.category === categoryFilter;
    return matchesQuery && matchesCategory;
  }), [categoryFilter, query]);

  const cartCount = cartItems.reduce((total, item) => total + item.quantity, 0);
  const cartProducts = cartItems.map(item => ({ ...item, product: products.find(product => product.id === item.productId)! })).filter(item => item.product);

  function showNotice(message: string) {
    setNotice(message);
    if (noticeTimer.current) window.clearTimeout(noticeTimer.current);
    noticeTimer.current = window.setTimeout(() => setNotice(""), 3200);
  }

  function scrollToSection(id: SectionId, updateUrl = true) {
    setActive(id);
    if (updateUrl) window.history.replaceState(null, "", id === "overview" ? "/dashboard" : `/dashboard#${id}`);
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  useEffect(() => {
    const validIds = new Set<SectionId>(["overview", "collections", "products", "care"]);
    const syncHash = (shouldScroll: boolean) => {
      const hash = window.location.hash.slice(1) as SectionId;
      const id = validIds.has(hash) ? hash : "overview";
      setActive(id);
      if (shouldScroll && hash) window.setTimeout(() => document.getElementById(id)?.scrollIntoView({ behavior: "auto", block: "start" }), 0);
    };
    syncHash(true);
    window.addEventListener("hashchange", () => syncHash(true));
    const root = mainRef.current;
    const sections = Array.from(document.querySelectorAll<HTMLElement>(".dashboard-main > [id]"));
    const observer = root ? new IntersectionObserver(entries => {
      const visible = entries.filter(entry => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (visible && validIds.has(visible.target.id as SectionId)) setActive(visible.target.id as SectionId);
    }, { root, threshold: [0.15, 0.5, 0.8] }) : null;
    sections.forEach(section => observer?.observe(section));
    return () => {
      window.removeEventListener("hashchange", () => syncHash(true));
      observer?.disconnect();
      if (noticeTimer.current) window.clearTimeout(noticeTimer.current);
    };
  }, []);

  useEffect(() => {
    function closeOnEscape(event: KeyboardEvent) {
      if (event.key === "Escape") {
        setSelectedProduct(null);
        setCartOpen(false);
      }
    }
    window.addEventListener("keydown", closeOnEscape);
    return () => window.removeEventListener("keydown", closeOnEscape);
  }, []);

  function handleSearchSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    scrollToSection("products");
  }

  function addToCart(product: Product) {
    setCartItems(current => {
      const existing = current.find(item => item.productId === product.id);
      if (existing) return current.map(item => item.productId === product.id ? { ...item, quantity: item.quantity + 1 } : item);
      return [...current, { productId: product.id, quantity: 1 }];
    });
    showNotice(`Đã thêm ${product.name} vào giỏ hàng demo.`);
  }

  function updateQuantity(productId: string, delta: number) {
    setCartItems(current => current.flatMap(item => item.productId === productId ? [{ ...item, quantity: item.quantity + delta }].filter(next => next.quantity > 0) : [item]));
  }

  function chooseCollection(name: string) {
    setQuery("");
    setCategoryFilter(name);
    scrollToSection("products");
  }

  return <div className="dashboard-shell flex h-screen overflow-hidden" style={{ fontFamily: "var(--font-sans)", background: "var(--color-cream)" }}>
    <aside className="dashboard-sidebar flex flex-col w-[220px] shrink-0 h-full">
      <div className="flex items-center gap-2.5 px-6 pt-7 pb-8">
        <div className="dashboard-logo-mark"><LeafLogoIcon /></div>
        <span className="dashboard-brand text-xl font-semibold tracking-wide" style={{ fontFamily: "var(--font-display)" }}>VŨ ĐIỆU<br />RỪNG XANH</span>
      </div>
      <nav className="dashboard-nav flex-1 px-3 space-y-0.5" aria-label="Điều hướng catalog">
        {navItems.map(({ id, label, icon: Icon }) => <button key={id} type="button" onClick={() => scrollToSection(id)} aria-current={active === id ? "location" : undefined} className={`dashboard-nav-button ${active === id ? "is-active" : ""}`}><Icon />{label}</button>)}
      </nav>
      <div className="m-4 mt-2"><div className="dashboard-tip-card rounded-2xl p-4"><div className="dashboard-tip-icon"><LeafIcon small /></div><p className="text-xs font-medium mb-0.5">Góc chăm cây</p><p className="text-xs">Cùng cây lớn mỗi ngày</p></div></div>
    </aside>

    <div className="flex-1 flex flex-col overflow-hidden">
      <header className="dashboard-toolbar flex items-center gap-4 px-8 py-4 border-b">
        <form className={`dashboard-search ${searchFocused ? "is-focused" : ""}`} role="search" onSubmit={handleSearchSubmit}>
          <SearchBarIcon />
          <input value={query} onChange={event => setQuery(event.target.value)} onFocus={() => setSearchFocused(true)} onBlur={() => setSearchFocused(false)} placeholder="Tìm tên cây..." aria-label="Tìm tên cây" />
          {query && <button type="button" className="search-clear" aria-label="Xóa tìm kiếm" onClick={() => setQuery("")}>×</button>}
        </form>
        <div className="dashboard-toolbar-actions">
          <button type="button" className="toolbar-button" onClick={() => showNotice("Danh sách yêu thích là nội dung minh họa trong bản demo.")}><HeartIcon /><span>Yêu thích</span></button>
          <button type="button" className="toolbar-button cart-button" onClick={() => setCartOpen(true)}><CartIcon /><span>Giỏ hàng</span><span className="cart-count">{cartCount}</span></button>
          <div className="toolbar-links"><Link className="link-brand" href="/">Trang chủ</Link><Link className="link-brand" href="/blog">Blog</Link></div>
        </div>
      </header>

      <main id="main-content" ref={mainRef} className="dashboard-main flex-1 overflow-y-auto px-8 py-8 space-y-8">
        <div className="demo-banner" role="note"><InfoIcon /> <span>Bản demo — dữ liệu sản phẩm, giỏ hàng và trạng thái sẽ đặt lại khi tải lại trang.</span></div>
        {notice && <div className="status-note" role="status" aria-live="polite">{notice}<button type="button" aria-label="Đóng thông báo" onClick={() => setNotice("")}>×</button></div>}

        <section id="overview" className="dashboard-hero-row grid grid-cols-[1fr_320px] gap-6" aria-labelledby="dashboard-title">
          <div className="dashboard-hero rounded-3xl p-10 flex flex-col justify-between min-h-[280px] relative overflow-hidden">
            <div className="space-y-3 max-w-lg"><p className="sample-label">KHÔNG GIAN XANH · CATALOG DEMO</p><h1 id="dashboard-title" className="text-5xl leading-tight" style={{ fontFamily: "var(--font-display)" }}>Mang thiên nhiên<br /><em>vào cuộc sống</em></h1><p className="text-sm leading-relaxed">Một mảng xanh nhỏ, một niềm vui mỗi ngày.</p></div>
            <div className="flex gap-3 mt-6"><button type="button" onClick={() => scrollToSection("products")} className="dashboard-primary-button">Khám phá cây →</button><button type="button" onClick={() => scrollToSection("care")} className="dashboard-secondary-button">Cách chăm sóc cây</button></div>
          </div>
          <div className="dashboard-featured rounded-3xl p-6 flex flex-col"><p className="text-xs font-medium mb-1">Cây nổi bật trong tuần</p><p className="text-lg font-semibold mb-4">Trầu bà lá xẻ</p><button type="button" className="featured-product-art" onClick={() => setSelectedProduct(products[3])} aria-label="Xem chi tiết Trầu bà lá xẻ"><PlantIllustration variant="tree" /></button><div className="flex items-center justify-between"><div><span className="text-xl font-semibold">{formatPrice(products[3].price)}</span><span className="text-xs ml-1.5 line-through">{formatPrice(products[3].oldPrice ?? 0)}</span></div><button type="button" className="dashboard-feature-button" onClick={() => setSelectedProduct(products[3])}>Xem chi tiết</button></div></div>
        </section>

        <div className="dashboard-grid grid grid-cols-4 gap-4">{[{ label: "Người yêu cây", value: "20K" }, { label: "Đánh giá", value: "4.9 ★" }, { label: "Cây đã trao tay", value: "4.8K+" }, { label: "Giống cây", value: "320+" }].map(item => <div key={item.label} className="dashboard-stat"><p>{item.value}</p><span>{item.label}</span></div>)}</div>
        <div className="dashboard-grid grid grid-cols-4 gap-4">{[{ title: "Cây được chọn lọc", desc: "Chọn cây khỏe cho không gian sống" }, { title: "Giao hàng tận nơi", desc: "Đóng gói cẩn thận đến tay bạn" }, { title: "Hướng dẫn chăm sóc", desc: "Đồng hành cùng bạn chăm cây mỗi ngày" }, { title: "Nuôi dưỡng mảng xanh", desc: "Một thói quen nhỏ cho không gian sống" }].map(item => <div key={item.title} className="dashboard-feature-card"><div className="feature-icon"><LeafIcon /></div><p>{item.title}</p><span>{item.desc}</span></div>)}</div>

        <section id="collections" aria-labelledby="collections-title"><div className="section-heading"><h2 id="collections-title">Khám phá bộ sưu tập</h2><button type="button" className="section-action" onClick={() => { setCategoryFilter(""); scrollToSection("products"); }}>Xem tất cả sản phẩm</button></div><div className="dashboard-grid grid grid-cols-4 gap-4">{collections.map(collection => <button type="button" key={collection.name} className="collection-card" onClick={() => chooseCollection(collection.name)}><div className="collection-art"><PlantIllustration variant={collection.illustration} /></div><span>{collection.name}</span><small>Lọc sản phẩm →</small></button>)}</div></section>

        <section id="products" aria-labelledby="products-title"><div className="section-heading"><div><h2 id="products-title">Cây được yêu thích</h2><p className="result-count" role="status" aria-live="polite">{filteredProducts.length} sản phẩm phù hợp{categoryFilter ? ` · ${categoryFilter}` : ""}</p></div><button type="button" className="section-action" onClick={() => { setQuery(""); setCategoryFilter(""); }}>Xóa bộ lọc</button></div><div className="dashboard-grid grid grid-cols-4 gap-4">{filteredProducts.length === 0 && <p className="empty-state" role="status">Không tìm thấy cây phù hợp. Hãy thử từ khóa khác.</p>}{filteredProducts.map(product => <article key={product.id} className="product-card"><button type="button" className="product-card-main" onClick={() => setSelectedProduct(product)}><div className="product-art"><PlantIllustration variant={product.illustration} /></div><div className="product-card-copy"><strong>{product.name}</strong><span>{formatPrice(product.price)}</span><small>★ {product.rating}</small></div></button><button type="button" className="cart-add-button" aria-label={`Thêm ${product.name} vào giỏ hàng demo`} onClick={() => addToCart(product)}><CartSmallIcon /></button></article>)}</div></section>

        <section id="care" className="dashboard-banner rounded-3xl p-6 flex items-center justify-between" aria-labelledby="care-title"><div><p id="care-title">Bạn mới bắt đầu chăm cây?</p><span>Bắt đầu từ ánh sáng, độ ẩm và một chút quan tâm mỗi ngày.</span></div><button type="button" onClick={() => showNotice("Gợi ý demo: đặt cây nơi có ánh sáng phù hợp, kiểm tra độ ẩm trước khi tưới và dùng chậu có lỗ thoát nước.")}>Xem gợi ý →</button></section>
      </main>
    </div>

    {selectedProduct && <div className="dialog-backdrop" role="presentation" onMouseDown={event => { if (event.target === event.currentTarget) setSelectedProduct(null); }}><section className="product-dialog" role="dialog" aria-modal="true" aria-labelledby="product-dialog-title"><button type="button" className="dialog-close" aria-label="Đóng chi tiết sản phẩm" onClick={() => setSelectedProduct(null)}>×</button><div className="dialog-art"><PlantIllustration variant={selectedProduct.illustration} /></div><p className="eyebrow">{selectedProduct.category}</p><h2 id="product-dialog-title">{selectedProduct.name}</h2><p>{selectedProduct.description}</p><strong className="dialog-price">{formatPrice(selectedProduct.price)}</strong><button type="button" className="dashboard-primary-button dialog-add" onClick={() => { addToCart(selectedProduct); setSelectedProduct(null); }}>Thêm vào giỏ demo</button></section></div>}
    {cartOpen && <div className="dialog-backdrop" role="presentation" onMouseDown={event => { if (event.target === event.currentTarget) setCartOpen(false); }}><aside className="cart-drawer" role="dialog" aria-modal="true" aria-labelledby="cart-title"><div className="drawer-heading"><div><p className="eyebrow">CATALOG DEMO</p><h2 id="cart-title">Giỏ hàng <span>({cartCount})</span></h2></div><button type="button" className="dialog-close" aria-label="Đóng giỏ hàng" onClick={() => setCartOpen(false)}>×</button></div>{cartProducts.length === 0 ? <p className="empty-state">Giỏ hàng đang trống. Hãy thêm một mảng xanh nhỏ.</p> : <div className="cart-items">{cartProducts.map(({ product, quantity }) => <div className="cart-item" key={product.id}><PlantIllustration variant={product.illustration} /><div><strong>{product.name}</strong><span>{formatPrice(product.price)}</span><div className="quantity-controls"><button type="button" aria-label={`Giảm số lượng ${product.name}`} onClick={() => updateQuantity(product.id, -1)}>−</button><span>{quantity}</span><button type="button" aria-label={`Tăng số lượng ${product.name}`} onClick={() => updateQuantity(product.id, 1)}>+</button></div></div></div>)}</div>}<p className="demo-disclaimer">Thanh toán chưa được kết nối trong bản demo.</p><button type="button" className="dashboard-primary-button w-full" disabled>Thanh toán demo</button></aside></div>}
  </div>;
}

function HomeIcon() { return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5Z" /><path d="M9 21v-9h6v9" /></svg>; }
function ShopIcon() { return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" /><path d="M3 6h18M16 10a4 4 0 0 1-8 0" /></svg>; }
function LeafIcon({ small }: { small?: boolean } = {}) { return <svg width={small ? 14 : 16} height={small ? 14 : 16} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M11 20A7 7 0 0 1 4 13V6s3 0 6 3 7 3 7 3v1a7 7 0 0 1-6 7Z" /><path d="M4 6s2 6 8 14" /></svg>; }
function LeafLogoIcon() { return <LeafIcon />; }
function SearchNavIcon() { return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>; }
function SearchBarIcon() { return <SearchNavIcon />; }
function HeartIcon() { return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.9-8.84a5.5 5.5 0 0 0-.06-7.78Z" /></svg>; }
function CartIcon() { return <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /></svg>; }
function CartSmallIcon() { return <CartIcon />; }
function InfoIcon() { return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 16v-4M12 8h.01" /></svg>; }
