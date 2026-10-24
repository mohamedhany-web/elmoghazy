const { esc, say, state } = Admin;

async function loadRequests() {
  const rows = await Elmo.api("/api/teacher/requests");
  const host = document.getElementById("request-list");
  host.innerHTML = rows.length
    ? `<table class="cs-table"><thead><tr><th>الاسم</th><th>الموبايل</th><th>الشهر</th><th>الحالة</th><th></th></tr></thead><tbody>${rows
        .map((row) => `<tr data-search="${esc(row.name)} ${esc(row.phone)} ${esc(row.month)}" data-status="${esc(row.status)}">
            <td>${esc(row.name)}</td>
            <td>${esc(row.phone || "—")}</td>
            <td>${esc(row.month || "—")}</td>
            <td>${state(row.status !== "done", "جديد", "تم")}</td>
            <td class="cs-row-acts">
              ${row.status === "done" ? "" : `<button type="button" class="cs-btn cs-btn--ghost" data-act="request-close" data-id="${row.id}">تم التعامل</button>`}
              <button type="button" class="cs-btn cs-btn--danger" data-act="request-delete" data-id="${row.id}">حذف</button>
            </td>
          </tr>`)
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش طلبات شراء.</p></div>`;
  Admin.applyFilters();
}

document.getElementById("desk").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  try {
    if (button.dataset.act === "request-close") {
      await Elmo.api(`/api/teacher/requests/${button.dataset.id}/close`, { method: "POST", body: "{}" });
      say("اتسجل إن الطلب خلص.", true);
    } else if (button.dataset.act === "request-delete") {
      await Elmo.api(`/api/teacher/requests/${button.dataset.id}`, { method: "DELETE" });
      say("اتمسح الطلب.", true);
    } else {
      return;
    }
    await loadRequests();
  } catch (err) {
    say(err.message);
  }
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  await loadRequests();
});
