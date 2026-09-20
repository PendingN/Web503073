import { NextRequest, NextResponse } from "next/server";

// API đăng nhập minh họa; không tạo phiên đăng nhập thật.
export async function POST(request: NextRequest) {
  let input: unknown;

  try {
    input = await request.json();
  } catch {
    return NextResponse.json({ success: false, message: "Dữ liệu đăng nhập không hợp lệ." }, { status: 400 });
  }

  if (!input || typeof input !== "object") {
    return NextResponse.json({ success: false, message: "Dữ liệu đăng nhập không hợp lệ." }, { status: 400 });
  }

  const { email, password } = input as Record<string, unknown>;
  if (typeof email !== "string" || typeof password !== "string" || !email.trim() || !password) {
    return NextResponse.json({ success: false, message: "Vui lòng nhập đầy đủ email và mật khẩu." }, { status: 400 });
  }

  if (email.trim().toLowerCase() === "demo@dv03.vn" && password === "123456") {
    return NextResponse.json({ success: true, message: "Đăng nhập demo thành công!" });
  }

  return NextResponse.json({ success: false, message: "Email hoặc mật khẩu không đúng." }, { status: 401 });
}
