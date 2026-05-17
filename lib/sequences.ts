export interface SequenceStep {
  position: number;
  delayDays: number;
  description: string;
}

export interface Sequence {
  id: string;
  name: string;
  listName: string;
  brandContext: string;
  fromEmail: string;
  fromName: string;
  targetAction: string;
  steps: SequenceStep[];
}

export const SEQUENCES: Sequence[] = [
  {
    id: "bol-faith-prelaunch",
    name: "Book of Lies: Faith — Pre-Launch",
    listName: "BOL Faith Pre-Launch",
    brandContext:
      "88% of Americans don't trust their institutions. 68% don't trust organized religion. Have you ever wondered why? The Book of Lies series has the answers. The Book of Lies: Faith is the first book in the series, targeting people who feel lied to by the church. The author challenges false religious narratives with compassion and truth. Chapter 1 is available as a free download at thebookoflies.online/chapter-one. The book launches in 30 days. The author signs all emails as 'the AMerican'. Always weave the series hook naturally into the email copy.",
    fromEmail: "theamerican@thebookoflies.shop",
    fromName: "the AMerican",
    targetAction: "Join the waitlist at thebookoflies.online/waitlist",
    steps: [
      { position: 1, delayDays: 0, description: "Welcome + Chapter 1 delivery" },
      { position: 2, delayDays: 3, description: "Validation — you were not wrong to question" },
      { position: 3, delayDays: 7, description: "Name the lies — tease the book" },
      { position: 4, delayDays: 10, description: "Soft pitch — join the waitlist" },
      { position: 5, delayDays: 14, description: "Final urgency — launch is coming" },
    ],
  },
];
