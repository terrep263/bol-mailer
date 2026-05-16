import Anthropic from "@anthropic-ai/sdk";

const anthropic = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY });

export interface GenerateEmailParams {
  sequencePosition: number;
  firstName: string;
  brandContext: string;
  previousEmails: string[];
  targetAction: string;
}

export async function generateEmail(
  params: GenerateEmailParams
): Promise<{ subject: string; body: string }> {
  const { sequencePosition, firstName, brandContext, previousEmails, targetAction } = params;

  const userPrompt = `
You are writing email #${sequencePosition} in a sequence for ${firstName}.

Brand context: ${brandContext}

Target action you want the reader to take: ${targetAction}

${
  previousEmails.length > 0
    ? `Previous emails already sent (do not repeat content from these):
${previousEmails.map((e, i) => `Email ${i + 1}: ${e}`).join("\n\n")}`
    : ""
}

Write email #${sequencePosition} now. Use ${firstName} as the first name. Make it feel personal, warm, and genuine. Never pushy. The email should naturally lead toward: ${targetAction}.

Return ONLY a JSON object with exactly two fields: "subject" and "body". No markdown. No explanation. No preamble.
`;

  const message = await anthropic.messages.create({
    model: "claude-sonnet-4-20250514",
    max_tokens: 1024,
    system:
      "You are an expert email copywriter for The Book of Lies brand. This is a book series targeting people who feel lied to by the church. Your tone is friendly, conversational, validating, and truth-telling. Never preachy. Never salesy. Write like a trusted friend sharing something important. Always write in plain text — no markdown, no HTML tags. Return ONLY valid JSON with two fields: subject and body. No preamble, no explanation.",
    messages: [{ role: "user", content: userPrompt }],
  });

  const raw = message.content[0].type === "text" ? message.content[0].text : "";
  const clean = raw.replace(/```json|```/g, "").trim();

  try {
    const parsed = JSON.parse(clean);
    if (!parsed.subject || !parsed.body) throw new Error("Missing subject or body");
    return { subject: parsed.subject, body: parsed.body };
  } catch {
    throw new Error(`Claude returned invalid JSON: ${clean}`);
  }
}
