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
  lists?: any[];
  status?: string;
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
): Promise<Subscriber> {
  const res = await client.post("/api/subscribers", {
    email,
    name: firstName,
    lists: [listId],
    status: "enabled",
    preconfirm_subscriptions: true,
    attribs: {},
  });
  return res.data.data;
}

export async function getSubscribersByList(listId: number): Promise<Subscriber[]> {
  const res = await client.get(`/api/subscribers?list_id=${listId}&per_page=1000`);
  return res.data.data.results || [];
}

export async function getSubscriberByEmail(email: string): Promise<Subscriber | null> {
  try {
    const res = await client.get(`/api/subscribers?query=subscribers.email+%3D+%27${encodeURIComponent(email)}%27&per_page=1`);
    const results = res.data.data.results || [];
    return results[0] || null;
  } catch {
    return null;
  }
}

export async function updateSubscriberAttribs(
  subscriber: Subscriber,
  newAttribs: Record<string, any>
): Promise<void> {
  const merged = { ...(subscriber.attribs || {}), ...newAttribs };
  await client.put(`/api/subscribers/${subscriber.id}`, {
    email: subscriber.email,
    name: subscriber.name,
    lists: subscriber.lists?.map((l: any) => l.id) || [],
    status: subscriber.status || "enabled",
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
