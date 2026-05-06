import { apiFetch } from "./api.js";

export async function renderAttachments(noteId) {
  const holder = document.getElementById("attachments");
  if (!holder) return;
  const list = await apiFetch(`/notes/${noteId}/attachments`);
  holder.innerHTML = `
    <h4>Attachments</h4>
    <form id="upload-form">
      <input name="file" type="file" accept="image/jpeg,image/png,image/gif,application/pdf" />
      <button type="submit">Upload</button>
    </form>
    <ul>
      ${(list?.data || []).map((a) => `<li>${a.filename} <button data-download="${a.id}">Download</button> <button data-delete="${a.id}">Delete</button></li>`).join("")}
    </ul>
  `;
  document.getElementById("upload-form")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const file = e.target.querySelector('input[name="file"]').files[0];
    if (!file) return;
    const body = new FormData();
    body.set("file", file);
    await apiFetch(`/notes/${noteId}/attachments`, { method: "POST", body });
    renderAttachments(noteId);
  });
  holder.querySelectorAll("[data-download]").forEach((btn) => btn.addEventListener("click", async () => {
    const id = btn.getAttribute("data-download");
    const data = await apiFetch(`/notes/${noteId}/attachments/${id}/download`);
    window.open(data.url, "_blank");
  }));
  holder.querySelectorAll("[data-delete]").forEach((btn) => btn.addEventListener("click", async () => {
    const id = btn.getAttribute("data-delete");
    await apiFetch(`/notes/${noteId}/attachments/${id}`, { method: "DELETE" });
    renderAttachments(noteId);
  }));
}
