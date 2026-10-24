const { esc, say, openModal, closeModal, state } = Admin;
let roster = [];
let page = 1;
let lastPage = 1;
let searchTimer = 0;

async function fillGrades() {
  const grades = await Elmo.api("/api/grades");
  const select = document.querySelector('#student-form [name="grade"]');
  select.innerHTML = grades.map((grade) => `<option value="${esc(grade.slug)}">${esc(grade.title)}</option>`).join("");
  const year = document.getElementById("student-year");
  const current = year.value;
  year.innerHTML = `<option value="">كل السنوات</option>${grades.map((grade) => `<option value="${esc(grade.title)}">${esc(grade.title)}</option>`).join("")}`;
  if (current) year.value = current;
}

async function fillCourses() {
  const courses = await Elmo.api("/api/teacher/courses");
  const select = document.querySelector('#activate-form [name="course_id"]');
  select.innerHTML = `<option value="">كل كورسات الصف</option>${courses
    .map((course) => `<option value="${course.id}">${esc(course.title)}</option>`)
    .join("")}`;
}

async function loadStudents(nextPage = page) {
  page = Math.max(1, nextPage);
  const params = new URLSearchParams({ page: String(page) });
  const q = document.getElementById("student-search")?.value.trim();
  const status = document.getElementById("student-status")?.value;
  const grade = document.getElementById("student-year")?.value;
  if (q) params.set("q", q);
  if (status) params.set("status", status);
  if (grade) params.set("grade", grade);
  const result = await Elmo.api(`/api/teacher/students?${params}`);
  roster = result.data || [];
  lastPage = result.last_page || 1;
  page = result.current_page || page;
  const host = document.getElementById("student-list");
  host.innerHTML = roster.length
    ? `<table class="cs-table"><thead><tr><th>الاسم</th><th>كود الحماية</th><th>التليفون</th><th>ولي الأمر</th><th>الصف</th><th>الحالة</th><th></th></tr></thead><tbody>${roster
        .map(
          (student) => `<tr data-id="${student.id}" data-status="${student.status === "disabled" ? "disabled" : "active"}" data-year="${esc(student.grade || "")}" data-search="${esc(student.name)} ${esc(student.watch_mark)} ${esc(student.phone)} ${esc(student.parent_phone)} ${esc(student.grade)}">
            <td>${esc(student.name)}</td>
            <td><code dir="ltr">${esc(student.watch_mark || "—")}</code></td>
            <td>${esc(student.phone || "—")}</td>
            <td>${esc(student.parent_phone || "—")}</td>
            <td>${esc(student.grade || "—")}</td>
            <td>${state(student.status !== "disabled", "نشط", "متوقف")}</td>
            <td class="cs-row-acts"><a class="cs-btn cs-btn--primary" href="/admin/students/${student.id}">التفاصيل</a></td>
          </tr>`,
        )
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>${q || status || grade ? "مفيش طلبة مطابقين." : "لسه مفيش طلبة."}</p></div>`;
  const pager = document.getElementById("student-pager");
  pager.hidden = (result.total || 0) <= 25;
  document.getElementById("student-pager-label").textContent = `${page} / ${lastPage} · ${result.total || 0} طالب`;
  pager.querySelector('[data-student-page="prev"]').disabled = page <= 1;
  pager.querySelector('[data-student-page="next"]').disabled = page >= lastPage;
}

document.querySelector('[data-open-modal="modal-student"]')?.addEventListener("click", () => {
  const form = document.getElementById("student-form");
  form.reset();
  form.querySelector('[name="id"]').value = "";
  form.querySelector('[name="password"]').required = true;
  document.getElementById("student-form-title").textContent = "طالب جديد";
  document.getElementById("student-form-copy").textContent = "الحساب يتسجل طالبًا نشطًا.";
});

document.getElementById("student-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const id = data.get("id");
  const payload = {
    first_name: data.get("first_name"),
    second_name: data.get("second_name"),
    phone: data.get("phone"),
    parent_phone: data.get("parent_phone"),
    grade: data.get("grade"),
  };
  if (data.get("password")) payload.password = data.get("password");
  try {
    await Elmo.api(id ? `/api/teacher/students/${id}` : "/api/teacher/students", {
      method: id ? "PATCH" : "POST",
      body: JSON.stringify(payload),
    });
    form.reset();
    closeModal(form.closest(".cs-modal"));
    say(id ? "اتحفظ الطالب." : "اتضاف الطالب.", true);
    await loadStudents();
  } catch (err) {
    say(err.message);
    openModal("modal-student");
  }
});

