import axios from "axios";

const client = axios.create({
  baseURL: process.env.LISTMONK_URL,
  headers: {
    "Content-Type": "application/json",
    Authorization: `token ${process.env.LISTMONK_USER}:${process.env.LISTMONK_PASSWORD}`,
  },
});

export interface Subscriber {
  id: number;
  email: string;
  name: string;
  attribs: Record<string, any>;
  created_at: string;
}

export async function getLists(): Promise<{ id: number; name: string }[]> {
  const res = await client.get("/api/lists?per_page=100");
  return res.data.data.results || [];
}

export async function createList(name: string, description: string): Promise<number> {
  const existing = await getLists();
  const found = existing.find((l) => l.name === name);
  if (found) return found.id;
  const res = await client.post("/api/lists", {
    name,
    description,
    type: "private",
    optin: "single",
    tags: [],
  });
  return res.data.data.id;
}

export async function addSubscriber(
  email: string,
  firstName: string,
  listId: number
): Promise<void> {
  await client.post("/api/subscribers", {
    email,
    name: firstName,
    lists: [listId],
    status: "enabled",
    preconfirm_subscriptions: true,
    attribs: {},
  });
}

export async function getSubscribersByList(listId: number): Promise<Subscriber[]> {
  const res = await client.get(`/api/subscribers?list_id=${listId}&per_page=1000`);
  return res.data.data.results || [];
}

export async function getSubscriberByEmail(email: string): Promise<Subscriber | null> {
  try {
    const res = await client.get(`/api/subscribers?query=email='${email}'&per_page=1`);
    const results = res.data.data.results || [];
    return results[0] || null;
  } catch {
    return null;
  }
}

export async function updateSubscriberAttribs(
  subscriberId: number,
  attribs: Record<string, any>
): Promise<void> {
  const res = await client.get(`/api/subscribers?id=${subscriberId}&per_page=1`);
  const existing = res.data.data.results?.[0];
  const merged = { ...(existing?.attribs || {}), ...attribs };
  await client.put(`/api/subscribers/${subscriberId}`, {
    email: existing.email,
    name: existing.name,
    lists: existing.lists?.map((l: any) => l.id) || [],
    status: existing.status,
    attribs: merged,
  });
}

export async function sendTransactionalEmail(
  email: string,
  subject: string,
  body: string,
  fromEmail: string,
  fromName: string
): Promise<void> {
  await client.post("/api/tx", {
    subscriber_email: email,
    template_id: 1,
    data: {
      subject,
      body,
      from_email: fromEmail,
      from_name: fromName,
    },
  });
}
