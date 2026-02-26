import { request } from "@/services/api";

export async function getTasks(params = { page: 1, per_page: 12 }) {
  const query = new URLSearchParams(params).toString();
  const payload = await request(`/tasks?${query}`);

  return {
    data: payload.data || [],
    pagination: payload.pagination || null,
  };
}

export async function updateTask(taskId, payload) {
  const response = await request(`/tasks/${taskId}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });

  return response.data;
}

export async function createTask(payload) {
  const response = await request(`/tasks`, {
    method: "POST",
    body: JSON.stringify(payload),
  });

  return response.data;
}

export async function deleteTask(taskId) {
  await request(`/tasks/${taskId}`, {
    method: "DELETE",
  });
}

export async function followTask(taskId) {
  await request(`/tasks/${taskId}/follow`, {
    method: "POST",
  });
}

export async function unfollowTask(taskId) {
  await request(`/tasks/${taskId}/follow`, {
    method: "DELETE",
  });
}

export async function getTaskComments(taskId, params = { page: 1, per_page: 10 }) {
  const query = new URLSearchParams(params).toString();
  const payload = await request(`/tasks/${taskId}/comments?${query}`);

  return {
    data: payload.data || [],
    pagination: payload.pagination || null,
  };
}

export async function addTaskComment(taskId, body, parentCommentId = null) {
  const payload = await request(`/tasks/${taskId}/comments`, {
    method: "POST",
    body: JSON.stringify({
      body,
      ...(parentCommentId ? { parent_comment_id: parentCommentId } : {}),
    }),
  });

  return payload.data;
}

export async function getUnreadTaskMessages() {
  const payload = await request("/tasks/messages/unread", {
    method: "GET",
  });

  return payload.data || { total_unread: 0, tasks: [] };
}

export async function markTaskMessagesRead(taskId) {
  const payload = await request(`/tasks/${taskId}/messages/read`, {
    method: "POST",
  });

  return payload.data || { marked: 0 };
}
