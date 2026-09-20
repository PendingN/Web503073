"use client";

import Link from "next/link";
import { useState } from "react";

const orders = [
  { id: "#RX1048", customer: "Nguyễn Minh Anh", product: "Trầu bà lá xẻ", total: "349.000 ₫", status: "Đang giao" },
  { id: "#RX1047", customer: "Lê Hoàng Nam", product: "Lưỡi hổ mini", total: "259.000 ₫", status: "Hoàn thành" },
  { id: "#RX1046", customer: "Trần Thu Hà", product: "Kim tiền", total: "429.000 ₫", status: "Chờ xác nhận" },
  { id: "#RX1045", customer: "Phạm Gia Bảo", product: "Sen đá · 2 cây", total: "318.000 ₫", status: "Hoàn thành" },
];

const nav = ["Tổng quan", "Đơn hàng", "Sản phẩm", "Bài viết", "Khách hàng"];

export default function AdminDashboard() {
  const [active, setActive] = useState("Tổng quan");
  const [notice, setNotice] = useState("");
  const [published, setPublished] = useState(true);

  const show = (message: string) => { setNotice(message); window.setTimeout(() => setNotice(""), 2600); };

  return <div className="admin-shell">
    <aside className="admin-sidebar">
      <Link className="admin-brand" href="/"><span>↗</span><strong>VŨ ĐIỆU<br />RỪNG XANH</strong></Link>
      <p className="admin-label">QUẢN TRỊ</p>
      <nav aria-label="Điều hướng quản trị">{nav.map(item => <button key={item} className={active === item ? "is-active" : ""} onClick={() => { setActive(item); show(`Đã chuyển đến mục ${item}.`); }}><span aria-hidden="true">{({ "Tổng quan": "⌂", "Đơn hàng": "□", "Sản phẩm": "◇", "Bài viết": "✎", "Khách hàng": "○" } as Record<string,string>)[item]}</span>{item}</button>)}</nav>
      <div className="admin-sidebar-foot"><div className="admin-avatar">AD</div><div><strong>Anh Dũng</strong><small>Quản trị viên</small></div><Link href="/" aria-label="Về trang chủ">↗</Link></div>
    </aside>

    <div className="admin-content">
      <header className="admin-topbar"><div><p>Thứ hai, 14 tháng 9</p><h1>Chào buổi tối, Anh Dũng.</h1></div><div className="admin-actions"><Link href="/blog">Xem blog</Link><button onClick={() => show("Đã mở biểu mẫu thêm sản phẩm (bản minh họa).")}>＋ Thêm sản phẩm</button></div></header>
      <main className="admin-main">
        {notice && <div className="admin-toast" role="status">✓ {notice}</div>}
        <section className="admin-stats" aria-label="Số liệu tổng quan">
          {[
            ["Doanh thu tháng", "24,8 triệu", "+12,5%", "↗"], ["Đơn hàng", "128", "+8 đơn hôm nay", "□"], ["Khách hàng", "1.842", "+34 người mới", "○"], ["Bài viết", "16", "3 bản nháp", "✎"]
          ].map(([label,value,note,icon]) => <article key={label}><div className="stat-top"><span>{label}</span><i>{icon}</i></div><strong>{value}</strong><small>{note}</small></article>)}
        </section>

        <div className="admin-columns">
          <section className="admin-panel revenue-panel"><div className="panel-head"><div><p className="eyebrow">HIỆU SUẤT</p><h2>Doanh thu 7 ngày</h2></div><button onClick={() => show("Báo cáo đang hiển thị theo 7 ngày gần nhất.")}>7 ngày⌄</button></div><div className="chart-area" aria-label="Biểu đồ doanh thu từ thứ hai đến chủ nhật">{[42,58,48,78,65,92,73].map((height,i) => <div className="chart-column" key={i}><div className="chart-bar"><span style={{height: `${height}%`}} /></div><small>{["T2","T3","T4","T5","T6","T7","CN"][i]}</small></div>)}</div><div className="chart-summary"><span><i /> Doanh thu</span><strong>8.420.000 ₫</strong></div></section>
          <section className="admin-panel quick-panel"><div className="panel-head"><div><p className="eyebrow">HÔM NAY</p><h2>Cần chú ý</h2></div><span className="count-badge">4</span></div><ul><li><span>06</span><div><strong>Đơn chờ xác nhận</strong><small>Cần xử lý trước 18:00</small></div><button onClick={() => show("Đang mở danh sách đơn chờ xác nhận.")}>→</button></li><li><span>03</span><div><strong>Sản phẩm sắp hết</strong><small>Kiểm tra lại tồn kho</small></div><button onClick={() => show("Đang mở danh sách tồn kho.")}>→</button></li><li><span>02</span><div><strong>Bình luận mới</strong><small>Đang chờ phản hồi</small></div><button onClick={() => show("Đang mở bình luận mới.")}>→</button></li></ul></section>
        </div>

        <section className="admin-panel orders-panel"><div className="panel-head"><div><p className="eyebrow">CỬA HÀNG</p><h2>Đơn hàng gần đây</h2></div><button onClick={() => show("Đang hiển thị 4 đơn gần nhất.")}>Xem tất cả →</button></div><div className="admin-table-wrap"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Trạng thái</th><th /></tr></thead><tbody>{orders.map(order => <tr key={order.id}><td><strong>{order.id}</strong></td><td>{order.customer}</td><td>{order.product}</td><td>{order.total}</td><td><span className={`order-status status-${order.status.replaceAll(" ", "-").toLowerCase()}`}>{order.status}</span></td><td><button onClick={() => show(`Đang xem chi tiết đơn ${order.id}.`)} aria-label={`Xem ${order.id}`}>•••</button></td></tr>)}</tbody></table></div></section>

        <section className="admin-panel editorial-panel"><div><p className="eyebrow">BLOG</p><h2>Bài viết nổi bật</h2><p>“Bắt đầu một góc xanh từ đâu?” đang được chọn làm bài viết chính trên trang blog.</p></div><label className="publish-toggle"><input type="checkbox" checked={published} onChange={e => { setPublished(e.target.checked); show(e.target.checked ? "Đã xuất bản bài viết." : "Đã chuyển bài viết về bản nháp."); }} /><span /><small>{published ? "Đang xuất bản" : "Bản nháp"}</small></label><Link href="/blog">Xem bài viết ↗</Link></section>
      </main>
    </div>
  </div>;
}
