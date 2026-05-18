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
    const hmac = crypto.createHmac("sha256", secret);
    hmac.update(payload);
    const expected = hmac.digest("hex");
    const sig = signature.replace(/^sha256=/, "");
    if (sig.length === expected.length) {
      return crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(expected));
    }
    return false;
  } catch {
    return false;
  }
}

export async function POST(req: NextRequest) {
  try {
    const rawBody = await req.text();
    const webhookSecret = process.env.ZYLVIE_WEBHOOK_SECRET;

    // Verify signature if secret is configured
    if (webhookSecret && webhookSecret !== "PENDING_SET_FROM_ZYLVIE") {
      const sig =
        req.headers.get("x-zylvie-signature") ||
        req.headers.get("x-signature") ||
        req.headers.get("x-webhook-signature") ||
        req.headers.get("signature") ||
        "";

      // Try body field as fallback
      let bodySecret = "";
      try {
        const parsed = JSON.parse(rawBody);
        bodySecret = parsed.secret || parsed.signing_secret || "";
      } catch {}

      const sigValid = sig ? verifySignature(rawBody, sig, webhookSecret) : false;
      const bodyValid = bodySecret === webhookSecret;

      if (!sigValid && !bodyValid) {
        // Log headers to help diagnose
        const allHeaders: Record<string, string> = {};
        req.headers.forEach((v, k) => { allHeaders[k] = v; });
        console.error("Zylvie webhook auth failed", { sig, bodySecret, headers: allHeaders });
        return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
      }
    }

    const body = JSON.parse(rawBody);

    // Only process completed purchases — skip if explicit non-purchase event
    const event = body.event || body.type || body.trigger;
    const skipEvents = ["subscription.cancelled", "refund", "chargeback"];
    if (event && skipEvents.includes(event)) {
      return NextResponse.json({ received: true, skipped: true, event });
    }

    // Extract buyer info
    const email =
      body.customer?.email || body.buyer?.email || body.email || body.customer_email;
    const firstName =
      body.customer?.first_name ||
      body.buyer?.first_name ||
      body.first_name ||
      body.customer_name?.split(" ")[0] ||
      "Friend";
    const productId =
      body.product?.id || body.product_id || body.base_product || body.item?.id;

    if (!email) {
      console.error("No email in Zylvie payload", body);
      return NextResponse.json({ error: "No email in payload" }, { status: 400 });
    }

    // Map product to sequence (default to first if no product ID)
    const sequenceId = productId
      ? PRODUCT_SEQUENCE_MAP[productId]
      : Object.values(PRODUCT_SEQUENCE_MAP)[0];
    const sequence = SEQUENCES.find((s) => s.id === sequenceId);

    if (!sequence) {
      console.error(`No sequence found for product ${productId}`);
      return NextResponse.json({ error: "No sequence mapped" }, { status: 400 });
    }

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

    return NextResponse.json({ success: true, sequence: sequenceId, email });
  } catch (error: any) {
    console.error("Zylvie webhook error:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function GET() {
  return NextResponse.json({ status: "ok" });
}
