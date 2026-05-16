import { NextRequest, NextResponse } from "next/server";
import { SEQUENCES } from "@/lib/sequences";
import { createList } from "@/lib/listmonk";

export async function POST(req: NextRequest) {
  const created: { name: string; id: number }[] = [];

  for (const sequence of SEQUENCES) {
    const id = await createList(sequence.listName, sequence.name);
    created.push({ name: sequence.listName, id });
  }

  return NextResponse.json({ success: true, lists: created });
}
