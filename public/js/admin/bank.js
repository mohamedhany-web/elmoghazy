const { esc, say, openModal, closeModal } = Admin;
const gradeId = window.BANK_GRADE;
const letters = ["أ", "ب", "ج", "د", "هـ", "و", "ز", "ح", "ط", "ي", "ك", "ل"];
const typeLabels = {
  single: "اختيار واحد",
  multiple: "أكتر من إجابة",
  true_false: "صح أو غلط",
  fill_blank: "املأ الفراغ",
  numerical: "رقم",
  short: "إجابة قصيرة",
  essay: "مقالي",
};
const statusLabels = { published: "منشور", draft: "مسودة", archived: "مؤرشف" };
let titleTask = null;
let outline = null;
const opened = new URLSearchParams(location.search);
let selectedUnit = opened.get("unit") || "";
let selectedLesson = opened.get("lesson") || "";
let page = 1;
let rows = [];
let picked = new Set();
let searchTimer = null;

function askTitle(heading, label, task) {
  titleTask = task;
  document.getElementById("title-form-heading").textContent = heading;
  document.getElementById("title-form-label").textContent = label;
  document.getElementById("title-form").reset();
  openModal("modal-title");
}

function diffLabel(level) {
  if (level <= 2) return "سهل";
  if (level >= 4) return "صعب";
  return "متوسط";
}

function currentLesson() {
  for (const unit of outline?.units || []) {
    const lesson = unit.lessons.find((item) => String(item.id) === String(selectedLesson));
    if (lesson) return { unit, lesson };
  }
  return null;
}

function fillTypeSelect() {
  const select = document.getElementById("question-type");
  if (select.options.length) return;
  select.innerHTML = Object.entries(typeLabels).map(([value, label]) => `<option value="${value}">${label}</option>`).join("");
  document.getElementById("filter-type").insertAdjacentHTML("beforeend", Object.entries(typeLabels).map(([value, label]) => `<option value="${value}">${label}</option>`).join(""));
}

function optionRow(option = { body: "", is_correct: false }, index = 0) {
  return `<div class="option-row">
    <span class="option-letter">${letters[index] || index + 1}</span>
    <label class="cs-check"><input type="checkbox" data-correct ${option.is_correct ? "checked" : ""}> صحيح</label>
    <input class="cs-input" data-body value="${esc(option.body)}" placeholder="نص الاختيار" required>
    <button type="button" class="cs-btn cs-btn--ghost" data-act="option-remove">حذف</button>
  </div>`;
}

function paintTypeFields(question) {
  const type = document.getElementById("question-type").value;
  const host = document.getElementById("type-fields");
  const settings = question?.settings || {};
  if (type === "single" || type === "multiple") {
    const options = question?.options?.length ? question.options : [
      { body: "", is_correct: true },
      { body: "", is_correct: false },
      { body: "", is_correct: false },
      { body: "", is_correct: false },
    ];
    host.innerHTML = `${type === "multiple" ? `<label class="cs-field"><span class="cs-label">طريقة الدرجة</span><select class="cs-input" data-setting="scoring">
      <option value="all" ${settings.scoring === "all" ? "selected" : ""}>كل الإجابات أو لا شيء</option>
      <option value="partial" ${settings.scoring === "partial" ? "selected" : ""}>درجة جزئية</option>
      <option value="deduct" ${settings.scoring === "deduct" ? "selected" : ""}>خصم الاختيار الغلط</option>
    </select></label>` : ""}<div id="option-list" class="option-list">${options.map(optionRow).join("")}</div>
    <button type="button" class="cs-btn cs-btn--ghost" data-act="option-add">اختيار جديد</button>`;
    return;
  }
  if (type === "true_false") {
    const correct = settings.correct ?? question?.options?.find((option) => option.is_correct)?.body === "صح";
    host.innerHTML = `<label class="cs-check"><input type="checkbox" data-setting="correct" ${correct ? "checked" : ""}> الإجابة صح</label>`;
    return;
  }
  if (type === "numerical") {
    host.innerHTML = `<div class="cs-filter-grid bank-filters">
      <label class="cs-field"><span class="cs-label">الإجابة</span><input class="cs-input" data-setting="answer" value="${esc(settings.answer || "")}" required></label>
      <label class="cs-field"><span class="cs-label">السماح ±</span><input class="cs-input" data-setting="tolerance" type="number" min="0" step="0.01" value="${esc(settings.tolerance ?? 0)}"></label>
    </div>`;
    return;
  }
  if (type === "fill_blank") {
    host.innerHTML = `<label class="cs-field"><span class="cs-label">إجابات مقبولة، سطر لكل إجابة</span><textarea class="cs-input" data-setting="answers" rows="4">${esc((settings.answers || []).join("\n"))}</textarea></label>`;
    return;
  }
  if (type === "short") {
    host.innerHTML = `<label class="cs-field"><span class="cs-label">نموذج الإجابة</span><textarea class="cs-input" data-setting="model_answer" rows="3">${esc(settings.model_answer || "")}</textarea></label><p class="cs-copy">الإجابة القصيرة بتتصحح يدوي من المدرس.</p>`;
    return;
  }
  host.innerHTML = `<div class="cs-filter-grid bank-filters">
    <label class="cs-field"><span class="cs-label">نموذج الإجابة</span><textarea class="cs-input" data-setting="model_answer" rows="3">${esc(settings.model_answer || "")}</textarea></label>
    <label class="cs-field"><span class="cs-label">أقل عدد كلمات</span><input class="cs-input" data-setting="min_words" type="number" min="0" value="${settings.min_words || 0}"></label>
    <label class="cs-field"><span class="cs-label">أقصى عدد كلمات</span><input class="cs-input" data-setting="max_words" type="number" min="0" value="${settings.max_words || 0}"></label>
  </div>`;
}

