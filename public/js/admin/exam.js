const { esc, say } = Admin;
const examId = window.EXAM_ID;
const picked = new Map();
let examGrade = null;
let pickPage = 1;
let pickTimer = null;

function lockValue(value) {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  const pad = (part) => String(part).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function toggleClose() {
  const permanent = document.querySelector('#exam-settings [name="permanent"]').checked;
  document.getElementById("settings-close").hidden = permanent;
}

function render(detail) {
  const exam = detail.exam;
  document.getElementById("exam-title").textContent = exam.title;
  document.getElementById("exam-crumb").textContent = exam.title;
  document.getElementById("exam-copy").textContent = `${exam.grade} · ${exam.permanent ? "دائم" : "له موعد إغلاق"}`;
  const form = document.getElementById("exam-settings");
  form.querySelector('[name="title"]').value = exam.title || "";
  form.querySelector('[name="instructions"]').value = exam.instructions || "";
  form.querySelector('[name="duration_minutes"]').value = exam.duration_minutes || "";
  form.querySelector('[name="pass_percent"]').value = exam.pass_percent || "";
  form.querySelector('[name="attempts_allowed"]').value = exam.attempts_allowed ?? 0;
  form.querySelector('[name="permanent"]').checked = Boolean(exam.permanent);
  form.querySelector('[name="shuffle_questions"]').checked = Boolean(exam.shuffle_questions);
  form.querySelector('[name="shuffle_choices"]').checked = Boolean(exam.shuffle_choices);
  form.querySelector('[name="show_results"]').checked = exam.show_results !== false;
  form.querySelector('[name="show_answers"]').checked = exam.show_answers !== false;
  form.querySelector('[name="show_explanations"]').checked = exam.show_explanations !== false;
  form.querySelector('[name="allow_review"]').checked = exam.allow_review !== false;
  form.querySelector('[name="prevent_tab_switch"]').checked = Boolean(exam.prevent_tab_switch);
  form.querySelector('[name="opens_at"]').value = lockValue(exam.opens_at);
  form.querySelector('[name="closes_at"]').value = lockValue(exam.closes_at);
  form.querySelector('[name="study_month_id"]').value = exam.study_month_id || "";
  toggleClose();
  const publish = document.getElementById("exam-publish");
  publish.dataset.published = exam.published ? "1" : "0";
  publish.textContent = exam.published ? "إخفاء" : "نشر";

  picked.clear();
  (exam.questions || []).forEach((question) => picked.set(String(question.id), question.points || 1));
  paintPickedCount();
}

function rememberPicks() {
  document.querySelectorAll("[data-pick]").forEach((box) => {
    const id = box.dataset.pick;
    const points = Number(document.querySelector(`[data-points="${id}"]`)?.value || 1);
    if (box.checked) picked.set(id, points);
    else picked.delete(id);
  });
  paintPickedCount();
}

function paintPickedCount() {
  document.getElementById("pick-count").textContent = `أسئلة البنك · ${picked.size} مختارة`;
}

function renderPicks(result) {
  const body = document.getElementById("pick-list");
  body.innerHTML = result.data.length
    ? result.data
        .map((question) => {
          const id = String(question.id);
          return `<tr>
            <td><input type="checkbox" data-pick="${id}" ${picked.has(id) ? "checked" : ""}></td>
            <td class="bank-prompt">${esc(question.prompt)}</td>
            <td>${esc(question.unit)} · ${esc(question.lesson)}</td>
            <td><input class="cs-input" data-points="${id}" type="number" min="0.5" max="100" step="0.5" value="${picked.get(id) || 1}"></td>
          </tr>`;
        })
        .join("")
    : `<tr><td colspan="4" class="cs-empty">مفيش أسئلة في العرض ده. <a href="/admin/banks/${examGrade}">افتح البنك</a></td></tr>`;
  const pager = document.getElementById("pick-pager");
  pager.innerHTML = result.total
    ? `<button type="button" class="cs-btn cs-btn--ghost" data-pick-page="${result.page - 1}" ${result.page <= 1 ? "disabled" : ""}>السابق</button>
       <span>صفحة ${result.page} من ${result.last_page} · ${result.total} سؤال</span>
       <button type="button" class="cs-btn cs-btn--ghost" data-pick-page="${result.page + 1}" ${result.page >= result.last_page ? "disabled" : ""}>التالي</button>`
    : "";
}

async function loadPicks() {
  if (!examGrade) return;
  const params = new URLSearchParams({ page: String(pickPage) });
  const term = document.getElementById("pick-search").value.trim();
  const unit = document.getElementById("pick-unit").value;
  if (term) params.set("q", term);
  if (unit) params.set("unit", unit);
  renderPicks(await Elmo.api(`/api/teacher/banks/${examGrade}/questions?${params}`));
}

async function loadOutline() {
  const outline = await Elmo.api(`/api/teacher/banks/${examGrade}`);
  const current = document.getElementById("pick-unit").value;
  document.getElementById("pick-unit").innerHTML = `<option value="">كل الوحدات</option>${outline.units
    .map((unit) => `<option value="${unit.id}">${esc(unit.title)} · ${unit.questions}</option>`)
    .join("")}`;
  document.getElementById("pick-unit").value = current;
  const generateUnit = document.getElementById("generate-unit");
  const generateCurrent = generateUnit.value;
  generateUnit.innerHTML = document.getElementById("pick-unit").innerHTML;
  generateUnit.value = generateCurrent;
}

async function loadAttempts() {
  const rows = await Elmo.api(`/api/teacher/exams/${examId}/attempts`);
  const host = document.getElementById("attempt-list");
  const stats = rows.stats || {};
  const list = rows.rows || [];
  if (!list.length) {
    host.innerHTML = `<p class="cs-empty">لسه مفيش تسليمات.</p>`;
    return;
  }
  const summary = stats.count
    ? `<p class="cs-copy">التسليمات ${stats.count} · المتوسط ${stats.average ?? "—"} · الأعلى ${stats.highest ?? "—"} · الأقل ${stats.lowest ?? "—"}${stats.pass_rate === null ? "" : ` · النجاح ${stats.pass_rate}%`}</p>`
    : "";
  host.innerHTML = summary + list.map((attempt) => {
    const pending = (attempt.pending || []).map((answer) => `<form class="builder-block" data-grade="${answer.id}">
      <strong>${esc(answer.prompt)}</strong>
      <p>${esc(answer.response || "من غير إجابة")}</p>
      ${answer.model ? `<p>النموذج: ${esc(answer.model)}</p>` : ""}
      <label class="cs-field"><span class="cs-label">الدرجة من ${answer.max_points}</span><input class="cs-input" name="awarded" type="number" min="0" max="${answer.max_points}" step="0.25" value="0"></label>
      <label class="cs-field"><span class="cs-label">ملاحظة</span><input class="cs-input" name="feedback"></label>
      <button type="submit" class="cs-btn cs-btn--primary">حفظ التصحيح</button>
    </form>`).join("");
    return `<article class="builder-block"><strong>${esc(attempt.student)}</strong><p>${attempt.score} / ${attempt.total}</p>${pending || ""}</article>`;
  }).join("");
}

async function loadMonths(gradeId, selected) {
  const months = await Elmo.api("/api/teacher/months");
  const select = document.querySelector('#exam-settings [name="study_month_id"]');
  select.innerHTML = `<option value="">من غير شهر</option>${months
    .filter((month) => String(month.grade_id) === String(gradeId))
    .map((month) => `<option value="${month.id}">${esc(month.title)}</option>`)
    .join("")}`;
  select.value = selected || "";
}

async function load() {
  const detail = await Elmo.api(`/api/teacher/exams/${examId}`);
  examGrade = detail.exam.grade_id;
  await loadMonths(examGrade, detail.exam.study_month_id);
  render(detail);
  await loadOutline();
  await loadPicks();
  await loadAttempts();
}

document.querySelector('#exam-settings [name="permanent"]').addEventListener("change", toggleClose);

document.getElementById("exam-settings").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const permanent = data.get("permanent") === "on";
  try {
    await Elmo.api(`/api/teacher/exams/${examId}`, {
      method: "PATCH",
      body: JSON.stringify({
        title: data.get("title"),
        instructions: data.get("instructions") || null,
        permanent,
        shuffle_questions: data.get("shuffle_questions") === "on",
        shuffle_choices: data.get("shuffle_choices") === "on",
        show_results: data.get("show_results") === "on",
        show_answers: data.get("show_answers") === "on",
        show_explanations: data.get("show_explanations") === "on",
        allow_review: data.get("allow_review") === "on",
        prevent_tab_switch: data.get("prevent_tab_switch") === "on",
        duration_minutes: data.get("duration_minutes") ? Number(data.get("duration_minutes")) : null,
        pass_percent: data.get("pass_percent") ? Number(data.get("pass_percent")) : null,
        attempts_allowed: Number(data.get("attempts_allowed") || 0),
        opens_at: data.get("opens_at") || null,
        study_month_id: data.get("study_month_id") || null,
        closes_at: permanent ? null : data.get("closes_at") || null,
      }),
    });
    say("الإعدادات اتحفظت.", true);
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("pick-search").addEventListener("input", () => {
  window.clearTimeout(pickTimer);
  pickTimer = window.setTimeout(() => {
    rememberPicks();
    pickPage = 1;
    loadPicks().catch((err) => say(err.message));
  }, 250);
});

document.getElementById("pick-unit").addEventListener("change", () => {
  rememberPicks();
  pickPage = 1;
  loadPicks().catch((err) => say(err.message));
});

document.getElementById("desk").addEventListener("click", (event) => {
  const button = event.target.closest("[data-pick-page]");
  if (!button || button.disabled) return;
  rememberPicks();
  pickPage = Number(button.dataset.pickPage);
  loadPicks().catch((err) => say(err.message));
});

document.getElementById("pick-list").addEventListener("change", rememberPicks);

document.getElementById("save-picks").addEventListener("click", async () => {
  rememberPicks();
  const questions = [...picked.entries()].map(([id, points]) => ({
    id: Number(id),
    points: Number(points || 1),
  }));
  try {
    await Elmo.api(`/api/teacher/exams/${examId}/questions`, {
      method: "PUT",
      body: JSON.stringify({ questions }),
    });
    say("الأسئلة اتسجلت على الامتحان.", true);
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("generate-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const data = new FormData(event.currentTarget);
  try {
    const result = await Elmo.api(`/api/teacher/exams/${examId}/generate`, {
      method: "POST",
      body: JSON.stringify({
        unit: data.get("unit") || null,
        difficulty: data.get("difficulty") || null,
        count: Number(data.get("count") || 5),
      }),
    });
    say(`اتضاف ${result.count} سؤال.`, true);
    await load();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("attempt-list").addEventListener("submit", async (event) => {
  const form = event.target.closest("[data-grade]");
  if (!form) return;
  event.preventDefault();
  const data = new FormData(form);
  try {
    await Elmo.api(`/api/teacher/answers/${form.dataset.grade}`, {
      method: "PATCH",
      body: JSON.stringify({ awarded: Number(data.get("awarded") || 0), feedback: data.get("feedback") || null }),
    });
    say("التصحيح اتحفظ.", true);
    await loadAttempts();
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("exam-publish").addEventListener("click", async () => {
  const button = document.getElementById("exam-publish");
  try {
    await Elmo.api(`/api/teacher/exams/${examId}`, {
      method: "PATCH",
      body: JSON.stringify({ published: button.dataset.published !== "1" }),
    });
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
