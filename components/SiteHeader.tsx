"use client";
import { useState } from "react";
import Link from "next/link";

export default function SiteHeader({ active = "home" }: { active?: "home" | "login" | "blog" }) {
  const [open, setOpen] = useState(false);
  return <header className="navbar site-header">
    <Link className="brand-link" href="/" aria-label="Vũ Điệu Rừng Xanh — Trang chủ"><span className="brand-mark" aria-hidden="true">✦</span><span>VŨ ĐIỆU<br />RỪNG XANH</span></Link>
    <nav aria-label="Điều hướng chính" id="site-menu" className={`nav-center ${open ? "is-open" : ""}`}>
      <Link className={`nav-link ${active === "home" ? "active" : ""}`} aria-current={active === "home" ? "page" : undefined} href="/" onClick={() => setOpen(false)}>Trang chủ</Link>
      <Link className="nav-link" href="/#about" onClick={() => setOpen(false)}>Giới thiệu</Link>
      <Link className="nav-link" href="/dashboard#collections" onClick={() => setOpen(false)}>Bộ sưu tập</Link>
      <Link className="nav-link" href="/dashboard#products" onClick={() => setOpen(false)}>Sản phẩm</Link>
      <Link className="nav-link" href="/dashboard#care" onClick={() => setOpen(false)}>Chăm sóc cây</Link>
      <Link className={`nav-link ${active === "blog" ? "active" : ""}`} aria-current={active === "blog" ? "page" : undefined} href="/blog" onClick={() => setOpen(false)}>Blog</Link>
    </nav>
    <div className="nav-right"><Link href={active === "login" ? "/dashboard" : "/login"} className="btn-contact">{active === "login" ? "Xem catalog" : "Đăng nhập demo"}<span aria-hidden="true">→</span></Link>
    <button className="menu-icon" type="button" aria-label={open ? "Đóng menu" : "Mở menu"} aria-controls="site-menu" aria-expanded={open} onClick={() => setOpen(!open)}>☰</button></div>
  </header>;
}
