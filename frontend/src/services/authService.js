import { request } from "@/services/api";

const TOKEN_KEY = "taskflow_access_token";
const USER_KEY = "taskflow_user";

function setAuthSession(accessToken, user) {
  localStorage.setItem(TOKEN_KEY, accessToken);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

function clearAuthSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

function getStoredUser() {
  const raw = localStorage.getItem(USER_KEY);

  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw);
  } catch (error) {
    return null;
  }
}

function isAuthenticated() {
  return Boolean(localStorage.getItem(TOKEN_KEY));
}

async function register(payload) {
  const response = await request("/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
  });

  const { access_token: accessToken, user } = response.data;
  setAuthSession(accessToken, user);
  return user;
}

async function login(payload) {
  const response = await request("/auth/login", {
    method: "POST",
    body: JSON.stringify(payload),
  });

  const { access_token: accessToken, user } = response.data;
  setAuthSession(accessToken, user);
  return user;
}

async function fetchCurrentUser() {
  const response = await request("/auth/me", {
    method: "GET",
  });

  const user = response.data;
  localStorage.setItem(USER_KEY, JSON.stringify(user));
  return user;
}

async function logout() {
  try {
    await request("/auth/logout", {
      method: "POST",
    });
  } finally {
    clearAuthSession();
  }
}

export {
  TOKEN_KEY,
  USER_KEY,
  register,
  login,
  logout,
  isAuthenticated,
  getStoredUser,
  fetchCurrentUser,
  clearAuthSession,
};
