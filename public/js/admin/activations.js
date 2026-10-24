const { esc, when, say, openModal, closeModal, state } = Admin;
let page = 1;
let lastPage = 1;
let searchTimer = 0;
let pickTimer = 0;

function sourceLabel(source) {
  return source === "manual" ? "يدوي" : "كود";
}

function activationTable(rows) {
  return rows.length
    ? `<table class="cs-table"><thead><tr><th>الطالب</th><th>الصف</th><th>النطاق</th><th>المصدر</th><th>حتى</th><th>الحالة</th><th></th></tr></thead><tbody>${rows
        .map((row) => `<tr data-id="${row.id}">
            <td>${row.student_id ? `<a href="/admin/students/${row.student_id}">${esc(row.student || "—")}</a>` : esc(row.student || "—")}</td>
            <td>${esc(row.grade || "—")}</td>
            <td>${esc(row.course || "—")}</td>
            <td>${esc(sourceLabel(row.source))}</td>
            <td>${esc(when(row.ends_at))}</td>
            <td>${state(row.status === "active", "ساري", "مقفول")}</td>
            <td><form class="cs-row-acts" data-extend><input class="cs-input" type="number" name="duration_days" min="1" max="366" value="30" aria-label="أيام"><button type="submit" class="cs-btn cs-btn--ghost">مدّ</button></form></td>
          </tr>`)
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>${document.getElementById("activation-search")?.value.trim() || document.getElementById("activation-status")?.value ? "مفيش تفعيلات مطابقة." : "لسه مفيش تفعيلات."}</p></div>`;
}

async function loadActivations(nextPage = page) {
  page = Math.max(1, nextPage);
  const params = new URLSearchParams({ page: String(page) });
  const q = document.getElementById("activation-search")?.value.trim();
  const status = document.getElementById("activation-status")?.value;
  if (q) params.set("q", q);
  if (status) params.set("status", status);
  const result = await Elmo.api(`/api/teacher/activations?${params}`);
  const rows = result.data || [];
  lastPage = result.last_page || 1;
  page = result.current_page || page;
  document.getElementById("activation-list").innerHTML = activationTable(rows);
  const pager = document.getElementById("activation-pager");
  pager.hidden = (result.total || 0) <= 25;
  document.getElementById("activation-pager-label").textContent = `${page} / ${lastPage} · ${result.total || 0} تفعيل`;
  pager.querySelector('[data-activation-page="prev"]').disabled = page <= 1;
  pager.querySelector('[data-activation-page="next"]').disabled = page >= lastPage;
}

async function fillCourses() {
  const courses = await Elmo.api("/api/teacher/courses");
  document.querySelector('#activate-form [name="course_id"]').innerHTML = `<option value="">كل كورسات الصف</option>${courses
    .map((course) => `<option value="${course.id}">${esc(course.title)}</option>`)
    .join("")}`;
}

document.querySelector('[data-open-modal="modal-activate"]')?.addEventListener("click", () => {
  const form = document.getElementById("activate-form");
  form.reset();
  form.querySelector('[name="student_id"]').value = "";
  form.querySelector('[name="duration_days"]').value = "30";
  document.getElementById("activate-student-name").textContent = "";
  document.getElementById("activate-student-picks").innerHTML = "";
});

document.getElementById("activate-student-search").addEventListener("input", () => {
  clearTimeout(pickTimer);
  pickTimer = setTimeout(async () => {
    const q = document.getElementById("activate-student-search").value.trim();
    const host = document.getElementById("activate-student-picks");
    if (!q) {
      host.innerHTML = "";
      return;
    }
    const result = await Elmo.api(`/api/teacher/students?q=${encodeURIComponent(q)}&page=1`);
    const rows = result.data || [];
    host.innerHTML = rows.length
      ? `<div class="cs-picks">${rows
          .map((student) => `<button type="button" class="cs-btn cs-btn--ghost" data-pick-student="${student.id}" data-pick-name="${esc(student.name)}">${esc(student.name)}</button>`)
          .join("")}</div>`
      : `<p class="outline-empty">مفيش طالب بالاسم ده.</p>`;
  }, 250);
});

document.getElementById("activate-student-picks").addEventListener("click", (event) => {
  const button = event.target.closest("[data-pick-student]");
  if (!button) return;
  document.querySelector('#activate-form [name="student_id"]').value = button.dataset.pickStudent;
  document.getElementById("activate-student-name").textContent = button.dataset.pickName;
  document.getElementById("activate-student-picks").innerHTML = "";
  document.getElementById("activate-student-search").value = button.dataset.pickName;
});

document.getElementById("activate-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const studentId = data.get("student_id");
  if (!studentId) {
    say("اختار الطالب الأول.");
    openModal("modal-activate");
    return;
  }
  const payload = { duration_days: Number(data.get("duration_days")) };
  if (data.get("course_id")) payload.course_id = Number(data.get("course_id"));
  try {
    await Elmo.api(`/api/teacher/students/${studentId}/activate`, {
      method: "POST",
      body: JSON.stringify(payload),
    });
    closeModal(form.closest(".cs-modal"));
    say("التفعيل اشتغل.", true);
    await loadActivations(1);
  } catch (err) {
    say(err.message);
    openModal("modal-activate");
  }
});

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
    await loadActivations();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("activation-search").addEventListener("input", () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadActivations(1).catch((err) => say(err.message)), 250);
});
document.getElementById("activation-status").addEventListener("change", () => loadActivations(1).catch((err) => say(err.message)));
document.getElementById("activation-pager").addEventListener("click", (event) => {
  const button = event.target.closest("[data-activation-page]");
  if (!button || button.disabled) return;
  const next = button.dataset.activationPage === "prev" ? page - 1 : page + 1;
  loadActivations(next).catch((err) => say(err.message));
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  await Promise.all([fillCourses(), loadActivations(1)]);
});
