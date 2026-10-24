const { esc, say, openModal, closeModal } = Admin;
const board = window.BANK_BOARD;
let outline = null;
let titleTask = null;

function askTitle(heading, label, value, task) {
  titleTask = task;
  document.getElementById("title-form-heading").textContent = heading;
  document.getElementById("title-form-label").textContent = label;
  const form = document.getElementById("title-form");
  form.reset();
  form.querySelector('[name="title"]').value = value || "";
  openModal("modal-title");
}

function currentUnit() {
  return (outline?.units || []).find((unit) => String(unit.id) === String(board.unit));
}

function currentLesson() {
  const unit = currentUnit();
  return (unit?.lessons || []).find((lesson) => String(lesson.id) === String(board.lesson));
}

function paint() {
  const grade = outline.grade;
  const unit = currentUnit();
  const lesson = currentLesson();
  const crumb = [`<a href="/admin/banks">بنوك الأسئلة</a>`, `<a href="/admin/banks/${board.grade}/units">${esc(grade.title)}</a>`];
  const title = document.getElementById("board-title");
  const copy = document.getElementById("board-copy");
  const add = document.getElementById("board-add");
  let cards = "";

  if (board.mode === "units") {
    title.textContent = grade.title;
    copy.textContent = "وحدات السنة. التفاصيل تفتح دروس الوحدة.";
    add.textContent = "وحدة جديدة";
    const rows = outline.units || [];
    cards = rows.length
      ? `<table class="cs-table"><thead><tr><th>الوحدة</th><th>دروس</th><th>أسئلة</th><th></th></tr></thead><tbody>${rows.map((item) => `<tr>
            <td>${esc(item.title)}</td>
            <td>${(item.lessons || []).length}</td>
            <td>${esc(item.questions)}</td>
            <td class="cs-row-acts">
              <a class="cs-btn cs-btn--primary" href="/admin/banks/${board.grade}/units/${item.id}">التفاصيل</a>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="rename" data-id="${item.id}" data-title="${esc(item.title)}" data-kind="unit">تعديل</button>
              <button type="button" class="cs-btn cs-btn--danger" data-act="remove" data-id="${item.id}" data-kind="unit">حذف</button>
            </td>
          </tr>`).join("")}</tbody></table>`
      : "";
  }

  if (board.mode === "lessons") {
    crumb.push(`<span>${esc(unit?.title || "الوحدة")}</span>`);
    title.textContent = unit?.title || "الدروس";
    copy.textContent = "دروس الوحدة. التفاصيل تفتح المواضيع.";
    add.textContent = "درس جديد";
    const rows = unit?.lessons || [];
    cards = rows.length
      ? `<table class="cs-table"><thead><tr><th>الدرس</th><th>مواضيع</th><th>أسئلة</th><th></th></tr></thead><tbody>${rows.map((item) => `<tr>
            <td>${esc(item.title)}</td>
            <td>${(item.topics || []).length}</td>
            <td>${esc(item.questions)}</td>
            <td class="cs-row-acts">
              <a class="cs-btn cs-btn--primary" href="/admin/banks/${board.grade}/units/${board.unit}/lessons/${item.id}">التفاصيل</a>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="rename" data-id="${item.id}" data-title="${esc(item.title)}" data-kind="lesson">تعديل</button>
              <button type="button" class="cs-btn cs-btn--danger" data-act="remove" data-id="${item.id}" data-kind="lesson">حذف</button>
            </td>
          </tr>`).join("")}</tbody></table>`
      : "";
  }

  if (board.mode === "topics") {
    crumb.push(`<a href="/admin/banks/${board.grade}/units/${board.unit}">${esc(unit?.title || "الوحدة")}</a>`);
    crumb.push(`<span>${esc(lesson?.title || "الدرس")}</span>`);
    title.textContent = lesson?.title || "المواضيع";
    copy.textContent = "مواضيع الدرس.";
    add.textContent = "موضوع جديد";
    const rows = lesson?.topics || [];
    cards = rows.length
      ? `<table class="cs-table"><thead><tr><th>الموضوع</th><th></th></tr></thead><tbody>${rows.map((item) => `<tr>
            <td>${esc(item.title)}</td>
            <td class="cs-row-acts">
              <a class="cs-btn cs-btn--ghost" href="/admin/banks/${board.grade}?unit=${board.unit}&lesson=${board.lesson}">أسئلة الدرس</a>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="rename" data-id="${item.id}" data-title="${esc(item.title)}" data-kind="topic">تعديل</button>
              <button type="button" class="cs-btn cs-btn--danger" data-act="remove" data-id="${item.id}" data-kind="topic">حذف</button>
            </td>
          </tr>`).join("")}</tbody></table>`
      : "";
  }

  document.getElementById("bank-crumb").innerHTML = crumb.join("<span>/</span>");
  document.getElementById("board-list").innerHTML = cards || `<div class="cs-empty"><p>لسه فاضي.</p></div>`;
}

async function load() {
  outline = await Elmo.api(`/api/teacher/banks/${board.grade}`);
  if ((board.mode === "lessons" || board.mode === "topics") && !currentUnit()) {
    document.getElementById("board-list").innerHTML = `<div class="cs-empty"><p>الوحدة مش موجودة.</p></div>`;
    return;
  }
  if (board.mode === "topics" && !currentLesson()) {
    document.getElementById("board-list").innerHTML = `<div class="cs-empty"><p>الدرس مش موجود.</p></div>`;
    return;
  }
  paint();
}

document.getElementById("title-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const title = String(new FormData(event.currentTarget).get("title") || "").trim();
  try {
    await titleTask?.(title);
    closeModal(document.getElementById("modal-title"));
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("board-add").addEventListener("click", () => {
  if (board.mode === "units") {
    askTitle("وحدة جديدة", "اسم الوحدة", "", async (title) => {
      await Elmo.api(`/api/teacher/banks/${board.grade}/units`, { method: "POST", body: JSON.stringify({ title }) });
    });
  }
  if (board.mode === "lessons") {
    askTitle("درس جديد", "اسم الدرس", "", async (title) => {
      await Elmo.api(`/api/teacher/bank-units/${board.unit}/lessons`, { method: "POST", body: JSON.stringify({ title }) });
    });
  }
  if (board.mode === "topics") {
    askTitle("موضوع جديد", "اسم الموضوع", "", async (title) => {
      await Elmo.api(`/api/teacher/bank-lessons/${board.lesson}/topics`, { method: "POST", body: JSON.stringify({ title }) });
    });
  }
});

document.getElementById("board-list").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  const kind = button.dataset.kind;
  const id = button.dataset.id;
  if (button.dataset.act === "rename") {
    const labels = { unit: ["تعديل الوحدة", "اسم الوحدة"], lesson: ["تعديل الدرس", "اسم الدرس"], topic: ["تعديل الموضوع", "اسم الموضوع"] };
    askTitle(labels[kind][0], labels[kind][1], button.dataset.title, async (title) => {
      const path = kind === "unit" ? `/api/teacher/bank-units/${id}` : kind === "lesson" ? `/api/teacher/bank-lessons/${id}` : `/api/teacher/bank-topics/${id}`;
      await Elmo.api(path, { method: "PATCH", body: JSON.stringify({ title }) });
    });
    return;
  }
  if (button.dataset.act !== "remove") return;
  if (!window.confirm("تأكيد الحذف.")) return;
  try {
    const path = kind === "unit" ? `/api/teacher/bank-units/${id}` : kind === "lesson" ? `/api/teacher/bank-lessons/${id}` : `/api/teacher/bank-topics/${id}`;
    await Elmo.api(path, { method: "DELETE" });
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
