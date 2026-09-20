"use client";

import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import SiteHeader from "./SiteHeader";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setStatus(null);
    try {
      const res = await fetch("/api/login", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email.trim(), password }),
      });
      const data = await res.json();
      if (res.ok && data.success) { router.push("/dashboard"); return; }
      setStatus(data.message || "Không thể đăng nhập. Vui lòng thử lại.");
    } catch {
      setStatus("Không thể kết nối tới máy chủ. Vui lòng thử lại.");
    } finally { setLoading(false); }
  }

  return <div className="login-page"><SiteHeader active="login" />
    <main className="container-fluid">
      <div className="row login-row">
        <div className="col-md-6 brand-pane d-none d-md-flex flex-column justify-content-end p-5">
          <p className="fw-semibold small mb-4">CÙNG THIÊN NHIÊN, CÙNG BẠN</p>
          <h1 className="display-5 mb-4" style={{ color: "#fff" }}>Vũ điệu<br />rừng xanh</h1>
          <p className="mb-4" style={{ maxWidth: "28rem", lineHeight: 1.9 }}>Cùng thiên nhiên tạo nên những giá trị xanh bền vững. Bắt đầu hành trình chăm sóc mảng xanh của riêng bạn.</p>
          <div className="brand-pane__stats pt-4"><Link href="/" style={{ color: "white", fontSize: 13 }}>← Trở về trang chủ</Link></div>
        </div>
        <div className="col-12 col-md-6 d-flex align-items-center justify-content-center p-4 p-md-5">
          <div style={{ width: "100%", maxWidth: "26rem" }}>
            <p className="eyebrow">KHÔNG GIAN XANH CỦA BẠN</p>
            <h2 className="fw-bold mb-3" style={{ fontSize: 32 }}>Chào mừng trở lại.</h2>
            <p className="text-secondary mb-4" style={{ fontSize: 14, lineHeight: 1.8 }}>Đăng nhập để tiếp tục khám phá Vũ Điệu Rừng Xanh.</p>
            <form onSubmit={handleSubmit} aria-label="Đăng nhập" aria-busy={loading}>
              <div className="mb-3"><label htmlFor="email" className="form-label">Email</label>
                <input id="email" name="email" type="email" autoComplete="username" className="form-control" placeholder="ban@vidu.com" required value={email} onChange={e => setEmail(e.target.value)} /></div>
              <div className="mb-3"><label htmlFor="password" className="form-label">Mật khẩu</label>
                <input id="password" name="password" type={showPassword ? "text" : "password"} autoComplete="current-password" className="form-control" placeholder="Nhập mật khẩu" required value={password} onChange={e => setPassword(e.target.value)} /></div>
              <div className="form-check mb-4"><input id="show-password" type="checkbox" className="form-check-input" checked={showPassword} onChange={e => setShowPassword(e.target.checked)} /><label htmlFor="show-password" className="form-check-label small">Hiển thị mật khẩu</label></div>
              {status && <div className="alert alert-danger py-2 small" role="alert">{status}</div>}
              <button type="submit" className="btn btn-brand w-100 py-3 mb-3" disabled={loading}>{loading ? "Đang đăng nhập…" : "Đăng nhập ↗"}</button>
            </form>
            <p className="demo-note mt-3 mb-4">Bản demo giao diện · chưa kết nối tài khoản thật.<br />Email: <strong>demo@dv03.vn</strong><br />Mật khẩu: <strong>123456</strong></p>
            <Link href="/dashboard" className="link-brand small">Khám phá với tư cách khách →</Link>
          </div>
        </div>
      </div>
    </main>
  </div>;
}
