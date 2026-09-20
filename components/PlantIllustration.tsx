import type { Product } from "../shared/content";

type Illustration = Product["illustration"] | "sprout" | "pot" | "leaf" | "water" | "grass" | "sun" | "tree";

export default function PlantIllustration({ variant, className = "" }: { variant: Illustration; className?: string }) {
  return (
    <svg className={`plant-illustration ${className}`} viewBox="0 0 120 120" aria-hidden="true" focusable="false">
      <circle cx="60" cy="60" r="46" fill="currentColor" opacity=".12" />
      {variant === "water" ? <>
        <path d="M60 25c-11 15-18 24-18 34a18 18 0 0 0 36 0c0-10-7-19-18-34Z" fill="currentColor" opacity=".78" />
        <path d="M39 80c13 6 29 6 42 0" fill="none" stroke="currentColor" strokeWidth="4" strokeLinecap="round" opacity=".55" />
      </> : variant === "sun" ? <>
        <circle cx="60" cy="56" r="20" fill="currentColor" opacity=".72" />
        {Array.from({ length: 8 }).map((_, index) => {
          const angle = (index * Math.PI) / 4;
          const x1 = 60 + Math.cos(angle) * 30;
          const y1 = 56 + Math.sin(angle) * 30;
          const x2 = 60 + Math.cos(angle) * 40;
          const y2 = 56 + Math.sin(angle) * 40;
          return <line key={index} x1={x1} y1={y1} x2={x2} y2={y2} stroke="currentColor" strokeWidth="4" strokeLinecap="round" opacity=".65" />;
        })}
      </> : variant === "grass" ? <>
        <path d="M42 83c6-22 10-33 18-49M60 83c0-24 4-37 10-50M78 83c-4-20-3-31 2-41" fill="none" stroke="currentColor" strokeWidth="5" strokeLinecap="round" />
        <path d="M36 84h48" stroke="currentColor" strokeWidth="5" strokeLinecap="round" opacity=".5" />
      </> : variant === "tree" ? <>
        <path d="M56 56h8v28h-8z" fill="currentColor" opacity=".68" />
        <path d="M60 24 39 57h12L36 76h48L69 57h12Z" fill="currentColor" opacity=".78" />
      </> : variant === "pot" ? <>
        <path d="M39 59h42l-5 28H44Z" fill="currentColor" opacity=".62" />
        <path d="M36 57h48" stroke="currentColor" strokeWidth="6" strokeLinecap="round" />
        <path d="M60 57V31M60 39c-11-12-22-3-20 7 8 1 15-1 20-7Zm0 9c11-12 22-3 20 7-8 1-15-1-20-7Z" fill="currentColor" opacity=".82" />
      </> : variant === "sprout" ? <>
        <path d="M60 84V48" stroke="currentColor" strokeWidth="6" strokeLinecap="round" />
        <path d="M60 54c-17-20-34-7-31 8 13 2 23-1 31-8Zm0-5c17-20 34-7 31 8-13 2-23-1-31-8Z" fill="currentColor" opacity=".78" />
        <path d="M45 86h30" stroke="currentColor" strokeWidth="6" strokeLinecap="round" opacity=".5" />
      </> : <>
        <path d="M58 86c2-17 5-31 14-45" fill="none" stroke="currentColor" strokeWidth="6" strokeLinecap="round" />
        <path d="M67 48c-8-17 7-28 20-23 1 12-6 21-20 23Zm-7 13C48 48 31 55 28 69c12 5 24 1 32-8Z" fill="currentColor" opacity=".8" />
        <path d="M47 86h28" stroke="currentColor" strokeWidth="6" strokeLinecap="round" opacity=".5" />
      </>}
    </svg>
  );
}
