import { NextRequest, NextResponse } from "next/server";
import { SEQUENCES } from "@/lib/sequences";
import { createList, addSubscriber, getSubscriberByEmail, updateSubscriberAttribs, sendTransactionalEmail } from "@/lib/listmonk";
import { generateEmail } from "@/lib/claude";

const CORS_HEADERS = {
  "Access-Control-Allow-Origin": "https://thebookoflies.online",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type",
};

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: CORS_HEADERS });
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const { email, firstName, sequenceId } = body;

    if (!email || !firstName || !sequenceId) {
      return NextResponse.json({ success: false, error: "Missing required fields" }, { status: 400, headers: CORS_HEADERS });
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return NextResponse.json({ success: false, error: "Invalid email format" }, { status: 400, headers: CORS_HEADERS });
    }

    const sequence = SEQUENCES.find((s) => s.id === sequenceId);
    if (!sequence) {
      return NextResponse.json({ success: false, error: "Sequence not found" }, { status: 404, headers: CORS_HEADERS });
    }

    // Create or get list
    const listId = await createList(sequence.listName, sequence.name);

    // Add subscriber to Listmonk
    await addSubscriber(email, firstName, listId);

    // Get subscriber record
    const subscriber = await getSubscriberByEmail(email);
    if (!subscriber) {
      return NextResponse.json({ success: false, error: "Failed to retrieve subscriber" }, { status: 500, headers: CORS_HEADERS });
    }

    // Set initial attribs
    await updateSubscriberAttribs(subscriber.id, {
      sequence_id: sequenceId,
      sequence_step: 0,
      subscribed_at: new Date().toISOString(),
      sequence_complete: false,
      previous_emails: [],
    });

    // Generate and send email #1 immediately
    const email1 = await generateEmail({
      sequencePosition: 1,
      firstName,
      brandContext: sequence.brandContext,
      previousEmails: [],
      targetAction: sequence.targetAction,
    });

    await sendTransactionalEmail(email, email1.subject, email1.body, sequence.fromEmail, sequence.fromName);

    // Update attribs after send
    await updateSubscriberAttribs(subscriber.id, {
      sequence_step: 1,
      last_sent_at: new Date().toISOString(),
      previous_emails: [`Subject: ${email1.subject}\n\n${email1.body}`],
    });

    return NextResponse.json({ success: true }, { headers: CORS_HEADERS });
  } catch (err: any) {
    console.error(`[subscribe] ${new Date().toISOString()} ERROR:`, err?.message || err);
    return NextResponse.json({ success: false, error: "Internal server error" }, { status: 500, headers: CORS_HEADERS });
  }
}
