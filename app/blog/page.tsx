import type { Metadata } from "next";
import Link from "next/link";
import SiteHeader from "../../components/SiteHeader";
import NewsletterForm from "../../components/NewsletterForm";
import PlantIllustration from "../../components/PlantIllustration";
import { featuredPost, posts } from "../../shared/content";

export const metadata: Metadata = {
  title: "Blog xanh",
  description: "Chuyện về cây, không gian sống và những thói quen xanh mỗi ngày.",
};

export default function BlogPage() {
  return <div className="blog-page">
    <SiteHeader active="blog" />
    <main id="main-content">
      <section className="blog-intro">
        <div>
          <p className="eyebrow">NHẬT KÝ RỪNG XANH</p>
          <h1>Chuyện nhỏ<br /><em>giữa những tán cây.</em></h1>
        </div>
        <p className="blog-lead">Ghi lại những cách thật đơn giản để hiểu cây hơn, sống chậm hơn và mang thiên nhiên vào từng góc nhỏ.</p>
      </section>

      <section className="featured-post" aria-labelledby="featured-title">
        <div className="featured-visual" aria-hidden="true"><PlantIllustration variant={featuredPost.illustration} /><small>GÓC XANH MỖI NGÀY</small></div>
        <div className="featured-copy">
          <div className="post-meta"><span className="post-tag">{featuredPost.tag}</span><span>{featuredPost.date} · {featuredPost.time}</span></div>
          <h2 id="featured-title">{featuredPost.title}</h2>
          <p>{featuredPost.excerpt}</p>
          <Link href={`/blog/${featuredPost.slug}`} className="blog-read-link">Đọc câu chuyện <span aria-hidden="true">→</span></Link>
        </div>
      </section>

      <section className="blog-list" aria-labelledby="latest-title">
        <div className="blog-section-head"><div><p className="eyebrow">MỚI NHẤT</p><h2 id="latest-title">Đọc một chút, xanh thêm một chút.</h2></div><Link href="/dashboard#collections">Xem bộ sưu tập cây →</Link></div>
        <div className="post-grid">
          {posts.map(post => <article className="post-card" key={post.slug}>
            <Link className="post-card-link" href={`/blog/${post.slug}`} aria-label={`Đọc bài ${post.title}`}>
              <div className={`post-art post-art-${post.tone}`} aria-hidden="true"><PlantIllustration variant={post.illustration} /></div>
              <div className="post-card-body">
                <div className="post-meta"><span>{post.tag}</span><span>{post.date}</span></div>
                <h3>{post.title}</h3><p>{post.excerpt}</p>
                <div className="post-card-foot"><span>{post.time}</span><span>Đọc bài →</span></div>
              </div>
            </Link>
          </article>)}
        </div>
      </section>

      <section className="blog-newsletter"><div><p className="eyebrow">LÁ THƯ XANH</p><h2>Mỗi tháng, một chút cảm hứng.</h2><p>Nhận mẹo chăm cây và những câu chuyện nhỏ từ Vũ Điệu Rừng Xanh.</p></div><NewsletterForm /></section>
    </main>
    <footer className="site-footer">Vũ Điệu Rừng Xanh <span>Chậm lại một chút, gần thiên nhiên thêm một chút.</span></footer>
  </div>;
}
