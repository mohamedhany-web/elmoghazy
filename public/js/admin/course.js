const { esc, say, openModal, closeModal, state } = Admin;
const courseId = window.COURSE_ID;
let course = null;

const kinds = { text: "نص", video: "فيديو", file: "ملف" };

function paint() {
  document.getElementById("course-title").textContent = course?.title || "الحصص";
  document.getElementById("course-crumb").textContent = course?.title || "الحصص";
  const lessons = course?.lessons || [];
  document.getElementById("lesson-list").innerHTML = lessons.length
    ? `<table class="cs-table"><thead><tr><th>الحصة</th><th>النوع</th><th>الحالة</th><th></th></tr></thead><tbody>${lessons
        .map((lesson) => `<tr>
            <td>${esc(lesson.title)}</td>
            <td>${esc(kinds[lesson.kind] || lesson.kind)}</td>
            <td>${state(lesson.published !== false, "ظاهرة", "مخفية")}</td>
            <td class="cs-row-acts">
              <button type="button" class="cs-btn cs-btn--ghost" data-act="lesson-edit" data-lesson="${lesson.id}">تعديل</button>
              <button type="button" class="cs-btn cs-btn--danger" data-act="lesson-delete" data-lesson="${lesson.id}">حذف</button>
            </td>
          </tr>`)
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش حصص في الكورس ده.</p></div>`;
}

async function load() {
  const courses = await Elmo.api("/api/teacher/courses");
  course = courses.find((item) => String(item.id) === String(courseId));
  if (!course) {
    document.getElementById("lesson-list").innerHTML = `<div class="cs-empty"><p>الكورس مش موجود.</p></div>`;
    return;
  }
  paint();
}

function openLesson(lesson) {
  const form = document.getElementById("lesson-form");
  form.reset();
  form.querySelector('[name="lesson_id"]').value = lesson?.id || "";
  form.querySelector('[name="title"]').value = lesson?.title || "";
  form.querySelector('[name="kind"]').value = lesson?.kind || "text";
  form.querySelector('[name="body"]').value = lesson?.body || "";
  document.getElementById("lesson-form-title").textContent = lesson ? "تعديل الحصة" : "حصة جديدة";
  openModal("modal-lesson");
}

document.getElementById("lesson-add").addEventListener("click", () => openLesson(null));

document.getElementById("lesson-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const data = new FormData(event.currentTarget);
  const lessonId = data.get("lesson_id");
  const payload = {
    title: data.get("title"),
    kind: data.get("kind"),
    body: data.get("body") || null,
    published: true,
  };
  try {
    await Elmo.api(lessonId ? `/api/teacher/lessons/${lessonId}` : `/api/teacher/courses/${courseId}/lessons`, {
      method: lessonId ? "PATCH" : "POST",
      body: JSON.stringify(payload),
    });
    closeModal(document.getElementById("modal-lesson"));
    say("اتحفظت الحصة.", true);
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("lesson-list").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  if (button.dataset.act === "lesson-edit") {
    openLesson((course?.lessons || []).find((item) => String(item.id) === button.dataset.lesson));
    return;
  }
  if (button.dataset.act !== "lesson-delete" || !window.confirm("حذف الحصة.")) return;
  try {
    await Elmo.api(`/api/teacher/lessons/${button.dataset.lesson}`, { method: "DELETE" });
    say("تم.", true);
    await load();
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
