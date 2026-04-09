import { apiFetch } from "./api.js";

export async function renderTagsPicker(noteId) {
  const holder = document.getElementById("tag-picker");
  if (!holder) return;
  const tags = await apiFetch("/tags");
  holder.innerHTML = `
    <h4>Tags</h4>
    <form id="new-tag-form">
      <input name="name" placeholder="New tag" />
      <button type="submit">Create Tag</button>
    </form>
    <ul>${(tags?.data || []).map((t) => `<li>${t.name}</li>`).join("")}</ul>
  `;
  document.getElementById("new-tag-form")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    await apiFetch("/tags", { method: "POST", body: JSON.stringify(Object.fromEntries(fd)) });
    renderTagsPicker(noteId);
  });
}
