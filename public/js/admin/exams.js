const { esc, say, openModal, closeModal, state } = Admin;

function toggleClose() {
  const permanent = document.querySelector('#exam-form [name="permanent"]').checked;
  document.getElementById("exam-close-field").hidden = permanent;
}

async function loadExams() {
  const [rows, grades] = await Promise.all([Elmo.api("/api/teacher/exams"), Elmo.api("/api/grades")]);
  const year = document.getElementById("exam-year");
  const grade = document.getElementById("exam-grade");
  const options = grades.map((item) => `<option value="${item.id}">${esc(item.title)}</option>`).join("");
  year.innerHTML = `<option value="">كل السنوات</option>${grades.map((item) => `<option value="${esc(item.title)}">${esc(item.title)}</option>`).join("")}`;
  grade.innerHTML = options;
  const host = document.getElementById("exam-list");
  host.innerHTML = rows.length
    ? `<table class="cs-table"><thead><tr><th>الامتحان</th><th>السنة</th><th>أسئلة</th><th>الحالة</th><th></th></tr></thead><tbody>${rows
        .map((row) => `<tr data-search="${esc(row.title)} ${esc(row.grade || "")}" data-year="${esc(row.grade || "")}" data-kind="${row.permanent ? "permanent" : "timed"}" data-status="${row.published ? "published" : "hidden"}">
            <td>${esc(row.title)}</td>
            <td>${esc(row.grade || "—")}</td>
            <td>${esc(row.question_count)}</td>
            <td>${state(!!row.published, "منشور", "مخفي")}</td>
            <td class="cs-row-acts">
              <a class="cs-btn cs-btn--primary" href="/admin/exams/${row.id}">التفاصيل</a>
              <button type="button" class="cs-btn cs-btn--danger" data-act="exam-delete" data-exam="${row.id}">حذف</button>
            </td>
          </tr>`)
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش امتحانات. أنشئ امتحان واختار أسئلته من بنك السنة.</p></div>`;
  Admin.applyFilters();
}

document.querySelector('#exam-form [name="permanent"]').addEventListener("change", toggleClose);

document.getElementById("exam-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const permanent = data.get("permanent") === "on";
  const payload = {
    title: data.get("title"),
    grade_id: Number(data.get("grade_id")),
    instructions: data.get("instructions") || null,
    permanent,
    duration_minutes: data.get("duration_minutes") ? Number(data.get("duration_minutes")) : null,
    pass_percent: data.get("pass_percent") ? Number(data.get("pass_percent")) : null,
    closes_at: permanent ? null : data.get("closes_at") || null,
  };
  try {
    const created = await Elmo.api("/api/teacher/exams", { method: "POST", body: JSON.stringify(payload) });
    window.location.href = `/admin/exams/${created.id}`;
  } catch (err) {
    say(err.message);
    openModal("modal-exam");
  }
});

document.getElementById("desk").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  if (button.dataset.act === "exam-new") {
    document.getElementById("exam-form").reset();
    document.querySelector('#exam-form [name="permanent"]').checked = true;
    toggleClose();
    return;
  }
  if (button.dataset.act !== "exam-delete") return;
  try {
    await Elmo.api(`/api/teacher/exams/${button.dataset.exam}`, { method: "DELETE" });
    say("تم.", true);
    await loadExams();
  } catch (err) {
    say(err.message);
  }
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  try {
    await loadExams();
  } catch (err) {
    say(err.message);
  }
});
