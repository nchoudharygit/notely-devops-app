export async function apiFetch(path, options = {}) {
  const token = localStorage.getItem("sessionToken");
  const headers = { ...(options.headers || {}) };
  if (!(options.body instanceof FormData)) {
    headers["Content-Type"] = headers["Content-Type"] || "application/json";
  }
  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }
  const response = await fetch(`/api/v1${path}`, { ...options, headers });
  if (response.status === 204) {
    return null;
  }
  const body = await response.json();
  if (!response.ok) {
    throw new Error(body?.error?.message || "Request failed");
  }
  return body;
}
