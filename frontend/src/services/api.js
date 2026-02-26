const API_BASE_URL = process.env.VUE_APP_API_BASE_URL || "/api/v1";
const TOKEN_KEY = "taskflow_access_token";

async function request(path, options = {}) {
  const token = localStorage.getItem(TOKEN_KEY);
  const method = (options.method || "GET").toUpperCase();
  const requestUrl = `${API_BASE_URL}${path}`;
  // Passport gives us an access token after login/register.
  // We send it on every API call as a Bearer token.
  const headers = {
    "Content-Type": "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(options.headers || {}),
  };

  const finalUrl = method === "GET"
    ? `${requestUrl}${requestUrl.includes("?") ? "&" : "?"}_ts=${Date.now()}`
    : requestUrl;

  const payload = await new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open(method, finalUrl, true);

    Object.entries(headers).forEach(([key, value]) => {
      xhr.setRequestHeader(key, value);
    });

    xhr.onreadystatechange = () => {
      if (xhr.readyState !== XMLHttpRequest.DONE) {
        return;
      }

      let responsePayload = {};
      try {
        responsePayload = xhr.responseText ? JSON.parse(xhr.responseText) : {};
      } catch (error) {
        responsePayload = {};
      }

      if (xhr.status < 200 || xhr.status >= 300 || responsePayload.success === false) {
        reject(new Error(responsePayload.message || "Request failed"));
        return;
      }

      resolve(responsePayload);
    };

    xhr.onerror = () => {
      reject(new Error("Network error"));
    };

    xhr.send(options.body ?? null);
  });

  return payload;
}

export { request, API_BASE_URL };