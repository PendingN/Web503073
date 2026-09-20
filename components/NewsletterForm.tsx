"use client";

import { FormEvent, useState } from "react";

export default function NewsletterForm() {
  const [subscribed, setSubscribed] = useState(false);

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubscribed(true);
  }

  if (subscribed) return <p className="newsletter-success" role="status">✓ Cảm ơn bạn! Lá thư xanh tiếp theo sẽ sớm ghé hộp thư.</p>;

  return <form onSubmit={handleSubmit}>
    <label className="sr-only" htmlFor="newsletter-email">Email của bạn</label>
    <input id="newsletter-email" name="email" type="email" placeholder="Email của bạn" required />
    <button type="submit">Đăng ký →</button>
  </form>;
}
