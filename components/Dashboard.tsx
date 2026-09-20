"use client";

import { useEffect, useState } from "react";

const navItems = [
  { label: "Tổng quan", icon: HomeIcon },
  { label: "Bộ sưu tập", icon: SearchNavIcon },
  { label: "Yêu thích", icon: HeartIcon },
];

const collectionItems = [
  { name: "Lưỡi hổ", description: "Cây trong nhà", icon: "🌿" },
  { name: "Trầu bà", description: "Cây để bàn", icon: "🪴" },
  { name: "Kim tiền", description: "Cây dễ chăm sóc", icon: "🌱" },
  { name: "Cau tiểu trâm", description: "Cây ngoài trời", icon: "🌳" },
];

export default function App() {
  const [active, setActive] = useState("Tổng quan");
  const [notice, setNotice] = useState("");
  const [query, setQuery] = useState("");
  const [favorites, setFavorites] = useState<string[]>([]);
  const collections = collectionItems.filter(item =>
    `${item.name} ${item.description}`.toLocaleLowerCase("vi").includes(query.toLocaleLowerCase("vi")),
  );
  useEffect(() => {
    if (!notice) return;
    const timer = window.setTimeout(() => setNotice(""), 3000);
    return () => window.clearTimeout(timer);
  }, [notice]);
  function navigate(label: string) {
    setActive(label);
    const id = label === "Bộ sưu tập" ? "collections" : label === "Yêu thích" ? "favorites" : "overview";
    window.requestAnimationFrame(() => document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" }));
  }
  function toggleFavorite(name: string) {
    const isFavorite = favorites.includes(name);
    setFavorites(current => isFavorite ? current.filter(item => item !== name) : [...current, name]);
    setNotice(isFavorite ? `Đã xóa ${name} khỏi mục yêu thích.` : `Đã thêm ${name} vào mục yêu thích.`);
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

      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Top bar */}
        <header
          className="dashboard-toolbar flex items-center gap-4 px-8 py-4 border-b"
          style={{ background: "var(--color-cream)", borderColor: "var(--color-cream-dark)" }}
        >
          <div
            className="flex items-center gap-2.5 flex-1 max-w-md px-4 py-2.5 rounded-full"
            style={{
              background: "white",
              border: "1px solid var(--color-cream-dark)",
            }}
          >
            <SearchBarIcon />
            <input
              className="flex-1 text-sm bg-transparent outline-none focus:outline-none focus:ring-0 placeholder:text-gray-400"
              placeholder="Tìm tên cây..."
              aria-label="Tìm tên cây"
              value={query}
              onChange={e => { setQuery(e.target.value); navigate("Bộ sưu tập"); }}
            />

          </div>

          <div className="flex items-center gap-5 ml-auto">
            <button onClick={() => navigate("Yêu thích")} className="flex items-center gap-1.5 text-sm" style={{ color: "#555" }}>
              <HeartIcon />
              <span>Yêu thích</span>
            </button>
            <a className="link-brand text-sm" href="/">Trang chủ ↗</a>
            <a className="link-brand text-sm" href="/blog">Blog ↗</a>
            <a className="link-brand text-sm" href="/admin">Quản trị ↗</a>
          </div>
        </header>

        {/* Scrollable content */}
        <main className="dashboard-main flex-1 overflow-y-auto px-8 py-8">
          {notice && <div className="status-note" role="status">{notice}</div>}
          <div className={active === "Yêu thích" ? "hidden" : "space-y-8"}>
          <p className="sample-label">KHÔNG GIAN XANH · Bộ sưu tập cây xanh</p>
          {/* Hero row */}
          <div id="overview" className="dashboard-hero-row grid grid-cols-1 gap-6">
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
                  onClick={() => navigate("Bộ sưu tập")} className="px-5 py-2.5 rounded-full text-sm font-medium text-white transition-opacity hover:opacity-90"
                  style={{ background: "var(--color-forest)" }}
                >
                  Khám phá cây →
                </button>
              </div>
              {/* Decorative circle */}
              <div
                className="decorative-circle absolute right-0 top-0 w-64 h-64 rounded-full opacity-30"
                style={{ background: "var(--color-sage-light)", transform: "translate(30%, -30%)" }}
              />
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
              { title: "Hợp với không gian", desc: "Tìm lựa chọn phù hợp cho ngôi nhà" },
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
            </div>
            <div className="collection-marquee" aria-label="Bộ sưu tập cây xanh">
              <div className="collection-track">
              {collections.length === 0 && <p role="status">Không tìm thấy cây phù hợp.</p>}
              {[...collections, ...collections].map((item, index) => (
                <div
                  key={`${item.name}-${index}`}
                  className="collection-card rounded-2xl overflow-hidden group"
                  style={{ border: "1px solid var(--color-cream-dark)", background: "white" }}
                >
                  <div
                    className="collection-art h-36 transition-colors group-hover:opacity-90 relative"
                    style={{ background: "var(--color-cream-dark)" }}
                  >
                    <span aria-hidden="true">{item.icon}</span>
                    <button
                      type="button"
                      aria-label={`${favorites.includes(item.name) ? "Xóa" : "Thêm"} ${item.name} ${favorites.includes(item.name) ? "khỏi" : "vào"} mục yêu thích`}
                      aria-pressed={favorites.includes(item.name)}
                      onClick={() => toggleFavorite(item.name)}
                      className="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 transition-opacity hover:opacity-75"
                      style={{ color: favorites.includes(item.name) ? "var(--color-olive)" : "var(--color-forest)" }}
                    >
                      <HeartIcon filled={favorites.includes(item.name)} />
                    </button>
                  </div>
                  <div className="p-3">
                    <p className="text-sm font-medium" style={{ color: "var(--color-forest)" }}>
                      {item.name}
                    </p>
                    <p className="text-xs" style={{ color: "#626b5a" }}>
                      {item.description}
                    </p>
                  </div>
                </div>
              ))}
              </div>
            </div>
          </section>

          </div>

          {/* Cây đã yêu thích */}
          <div className={active === "Yêu thích" ? "space-y-8" : "hidden"}>
          <section id="favorites">
            <div className="flex items-center justify-between mb-4">
              <h2
                className="text-lg font-semibold"
                style={{ fontFamily: "var(--font-display)", color: "var(--color-forest)" }}
              >
                Cây yêu thích
              </h2>
            </div>
            {favorites.length === 0 ? (
              <div className="flex min-h-44 flex-col items-center justify-center rounded-2xl border bg-white p-8 text-center" style={{ borderColor: "var(--color-cream-dark)" }}>
                <EmptyHeartIcon />
                <p className="mt-3 text-sm font-medium" style={{ color: "var(--color-forest)" }}>Chưa có cây yêu thích</p>
                <p className="mt-1 text-xs" style={{ color: "#626b5a" }}>Nhấn biểu tượng trái tim trên cây bạn muốn lưu.</p>
              </div>
            ) : (
              <div className="dashboard-grid grid grid-cols-4 gap-4">
                {favorites.map(name => {
                  const item = collectionItems.find(collection => collection.name === name);
                  if (!item) return null;
                  return (
                <div
                  key={item.name}
                  className="rounded-2xl overflow-hidden group"
                  style={{ border: "1px solid var(--color-cream-dark)", background: "white" }}
                >
                  <div
                    className="collection-art h-28 transition-opacity group-hover:opacity-80"
                    style={{ background: "var(--color-cream-dark)" }}
                  ><span aria-hidden="true">{item.icon}</span></div>
                  <div className="flex items-center justify-between p-3">
                    <div>
                      <p className="text-sm font-medium" style={{ color: "var(--color-forest)" }}>{item.name}</p>
                      <p className="text-xs" style={{ color: "#626b5a" }}>{item.description}</p>
                    </div>
                    <button
                      type="button"
                      aria-label={`Xóa ${item.name} khỏi mục yêu thích`}
                      onClick={() => toggleFavorite(item.name)}
                      className="flex h-8 w-8 items-center justify-center rounded-full transition-opacity hover:opacity-75"
                      style={{ color: "var(--color-olive)" }}
                    >
                      <HeartIcon filled />
                    </button>
                  </div>
                </div>
                  );
                })}
              </div>
            )}
          </section>
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

function HeartIcon({ filled = false }: { filled?: boolean }) {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill={filled ? "currentColor" : "none"} stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
    </svg>
  );
}

function EmptyHeartIcon() {
  return (
    <span className="flex h-14 w-14 items-center justify-center rounded-full" style={{ background: "var(--color-cream-dark)", color: "var(--color-olive)" }}>
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
      </svg>
    </span>
  );
}
