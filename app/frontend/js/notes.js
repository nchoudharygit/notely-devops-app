import { apiFetch } from "./api.js";
import { renderTagsPicker } from "./tags.js";
import { renderAttachments } from "./attachments.js";
const app = document.getElementById("app");
export async function renderNotes() {
  const result = await apiFetch("/notes");
  const notes = result?.data || [];
  app.innerHTML = `
    <section>
      <div class="note-form">
        <h2>New Note</h2>
        <form id="new-note-form">
          <input name="title" placeholder="Title" required />
          <textarea name="body" placeholder="Body"></textarea>
          <button type="submit" id="create-btn">Create</button>
        </form>
      </div>
      <div class="note-list">
        ${notes.length === 0
          ? `<div class="empty-state"><p>No notes yet — create one above! 📝</p></div>`
          : notes.map((n) => `
          <div class="note-item" data-id="${n.id}">
            <div class="note-title">${n.title}</div>
            <div class="note-body">${n.body ?? ""}</div>
            <div class="note-actions">
              <button class="btn-open" data-open="${n.id}">Open</button>
              <button class="btn-delete" data-del="${n.id}">Delete</button>
            </div>
          </div>`).join("")}
      </div>
      <div id="note-detail"></div>
    </section>
  `;
  document.getElementById("new-note-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    await apiFetch("/notes", { method: "POST", body: JSON.stringify(Object.fromEntries(fd)) });
    renderNotes();
  });
  document.querySelectorAll("[data-del]").forEach((btn) => btn.addEventListener("click", async () => {
    await apiFetch(`/notes/${btn.getAttribute("data-del")}`, { method: "DELETE" });
    renderNotes();
  }));
  document.querySelectorAll("[data-open]").forEach((btn) => btn.addEventListener("click", async () => {
    const id = btn.getAttribute("data-open");
    const note = await apiFetch(`/notes/${id}`);
    document.getElementById("note-detail").innerHTML = `
      <div class="note-form" style="margin-top:24px">
        <h2>Edit Note</h2>
        <form id="edit-note-form">
          <input name="title" value="${note.title}" />
          <textarea name="body">${note.body ?? ""}</textarea>
          <button type="submit" id="save-btn">Save</button>
        </form>
        <div id="tag-picker"></div>
        <div id="attachments"></div>
      </div>
    `;
    document.getElementById("edit-note-form").addEventListener("submit", async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      await apiFetch(`/notes/${id}`, { method: "PATCH", body: JSON.stringify(Object.fromEntries(fd)) });
      renderNotes();
    });
    renderTagsPicker(id);
    renderAttachments(id);
  }));
}