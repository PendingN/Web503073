import type { Metadata } from "next";
import AdminDashboard from "../../components/AdminDashboard";

export const metadata: Metadata = { title: "Quản trị" };

export default function AdminPage() {
  return <AdminDashboard />;
}
