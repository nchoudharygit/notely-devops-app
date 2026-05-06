import { apiFetch } from "./api.js";
import { renderNotes } from "./notes.js";

const app = document.getElementById("app");
const nav = document.getElementById("nav");

function showAuth() {
  nav.classList.add("hidden");
  app.innerHTML = `
    <section class="card">
      <h1>Notely</h1>
      <form id="login-form">
        <input name="email" type="email" placeholder="Email" required />
        <input name="password" type="password" placeholder="Password" minlength="8" required />
        <button type="submit">Login</button>
      </form>
      <form id="register-form">
        <input name="email" type="email" placeholder="Email" required />
        <input name="password" type="password" placeholder="Password" minlength="8" required />
        <button type="submit">Register</button>
      </form>
      <p id="message"></p>
    </section>
  `;
  const msg = document.getElementById("message");
  document.getElementById("login-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      const data = await apiFetch("/auth/login", {
        method: "POST",
        body: JSON.stringify(Object.fromEntries(fd)),
      });
      localStorage.setItem("sessionToken", data.token);
      renderApp();
    } catch (err) {
      msg.textContent = err.message;
    }
  });
  document.getElementById("register-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await apiFetch("/auth/register", {
        method: "POST",
        body: JSON.stringify(Object.fromEntries(fd)),
      });
      msg.textContent = "Registration successful. You can login now.";
    } catch (err) {
      msg.textContent = err.message;
    }
  });
}

export function renderApp() {
  const token = localStorage.getItem("sessionToken");
  if (!token) {
    showAuth();
    return;
  }
  nav.classList.remove("hidden");
  renderNotes();
}

document.getElementById("nav-logout").addEventListener("click", async () => {
  try {
    await apiFetch("/auth/logout", { method: "POST" });
  } catch (_) {}
  localStorage.removeItem("sessionToken");
  renderApp();
});

renderApp();
