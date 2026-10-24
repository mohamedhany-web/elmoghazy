function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[char]));
}

const root = document.getElementById("exam-root");
const examId = root.dataset.exam;
const state = { exam: null, cursor: 0, clock: null, tabs: 0 };

function kindOf(question) {
  return question.type || (question.multiple ? "multiple" : "single");
}

function written(kind) {
  return kind === "numerical" || kind === "short" || kind === "essay" || kind === "fill_blank";
}

function field(question, review) {
  const kind = kindOf(question);
  if (written(kind)) {
    const value = esc(question.response || "");
    const placeholder = kind === "numerical" ? "اكتب الرقم" : kind === "fill_blank" ? "اكتب الإجابة" : "";
    return kind === "essay" || kind === "short"
      ? `<textarea class="exam-write" data-response rows="${kind === "essay" ? 6 : 3}"${review ? " disabled" : ""}>${value}</textarea>`
      : `<input class="exam-write" data-response value="${value}"${review ? " disabled" : ""} placeholder="${placeholder}">`;
  }
  const type = kind === "multiple" ? "checkbox" : "radio";
  const name = `q-${question.id}`;
  const selected = new Set((question.selected || []).map(String));
  return (question.options || [])
    .map((option) => {
      const mark = review && option.is_correct ? " is-right" : "";
      const chosen = selected.has(String(option.id)) ? " checked" : "";
      return `<label class="exam-choice${mark}"><input type="${type}" name="${name}" value="${option.id}" data-body="${esc(option.body)}"${chosen}${review ? " disabled" : ""}> ${esc(option.body)}</label>`;
    })
    .join("");
}

function questionBlock(question, index, review) {
  const why = review && question.explanation ? `<p class="desk-copy">${esc(question.explanation)}</p>` : "";
  const verdict = review && question.correct !== null && question.correct !== undefined
    ? `<p class="desk-copy">${question.pending ? "في انتظار تصحيح المدرس" : question.correct ? "إجابة صحيحة" : "إجابة غير مكتملة"}${question.feedback ? ` · ${esc(question.feedback)}` : ""}</p>`
    : "";
  return `<fieldset class="exam-question" data-id="${question.id}">
    <legend>${index + 1}. ${esc(question.prompt)} <small>${question.points} درجة</small></legend>
    ${field(question, review)}${verdict}${why}
  </fieldset>`;
}

function readField(box, question) {
  const kind = kindOf(question);
  if (written(kind)) {
    const value = box.querySelector("[data-response]")?.value || "";
    question.response = value;
    question.answer = value;
    return;
  }
  const chosen = [...box.querySelectorAll("input:checked")];
  question.selected = chosen.map((input) => Number(input.value));
  question.answer = chosen.map((input) => input.dataset.body || "").filter(Boolean);
  if (kind !== "multiple") question.answer = question.answer[0] || "";
}

function payload(question) {
  return {
    question_id: Number(question.id),
    option_ids: question.selected || [],
    response: question.response ?? null,
    answer: question.answer ?? null,
  };
}

function resultBanner(result) {
  const pass = result.passed === null ? "" : result.passed ? " · ناجح" : " · لسه محتاج مراجعة";
  return `<article class="desk-stat"><strong>${result.score} / ${result.total}</strong><span>${result.percent}%${pass}</span></article>`;
}

async function saveCurrent() {
  const exam = state.exam;
  const question = exam?.questions?.[state.cursor];
  const box = root.querySelector("fieldset");
  if (!question || !box) return;
  readField(box, question);
  await Elmo.api(`/api/student/exams/${examId}/save`, {
    method: "POST",
    body: JSON.stringify({ answers: [payload(question)] }),
  });
}

function drawQuestion() {
  const exam = state.exam;
  const questions = exam.questions || [];
  const question = questions[state.cursor];
  if (!question) return;
  const last = state.cursor === questions.length - 1;
  root.querySelector("#exam-stage").innerHTML = questionBlock(question, state.cursor, false);
  const position = root.querySelector("#exam-position");
  if (position) position.textContent = `سؤال ${state.cursor + 1} من ${questions.length}`;
  const next = root.querySelector("#exam-next");
  if (next) next.textContent = last ? "تسليم" : "التالي";
  const prev = root.querySelector("#exam-prev");
  if (prev) prev.disabled = state.cursor === 0;
}

