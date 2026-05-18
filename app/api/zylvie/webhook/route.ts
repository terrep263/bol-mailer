import { NextRequest, NextResponse } from "next/server";
import { addSubscriber, createList, sendTransactionalEmail } from "@/lib/listmonk";
import { SEQUENCES } from "@/lib/sequences";

// Map Zylvie product IDs to sequence IDs
const PRODUCT_SEQUENCE_MAP: Record<string, string> = {
  "D8njKL8ni": "bol-faith-prelaunch",
};

const PDF_URL = "https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/The+Book+Of+Lies+Faith.pdf";
const EPUB_URL = "https://bookoflies-853537565894-us-east-1-an.s3.us-east-1.amazonaws.com/the+book+of+lies+faith.epub";

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();

    // Verify webhook secret
    const secret = req.headers.get("x-zylvie-secret") || body.secret;
    if (process.env.ZYLVIE_WEBHOOK_SECRET && secret !== process.env.ZYLVIE_WEBHOOK_SECRET) {
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
    }

    // Only process completed purchases
    const event = body.event || body.type;
    if (event !== "order.completed" && event !== "purchase" && event !== "order.success") {
      return NextResponse.json({ received: true, skipped: true });
    }

    // Extract buyer info
    const email = body.customer?.email || body.email;
    const firstName = body.customer?.first_name || body.first_name || "Friend";
    const productId = body.product?.id || body.product_id || body.base_product;

    if (!email) {
      return NextResponse.json({ error: "No email in payload" }, { status: 400 });
    }

    // Map product to sequence
    const sequenceId = productId ? PRODUCT_SEQUENCE_MAP[productId] : Object.values(PRODUCT_SEQUENCE_MAP)[0];
    const sequence = SEQUENCES.find((s) => s.id === sequenceId);

    if (!sequence) {
      console.error(`No sequence found for product ${productId}`);
      return NextResponse.json({ error: "No sequence mapped" }, { status: 400 });
    }

    // Add buyer to Brevo list
    const listId = await createList(sequence.listName);
    await addSubscriber(email, firstName, listId);

    // Send post-purchase welcome email with download links
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

// Required for Zylvie webhook verification
export async function GET() {
  return NextResponse.json({ status: "ok" });
}
