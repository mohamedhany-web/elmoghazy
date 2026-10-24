const { esc, say, openModal, closeModal, applyFilters, state } = Admin;
let catalog = [];

async function loadCourses() {
  const [courses, grades] = await Promise.all([Elmo.api("/api/teacher/courses"), Elmo.api("/api/grades")]);
  catalog = courses;
  const select = document.querySelector('#course-form [name="grade"]');
  const current = select.value;
  select.innerHTML = grades.map((grade) => `<option value="${esc(grade.slug)}">${esc(grade.title)}</option>`).join("");
  if (current) select.value = current;
  const year = document.getElementById("course-year");
  const yearCurrent = year.value;
  year.innerHTML = `<option value="">كل السنوات</option>${grades.map((grade) => `<option value="${esc(grade.title)}">${esc(grade.title)}</option>`).join("")}`;
  if (yearCurrent) year.value = yearCurrent;
  const host = document.getElementById("course-list");
  host.innerHTML = courses.length
    ? `<table class="cs-table"><thead><tr><th>الكورس</th><th>الصف</th><th>الحصص</th><th>الحالة</th><th></th></tr></thead><tbody>${courses
        .map((course) => `<tr data-id="${course.id}" data-published="${course.published ? "1" : "0"}" data-status="${course.published ? "published" : "hidden"}" data-year="${esc(course.grade || "")}" data-search="${esc(course.title)} ${esc(course.grade)}">
            <td>${esc(course.title)}</td>
            <td>${esc(course.grade || "—")}</td>
            <td>${esc(course.lesson_count)}</td>
            <td>${state(!!course.published, "ظاهر", "مخفي")}</td>
            <td class="cs-row-acts">
              <a class="cs-btn cs-btn--primary" href="/admin/courses/${course.id}">التفاصيل</a>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="course-edit">تعديل</button>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="publish">${course.published ? "إخفاء" : "نشر"}</button>
              <button type="button" class="cs-btn cs-btn--danger" data-act="course-delete">حذف</button>
            </td>
          </tr>`)
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش كورسات.</p></div>`;
  applyFilters();
}

document.querySelector('[data-open-modal="modal-course"]')?.addEventListener("click", () => {
  const form = document.getElementById("course-form");
  form.reset();
  form.querySelector('[name="id"]').value = "";
  document.getElementById("course-form-title").textContent = "كورس جديد";
});

document.getElementById("course-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const id = data.get("id");
  const payload = {
    grade: data.get("grade"),
    title: data.get("title"),
    description: data.get("description"),
    published: form.querySelector('[name="published"]').checked,
    unlock_in_order: form.querySelector('[name="unlock_in_order"]').checked,
  };
  try {
    await Elmo.api(id ? `/api/teacher/courses/${id}` : "/api/teacher/courses", {
      method: id ? "PATCH" : "POST",
      body: JSON.stringify(payload),
    });
    form.reset();
    closeModal(form.closest(".cs-modal"));
    say(id ? "اتحفظ الكورس." : "اتضاف الكورس.", true);
    await loadCourses();
  } catch (err) {
    say(err.message);
    openModal("modal-course");
  }
});

document.getElementById("lesson-form")?.addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const lessonId = data.get("lesson_id");
  const payload = {
    title: data.get("title"),
    kind: data.get("kind"),
    body: data.get("body") || null,
    published: true,
  };
  try {
    await Elmo.api(lessonId ? `/api/teacher/lessons/${lessonId}` : `/api/teacher/courses/${data.get("course_id")}/lessons`, {
      method: lessonId ? "PATCH" : "POST",
      body: JSON.stringify(payload),
    });
    form.reset();
    closeModal(form.closest(".cs-modal"));
    say("اتضافت الحصة.", true);
    await loadCourses();
  } catch (err) {
    say(err.message);
    openModal("modal-lesson");
  }
});

document.getElementById("desk").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  const row = button.closest("[data-id]");
  const id = row?.dataset.id;
  if (button.dataset.act === "lesson" || button.dataset.act === "lesson-edit") {
    const form = document.getElementById("lesson-form");
    if (!form) return;
    form.reset();
    form.querySelector('[name="course_id"]').value = button.dataset.course || id || "";
    form.querySelector('[name="lesson_id"]').value = "";
    document.getElementById("lesson-course-name").textContent = button.dataset.name || "";
    document.getElementById("lesson-form-title").textContent = "حصة جديدة";
    if (button.dataset.act === "lesson-edit") {
      const course = catalog.find((item) => String(item.id) === button.dataset.course);
      const lesson = course?.lessons?.find((item) => String(item.id) === button.dataset.lesson);
      if (!lesson) return;
      form.querySelector('[name="lesson_id"]').value = lesson.id;
      form.querySelector('[name="title"]').value = lesson.title || "";
      form.querySelector('[name="kind"]').value = lesson.kind || "text";
      form.querySelector('[name="body"]').value = lesson.body || "";
      document.getElementById("lesson-form-title").textContent = "تعديل الحصة";
    }
    openModal("modal-lesson");
    return;
  }
  if (button.dataset.act === "course-edit") {
    const course = catalog.find((item) => String(item.id) === id);
    if (!course) return;
    const form = document.getElementById("course-form");
    form.querySelector('[name="id"]').value = course.id;
    form.querySelector('[name="grade"]').value = course.grade_slug || "";
    form.querySelector('[name="title"]').value = course.title || "";
    form.querySelector('[name="description"]').value = course.description || "";
    form.querySelector('[name="published"]').checked = !!course.published;
    form.querySelector('[name="unlock_in_order"]').checked = !!course.unlock_in_order;
    document.getElementById("course-form-title").textContent = "تعديل الكورس";
    openModal("modal-course");
    return;
  }
  try {
    if (button.dataset.act === "publish") {
      await Elmo.api(`/api/teacher/courses/${id}`, {
        method: "PATCH",
        body: JSON.stringify({ published: row.dataset.published !== "1" }),
      });
    } else if (button.dataset.act === "course-delete") {
      await Elmo.api(`/api/teacher/courses/${id}`, { method: "DELETE" });
    } else if (button.dataset.act === "lesson-delete") {
      await Elmo.api(`/api/teacher/lessons/${button.dataset.lesson}`, { method: "DELETE" });
    } else {
      return;
    }
    say("تم.", true);
    await loadCourses();
  } catch (err) {
    say(err.message);
  }
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  await loadCourses();
});
