const { esc, when, say, state } = Admin;

function activationTable(rows) {
  return rows.length
    ? `<table class="cs-table"><thead><tr><th>الطالب</th><th>الكورس</th><th>حتى</th><th>الحالة</th><th></th></tr></thead><tbody>${rows
        .map((row) => `<tr data-id="${row.id}" data-status="${row.status === "active" ? "active" : "locked"}" data-search="${esc(row.student)} ${esc(row.course)}">
            <td>${esc(row.student)}</td>
            <td>${esc(row.course || "—")}</td>
            <td>${esc(when(row.ends_at))}</td>
            <td>${state(row.status === "active", "ساري", "مقفول")}</td>
            <td><form class="cs-row-acts" data-extend><input class="cs-input" type="number" name="duration_days" min="1" max="366" value="30" aria-label="أيام"><button type="submit" class="cs-btn cs-btn--ghost">مدّ</button></form></td>
          </tr>`)
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش تفعيلات.</p></div>`;
}

async function loadSummary() {
  const summary = await Elmo.api("/api/teacher/summary");
  const cards = [
    ["طلبة مفعّلون", summary.active_students, "#dash-activations"],
    ["تفعيلات مقفولة", summary.locked_students, "#dash-activations"],
    ["أكواد مستخدمة", summary.codes_used, "/admin/codes"],
    ["أكواد متبقية", summary.codes_left, "/admin/codes"],
  ];
  document.getElementById("summary-stats").innerHTML = cards
    .map(
      ([title, value, href]) => `<a class="cs-stat" href="${href}"><span>${esc(title)}</span><strong>${esc(value)}</strong></a>`,
    )
    .join("");
}

async function loadActivations() {
  const rows = await Elmo.api("/api/teacher/activations");
  document.getElementById("dash-activations").innerHTML = activationTable(rows);
  Admin.applyFilters();
}

document.getElementById("desk").addEventListener("submit", async (event) => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement) || !form.dataset.extend) return;
  event.preventDefault();
  const row = form.closest("[data-id]");
  const data = new FormData(form);
  try {
    await Elmo.api(`/api/teacher/activations/${row.dataset.id}/extend`, {
      method: "POST",
      body: JSON.stringify({ duration_days: Number(data.get("duration_days")) }),
    });
    say("اتمدت المدة.", true);
    await Promise.all([loadSummary(), loadActivations()]);
  } catch (err) {
    say(err.message);
  }
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  await Promise.all([loadSummary(), loadActivations()]);
});
