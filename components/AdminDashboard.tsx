"use client";

import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";

const orders = [
  { id: "#RX1048", customer: "Nguyễn Minh Anh", product: "Trầu bà lá xẻ", total: "349.000 ₫", status: "Đang giao", note: "Đơn đang trên đường giao tới khách hàng." },
  { id: "#RX1047", customer: "Lê Hoàng Nam", product: "Lưỡi hổ mini", total: "259.000 ₫", status: "Hoàn thành", note: "Đơn đã được giao thành công." },
  { id: "#RX1046", customer: "Trần Thu Hà", product: "Kim tiền", total: "429.000 ₫", status: "Chờ xác nhận", note: "Cần xác nhận thời gian giao hàng." },
  { id: "#RX1045", customer: "Phạm Gia Bảo", product: "Sen đá · 2 cây", total: "318.000 ₫", status: "Hoàn thành", note: "Đơn đã được giao thành công." },
];

const nav = [
  { label: "Tổng quan", id: "overview", icon: "⌂" },
  { label: "Đơn hàng", id: "orders", icon: "□" },
  { label: "Sản phẩm", id: "products", icon: "◇" },
  { label: "Bài viết", id: "posts", icon: "✎" },
  { label: "Khách hàng", id: "customers", icon: "○" },
] as const;

type AdminSection = typeof nav[number]["id"];

