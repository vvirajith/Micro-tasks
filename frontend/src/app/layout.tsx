import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Micro Task",
  description: "Next.js frontend backed by a Laravel API.",
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
