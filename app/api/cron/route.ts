import { NextRequest, NextResponse } from "next/server";
import { SEQUENCES } from "@/lib/sequences";
import { createList, getSubscribersByList, updateSubscriberAttribs, sendTransactionalEmail } from "@/lib/listmonk";
import { generateEmail } from "@/lib/claude";

export async function GET(req: NextRequest) {
  const secret = req.headers.get("x-cron-secret");
  if (secret !== process.env.CRON_SECRET) {
    return NextResponse.json({ success: false, error: "Unauthorized" }, { status: 401 });
  }

  let processed = 0;

  for (const sequence of SEQUENCES) {
    const listId = await createList(sequence.listName);
    const subscribers = await getSubscribersByList(listId);

    for (const subscriber of subscribers) {
      const attribs = subscriber.attribs || {};

      if (attribs.sequence_complete === "true" || attribs.sequence_complete === true) continue;

      const currentStep: number = parseInt(attribs.sequence_step || "0", 10);
      const nextStep = currentStep + 1;

      if (nextStep > sequence.steps.length) {
        await updateSubscriberAttribs(subscriber, { sequence_complete: "true" });
        continue;
      }

      const stepConfig = sequence.steps.find((s) => s.position === nextStep);
      if (!stepConfig) continue;

      const lastSentAt = attribs.last_sent_at ? new Date(attribs.last_sent_at) : null;
      if (!lastSentAt) continue;

      const daysSinceLastSent = (Date.now() - lastSentAt.getTime()) / (1000 * 60 * 60 * 24);
      if (daysSinceLastSent < stepConfig.delayDays) continue;

      const previousEmails: string[] = attribs.previous_emails
        ? [attribs.previous_emails]
        : [];

      const generated = await generateEmail({
        sequencePosition: nextStep,
        firstName: subscriber.name,
        brandContext: sequence.brandContext,
        previousEmails,
        targetAction: sequence.targetAction,
      });

      await sendTransactionalEmail(
        subscriber.email,
        generated.subject,
        generated.body,
        sequence.fromEmail,
        sequence.fromName
      );

      await updateSubscriberAttribs(subscriber, {
        sequence_step: String(nextStep),
        last_sent_at: new Date().toISOString(),
        previous_emails: `${attribs.previous_emails || ""}\n\nSubject: ${generated.subject}\n\n${generated.body}`,
        sequence_complete: nextStep >= sequence.steps.length ? "true" : "false",
      });

      processed++;
    }
  }

  return NextResponse.json({ success: true, processed });
}