document.getElementById("activate-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const payload = { duration_days: Number(data.get("duration_days")) };
  if (data.get("course_id")) payload.course_id = Number(data.get("course_id"));
  try {
    await Elmo.api(`/api/teacher/students/${data.get("student_id")}/activate`, {
      method: "POST",
      body: JSON.stringify(payload),
    });
    closeModal(form.closest(".cs-modal"));
    say("التفعيل اشتغل.", true);
    await loadStudents();
  } catch (err) {
    say(err.message);
    openModal("modal-activate");
  }
});

document.getElementById("desk").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  const row = button.closest("[data-id]");
  const id = row?.dataset.id;
  if (button.dataset.act === "student-edit") {
    const student = roster.find((item) => String(item.id) === id);
    if (!student) return;
    const form = document.getElementById("student-form");
    form.querySelector('[name="id"]').value = student.id;
    form.querySelector('[name="first_name"]').value = student.first_name || "";
    form.querySelector('[name="second_name"]').value = student.second_name || "";
    form.querySelector('[name="phone"]').value = student.phone || "";
    form.querySelector('[name="parent_phone"]').value = student.parent_phone || "";
    form.querySelector('[name="password"]').value = "";
    form.querySelector('[name="password"]').required = false;
    if (student.grade_slug) form.querySelector('[name="grade"]').value = student.grade_slug;
    document.getElementById("student-form-title").textContent = "تعديل الطالب";
    document.getElementById("student-form-copy").textContent = "سيبها فاضية لو مش هتغيّر كلمة المرور.";
    openModal("modal-student");
    return;
  }
  if (button.dataset.act === "activate") {
    document.querySelector('#activate-form [name="student_id"]').value = id;
    document.getElementById("activate-student-name").textContent = button.dataset.name || "";
    openModal("modal-activate");
    return;
  }
  try {
    if (button.dataset.act === "disable") await Elmo.api(`/api/teacher/students/${id}/disable`, { method: "POST" });
    if (button.dataset.act === "enable") await Elmo.api(`/api/teacher/students/${id}/enable`, { method: "POST" });
    if (button.dataset.act === "lock") await Elmo.api(`/api/teacher/students/${id}/lock`, { method: "POST" });
    if (button.dataset.act === "student-delete") await Elmo.api(`/api/teacher/students/${id}`, { method: "DELETE" });
    if (!["disable", "enable", "lock", "student-delete"].includes(button.dataset.act)) return;
    say("تم.", true);
    await loadStudents();
  } catch (err) {
    say(err.message);
  }
});

function sheetXml(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&apos;",
  }[char]));
}

document.getElementById("student-export").addEventListener("click", async () => {
  const year = document.getElementById("student-year");
  const grade = year.value;
  const label = grade || "كل السنوات";
  const button = document.getElementById("student-export");
  button.disabled = true;
  try {
    const params = new URLSearchParams();
    if (grade) params.set("grade", grade);
    const query = params.toString();
    const rows = await Elmo.api(`/api/teacher/students${query ? `?${query}` : ""}`);
    const list = Array.isArray(rows) ? rows : [];
    if (!list.length) {
      say(grade ? "مفيش طلبة في السنة دي." : "لسه مفيش طلبة.");
      return;
    }
    const table = [
      ["الاسم", "السنة الدراسية", "التليفون", "ولي الأمر", "الحالة"],
      ...list.map((student) => [
        student.name || "—",
        student.grade || "—",
        student.phone || "—",
        student.parent_phone || "—",
        student.status === "disabled" ? "متوقف" : "نشط",
      ]),
    ]
      .map((row) => `<Row>${row.map((cell) => `<Cell><Data ss:Type="String">${sheetXml(cell)}</Data></Cell>`).join("")}</Row>`)
      .join("");
    const book = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<Worksheet ss:Name="الطلبة" ss:RightToLeft="1"><Table>${table}</Table></Worksheet>
</Workbook>`;
    const file = new Blob([book], { type: "application/vnd.ms-excel" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(file);
    link.download = `طلبة ${label}.xls`.replace(/[^\p{L}\p{N}\s._-]+/gu, "").trim();
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 1500);
    say(`اتصدّر ${list.length} طالب.`, true);
  } catch (err) {
    say(err.message);
  } finally {
    button.disabled = false;
  }
});

document.getElementById("student-search").addEventListener("input", () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadStudents(1).catch((err) => say(err.message)), 250);
});
document.getElementById("student-status").addEventListener("change", () => loadStudents(1).catch((err) => say(err.message)));
document.getElementById("student-year").addEventListener("change", () => loadStudents(1).catch((err) => say(err.message)));
document.getElementById("student-pager").addEventListener("click", (event) => {
  const button = event.target.closest("[data-student-page]");
  if (!button || button.disabled) return;
  const next = button.dataset.studentPage === "prev" ? page - 1 : page + 1;
  loadStudents(next).catch((err) => say(err.message));
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  await Promise.all([fillGrades(), fillCourses(), loadStudents(1)]);
});