function readPayload(form) {
  const data = new FormData(form);
  const type = data.get("type");
  const tags = String(data.get("tags") || "").split(/[,،]/).map((tag) => tag.trim()).filter(Boolean);
  const payload = {
    prompt: data.get("prompt"),
    type,
    difficulty: Number(data.get("difficulty") || 3),
    marks: Number(data.get("marks") || 1),
    negative_mark: data.get("negative_mark") === "" ? null : Number(data.get("negative_mark")),
    explanation: data.get("explanation") || null,
    hint: data.get("hint") || null,
    status: data.get("status"),
    tags,
    topic_id: data.get("topic_id") || null,
    options: [],
    settings: {},
  };
  if (type === "single" || type === "multiple") {
    payload.options = [...document.querySelectorAll("#option-list .option-row")].map((row) => ({
      body: row.querySelector("[data-body]").value.trim(),
      is_correct: row.querySelector("[data-correct]").checked,
    }));
    if (type === "multiple") payload.settings.scoring = document.querySelector('[data-setting="scoring"]')?.value || "all";
  } else if (type === "true_false") {
    payload.settings.correct = Boolean(document.querySelector('[data-setting="correct"]')?.checked);
  } else if (type === "numerical") {
    payload.settings.answer = document.querySelector('[data-setting="answer"]').value.trim();
    payload.settings.tolerance = Number(document.querySelector('[data-setting="tolerance"]').value || 0);
  } else if (type === "fill_blank") {
    payload.settings.answers = document.querySelector('[data-setting="answers"]').value.split(/\n|\|/).map((item) => item.trim()).filter(Boolean);
  } else if (type === "short") {
    payload.settings.model_answer = document.querySelector('[data-setting="model_answer"]').value.trim();
  } else {
    payload.settings.model_answer = document.querySelector('[data-setting="model_answer"]').value.trim();
    payload.settings.min_words = Number(document.querySelector('[data-setting="min_words"]').value || 0);
    payload.settings.max_words = Number(document.querySelector('[data-setting="max_words"]').value || 0);
  }
  return payload;
}

function fillTopics(selected) {
  const place = currentLesson();
  const topics = place?.lesson.topics || [];
  document.getElementById("question-topic").innerHTML = `<option value="">من غير موضوع</option>${topics.map((topic) => `<option value="${topic.id}">${esc(topic.title)}</option>`).join("")}`;
  document.getElementById("question-topic").value = selected || "";
}

