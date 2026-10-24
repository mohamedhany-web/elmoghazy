const { esc, state } = Admin;

async function loadBanks() {
  const rows = await Elmo.api("/api/teacher/banks");
  const host = document.getElementById("bank-list");
  host.innerHTML = rows.length
    ? `<table class="cs-table"><thead><tr><th>السنة</th><th>وحدات</th><th>دروس</th><th>أسئلة</th><th>الحالة</th><th></th></tr></thead><tbody>${rows
        .map((row) => `<tr data-status="${row.questions ? "filled" : "empty"}" data-search="${esc(row.title)} ${esc(row.subtitle || "")}">
            <td>${esc(row.title)}</td>
            <td>${esc(row.units)}</td>
            <td>${esc(row.lessons || 0)}</td>
            <td>${esc(row.questions)}</td>
            <td>${state(row.questions > 0, "فيه أسئلة", "فاضي")}</td>
            <td class="cs-row-acts"><a class="cs-btn cs-btn--primary" href="/admin/banks/${row.id}/units">التفاصيل</a></td>
          </tr>`)
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش سنوات دراسية.</p></div>`;
  Admin.applyFilters();
}

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  try {
    await loadBanks();
  } catch (err) {
    Admin.say(err.message);
  }
});
