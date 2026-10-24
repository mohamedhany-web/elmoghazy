const { esc, say, openModal, closeModal } = Admin;
const studentId = window.STUDENT_ID;
let record = null;

function paintHead(sheet) {
  record = sheet.record || null;
  document.getElementById("student-title").textContent = sheet.subject || "تقرير الطالب";
  document.getElementById("student-crumb").textContent = sheet.subject || "التقرير";
  const stopped = record?.status === "disabled";
  document.getElementById("student-copy").innerHTML = `<span class="cs-state ${stopped ? "is-off" : "is-on"}"><i></i>${stopped ? "متوقف" : "نشط"}</span> ${esc(sheet.kicker || "")}`;
  document.getElementById("record-toggle").textContent = stopped ? "تشغيل" : "إيقاف";
}

async function load() {
  const sheet = await Elmo.api(`/api/teacher/students/${studentId}/report`);
  paintHead(sheet);
  ReportSheet.mount(document.getElementById("student-report"), sheet);
}

document.getElementById("record-edit").addEventListener("click", async () => {
  if (!record) return;
  const grades = await Elmo.api("/api/grades");
  const select = document.querySelector('#student-form [name="grade"]');
  select.innerHTML = grades.map((grade) => `<option value="${esc(grade.slug)}">${esc(grade.title)}</option>`).join("");
  const form = document.getElementById("student-form");
  form.querySelector('[name="first_name"]').value = record.first_name || "";
  form.querySelector('[name="second_name"]').value = record.second_name || "";
  form.querySelector('[name="phone"]').value = record.phone || "";
  form.querySelector('[name="parent_phone"]').value = record.parent_phone || "";
  form.querySelector('[name="password"]').value = "";
  if (record.grade_slug) select.value = record.grade_slug;
  openModal("modal-student");
});

document.getElementById("student-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const data = new FormData(event.currentTarget);
  const payload = {
    first_name: data.get("first_name"),
    second_name: data.get("second_name"),
    phone: data.get("phone"),
    parent_phone: data.get("parent_phone"),
    grade: data.get("grade"),
  };
  if (data.get("password")) payload.password = data.get("password");
  try {
    await Elmo.api(`/api/teacher/students/${studentId}`, { method: "PATCH", body: JSON.stringify(payload) });
    closeModal(event.currentTarget.closest(".cs-modal"));
    say("اتحفظ الطالب.", true);
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("record-activate").addEventListener("click", async () => {
  const courses = await Elmo.api("/api/teacher/courses");
  document.querySelector('#activate-form [name="course_id"]').innerHTML = `<option value="">كل كورسات الصف</option>${courses
    .map((course) => `<option value="${course.id}">${esc(course.title)}</option>`)
    .join("")}`;
  openModal("modal-activate");
});

document.getElementById("activate-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const data = new FormData(event.currentTarget);
  const payload = { duration_days: Number(data.get("duration_days")) };
  if (data.get("course_id")) payload.course_id = Number(data.get("course_id"));
  try {
    await Elmo.api(`/api/teacher/students/${studentId}/activate`, { method: "POST", body: JSON.stringify(payload) });
    closeModal(event.currentTarget.closest(".cs-modal"));
    say("التفعيل اشتغل.", true);
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("record-toggle").addEventListener("click", async () => {
  const enable = record?.status === "disabled";
  try {
    await Elmo.api(`/api/teacher/students/${studentId}/${enable ? "enable" : "disable"}`, { method: "POST" });
    say("تم.", true);
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("record-lock").addEventListener("click", async () => {
  try {
    await Elmo.api(`/api/teacher/students/${studentId}/lock`, { method: "POST" });
    say("تم.", true);
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("record-device").addEventListener("click", async () => {
  try {
    await Elmo.api(`/api/teacher/students/${studentId}/reset-device`, { method: "POST" });
    say("تم تصفير الجهاز. أول جهاز يفتح منه الطالب هيتربط بالاشتراك.", true);
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("record-delete").addEventListener("click", async () => {
  if (!window.confirm("حذف الطالب.")) return;
  try {
    await Elmo.api(`/api/teacher/students/${studentId}`, { method: "DELETE" });
    location.href = "/admin/students";
  } catch (err) {
    say(err.message);
  }
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  try {
    await load();
  } catch (err) {
    say(err.message);
  }
});