export default function AdminDashboard() {
  const [active, setActive] = useState<AdminSection>("overview");
  const [notice, setNotice] = useState("");
  const [selectedOrder, setSelectedOrder] = useState<typeof orders[number] | null>(null);
  const [addProductOpen, setAddProductOpen] = useState(false);
  const [newProduct, setNewProduct] = useState("");
  const [demoProducts, setDemoProducts] = useState<string[]>([]);

  function show(message: string) {
    setNotice(message);
    window.setTimeout(() => setNotice(""), 3200);
  }

  function goToSection(id: AdminSection) {
    setActive(id);
    window.history.replaceState(null, "", `/admin#${id}`);
    document.getElementById(`admin-${id}`)?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  useEffect(() => {
    const hash = window.location.hash.slice(1) as AdminSection;
    if (nav.some(item => item.id === hash)) {
      setActive(hash);
      window.setTimeout(() => document.getElementById(`admin-${hash}`)?.scrollIntoView({ behavior: "auto", block: "start" }), 0);
    }
  }, []);

  function submitProduct(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const name = newProduct.trim();
    if (!name) return;
    setDemoProducts(current => [...current, name]);
    setNewProduct("");
    setAddProductOpen(false);
    show(`Đã thêm “${name}” trong bản demo. Dữ liệu chưa được lưu.`);
  }

  return <div className="admin-shell">
    <aside className="admin-sidebar">
      <Link className="admin-brand" href="/"><span>✦</span><strong>VŨ ĐIỆU<br />RỪNG XANH</strong></Link>
      <p className="admin-label">QUẢN TRỊ DEMO</p>
      <nav aria-label="Điều hướng quản trị">{nav.map(item => <button key={item.id} type="button" className={active === item.id ? "is-active" : ""} aria-current={active === item.id ? "location" : undefined} onClick={() => goToSection(item.id)}><span aria-hidden="true">{item.icon}</span>{item.label}</button>)}</nav>
      <div className="admin-sidebar-foot"><div className="admin-avatar">AD</div><div><strong>Anh Dũng</strong><small>Quản trị viên demo</small></div><Link href="/" aria-label="Về trang chủ">→</Link></div>
    </aside>

    <div className="admin-content">
      <header className="admin-topbar"><div><p>QUẢN TRỊ DEMO · DỮ LIỆU MINH HỌA</p><h1>Chào mừng trở lại, Anh Dũng.</h1></div><div className="admin-actions"><Link href="/blog">Xem blog →</Link><button type="button" onClick={() => setAddProductOpen(true)}>＋ Thêm sản phẩm</button></div></header>
      <main id="main-content" className="admin-main">
        {notice && <div className="admin-toast" role="status" aria-live="polite">✓ {notice}<button type="button" aria-label="Đóng thông báo" onClick={() => setNotice("")}>×</button></div>}

        <section id="admin-overview" className="admin-section" aria-label="Tổng quan">
          <section className="admin-stats" aria-label="Số liệu tổng quan">
            {[ ["Doanh thu tháng", "24,8 triệu", "+12,5%", "↗"], ["Đơn hàng", "128", "+8 đơn hôm nay", "□"], ["Khách hàng", "1.842", "+34 người mới", "○"], ["Bài viết", "16", "3 bản nháp", "✎"] ].map(([label, value, note, icon]) => <article key={label}><div className="stat-top"><span>{label}</span><i aria-hidden="true">{icon}</i></div><strong>{value}</strong><small>{note}</small></article>)}
          </section>

          <div className="admin-columns">
            <section className="admin-panel revenue-panel"><div className="panel-head"><div><p className="eyebrow">HIỆU SUẤT</p><h2>Doanh thu 7 ngày</h2></div><span className="demo-chip">Bản demo</span></div><div className="chart-area" role="img" aria-label="Biểu đồ doanh thu: thứ hai 42%, thứ ba 58%, thứ tư 48%, thứ năm 78%, thứ sáu 65%, thứ bảy 92%, chủ nhật 73%">{[42, 58, 48, 78, 65, 92, 73].map((height, index) => <div className="chart-column" key={index}><div className="chart-bar"><span style={{ height: `${height}%` }} /></div><small>{["T2", "T3", "T4", "T5", "T6", "T7", "CN"][index]}</small></div>)}</div><div className="chart-summary"><span><i /> Doanh thu</span><strong>8.420.000 ₫</strong></div><ul className="chart-data sr-only">{[42, 58, 48, 78, 65, 92, 73].map((value, index) => <li key={index}>{["Thứ hai", "Thứ ba", "Thứ tư", "Thứ năm", "Thứ sáu", "Thứ bảy", "Chủ nhật"][index]}: {value}%</li>)}</ul></section>
            <section className="admin-panel quick-panel"><div className="panel-head"><div><p className="eyebrow">HÔM NAY</p><h2>Cần chú ý</h2></div><span className="count-badge">3</span></div><ul><li><span>06</span><div><strong>Đơn chờ xác nhận</strong><small>Cần xử lý trước 18:00</small></div><button type="button" aria-label="Xem đơn chờ xác nhận" onClick={() => goToSection("orders")}>→</button></li><li><span>03</span><div><strong>Sản phẩm sắp hết</strong><small>Kiểm tra lại tồn kho</small></div><button type="button" aria-label="Xem sản phẩm sắp hết" onClick={() => goToSection("products")}>→</button></li><li><span>02</span><div><strong>Bình luận mới</strong><small>Đang chờ phản hồi</small></div><button type="button" aria-label="Xem bình luận mới" onClick={() => goToSection("posts")}>→</button></li></ul></section>
          </div>
        </section>

        <section id="admin-orders" className="admin-panel orders-panel admin-section" aria-labelledby="orders-title"><div className="panel-head"><div><p className="eyebrow">CỬA HÀNG</p><h2 id="orders-title">Đơn hàng gần đây</h2></div><button type="button" onClick={() => goToSection("orders")}>Đang xem 4 đơn →</button></div><div className="admin-table-wrap"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Trạng thái</th><th>Chi tiết</th></tr></thead><tbody>{orders.map(order => <tr key={order.id}><td><strong>{order.id}</strong></td><td>{order.customer}</td><td>{order.product}</td><td>{order.total}</td><td><span className={`order-status status-${order.status.replaceAll(" ", "-").toLowerCase()}`}>{order.status}</span></td><td><button type="button" onClick={() => setSelectedOrder(order)} aria-label={`Xem chi tiết ${order.id}`}>Xem</button></td></tr>)}</tbody></table></div></section>

        <section id="admin-products" className="admin-panel admin-placeholder admin-section" aria-labelledby="products-title"><div><p className="eyebrow">SẢN PHẨM</p><h2 id="products-title">Danh mục sản phẩm</h2><p>Đây là khu vực minh họa cho danh mục sản phẩm. Các thay đổi trong phiên này sẽ không được lưu.</p></div><div className="demo-list">{demoProducts.length ? demoProducts.map(product => <span key={product}>{product}</span>) : <span>Chưa có sản phẩm mới trong phiên demo.</span>}</div></section>
        <section id="admin-posts" className="admin-panel editorial-panel admin-section"><div><p className="eyebrow">BLOG</p><h2>Bài viết nổi bật</h2><p>“Bắt đầu một góc xanh từ đâu?” đang được chọn làm bài viết chính trên trang blog.</p></div><span className="publish-status">Đang xuất bản · demo</span><Link href="/blog">Xem bài viết →</Link></section>
        <section id="admin-customers" className="admin-panel admin-placeholder admin-section"><div><p className="eyebrow">KHÁCH HÀNG</p><h2>Khách hàng thân thiết</h2><p>Dữ liệu khách hàng chỉ được minh họa ở thẻ tổng quan; chưa có danh sách thật trong bản demo.</p></div><span className="demo-chip">Chưa kết nối dữ liệu</span></section>
      </main>
    </div>

    {selectedOrder && <div className="dialog-backdrop" role="presentation" onMouseDown={event => { if (event.target === event.currentTarget) setSelectedOrder(null); }}><section className="admin-dialog" role="dialog" aria-modal="true" aria-labelledby="order-dialog-title"><button type="button" className="dialog-close" aria-label="Đóng chi tiết đơn hàng" onClick={() => setSelectedOrder(null)}>×</button><p className="eyebrow">CHI TIẾT ĐƠN HÀNG</p><h2 id="order-dialog-title">{selectedOrder.id}</h2><dl><div><dt>Khách hàng</dt><dd>{selectedOrder.customer}</dd></div><div><dt>Sản phẩm</dt><dd>{selectedOrder.product}</dd></div><div><dt>Tổng tiền</dt><dd>{selectedOrder.total}</dd></div><div><dt>Trạng thái</dt><dd>{selectedOrder.status}</dd></div></dl><p className="dialog-note">{selectedOrder.note} Đây là dữ liệu minh họa.</p></section></div>}
    {addProductOpen && <div className="dialog-backdrop" role="presentation" onMouseDown={event => { if (event.target === event.currentTarget) setAddProductOpen(false); }}><section className="admin-dialog" role="dialog" aria-modal="true" aria-labelledby="add-product-title"><button type="button" className="dialog-close" aria-label="Đóng biểu mẫu" onClick={() => setAddProductOpen(false)}>×</button><p className="eyebrow">SẢN PHẨM DEMO</p><h2 id="add-product-title">Thêm sản phẩm</h2><form className="admin-form" onSubmit={submitProduct}><label htmlFor="demo-product-name">Tên sản phẩm</label><input id="demo-product-name" value={newProduct} onChange={event => setNewProduct(event.target.value)} placeholder="Ví dụ: Cây đa búp đỏ" required /><button type="submit" className="dashboard-primary-button">Thêm vào phiên demo</button><p>Dữ liệu chỉ tồn tại tới khi tải lại trang.</p></form></section></div>}
  </div>;
}
