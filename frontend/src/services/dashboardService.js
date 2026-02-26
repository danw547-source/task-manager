import { request } from "@/services/api";

export async function getDashboardSummary(params = { months: 12 }) {
  const query = new URLSearchParams(params).toString();
  const payload = await request(`/dashboard/summary?${query}`);

  return payload.data || null;
}