async function paint() {
  window.clearInterval(state.clock);
  let exam = await Elmo.api(`/api/student/exams/${examId}`);
  if (!exam.locked && !exam.result && !exam.hidden_result && !exam.attempt) {
    await Elmo.api(`/api/student/exams/${examId}/start`, { method: "POST", body: JSON.stringify({}) });
    exam = await Elmo.api(`/api/student/exams/${examId}`);
  }
  state.exam = exam;
  if (exam.locked && !exam.questions && !exam.result) {
    root.innerHTML = `<h1 class="display">${esc(exam.title)}</h1><p class="desk-copy">${esc(exam.message || "الامتحان مش متاح.")}</p>`;
    return;
  }
  if (exam.hidden_result) {
    root.innerHTML = `<h1 class="display">${esc(exam.title)}</h1><p class="desk-copy">${esc(exam.message || "النتيجة هتظهر لاحقاً.")}</p><div class="desk-actions"><a class="btn btn-primary" href="/learn/exams">رجوع للامتحانات</a></div>`;
    return;
  }
  if (exam.result) {
    const review = (exam.result.questions || []).map((question, index) => questionBlock(question, index, true)).join("");
    const retry = exam.can_retry ? `<button type="button" class="btn btn-primary" id="retake">محاولة تانية</button>` : "";
    root.innerHTML = `<h1 class="display">${esc(exam.title)}</h1>${resultBanner(exam.result)}${review}<div class="desk-actions">${retry}<a class="btn" href="/learn/exams">كل الامتحانات</a></div><p id="exam-note" class="desk-note" hidden></p>`;
    document.getElementById("retake")?.addEventListener("click", retryExam);
    return;
  }
  const questions = exam.questions || [];
  state.cursor = Math.min(state.cursor, Math.max(questions.length - 1, 0));
  root.innerHTML = `<h1 class="display">${esc(exam.title)}</h1>
    <p class="desk-copy">${esc(exam.instructions || "")}${exam.permanent ? " الامتحان ده متاح دائمًا." : ""}${exam.duration_minutes ? ` المدة ${exam.duration_minutes} دقيقة.` : ""}${exam.prevent_tab_switch ? " الخروج من التبويب 3 مرات يسلّم الامتحان." : ""}</p>
    <p id="exam-clock" class="desk-copy" hidden></p>
    <p id="exam-position" class="desk-copy"></p>
    <div id="exam-stage"></div>
    <div class="desk-actions exam-nav">
      <button type="button" class="btn" id="exam-prev">السابق</button>
      <button type="button" class="btn btn-primary" id="exam-next">التالي</button>
    </div>
    <p id="exam-note" class="desk-note" hidden></p>`;
  drawQuestion();
  if (exam.duration_minutes && exam.attempt?.started_at) tick(exam.attempt.started_at, exam.duration_minutes);
  if (exam.prevent_tab_switch) watchTabs();
  document.getElementById("exam-prev").addEventListener("click", async () => {
    try {
      await saveCurrent();
      state.cursor = Math.max(0, state.cursor - 1);
      drawQuestion();
    } catch (err) {
      note(err.message);
    }
  });
  document.getElementById("exam-next").addEventListener("click", async () => {
    try {
      await saveCurrent();
      if (state.cursor >= (state.exam.questions || []).length - 1) {
        await deliver(false);
        return;
      }
      state.cursor += 1;
      drawQuestion();
    } catch (err) {
      if (String(err.message).includes("الوقت")) {
        await paint();
        return;
      }
      note(err.message);
    }
  });
  root.querySelector("#exam-stage").addEventListener("change", () => {
    saveCurrent().catch((err) => note(err.message));
  });
}

function note(message) {
  const line = document.getElementById("exam-note");
  if (!line) return;
  line.hidden = false;
  line.textContent = message;
}

function tick(started, minutes) {
  const clock = document.getElementById("exam-clock");
  if (!clock) return;
  clock.hidden = false;
  const end = new Date(started).getTime() + minutes * 60000;
  const draw = () => {
    const left = Math.max(0, end - Date.now());
    const mins = Math.floor(left / 60000);
    const secs = Math.floor((left % 60000) / 1000);
    clock.textContent = `الوقت المتبقي ${mins}:${String(secs).padStart(2, "0")}`;
    if (left <= 0) {
      window.clearInterval(state.clock);
      deliver(true);
    }
  };
  draw();
  state.clock = window.setInterval(draw, 1000);
}

function watchTabs() {
  if (state.tabs) return;
  state.tabs = 1;
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState !== "hidden" || !state.exam?.prevent_tab_switch || state.exam.result) return;
    Elmo.api(`/api/student/exams/${examId}/tab`, { method: "POST", body: JSON.stringify({}) })
      .then((result) => {
        if (result.submitted) paint();
      })
      .catch(() => {});
  });
}

async function deliver(auto) {
  const box = root.querySelector("fieldset");
  const question = state.exam?.questions?.[state.cursor];
  if (box && question) readField(box, question);
  try {
    if (question) {
      await Elmo.api(`/api/student/exams/${examId}/save`, {
        method: "POST",
        body: JSON.stringify({ answers: [payload(question)] }),
      });
    }
  } catch (err) {
    if (!String(err.message).includes("الوقت")) note(err.message);
  }
  try {
    await Elmo.api(`/api/student/exams/${examId}/submit`, {
      method: "POST",
      body: JSON.stringify({
        answers: (state.exam.questions || []).map(payload),
        auto,
      }),
    });
    state.cursor = 0;
    await paint();
  } catch (err) {
    if (String(err.message).includes("الوقت")) {
      await paint();
      return;
    }
    note(err.message);
  }
}

async function retryExam() {
  try {
    await Elmo.api(`/api/student/exams/${examId}/start`, { method: "POST", body: JSON.stringify({}) });
    state.cursor = 0;
    await paint();
  } catch (err) {
    note(err.message);
  }
}

Elmo.onReady(async (user) => {
  if (!user) {
    window.location.href = "/login";
    return;
  }
  if (user.role === "teacher") {
    window.location.href = `/admin/exams/${examId}`;
    return;
  }
  try {
    await paint();
  } catch (err) {
    root.innerHTML = `<p class="desk-copy">${esc(err.message)}</p>`;
  }
});
