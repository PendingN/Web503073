"use client";

import { useState } from "react";

const navItems = [
  { label: "Tổng quan", icon: HomeIcon },
  { label: "Sản phẩm", icon: ShopIcon },
  { label: "Chăm sóc cây", icon: LeafIcon },
  { label: "Bộ sưu tập", icon: SearchNavIcon },
];

export default function App() {
  const [active, setActive] = useState("Tổng quan");
  const [searchFocused, setSearchFocused] = useState(false);
  const [notice, setNotice] = useState("");
  const [cart, setCart] = useState(0);
  const [query, setQuery] = useState("");
  const products = ["Lưỡi hổ", "Trầu bà", "Kim tiền", "Trầu bà lá xẻ"].filter(name => name.toLocaleLowerCase('vi').includes(query.toLocaleLowerCase('vi')));
  function navigate(label: string) {
    setActive(label);
    const id = label === "Bộ sưu tập" ? "collections" : label === "Sản phẩm" ? "products" : label === "Chăm sóc cây" ? "care" : "overview";
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  return (
    <div
      className="dashboard-shell flex h-screen overflow-hidden"
      style={{ fontFamily: "var(--font-sans)", background: "var(--color-cream)" }}
    >
      {/* Sidebar */}
      <aside
        className="dashboard-sidebar flex flex-col w-[220px] shrink-0 h-full"
        style={{ background: "var(--color-forest)" }}
      >
        {/* Logo */}
        <div className="flex items-center gap-2.5 px-6 pt-7 pb-8">
          <div
            className="w-8 h-8 rounded-full flex items-center justify-center"
            style={{ background: "var(--color-sage)" }}
          >
            <LeafLogoIcon />
          </div>
          <span
            className="dashboard-brand text-xl font-semibold tracking-wide text-white"
            style={{ fontFamily: "var(--font-display)" }}
          >
            VŨ ĐIỆU RỪNG XANH
          </span>
        </div>

        {/* Nav */}
        <nav className="flex-1 px-3 space-y-0.5">
          {navItems.map(({ label, icon: Icon }) => {
            const isActive = active === label;
            return (
              <button
                key={label}
                onClick={() => navigate(label)}
                aria-current={isActive ? "page" : undefined}
                className="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                style={{
                  color: isActive ? "white" : "var(--color-sage-light)",
                  background: isActive ? "var(--color-forest-light)" : "transparent",
                }}
              >
                <Icon />
                {label}
              </button>
            );
          })}
        </nav>

        {/* Bottom plant tip card */}
        <div className="m-4 mt-2">
          <div
            className="rounded-2xl p-4"
            style={{ background: "var(--color-forest-light)" }}
          >
            <div
              className="w-8 h-8 rounded-full flex items-center justify-center mb-3"
              style={{ background: "var(--color-sage)" }}
            >
              <LeafIcon small />
            </div>
            <p className="text-xs font-medium mb-0.5" style={{ color: "var(--color-sage-light)" }}>
              Góc chăm cây
            </p>
            <p className="text-xs" style={{ color: "#dfe8d2" }}>
              Cùng cây lớn mỗi ngày
            </p>
          </div>
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Top bar */}
        <header
          className="dashboard-toolbar flex items-center gap-4 px-8 py-4 border-b"
          style={{ background: "var(--color-cream)", borderColor: "var(--color-cream-dark)" }}
        >
          <div
            className="flex items-center gap-2.5 flex-1 max-w-md px-4 py-2.5 rounded-full transition-shadow duration-150"
            style={{
              background: "white",
              border: `1px solid ${searchFocused ? "var(--color-sage)" : "var(--color-cream-dark)"}`,
              boxShadow: searchFocused ? "0 0 0 3px rgba(122,158,122,0.15)" : "none",
            }}
          >
            <SearchBarIcon />
            <input
              className="flex-1 text-sm bg-transparent outline-none placeholder:text-gray-400"
              placeholder="Tìm tên cây..."
              aria-label="Tìm tên cây"
              value={query}
              onChange={e => { setQuery(e.target.value); document.getElementById("products")?.scrollIntoView({ behavior: "smooth" }); }}
              onFocus={() => setSearchFocused(true)}
              onBlur={() => setSearchFocused(false)}
            />

          </div>

          <div className="flex items-center gap-5 ml-auto">
            <button onClick={() => setNotice("Danh sách yêu thích chưa có cây nào. Đây là giao diện minh họa.")} className="flex items-center gap-1.5 text-sm" style={{ color: "#555" }}>
              <HeartIcon />
              <span>Yêu thích</span>
            </button>
            <button onClick={() => setNotice(`Giỏ hàng minh họa có ${cart} cây. Chức năng thanh toán chưa được kết nối.`)} className="flex items-center gap-1.5 text-sm relative" style={{ color: "#555" }}>
              <CartIcon />
              <span>Giỏ hàng</span>
              <span
                className="absolute -top-1.5 left-3 w-4 h-4 rounded-full text-white text-[10px] flex items-center justify-center font-semibold"
                style={{ background: "var(--color-sage)" }}
              >
                {cart}
              </span>
            </button>
            <a className="link-brand text-sm" href="/">Trang chủ ↗</a>
            <a className="link-brand text-sm" href="/blog">Blog ↗</a>
            <a className="link-brand text-sm" href="/admin">Quản trị ↗</a>
          </div>
        </header>

        {/* Scrollable content */}
        <main className="dashboard-main flex-1 overflow-y-auto px-8 py-8 space-y-8">
          <p className="sample-label">KHÔNG GIAN XANH · Dữ liệu và sản phẩm minh họa</p>
          {notice && <div className="status-note" role="status">{notice}</div>}
          {/* Hero row */}
          <div id="overview" className="dashboard-hero-row grid grid-cols-[1fr_320px] gap-6">
            {/* Hero card */}
            <div
              className="dashboard-hero rounded-3xl p-10 flex flex-col justify-between min-h-[280px] relative overflow-hidden"
              style={{ background: "var(--color-cream-dark)" }}
            >
              <div className="space-y-3 max-w-lg">
                <h1
                  className="text-5xl leading-tight"
                  style={{ fontFamily: "var(--font-display)", color: "var(--color-forest)" }}
                >
                  Mang thiên nhiên
                  <br />
                  <em style={{ color: "var(--color-olive)" }}>vào cuộc sống</em>
                </h1>
                <p className="text-sm leading-relaxed" style={{ color: "#6b7c6b" }}>
                  Một mảng xanh nhỏ, một niềm vui mỗi ngày.
                </p>
              </div>
              <div className="flex gap-3 mt-6">
                <button
                  onClick={() => navigate("Sản phẩm")} className="px-5 py-2.5 rounded-full text-sm font-medium text-white transition-opacity hover:opacity-90"
                  style={{ background: "var(--color-forest)" }}
                >
                  Khám phá cây →
                </button>
                <button
                  onClick={() => navigate("Chăm sóc cây")} className="hero-secondary px-5 py-2.5 rounded-full text-sm font-medium transition-colors"
                  style={{
                    border: "1px solid var(--color-forest)",
                    color: "var(--color-forest)",
                    background: "transparent",
                  }}
                >
                  Cách chăm sóc cây
                </button>
              </div>
              {/* Decorative circle */}
              <div
                className="decorative-circle absolute right-0 top-0 w-64 h-64 rounded-full opacity-30"
                style={{ background: "var(--color-sage-light)", transform: "translate(30%, -30%)" }}
              />
            </div>

            {/* Featured plant card */}
            <div
              className="dashboard-featured rounded-3xl p-6 flex flex-col"
              style={{ background: "var(--color-forest)" }}
            >
              <p className="text-xs font-medium mb-1" style={{ color: "var(--color-sage-light)" }}>
                Cây nổi bật trong tuần
              </p>
              <p
                className="text-lg font-semibold mb-4 text-white"
                style={{ fontFamily: "var(--font-display)" }}
              >
                Trầu bà lá xẻ
              </p>
              <div
                className="product-art flex-1 rounded-2xl mb-4"
                style={{ background: "var(--color-forest-light)", minHeight: 120 }}
              ><span aria-hidden="true">🌿</span></div>
              <div className="flex items-center justify-between">
                <div>
                  <span className="text-xl font-semibold text-white">349.000 ₫</span>
                  <span className="text-xs ml-1.5 line-through" style={{ color: "#dfe8d2" }}>
                    499.000 ₫
                  </span>
                </div>
                <button
                  onClick={() => navigate("Sản phẩm")} className="px-4 py-2 rounded-full text-sm font-medium text-white transition-opacity hover:opacity-80"
                  style={{ background: "var(--color-sage)" }}
                >
                  Xem sản phẩm
                </button>
              </div>
            </div>
          </div>

          {/* Stats row */}
          <div className="dashboard-grid grid grid-cols-4 gap-4">
            {[
              { label: "Người yêu cây", value: "20K" },
              { label: "Đánh giá", value: "4.9 ★" },
              { label: "Cây đã trao tay", value: "4.8K+" },
              { label: "Giống cây", value: "320+" },
            ].map(({ label, value }) => (
              <div
                key={label}
                className="rounded-2xl px-5 py-4"
                style={{ background: "white", border: "1px solid var(--color-cream-dark)" }}
              >
                <p
                  className="text-2xl font-semibold mb-1"
                  style={{ fontFamily: "var(--font-display)", color: "var(--color-forest)" }}
                >
                  {value}
                </p>
                <p className="text-xs" style={{ color: "#626b5a" }}>
                  {label}
                </p>
              </div>
            ))}
          </div>

          {/* Features row */}
          <div className="dashboard-grid grid grid-cols-4 gap-4">
            {[
              { title: "Cây được chọn lọc", desc: "Chọn cây khỏe cho không gian sống" },
              { title: "Giao hàng tận nơi", desc: "Đóng gói cẩn thận đến tay bạn" },
              { title: "Hướng dẫn chăm sóc", desc: "Đồng hành cùng bạn chăm cây mỗi ngày" },
              { title: "Nuôi dưỡng mảng xanh", desc: "Đồng hành cùng bạn chăm cây mỗi ngày" },
            ].map(({ title, desc }) => (
              <div
                key={title}
                className="rounded-2xl p-5"
                style={{ background: "white", border: "1px solid var(--color-cream-dark)" }}
              >
                <div
                  className="w-10 h-10 rounded-xl mb-3 flex items-center justify-center"
                  style={{ background: "var(--color-cream-dark)" }}
                >
                  <LeafIcon />
                </div>
                <p className="text-sm font-medium mb-1" style={{ color: "var(--color-forest)" }}>
                  {title}
                </p>
                <p className="text-xs leading-relaxed" style={{ color: "#626b5a" }}>
                  {desc}
                </p>
              </div>
            ))}
          </div>

          {/* Khám phá bộ sưu tập */}
          <section id="collections">
            <div className="flex items-center justify-between mb-4">
              <h2
                className="text-lg font-semibold"
                style={{ fontFamily: "var(--font-display)", color: "var(--color-forest)" }}
              >
                Khám phá bộ sưu tập
              </h2>
              <button onClick={() => { setQuery(""); navigate("Sản phẩm"); }} className="text-xs font-medium px-4 py-1.5 rounded-full" style={{ border: "1px solid var(--color-cream-dark)", color: "#666" }}>
                Xem sản phẩm
              </button>
            </div>
            <div className="dashboard-grid grid grid-cols-4 gap-4">
              {Array.from({ length: 4 }).map((_, i) => (
                <div
                  key={i}
                  className="rounded-2xl overflow-hidden group"
                  style={{ border: "1px solid var(--color-cream-dark)", background: "white" }}
                >
                  <div
                    className="collection-art h-36 transition-colors group-hover:opacity-90"
                    style={{ background: "var(--color-cream-dark)" }}
                  ><span aria-hidden="true">{["🌿", "🪴", "🌱", "🌳"][i]}</span></div>
                  <div className="p-3">
                    <p className="text-sm font-medium" style={{ color: "var(--color-forest)" }}>
                      {["Cây trong nhà", "Cây để bàn", "Cây dễ chăm sóc", "Cây ngoài trời"][i]}
                    </p>
                    <p className="text-xs" style={{ color: "#626b5a" }}>
                      Khám phá cây xanh
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </section>

          {/* Cây được yêu thích */}
          <section id="products">
            <div className="flex items-center justify-between mb-4">
              <h2
                className="text-lg font-semibold"
                style={{ fontFamily: "var(--font-display)", color: "var(--color-forest)" }}
              >
                Cây được yêu thích
              </h2>
              <button onClick={() => { setQuery(""); navigate("Sản phẩm"); }} className="text-xs font-medium px-4 py-1.5 rounded-full" style={{ border: "1px solid var(--color-cream-dark)", color: "#666" }}>
                Xem sản phẩm
              </button>
            </div>
            <div className="dashboard-grid grid grid-cols-4 gap-4">
              {products.length === 0 && <p role="status">Không tìm thấy cây phù hợp.</p>}
              {products.map((name, i) => (
                <div
                  key={i}
                  className="rounded-2xl overflow-hidden group"
                  style={{ border: "1px solid var(--color-cream-dark)", background: "white" }}
                >
                  <div
                    className="product-art h-28 transition-opacity group-hover:opacity-80"
                    style={{ background: "var(--color-cream-dark)" }}
                  ><span aria-hidden="true">🪴</span></div>
                  <div className="p-3 flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium" style={{ color: "var(--color-forest)" }}>
                        {name}
                      </p>
                      <p className="text-xs font-semibold mt-0.5" style={{ color: "var(--color-olive)" }}>
                        289.000 ₫
                      </p>
                      <p className="text-xs" style={{ color: "#626b5a" }}>
                        ★ 4.9
                      </p>
                    </div>
                    <button
                      aria-label={`Thêm ${name} vào giỏ hàng minh họa`} onClick={() => { setCart(count => count + 1); setNotice(`Đã thêm ${name} vào giỏ hàng minh họa.`); }} className="w-8 h-8 rounded-full flex items-center justify-center text-white transition-opacity hover:opacity-80"
                      style={{ background: "var(--color-sage)" }}
                    >
                      <CartSmallIcon />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </section>

          {/* Bottom banner */}
          <div
            id="care" className="dashboard-banner rounded-3xl p-6 flex items-center justify-between"
            style={{ background: "var(--color-cream-dark)" }}
          >
            <div>
              <p className="text-sm font-medium" style={{ color: "var(--color-forest)" }}>
                Bạn mới bắt đầu chăm cây?
              </p>
              <p className="text-xs mt-0.5" style={{ color: "#626b5a" }}>
                Bắt đầu từ ánh sáng, độ ẩm và một chút quan tâm mỗi ngày.
              </p>
            </div>
            <button
              onClick={() => setNotice("Đặt cây ở nơi có ánh sáng phù hợp, kiểm tra độ ẩm đất trước khi tưới và chọn chậu có lỗ thoát nước.")} className="text-sm font-medium flex items-center gap-1 transition-opacity hover:opacity-70"
              style={{ color: "var(--color-olive)" }}
            >
              Xem gợi ý →
            </button>
          </div>
        </main>
      </div>
    </div>
  );
}

/* ---- Icons ---- */

function HomeIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z" />
      <path d="M9 21V12h6v9" />
    </svg>
  );
}

function ShopIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
      <line x1="3" y1="6" x2="21" y2="6" />
      <path d="M16 10a4 4 0 01-8 0" />
    </svg>
  );
}

