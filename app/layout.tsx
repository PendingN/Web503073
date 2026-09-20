import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: { default: "Vũ Điệu Rừng Xanh", template: "%s | Vũ Điệu Rừng Xanh" },
  description: "Cùng thiên nhiên tạo nên những giá trị xanh bền vững.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return <html lang="vi" data-scroll-behavior="smooth"><head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;900&display=swap" rel="stylesheet" />
  </head><body><a className="skip-link" href="#main-content">Bỏ qua đến nội dung chính</a>{children}</body></html>;
}