function openComposer(question) {
  const place = currentLesson();
  if (!place && !question) {
    say("اختار الدرس الأول عشان السؤال يتحط فيه.");
    return;
  }
  const form = document.getElementById("question-form");
  form.querySelector('[name="question_id"]').value = question?.id || "";
  form.querySelector('[name="lesson_id"]').value = question?.lesson_id || selectedLesson;
  form.querySelector('[name="prompt"]').value = question?.prompt || "";
  form.querySelector('[name="type"]').value = question?.type || "single";
  form.querySelector('[name="difficulty"]').value = String(question?.difficulty <= 2 ? 2 : question?.difficulty >= 4 ? 4 : question ? 3 : 3);
  form.querySelector('[name="marks"]').value = question?.marks || 1;
  form.querySelector('[name="status"]').value = question?.status || "published";
  form.querySelector('[name="explanation"]').value = question?.explanation || "";
  form.querySelector('[name="hint"]').value = question?.hint || "";
  form.querySelector('[name="negative_mark"]').value = question?.negative_mark ?? "";
  form.querySelector('[name="tags"]').value = (question?.tags || []).join("، ");
  document.getElementById("composer-title").textContent = question ? "تعديل السؤال" : "سؤال جديد";
  document.getElementById("composer-place").textContent = question ? `${question.unit} · ${question.lesson}` : `${place.unit.title} · ${place.lesson.title}`;
  document.getElementById("question-next").hidden = Boolean(question);
  fillTopics(question?.topic_id || "");
  paintTypeFields(question);
  openModal("modal-question");
  form.querySelector('[name="prompt"]').focus();
}

function renderMeter() {
  const counts = outline.counts;
  const cells = [
    ["الأسئلة", counts.questions],
    ["الوحدات", counts.units],
    ["الدروس", counts.lessons],
    ["المواضيع", counts.topics],
    ["سهل", counts.easy],
    ["متوسط", counts.medium],
    ["صعب", counts.hard],
    ["مستخدم", counts.used],
    ["مسودة", counts.draft],
    ["منشور", counts.published],
  ];
  document.getElementById("bank-meter").innerHTML = cells.map(([label, value]) => `<article class="bank-pill"><strong>${value || 0}</strong><span>${label}</span></article>`).join("");
  document.getElementById("bank-stats").textContent = "الجدول ٢٠ سؤال في الصفحة. المسودة متدخلش الامتحان غير لما تنشرها.";
}

function renderRail() {
  const host = document.getElementById("bank-rail");
  renderMeter();
  if (!outline.units.length) {
    host.innerHTML = `<p class="cs-empty">ابدأ بوحدة، وبعدين الدرس، وبعدين الأسئلة.</p>`;
    return;
  }
  host.innerHTML = `<button type="button" class="bank-node ${selectedUnit || selectedLesson ? "" : "is-on"}" data-act="pick-all">كل الأسئلة <span>${outline.counts.questions}</span></button>` + outline.units
    .map((unit) => {
      const lessons = unit.lessons.map((lesson) => {
        const topics = String(selectedLesson) === String(lesson.id)
          ? (lesson.topics || []).map((topic) => `<div class="cs-row-acts"><span>${esc(topic.title)}</span><button type="button" class="cs-btn cs-btn--ghost" data-act="topic-rename" data-topic="${topic.id}" data-title="${esc(topic.title)}">تعديل</button><button type="button" class="cs-btn cs-btn--ghost" data-act="topic-delete" data-topic="${topic.id}">حذف</button></div>`).join("")
          : "";
        return `<button type="button" class="bank-node bank-node--lesson ${String(selectedLesson) === String(lesson.id) ? "is-on" : ""}" data-act="pick-lesson" data-unit="${unit.id}" data-lesson="${lesson.id}"><span>${esc(lesson.title)}</span><span>${lesson.questions}</span></button>${topics}`;
      }).join("");
      return `<section class="bank-unit">
        <button type="button" class="bank-node ${String(selectedUnit) === String(unit.id) && !selectedLesson ? "is-on" : ""}" data-act="pick-unit" data-unit="${unit.id}"><span>${esc(unit.title)}</span><span>${unit.questions}</span></button>
        <div class="bank-lessons">${lessons}</div>
        <div class="cs-row-acts">
          <button type="button" class="cs-btn cs-btn--ghost" data-act="lesson-new" data-unit="${unit.id}">درس</button>
          <button type="button" class="cs-btn cs-btn--ghost" data-act="unit-rename" data-unit="${unit.id}">تعديل</button>
          <button type="button" class="cs-btn cs-btn--ghost" data-act="unit-delete" data-unit="${unit.id}">حذف</button>
        </div>
      </section>`;
    })
    .join("");
  const onLesson = Boolean(selectedLesson);
  document.getElementById("question-start").disabled = !onLesson;
  document.getElementById("lesson-rename").hidden = !onLesson;
  document.getElementById("lesson-delete").hidden = !onLesson;
  document.getElementById("topic-new").hidden = !onLesson;
  document.getElementById("lesson-rename").dataset.lesson = selectedLesson;
  document.getElementById("lesson-delete").dataset.lesson = selectedLesson;
}

