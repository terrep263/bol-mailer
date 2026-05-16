import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "BOL Mailer",
  description: "AI-powered email automation for The Book of Lies",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
