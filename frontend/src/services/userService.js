import { request } from "@/services/api";

function normalizeUsers(payload) {
  const candidates = [
    payload,
    payload?.data,
    payload?.data?.data,
    payload?.users,
    payload?.data?.users,
  ];

  for (const candidate of candidates) {
    if (Array.isArray(candidate)) {
      return candidate;
    }

    if (candidate && typeof candidate === "object") {
      const values = Object.values(candidate);
      if (values.length && values.every((entry) => entry && typeof entry === "object")) {
        return values;
      }
    }
  }

  return [];
}

export async function getUsers() {
  const payload = await request("/users");
  return normalizeUsers(payload);
}