function paintBulk() {
  const bar = document.getElementById("bulk-bar");
  bar.hidden = picked.size === 0;
  document.getElementById("bulk-count").textContent = `${picked.size} محدد`;
}

function renderRows(result) {
  const body = document.getElementById("question-rows");
  rows = result.data;
  body.innerHTML = result.data.length
    ? result.data.map((question) => `<tr>
        <td><input type="checkbox" data-row="${question.id}" ${picked.has(String(question.id)) ? "checked" : ""}></td>
        <td class="bank-prompt">${question.favorite ? "★ " : ""}${esc(question.prompt)}</td>
        <td class="bank-prompt">${esc(question.unit)} · ${esc(question.lesson)}${question.topic ? ` · ${esc(question.topic)}` : ""}</td>
        <td>${typeLabels[question.type] || question.type}</td>
        <td>${diffLabel(question.difficulty)}</td>
        <td>${question.marks}</td>
        <td>${question.usage}${question.correct_rate === null ? "" : ` · ${question.correct_rate}%`}</td>
        <td>${statusLabels[question.status] || question.status}</td>
        <td><div class="cs-row-acts">
          <button type="button" class="cs-btn cs-btn--ghost" data-act="question-edit" data-question="${question.id}">تعديل</button>
          <button type="button" class="cs-btn cs-btn--ghost" data-act="question-copy" data-question="${question.id}">نسخ</button>
          <button type="button" class="cs-btn cs-btn--ghost" data-act="question-delete" data-question="${question.id}">حذف</button>
        </div></td>
      </tr>`).join("")
    : `<tr><td colspan="9" class="cs-empty">مفيش أسئلة في العرض ده.</td></tr>`;
  const pager = document.getElementById("bank-pager");
  pager.innerHTML = result.total
    ? `<button type="button" class="cs-btn cs-btn--ghost" data-act="page" data-page="${result.page - 1}" ${result.page <= 1 ? "disabled" : ""}>السابق</button>
       <span>صفحة ${result.page} من ${result.last_page} · ${result.total} سؤال</span>
       <button type="button" class="cs-btn cs-btn--ghost" data-act="page" data-page="${result.page + 1}" ${result.page >= result.last_page ? "disabled" : ""}>التالي</button>`
    : "";
  paintBulk();
}

async function loadOutline() {
  outline = await Elmo.api(`/api/teacher/banks/${gradeId}`);
  document.getElementById("bank-title").textContent = `بنك ${outline.grade.title}`;
  document.getElementById("bank-crumb").textContent = outline.grade.title;
  if (selectedLesson && !currentLesson()) selectedLesson = "";
  if (selectedUnit && !outline.units.some((unit) => String(unit.id) === String(selectedUnit))) selectedUnit = "";
  renderRail();
}

async function loadQuestions() {
  const params = new URLSearchParams({ page: String(page) });
  const term = document.getElementById("bank-search").value.trim();
  const type = document.getElementById("filter-type").value;
  const difficulty = document.getElementById("filter-difficulty").value;
  const status = document.getElementById("filter-status").value;
  if (term) params.set("q", term);
  if (type) params.set("type", type);
  if (difficulty) params.set("difficulty", difficulty);
  if (status) params.set("status", status);
  if (selectedUnit) params.set("unit", selectedUnit);
  if (selectedLesson) params.set("lesson", selectedLesson);
  renderRows(await Elmo.api(`/api/teacher/banks/${gradeId}/questions?${params}`));
}

