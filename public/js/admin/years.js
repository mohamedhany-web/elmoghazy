const { esc, say, openModal, closeModal, state } = Admin;

async function loadYears() {
  const [grades, months] = await Promise.all([Elmo.api("/api/grades"), Elmo.api("/api/teacher/months")]);
  const host = document.getElementById("stage-list");
  host.innerHTML = grades.length
    ? `<table class="cs-table"><thead><tr><th>السنة</th><th>الشهور</th><th>الحالة</th><th></th></tr></thead><tbody>${grades
        .map((grade) => {
          const mine = months.filter((month) => month.grade_id === grade.id);
          return `<tr data-status="${mine.length ? "filled" : "empty"}" data-search="${esc(grade.title)} ${esc(grade.subtitle || "")}">
            <td>${esc(grade.title)}</td>
            <td>${mine.length}</td>
            <td>${state(mine.length > 0, "فيها شهور", "فاضية")}</td>
            <td class="cs-row-acts">
              <a class="cs-btn cs-btn--primary" href="/admin/months?year=${grade.id}">التفاصيل</a>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="stage-edit" data-stage="${grade.id}" data-title="${esc(grade.title)}" data-subtitle="${esc(grade.subtitle || "")}">تعديل</button>
              <button type="button" class="cs-btn cs-btn--danger" data-act="stage-delete" data-stage="${grade.id}">حذف</button>
            </td>
          </tr>`;
        })
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش سنوات.</p></div>`;
  Admin.applyFilters();
}

document.getElementById("stage-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const payload = { title: data.get("title"), subtitle: data.get("subtitle") || null };
  const id = data.get("id");
  try {
    await Elmo.api(id ? `/api/teacher/grades/${id}` : "/api/teacher/grades", {
      method: id ? "PATCH" : "POST",
      body: JSON.stringify(payload),
    });
    form.reset();
    closeModal(form.closest(".cs-modal"));
    say(id ? "اتعدلت السنة." : "اتضافت السنة.", true);
    await loadYears();
  } catch (err) {
    say(err.message);
    openModal("modal-stage");
  }
});

document.getElementById("desk").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  if (button.dataset.act === "stage-new") {
    const form = document.getElementById("stage-form");
    form.reset();
    form.querySelector('[name="id"]').value = "";
    document.getElementById("stage-form-title").textContent = "سنة دراسية";
    return;
  }
  if (button.dataset.act === "stage-edit") {
    const form = document.getElementById("stage-form");
    form.querySelector('[name="id"]').value = button.dataset.stage || "";
    form.querySelector('[name="title"]').value = button.dataset.title || "";
    form.querySelector('[name="subtitle"]').value = button.dataset.subtitle || "";
    document.getElementById("stage-form-title").textContent = "تعديل السنة";
    openModal("modal-stage");
    return;
  }
  if (button.dataset.act !== "stage-delete") return;
  try {
    await Elmo.api(`/api/teacher/grades/${button.dataset.stage}`, { method: "DELETE" });
    say("تم.", true);
    await loadYears();
  } catch (err) {
    say(err.message);
  }
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  await loadYears();
});
