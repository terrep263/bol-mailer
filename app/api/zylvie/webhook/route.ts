import { NextRequest, NextResponse } from "next/server";
import crypto from "crypto";
import { addSubscriber, createList, sendTransactionalEmail } from "@/lib/listmonk";
import { SEQUENCES } from "@/lib/sequences";

// Map Zylvie product IDs to sequence IDs
const PRODUCT_SEQUENCE_MAP: Record<string, string> = {
  "D8njKL8ni": "bol-faith-prelaunch",
};

const PDF_URL = "https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/The+Book+Of+Lies+Faith.pdf";
const EPUB_URL = "https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/the+book+of+lies+faith.epub";

function verifySignature(payload: string, signature: string, secret: string): boolean {
  try {
    const sig = signature.replace(/^sha256=/, "");
    const hmac = crypto.createHmac("sha256", secret);
    hmac.update(payload);
    const expected = hmac.digest("hex");
    if (sig.length !== expected.length) return false;
    return crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(expected));
  } catch {
    return false;
  }
}

export async function POST(req: NextRequest) {
  try {
    const rawBody = await req.text();
    const webhookSecret = process.env.ZYLVIE_WEBHOOK_SECRET;

    // Verify HMAC signature if secret is configured
    if (webhookSecret && webhookSecret !== "PENDING_SET_FROM_ZYLVIE") {
      const sig =
        req.headers.get("x-zylvie-signature") ||
        req.headers.get("x-signature") ||
        req.headers.get("x-webhook-signature") ||
        req.headers.get("x-hub-signature-256") ||
        "";

      if (sig && !verifySignature(rawBody, sig, webhookSecret)) {
        console.error("Zylvie webhook signature mismatch");
        return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
      }
    }

    const body = JSON.parse(rawBody);
    const event = body.event;
    const data = body.data;

    // Only process sale events
    if (event !== "sale") {
      return NextResponse.json({ received: true, skipped: true, event });
    }

    // Extract buyer info from correct payload path
    const email = data?.buyer?.email;
    const firstName = data?.buyer?.first_name || data?.buyer?.name?.split(" ")[0] || "Friend";

    // Get all product IDs from this sale
    const productIds: string[] = (data?.products || []).map((p: any) => p.id);

    if (!email) {
      console.error("No buyer email in Zylvie payload");
      return NextResponse.json({ error: "No email in payload" }, { status: 400 });
    }

    // Find matching sequences for purchased products
    const matchedSequenceIds = productIds
      .map((id) => PRODUCT_SEQUENCE_MAP[id])
      .filter(Boolean);

    // Default to first sequence if no product match (catches test events)
    if (matchedSequenceIds.length === 0) {
      const defaultId = Object.values(PRODUCT_SEQUENCE_MAP)[0];
      matchedSequenceIds.push(defaultId);
    }

    const results = [];

    for (const sequenceId of matchedSequenceIds) {
      const sequence = SEQUENCES.find((s) => s.id === sequenceId);
      if (!sequence) continue;

      // Add buyer to Brevo list
      const listId = await createList(sequence.listName);
      await addSubscriber(email, firstName, listId);

      // Send welcome email with download links
      const emailBody = `${firstName},

Your copy of The Book of Lies: Faith is ready.

You made a decision today that most people never make — to stop accepting answers that don't hold up and start looking for the truth.

Here are your download links:

PDF Version:
${PDF_URL}

EPUB Version:
${EPUB_URL}

Read it slowly. Some of what's in there will confirm what you already suspected. Some of it will surprise you. All of it is true.

Welcome to the other side of the lie.

— the AMerican

P.S. Over the next two weeks I'll be sending you a few more things. Keep an eye out.`;

      await sendTransactionalEmail(
        email,
        "Your copy of The Book of Lies: Faith is ready",
        emailBody,
        sequence.fromEmail,
        sequence.fromName
      );

      results.push({ sequence: sequenceId, email });
    }

    return NextResponse.json({ success: true, results });
  } catch (error: any) {
    console.error("Zylvie webhook error:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function GET() {
  return NextResponse.json({ status: "ok" });
}
