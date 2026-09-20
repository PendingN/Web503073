import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import SiteHeader from "../../../components/SiteHeader";
import PlantIllustration from "../../../components/PlantIllustration";
import { featuredPost, posts } from "../../../shared/content";

const allPosts = [featuredPost, ...posts];

export function generateStaticParams() {
  return allPosts.map(post => ({ slug: post.slug }));
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }): Promise<Metadata> {
  const { slug } = await params;
  const post = allPosts.find(item => item.slug === slug);
  return post ? { title: post.title, description: post.excerpt } : { title: "Không tìm thấy bài viết" };
}

export default async function BlogPostPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const post = allPosts.find(item => item.slug === slug);
  if (!post) notFound();

  return <div className="blog-page blog-post-page">
    <SiteHeader active="blog" />
    <main id="main-content">
      <article className="blog-post-layout">
        <Link href="/blog" className="back-link">← Quay lại Blog</Link>
        <div className={`blog-post-art post-art-${post.tone}`}><PlantIllustration variant={post.illustration} /></div>
        <div className="blog-post-copy">
          <div className="post-meta"><span className="post-tag">{post.tag}</span><span>{post.date} · {post.time}</span></div>
          <h1>{post.title}</h1>
          <p className="blog-post-lead">{post.excerpt}</p>
          <div className="blog-post-body">{post.body.map(paragraph => <p key={paragraph}>{paragraph}</p>)}</div>
          <div className="blog-post-footer"><Link className="btn-brand blog-post-cta" href="/dashboard#collections">Khám phá cây phù hợp →</Link><Link className="link-brand" href="/blog">Xem các bài khác</Link></div>
        </div>
      </article>
    </main>
    <footer className="site-footer">Vũ Điệu Rừng Xanh <span>Chậm lại một chút, gần thiên nhiên thêm một chút.</span></footer>
  </div>;
}
