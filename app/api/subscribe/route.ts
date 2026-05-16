import { NextRequest, NextResponse } from "next/server";
import { SEQUENCES } from "@/lib/sequences";
import { createList, addSubscriber, getSubscriberByEmail, updateSubscriberAttribs, sendTransactionalEmail } from "@/lib/listmonk";
import { generateEmail } from "@/lib/claude";

const ALLOWED_ORIGINS = [
  "https://thebookoflies.shop",
  "https://thebookoflies.online",
];

function getCorsHeaders(origin: string | null) {
  const allowed = origin && ALLOWED_ORIGINS.includes(origin) ? origin : ALLOWED_ORIGINS[0];
  return {
    "Access-Control-Allow-Origin": allowed,
    "Access-Control-Allow-Methods": "POST, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type",
  };
}

export async function OPTIONS(req: NextRequest) {
  const origin = req.headers.get("origin");
  return new NextResponse(null, { status: 204, headers: getCorsHeaders(origin) });
}

export async function POST(req: NextRequest) {
  const origin = req.headers.get("origin");
  const corsHeaders = getCorsHeaders(origin);

  try {
    const body = await req.json();
    const { email, firstName, sequenceId } = body;

    if (!email || !firstName || !sequenceId) {
      return NextResponse.json({ success: false, error: "Missing required fields" }, { status: 400, headers: corsHeaders });
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return NextResponse.json({ success: false, error: "Invalid email format" }, { status: 400, headers: corsHeaders });
    }

    const sequence = SEQUENCES.find((s) => s.id === sequenceId);
    if (!sequence) {
      return NextResponse.json({ success: false, error: "Sequence not found" }, { status: 404, headers: corsHeaders });
    }

    const listId = await createList(sequence.listName, sequence.name);
    await addSubscriber(email, firstName, listId);

    const subscriber = await getSubscriberByEmail(email);
    if (!subscriber) {
      return NextResponse.json({ success: false, error: "Failed to retrieve subscriber" }, { status: 500, headers: corsHeaders });
    }

    await updateSubscriberAttribs(subscriber.id, {
      sequence_id: sequenceId,
      sequence_step: 0,
      subscribed_at: new Date().toISOString(),
      sequence_complete: false,
      previous_emails: [],
    });

    const email1 = await generateEmail({
      sequencePosition: 1,
      firstName,
      brandContext: sequence.brandContext,
      previousEmails: [],
      targetAction: sequence.targetAction,
    });

    await sendTransactionalEmail(email, email1.subject, email1.body, sequence.fromEmail, sequence.fromName);

    await updateSubscriberAttribs(subscriber.id, {
      sequence_step: 1,
      last_sent_at: new Date().toISOString(),
      previous_emails: [`Subject: ${email1.subject}\n\n${email1.body}`],
    });

    return NextResponse.json({ success: true }, { headers: corsHeaders });
  } catch (err: any) {
    console.error(`[subscribe] ${new Date().toISOString()} ERROR:`, err?.message || err);
    return NextResponse.json({ success: false, error: "Internal server error" }, { status: 500, headers: corsHeaders });
  }
}
