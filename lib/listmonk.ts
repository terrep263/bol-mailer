import axios from "axios";

const client = axios.create({
  baseURL: "https://api.brevo.com/v3",
  headers: {
    "Content-Type": "application/json",
    "api-key": process.env.BREVO_API_KEY,
  },
});

export interface Subscriber {
  id?: number;
  email: string;
  name: string;
  attribs: Record<string, any>;
  listIds?: number[];
}

export async function getLists(): Promise<{ id: number; name: string }[]> {
  const res = await client.get("/contacts/lists?limit=50");
  return res.data.lists || [];
}

export async function createList(name: string): Promise<number> {
  const existing = await getLists();
  const found = existing.find((l) => l.name === name);
  if (found) return found.id;
  const res = await client.post("/contacts/lists", { name, folderId: 1 });
  return res.data.id;
}

export async function addSubscriber(
  email: string,
  firstName: string,
  listId: number
): Promise<void> {
  try {
    await client.post("/contacts", {
      email,
      attributes: { FIRSTNAME: firstName },
      listIds: [listId],
      updateEnabled: true,
    });
  } catch (err: any) {
    // 204 or duplicate — ignore
    if (err?.response?.status !== 204) throw err;
  }
}

export async function getSubscriberByEmail(email: string): Promise<Subscriber | null> {
  try {
    const res = await client.get(`/contacts/${encodeURIComponent(email)}`);
    const data = res.data;
    return {
      email: data.email,
      name: data.attributes?.FIRSTNAME || "",
      attribs: data.attributes || {},
      listIds: data.listIds || [],
      id: data.id,
    };
  } catch {
    return null;
  }
}

export async function getSubscribersByList(listId: number): Promise<Subscriber[]> {
  const res = await client.get(`/contacts/lists/${listId}/contacts?limit=500`);
  const contacts = res.data.contacts || [];
  return contacts.map((c: any) => ({
    email: c.email,
    name: c.attributes?.FIRSTNAME || "",
    attribs: c.attributes || {},
    listIds: c.listIds || [],
    id: c.id,
  }));
}

export async function updateSubscriberAttribs(
  subscriber: Subscriber,
  newAttribs: Record<string, any>
): Promise<void> {
  const merged = { ...(subscriber.attribs || {}), ...newAttribs };
  await client.put(`/contacts/${encodeURIComponent(subscriber.email)}`, {
    attributes: merged,
  });
}

export async function sendTransactionalEmail(
  email: string,
  subject: string,
  body: string,
  fromEmail: string,
  fromName: string
): Promise<void> {
  await client.post("/smtp/email", {
    sender: { email: fromEmail, name: fromName },
    to: [{ email }],
    subject,
    textContent: body,
    htmlContent: `<div style="font-family:Georgia,serif;max-width:600px;margin:0 auto;padding:24px;color:#222;">${body.replace(/\n/g, "<br>")}</div>`,
  });
}