async function refresh() {
  await loadOutline();
  await loadQuestions();
}

function queueSearch() {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => {
    page = 1;
    loadQuestions().catch((err) => say(err.message));
  }, 250);
}

document.getElementById("bank-search").addEventListener("input", queueSearch);
document.getElementById("filter-type").addEventListener("change", queueSearch);
document.getElementById("filter-difficulty").addEventListener("change", queueSearch);
document.getElementById("filter-status").addEventListener("change", queueSearch);
document.getElementById("question-type").addEventListener("change", () => paintTypeFields(null));
document.getElementById("question-start").addEventListener("click", () => openComposer(null));
document.getElementById("pick-all").addEventListener("change", (event) => {
  document.querySelectorAll("[data-row]").forEach((box) => {
    box.checked = event.target.checked;
    if (box.checked) picked.add(box.dataset.row);
    else picked.delete(box.dataset.row);
  });
  paintBulk();
});
document.getElementById("question-rows").addEventListener("change", (event) => {
  const box = event.target.closest("[data-row]");
  if (!box) return;
  if (box.checked) picked.add(box.dataset.row);
  else picked.delete(box.dataset.row);
  paintBulk();
});

document.getElementById("desk").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button || button.disabled) return;
  const act = button.dataset.act;
  try {
    if (act === "pick-all") {
      selectedUnit = "";
      selectedLesson = "";
      page = 1;
      renderRail();
      await loadQuestions();
      return;
    }
    if (act === "pick-unit") {
      selectedUnit = button.dataset.unit;
      selectedLesson = "";
      page = 1;
      renderRail();
      await loadQuestions();
      return;
    }
    if (act === "pick-lesson") {
      selectedUnit = button.dataset.unit;
      selectedLesson = button.dataset.lesson;
      page = 1;
      renderRail();
      await loadQuestions();
      return;
    }
    if (act === "page") {
      page = Number(button.dataset.page);
      await loadQuestions();
      return;
    }
    if (act === "unit-new" || act === "unit-rename") {
      const unitId = button.dataset.unit;
      askTitle(act === "unit-new" ? "وحدة جديدة" : "تعديل الوحدة", "اسم الوحدة", async (title) => {
        if (act === "unit-new") {
          const created = await Elmo.api(`/api/teacher/banks/${gradeId}/units`, { method: "POST", body: JSON.stringify({ title }) });
          selectedUnit = String(created.id);
        } else {
          await Elmo.api(`/api/teacher/bank-units/${unitId}`, { method: "PATCH", body: JSON.stringify({ title }) });
        }
      });
      return;
    }
    if (act === "unit-delete") {
      await Elmo.api(`/api/teacher/bank-units/${button.dataset.unit}`, { method: "DELETE" });
      if (String(selectedUnit) === String(button.dataset.unit)) {
        selectedUnit = "";
        selectedLesson = "";
      }
      await refresh();
      return;
    }
    if (act === "lesson-new") {
      const unitId = button.dataset.unit;
      askTitle("درس جديد", "اسم الدرس", async (title) => {
        const created = await Elmo.api(`/api/teacher/bank-units/${unitId}/lessons`, { method: "POST", body: JSON.stringify({ title }) });
        selectedUnit = String(unitId);
        selectedLesson = String(created.id);
      });
      return;
    }
    if (act === "lesson-rename") {
      const place = currentLesson();
      askTitle("تعديل الدرس", "اسم الدرس", async (title) => {
        await Elmo.api(`/api/teacher/bank-lessons/${button.dataset.lesson}`, { method: "PATCH", body: JSON.stringify({ title }) });
      });
      document.querySelector('#title-form [name="title"]').value = place?.lesson.title || "";
      return;
    }
    if (act === "lesson-delete") {
      await Elmo.api(`/api/teacher/bank-lessons/${button.dataset.lesson}`, { method: "DELETE" });
      selectedLesson = "";
      closeModal(document.getElementById("modal-question"));
      await refresh();
      return;
    }
    if (act === "topic-new") {
      askTitle("موضوع جديد", "اسم الموضوع", async (title) => {
        await Elmo.api(`/api/teacher/bank-lessons/${selectedLesson}/topics`, { method: "POST", body: JSON.stringify({ title }) });
      });
      return;
    }
    if (act === "topic-rename") {
      askTitle("تعديل الموضوع", "اسم الموضوع", async (title) => {
        await Elmo.api(`/api/teacher/bank-topics/${button.dataset.topic}`, { method: "PATCH", body: JSON.stringify({ title }) });
      });
      document.querySelector('#title-form [name="title"]').value = button.dataset.title || "";
      return;
    }
    if (act === "topic-delete") {
      await Elmo.api(`/api/teacher/bank-topics/${button.dataset.topic}`, { method: "DELETE" });
      await refresh();
      return;
    }
    if (act === "question-edit") {
      openComposer(rows.find((question) => String(question.id) === String(button.dataset.question)));
      return;
    }
    if (act === "question-copy") {
      await Elmo.api(`/api/teacher/questions/${button.dataset.question}/duplicate`, { method: "POST", body: JSON.stringify({}) });
      say("اتنسخ كمسودة.", true);
      await refresh();
      return;
    }
    if (act === "question-delete") {
      await Elmo.api(`/api/teacher/questions/${button.dataset.question}`, { method: "DELETE" });
      picked.delete(String(button.dataset.question));
      await refresh();
      return;
    }
    if (act === "option-add") {
      const count = document.querySelectorAll("#option-list .option-row").length;
      if (count >= 12) return say("أقصى عدد 12 اختيار.");
      document.getElementById("option-list").insertAdjacentHTML("beforeend", optionRow({ body: "", is_correct: false }, count));
      return;
    }
    if (act === "option-remove") {
      if (document.querySelectorAll("#option-list .option-row").length <= 2) return say("سيب اختيارين على الأقل.");
      button.closest(".option-row").remove();
      return;
    }
    if (act === "import-open") {
      if (!selectedLesson) return say("اختار الدرس اللي هتستورد جواه.");
      openModal("modal-import");
      return;
    }
    if (act === "bulk") {
      const ids = [...picked].map(Number);
      const action = button.dataset.bulk;
      const run = async (extra = {}) => {
        const result = await Elmo.api(`/api/teacher/banks/${gradeId}/bulk`, {
          method: "POST",
          body: JSON.stringify({ ids, action, ...extra }),
        });
        picked.clear();
        say(`اتنفذ على ${result.count} سؤال.`, true);
      };
      if (action === "difficulty") {
        askTitle("تغيير الصعوبة", "2 سهل، 3 متوسط، 4 صعب", async (title) => run({ difficulty: Number(title) }));
        document.querySelector('#title-form [name="title"]').value = "3";
        return;
      }
      if (action === "tag") {
        askTitle("إضافة وسم", "الوسم", async (title) => run({ tag: title }));
        return;
      }
      await run();
      await refresh();
    }
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("title-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const title = new FormData(form).get("title");
  const task = titleTask;
  try {
    if (task) await task(title);
    closeModal(form.closest(".cs-modal"));
    say("اتحفظ.", true);
    await refresh();
  } catch (err) {
    say(err.message);
    openModal("modal-title");
  }
});

document.getElementById("import-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const csv = new FormData(event.currentTarget).get("csv");
  try {
    const result = await Elmo.api(`/api/teacher/bank-lessons/${selectedLesson}/import`, {
      method: "POST",
      body: JSON.stringify({ csv }),
    });
    closeModal(document.getElementById("modal-import"));
    say(`دخل ${result.count} سؤال.`, true);
    await refresh();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("question-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const again = event.submitter?.id === "question-next";
  const questionId = new FormData(form).get("question_id");
  const lessonId = new FormData(form).get("lesson_id");
  try {
    await Elmo.api(questionId ? `/api/teacher/questions/${questionId}` : `/api/teacher/bank-lessons/${lessonId}/questions`, {
      method: questionId ? "PATCH" : "POST",
      body: JSON.stringify(readPayload(form)),
    });
    say(questionId ? "السؤال اتعدل." : "السؤال دخل البنك.", true);
    await refresh();
    if (again || !questionId) openComposer(null);
    else closeModal(document.getElementById("modal-question"));
  } catch (err) {
    say(err.message);
  }
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  fillTypeSelect();
  try {
    await refresh();
  } catch (err) {
    say(err.message);
  }
});
