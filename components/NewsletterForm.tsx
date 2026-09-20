"use client";

import { FormEvent, useState } from "react";

export default function NewsletterForm() {
  const [subscribed, setSubscribed] = useState(false);
  const [email, setEmail] = useState("");

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubscribed(true);
  }

  if (subscribed) return <div className="newsletter-success" role="status"><strong>✓ Đã ghi nhận trong bản demo.</strong><span>{email} chưa được gửi tới hệ thống thật.</span><button type="button" className="newsletter-reset" onClick={() => setSubscribed(false)}>Đổi email</button></div>;

  return <form onSubmit={handleSubmit}>
    <label className="sr-only" htmlFor="newsletter-email">Email của bạn</label>
    <input id="newsletter-email" name="email" type="email" placeholder="Email của bạn" required value={email} onChange={event => setEmail(event.target.value)} />
    <button type="submit">Đăng ký →</button>
  </form>;
}
