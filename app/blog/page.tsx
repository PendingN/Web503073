import type { Metadata } from "next";
import Link from "next/link";
import SiteHeader from "../../components/SiteHeader";
import NewsletterForm from "../../components/NewsletterForm";

export const metadata: Metadata = {
  title: "Blog xanh",
  description: "Chuyện về cây, không gian sống và những thói quen xanh mỗi ngày.",
};

const posts = [
  { tag: "Chăm cây", date: "12.09.2026", title: "5 dấu hiệu cây đang cần bạn quan tâm", excerpt: "Đọc màu lá, độ ẩm của đất và nhịp phát triển để hiểu cây hơn mỗi ngày.", icon: "🌱", tone: "mint", time: "5 phút đọc" },
  { tag: "Không gian", date: "08.09.2026", title: "Chọn cây xanh cho căn phòng ít nắng", excerpt: "Những giống cây bền bỉ, dịu mắt và phù hợp với góc nhỏ trong nhà.", icon: "🪴", tone: "cream", time: "4 phút đọc" },
  { tag: "Cảm hứng", date: "01.09.2026", title: "Một ban công nhỏ cũng có thể thành khu vườn", excerpt: "Bắt đầu từ vài chiếc chậu, một góc nắng và thói quen chăm chút đều đặn.", icon: "🌿", tone: "sage", time: "6 phút đọc" },
  { tag: "Mẹo hay", date: "26.08.2026", title: "Tưới cây đúng cách trong mùa mưa", excerpt: "Giảm lượng nước, tăng khả năng thoát ẩm và giữ bộ rễ luôn khỏe mạnh.", icon: "💧", tone: "blue", time: "3 phút đọc" },
  { tag: "Chăm cây", date: "20.08.2026", title: "Thay chậu: khi nào và làm thế nào?", excerpt: "Một hướng dẫn ngắn để cây có không gian mới mà không bị sốc.", icon: "🌾", tone: "sand", time: "5 phút đọc" },
  { tag: "Sống xanh", date: "14.08.2026", title: "Tạo một nhịp sống chậm cùng cây", excerpt: "Năm phút chăm cây mỗi sáng có thể trở thành khoảng nghỉ dễ chịu nhất ngày.", icon: "☀️", tone: "sun", time: "4 phút đọc" },
];

export default function BlogPage() {
  return <div className="blog-page">
    <SiteHeader active="blog" />
    <main>
      <section className="blog-intro">
        <div>
          <p className="eyebrow">NHẬT KÝ RỪNG XANH</p>
          <h1>Chuyện nhỏ<br /><em>giữa những tán cây.</em></h1>
        </div>
        <p className="blog-lead">Ghi lại những cách thật đơn giản để hiểu cây hơn, sống chậm hơn và mang thiên nhiên vào từng góc nhỏ.</p>
      </section>

      <section className="featured-post" aria-labelledby="featured-title">
        <div className="featured-visual" aria-hidden="true"><span>🌿</span><small>GÓC XANH MỖI NGÀY</small></div>
        <div className="featured-copy">
          <div className="post-meta"><span className="post-tag">Bài nổi bật</span><span>14.09.2026 · 7 phút đọc</span></div>
          <h2 id="featured-title">Bắt đầu một góc xanh từ đâu?</h2>
          <p>Không cần một khu vườn rộng. Chỉ cần chọn đúng loại cây, hiểu ánh sáng trong phòng và bắt đầu với một nhịp chăm sóc vừa đủ.</p>
          <Link href="/dashboard#care" className="blog-read-link">Đọc câu chuyện <span aria-hidden="true">→</span></Link>
        </div>
      </section>

      <section className="blog-list" aria-labelledby="latest-title">
        <div className="blog-section-head"><div><p className="eyebrow">MỚI NHẤT</p><h2 id="latest-title">Đọc một chút, xanh thêm một chút.</h2></div><Link href="/dashboard#collections">Xem bộ sưu tập cây ↗</Link></div>
        <div className="post-grid">
          {posts.map(post => <article className="post-card" key={post.title}>
            <div className={`post-art post-art-${post.tone}`} aria-hidden="true"><span>{post.icon}</span></div>
            <div className="post-card-body">
              <div className="post-meta"><span>{post.tag}</span><span>{post.date}</span></div>
              <h3>{post.title}</h3><p>{post.excerpt}</p>
              <div className="post-card-foot"><span>{post.time}</span><Link href="/dashboard#care" aria-label={`Đọc bài ${post.title}`}>Đọc bài →</Link></div>
            </div>
          </article>)}
        </div>
      </section>

      <section className="blog-newsletter"><div><p className="eyebrow">LÁ THƯ XANH</p><h2>Mỗi tháng, một chút cảm hứng.</h2><p>Nhận mẹo chăm cây và những câu chuyện nhỏ từ Vũ Điệu Rừng Xanh.</p></div><NewsletterForm /></section>
    </main>
    <footer className="site-footer">Vũ Điệu Rừng Xanh <span>Chậm lại một chút, gần thiên nhiên thêm một chút.</span></footer>
  </div>;
}
