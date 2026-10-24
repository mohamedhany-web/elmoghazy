const { esc, say, openModal, closeModal, state } = Admin;

const yearId = Number(new URLSearchParams(location.search).get("year")) || null;
let grades = [];
let catalog = [];

function lockValue(value) {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  const pad = (part) => String(part).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

async function loadMonths() {
  const [monthRows, gradeRows] = await Promise.all([Elmo.api("/api/teacher/months"), Elmo.api("/api/grades")]);
  grades = gradeRows;
  catalog = monthRows;
  const select = document.querySelector('#month-form [name="grade"]');
  const current = select.value;
  select.innerHTML = grades.map((grade) => `<option value="${esc(grade.slug)}">${esc(grade.title)}</option>`).join("");
  const yearSelect = document.getElementById("month-year");
  const picked = yearSelect.value || (yearId ? String(yearId) : "");
  yearSelect.innerHTML = `<option value="">كل السنوات</option>${grades.map((item) => `<option value="${item.id}">${esc(item.title)}</option>`).join("")}`;
  if (picked) yearSelect.value = picked;
  const grade = grades.find((item) => String(item.id) === yearSelect.value) || null;
  if (grade) select.value = grade.slug;
  else if (current) select.value = current;
  const filter = document.getElementById("month-filter");
  const clear = document.getElementById("month-filter-clear");
  filter.hidden = !grade;
  clear.hidden = !grade;
  filter.textContent = grade ? `شهور ${grade.title}` : "";
  const host = document.getElementById("month-board");
  host.innerHTML = monthRows.length
    ? `<table class="cs-table"><thead><tr><th>الشهر</th><th>السنة</th><th>أقسام</th><th>فيديو</th><th>الحالة</th><th></th></tr></thead><tbody>${monthRows
        .map((month) => `<tr data-year="${month.grade_id || ""}" data-status="${month.published ? "published" : "hidden"}" data-search="${esc(month.title)} ${esc(month.grade)} ${esc(month.description)}">
            <td>${esc(month.title)}</td>
            <td>${esc(month.grade || "—")}</td>
            <td>${esc(month.unit_count)}</td>
            <td>${esc(month.video_count)}</td>
            <td>${state(!!month.published, "منشور", "مخفي")}</td>
            <td class="cs-row-acts">
              <a class="cs-btn cs-btn--primary" href="/admin/months/${month.id}">التفاصيل</a>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="month-edit" data-month="${month.id}">تعديل</button>
              <button type="button" class="cs-btn cs-btn--ghost" data-act="month-publish" data-month="${month.id}" data-published="${month.published ? "1" : "0"}">${month.published ? "إخفاء" : "نشر"}</button>
              <button type="button" class="cs-btn cs-btn--danger" data-act="month-delete" data-month="${month.id}">حذف</button>
            </td>
          </tr>`)
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش شهور. اختار السنة وأضف الشهر وصورته.</p></div>`;
  Admin.applyFilters();
  host.querySelectorAll("[data-cover]").forEach((node) => {
    if (!node.dataset.cover) return;
    Elmo.fetchBlob(`/api/teacher/months/${node.dataset.cover}/cover?w=640`)
      .then((blob) => {
        node.style.backgroundImage = `url(${URL.createObjectURL(blob)})`;
      })
      .catch(() => {});
  });
}

document.getElementById("month-year").addEventListener("change", () => {
  const selected = grades.find((item) => String(item.id) === document.getElementById("month-year").value);
  const filter = document.getElementById("month-filter");
  const clear = document.getElementById("month-filter-clear");
  filter.hidden = !selected;
  clear.hidden = !selected;
  filter.textContent = selected ? `شهور ${selected.title}` : "";
  const formSelect = document.querySelector('#month-form [name="grade"]');
  if (selected && formSelect) formSelect.value = selected.slug;
});

document.getElementById("year-add-month").addEventListener("click", () => {
  const form = document.getElementById("month-form");
  form.reset();
  form.querySelector('[name="id"]').value = "";
  document.getElementById("month-form-title").textContent = "شهر جديد";
  openModal("modal-month");
});

document.getElementById("month-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const id = data.get("id");
  const payload = {
    grade: data.get("grade"),
    title: data.get("title"),
    description: data.get("description") || null,
    published: form.querySelector('[name="published"]').checked,
    locks_at: data.get("locks_at") || null,
  };
  try {
    if (id) {
      await Elmo.api(`/api/teacher/months/${id}`, { method: "PATCH", body: JSON.stringify(payload) });
      const cover = data.get("cover");
      if (cover && cover.size) {
        const fileData = new FormData();
        fileData.append("cover", cover);
        await Elmo.upload(`/api/teacher/months/${id}/cover`, fileData);
      }
    } else {
      data.set("published", payload.published ? "1" : "0");
      const cover = data.get("cover");
      if (!cover || !cover.size) data.delete("cover");
      if (!data.get("locks_at")) data.delete("locks_at");
      data.delete("id");
      await Elmo.upload("/api/teacher/months", data);
    }
    form.reset();
    closeModal(form.closest(".cs-modal"));
    say("اتحفظ الشهر.", true);
    await loadMonths();
  } catch (err) {
    say(err.message);
    openModal("modal-month");
  }
});

document.getElementById("desk").addEventListener("click", async (event) => {
  const button = event.target.closest("[data-act]");
  if (!button) return;
  if (button.dataset.act === "month-edit") {
    const month = catalog.find((item) => String(item.id) === button.dataset.month);
    if (!month) return;
    const form = document.getElementById("month-form");
    form.querySelector('[name="id"]').value = month.id;
    form.querySelector('[name="grade"]').value = month.grade_slug || "";
    form.querySelector('[name="title"]').value = month.title || "";
    form.querySelector('[name="description"]').value = month.description || "";
    form.querySelector('[name="locks_at"]').value = lockValue(month.locks_at);
    form.querySelector('[name="published"]').checked = !!month.published;
    document.getElementById("month-form-title").textContent = "تعديل الشهر";
    openModal("modal-month");
    return;
  }
  try {
    if (button.dataset.act === "month-publish") {
      await Elmo.api(`/api/teacher/months/${button.dataset.month}`, {
        method: "PATCH",
        body: JSON.stringify({ published: button.dataset.published !== "1" }),
      });
    } else if (button.dataset.act === "month-delete") {
      await Elmo.api(`/api/teacher/months/${button.dataset.month}`, { method: "DELETE" });
    } else {
      return;
    }
    say("تم.", true);
    await loadMonths();
  } catch (err) {
    say(err.message);
  }
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  await loadMonths();
});
