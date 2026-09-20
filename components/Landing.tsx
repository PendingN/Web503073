import Link from "next/link";
import SiteHeader from "./SiteHeader";

export default function Landing() {
  return <div className="landing-page">
    <SiteHeader />
    <main id="main-content">
      <section className="hero-section" id="home" aria-labelledby="landing-title">
        <div className="torn-paper" aria-hidden="true" />
        <div className="hero-overlay" aria-hidden="true" />
        <div className="title-container">
          <h1 id="landing-title" className="main-title">VŨ ĐIỆU<br /><span>RỪNG XANH</span></h1>
          <p className="hero-description">Cùng thiên nhiên tạo nên những giá trị xanh bền vững</p>
          <Link className="btn-contact hero-cta" href="/dashboard">Khám phá không gian xanh <span aria-hidden="true">→</span></Link>
        </div>
      </section>
      <section className="landing-about" id="about">
        <div><p className="eyebrow">VŨ ĐIỆU RỪNG XANH</p><h2>Mang thiên nhiên<br />đến gần hơn.</h2></div>
        <div><p>Tìm một mảng xanh cho không gian sống của bạn, khám phá các bộ sưu tập cây và cùng tìm hiểu cách chăm sóc mỗi ngày.</p><Link href="/dashboard#collections" className="link-brand">Khám phá bộ sưu tập →</Link></div>
      </section>
    </main>
    <footer className="site-footer">Vũ Điệu Rừng Xanh <span>Cùng thiên nhiên tạo nên những giá trị xanh bền vững.</span></footer>
  </div>;
}