function LeafIcon({ small }: { small?: boolean } = {}) {
  return (
    <svg width={small ? 14 : 16} height={small ? 14 : 16} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d="M11 20A7 7 0 014 13V6s3 0 6 3 7 3 7 3v1a7 7 0 01-6 7z" />
      <path d="M4 6c0 0 2 6 8 14" />
    </svg>
  );
}

function LeafLogoIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M11 20A7 7 0 014 13V6s3 0 6 3 7 3 7 3v1a7 7 0 01-6 7z" />
      <path d="M4 6c0 0 2 6 8 14" />
    </svg>
  );
}

function SearchNavIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="11" cy="11" r="8" />
      <path d="M21 21l-4.35-4.35" />
    </svg>
  );
}

function SparkleIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
    </svg>
  );
}

function InfoIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10" />
      <path d="M12 16v-4M12 8h.01" />
    </svg>
  );
}

function SearchBarIcon() {
  return (
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#626b5a" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="11" cy="11" r="8" />
      <path d="M21 21l-4.35-4.35" />
    </svg>
  );
}

function HeartIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
    </svg>
  );
}

function CartIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="9" cy="21" r="1" />
      <circle cx="20" cy="21" r="1" />
      <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" />
    </svg>
  );
}

function CartSmallIcon() {
  return (
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="9" cy="21" r="1" />
      <circle cx="20" cy="21" r="1" />
      <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" />
    </svg>
  );
}
