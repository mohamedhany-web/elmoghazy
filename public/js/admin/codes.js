const { esc, when, say, openModal, closeModal, state } = Admin;

let page = 1;
let lastPage = 1;
let batchPage = 1;
let batchLastPage = 1;
let searchTimer = 0;
let pickTimer = 0;
let activeBatch = null;
let activeRows = [];

const STATUS = { open: "متاح", used: "مستخدم", expired: "منتهي", revoked: "ملغي" };

function typeLabel(type) {
  return type === "parent" ? "ولي أمر" : "شهر";
}

function stamp(value) {
  if (!value) return "—";
  try {
    return new Intl.DateTimeFormat("ar-EG", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    }).format(new Date(value));
  } catch {
    return when(value);
  }
}

function codeTable(rows) {
  return rows.length
    ? `<table class="cs-table"><thead><tr><th>آخر الكود</th><th>الشهر</th><th>الطالب</th><th>صلاحية الكود</th><th>ظهور الشهر</th><th>الحالة</th><th></th></tr></thead><tbody>${rows
        .map(
          (row) => `<tr data-id="${row.id}">
            <td dir="ltr">••••${esc(row.hint || "")}</td>
            <td>${esc(row.month || "—")}</td>
            <td>${row.student_id ? `<a href="/admin/students/${row.student_id}">${esc(row.student)}</a>` : "—"}</td>
            <td>${esc(when(row.expires_at))}</td>
            <td>${row.duration_days ? `${esc(row.duration_days)} يوم` : "—"}</td>
            <td>${state(row.status === "open", STATUS[row.status] || row.status, STATUS[row.status] || row.status)}</td>
            <td class="cs-row-acts">${row.status === "open" ? `<button type="button" class="cs-btn cs-btn--danger" data-act="revoke">إلغاء</button>` : ""}</td>
          </tr>`,
        )
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>${document.getElementById("code-search")?.value.trim() || document.getElementById("code-status")?.value ? "مفيش أكواد مطابقة." : "لسه مفيش أكواد."}</p></div>`;
}

function batchTable(rows) {
  return rows.length
    ? `<table class="cs-table"><thead><tr><th>التاريخ</th><th>النوع</th><th>العنوان</th><th>العدد</th><th>متاح</th><th>مستخدم</th><th>ملغي</th><th>للطباعة</th><th></th></tr></thead><tbody>${rows
        .map(
          (row) => `<tr data-batch="${row.id}" class="${activeBatch && activeBatch.id === row.id ? "is-on" : ""}">
            <td>${esc(stamp(row.created_at))}</td>
            <td>${esc(typeLabel(row.type))}</td>
            <td>${esc(row.label || "—")}</td>
            <td>${esc(row.quantity)}</td>
            <td>${esc(row.open)}</td>
            <td>${esc(row.used)}</td>
            <td>${esc(row.revoked)}</td>
            <td>${row.printable ? "نعم" : "لا — قديمة"}</td>
            <td class="cs-row-acts">
              <button type="button" class="cs-btn cs-btn--primary" data-act="open-batch">${row.printable ? "عرض وطباعة" : "عرض"}</button>
              <button type="button" class="cs-btn cs-btn--danger" data-act="delete-batch">حذف</button>
            </td>
          </tr>`,
        )
        .join("")}</tbody></table>`
    : `<div class="cs-empty"><p>لسه مفيش دفعات توليد.</p></div>`;
}

function sheetHtml(batch, rows) {
  const kind = typeLabel(batch.type);
  const body = rows
    .map(
      (row, index) => `<tr>
        <td class="cs-mint-num">${index + 1}</td>
        <td class="cs-mint-code" dir="ltr">${esc(row.code || "")}</td>
        <td class="cs-mint-status">${esc(STATUS[row.status] || row.status || "—")}</td>
        <td>${esc(row.student || "—")}</td>
        <td>${row.expires_at ? esc(stamp(row.expires_at)) : "—"}</td>
      </tr>`,
    )
    .join("");

  return `
    <header class="cs-mint-print__head">
      <h3>أكاديمية المغازي — ورقة أكواد</h3>
      <p>${esc(batch.label || "دفعة أكواد")} · ${esc(kind)}</p>
    </header>
    <ul class="cs-mint-print__line">
      <li>التاريخ: <strong>${esc(stamp(batch.created_at))}</strong></li>
      <li>العدد: <strong>${esc(batch.quantity || rows.length)}</strong></li>
      <li>متاح: <strong>${esc(batch.open ?? rows.filter((row) => row.status === "open").length)}</strong></li>
      <li>مستخدم: <strong>${esc(batch.used ?? rows.filter((row) => row.status === "used").length)}</strong></li>
      <li>ملغي: <strong>${esc(batch.revoked ?? rows.filter((row) => row.status === "revoked").length)}</strong></li>
      ${batch.code_valid_days ? `<li>صلاحية الكود: <strong>${esc(batch.code_valid_days)} يوم</strong></li>` : ""}
      ${batch.duration_days ? `<li>مدة الظهور: <strong>${esc(batch.duration_days)} يوم</strong></li>` : ""}
    </ul>
    <div class="cs-mint-table-wrap">
      <table class="cs-mint-table">
        <thead>
          <tr>
            <th class="cs-mint-num">م</th>
            <th>الكود</th>
            <th>الحالة</th>
            <th>الطالب</th>
            <th>انتهاء الصلاحية</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    </div>
    <p class="cs-mint-print__note">الكود مرة واحدة وعلى جهاز واحد. الورقة تقدر تتعيد طباعتها من دفعات التوليد في أي وقت.</p>`;
}

function paintSheet(batch, rows) {
  activeBatch = batch;
  activeRows = rows;
  const host = document.getElementById("code-print-sheet");
  const panel = document.getElementById("sheet");
  if (!host || !panel) return;
  const canPrint = Boolean(batch.printable);
  document.getElementById("sheet-title").textContent = `دفعة #${batch.id}`;
  document.getElementById("sheet-copy").textContent = canPrint
    ? `${batch.label || "—"} · ${rows.length} كود جاهز للتوزيع والطباعة`
    : `${batch.label || "—"} · الدفعة قديمة والكود الكامل مش متاح. ولّد دفعة جديدة عشان توزّع.`;
  host.innerHTML =
    (canPrint
      ? ""
      : `<p class="cs-mint-print__note" style="color:#b42318;margin-bottom:12px">الأكواد الكاملة مش محفوظة في الدفعة دي لأنها اتولدت قبل تحديث الطباعة. مش هينفع توزّعها من هنا — اعمل توليد جديد وهتظهر الأكواد كاملة في الجدول والطباعة.</p>`) +
    sheetHtml(batch, rows);
  document.getElementById("code-print").disabled = !canPrint;
  document.getElementById("code-export").disabled = !canPrint;
  document.getElementById("code-copy").disabled = !canPrint;
  panel.hidden = false;
  panel.scrollIntoView({ behavior: "smooth", block: "start" });
  loadBatches(batchPage).catch(() => {});
}

async function openBatch(id) {
  const batch = await Elmo.api(`/api/teacher/code-batches/${id}`);
  paintSheet(batch, batch.codes || []);
}

async function loadBatches(nextPage = batchPage) {
  batchPage = Math.max(1, nextPage);
  const result = await Elmo.api(`/api/teacher/code-batches?page=${batchPage}`);
  const rows = result.data || [];
  batchLastPage = result.last_page || 1;
  batchPage = result.current_page || batchPage;
  document.getElementById("batch-list").innerHTML = batchTable(rows);
  const pager = document.getElementById("batch-pager");
  pager.hidden = (result.total || 0) <= 20;
  document.getElementById("batch-pager-label").textContent = `${batchPage} / ${batchLastPage} · ${result.total || 0} دفعة`;
  pager.querySelector('[data-batch-page="prev"]').disabled = batchPage <= 1;
  pager.querySelector('[data-batch-page="next"]').disabled = batchPage >= batchLastPage;
}

async function loadCodes(nextPage = page) {
  page = Math.max(1, nextPage);
  const params = new URLSearchParams({ page: String(page) });
  const q = document.getElementById("code-search").value.trim();
  const status = document.getElementById("code-status").value;
  if (q) params.set("q", q);
  if (status) params.set("status", status);
  const result = await Elmo.api(`/api/teacher/codes?${params}`);
  const rows = result.data || [];
  lastPage = result.last_page || 1;
  page = result.current_page || page;
  document.getElementById("code-list").innerHTML = codeTable(rows);
  const pager = document.getElementById("code-pager");
  pager.hidden = (result.total || 0) <= 25;
  document.getElementById("code-pager-label").textContent = `${page} / ${lastPage} · ${result.total || 0} كود`;
  pager.querySelector('[data-code-page="prev"]').disabled = page <= 1;
  pager.querySelector('[data-code-page="next"]').disabled = page >= lastPage;
}

async function fillMonths() {
  const months = await Elmo.api("/api/teacher/months");
  document.querySelector('#code-form [name="study_month_id"]').innerHTML = months
    .map((month) => `<option value="${month.id}">${esc(month.title)}${month.grade ? ` · ${esc(month.grade)}` : ""}</option>`)
    .join("");
}

function toggleKind() {
  const parent = document.querySelector('#code-form [name="type"]').value === "parent";
  document.querySelector("[data-code-month]").hidden = parent;
  document.querySelector("[data-code-parent]").hidden = !parent;
}

function sheetXml(rows, batch) {
  const header = ["م", "الكود", "الحالة", "الطالب", "العنوان"];
  const cells = (values) =>
    values
      .map((value) => `<Cell><Data ss:Type="String">${String(value ?? "").replace(/[&<>]/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;" }[char]))}</Data></Cell>`)
      .join("");
  const body = rows
    .map((row, index) => `<Row>${cells([index + 1, row.code, STATUS[row.status] || row.status, row.student || "", batch?.label || ""])}</Row>`)
    .join("");
  return `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<Worksheet ss:Name="الأكواد" ss:RightToLeft="1"><Table>
<Row>${cells(header)}</Row>${body}
</Table></Worksheet></Workbook>`;
}

function printSheet() {
  if (!activeBatch || !activeRows.length) return;
  if (!activeBatch.printable) {
    say("الدفعة قديمة ومفيهاش الأكواد الكاملة. ولّد دفعة جديدة للتوزيع.");
    return;
  }
  const html = `<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>أكواد — ${esc(activeBatch.label || activeBatch.id)}</title>
  <style>
    @page { size: A4; margin: 12mm; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      color: #142232;
      font-family: "IBM Plex Sans Arabic", Oxygen, "Helvetica Neue", Arial, sans-serif;
      background: #fff;
    }
    .cs-mint-print__head { border-bottom: 2px solid #142232; padding-bottom: 8px; margin-bottom: 10px; }
    .cs-mint-print__head h3 { margin: 0; font-size: 18pt; }
    .cs-mint-print__head p { margin: 4px 0 0; color: #5b6b7c; font-size: 11pt; }
    .cs-mint-print__line { display: flex; flex-wrap: wrap; gap: 6px 16px; margin: 0 0 12px; padding: 0; list-style: none; font-size: 10.5pt; }
    .cs-mint-table-wrap { overflow: visible; }
    .cs-mint-table { width: 100%; border-collapse: collapse; }
    .cs-mint-table thead { display: table-header-group; }
    .cs-mint-table tr { page-break-inside: avoid; break-inside: avoid; }
    .cs-mint-table th, .cs-mint-table td { border: 1px solid #98a8b6; padding: 7px 8px; text-align: right; font-size: 10.5pt; vertical-align: middle; }
    .cs-mint-table th { background: #142232; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .cs-mint-table tbody tr:nth-child(even) { background: #f3f7f9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .cs-mint-num { width: 42px; text-align: center; }
    .cs-mint-code { direction: ltr; text-align: left; font-weight: 700; letter-spacing: 0.04em; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; white-space: nowrap; }
    .cs-mint-print__note { margin-top: 10px; color: #5b6b7c; font-size: 9.5pt; }
  </style>
</head>
<body>${sheetHtml(activeBatch, activeRows)}</body>
</html>`;

  document.getElementById("code-print-frame")?.remove();
  const frame = document.createElement("iframe");
  frame.id = "code-print-frame";
  frame.setAttribute("aria-hidden", "true");
  frame.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;";
  document.body.appendChild(frame);

  const run = () => {
    try {
      frame.contentWindow.focus();
      frame.contentWindow.print();
    } catch {
      say("تعذر فتح الطباعة. جرّب تاني.");
    }
    setTimeout(() => frame.remove(), 1500);
  };

  frame.onload = () => setTimeout(run, 50);
  frame.srcdoc = html;
}

document.querySelector('#code-form [name="type"]').addEventListener("change", toggleKind);

document.getElementById("parent-student-search").addEventListener("input", () => {
  clearTimeout(pickTimer);
  pickTimer = setTimeout(async () => {
    const q = document.getElementById("parent-student-search").value.trim();
    const host = document.getElementById("parent-student-picks");
    if (!q) {
      host.innerHTML = "";
      return;
    }
    const result = await Elmo.api(`/api/teacher/students?q=${encodeURIComponent(q)}&page=1`);
    const rows = result.data || [];
    host.innerHTML = rows.length
      ? `<div class="cs-picks">${rows.map((student) => `<button type="button" class="cs-btn cs-btn--ghost" data-pick-student="${student.id}" data-pick-name="${esc(student.name)}">${esc(student.name)}</button>`).join("")}</div>`
      : `<p class="outline-empty">مفيش طالب بالاسم ده.</p>`;
  }, 250);
});

document.getElementById("parent-student-picks").addEventListener("click", (event) => {
  const button = event.target.closest("[data-pick-student]");
  if (!button) return;
  document.querySelector('#code-form [name="user_id"]').value = button.dataset.pickStudent;
  document.getElementById("parent-student-name").textContent = button.dataset.pickName;
  document.getElementById("parent-student-picks").innerHTML = "";
  document.getElementById("parent-student-search").value = button.dataset.pickName;
});

document.getElementById("code-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const data = new FormData(form);
  const type = data.get("type");
  const payload = { type };
  if (type === "parent") {
    if (!data.get("user_id")) {
      say("اختار الطالب الأول.");
      openModal("modal-code");
      return;
    }
    payload.user_id = Number(data.get("user_id"));
    payload.count = 1;
  } else {
    payload.study_month_id = Number(data.get("study_month_id"));
    payload.count = Number(data.get("count") || 1);
    payload.code_valid_days = Number(data.get("code_valid_days") || 30);
    payload.duration_days = Number(data.get("duration_days") || 30);
  }
  try {
    const result = await Elmo.api("/api/teacher/codes", { method: "POST", body: JSON.stringify(payload) });
    closeModal(form.closest(".cs-modal"));
    say("اتصدرت الدفعة. تقدر تطبعها وتعيد فتحها في أي وقت.", true);
    await loadBatches(1);
    await loadCodes(1);
    if (result.batch_id) await openBatch(result.batch_id);
  } catch (err) {
    say(err.message);
    openModal("modal-code");
  }
});

document.getElementById("code-export").addEventListener("click", () => {
  if (!activeRows.length) return;
  const blob = new Blob([sheetXml(activeRows, activeBatch)], { type: "application/vnd.ms-excel" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `أكواد-دفعة-${activeBatch?.id || "جديدة"}.xls`;
  link.click();
  setTimeout(() => URL.revokeObjectURL(url), 1500);
});

document.getElementById("code-copy").addEventListener("click", async () => {
  if (!activeRows.length) return;
  const text = activeRows.map((row, index) => `${index + 1}\t${row.code}\t${STATUS[row.status] || ""}\t${row.student || ""}`).join("\n");
  try {
    await navigator.clipboard.writeText(text);
    say("اتنسخت الأكواد.", true);
  } catch {
    say("تعذر النسخ. انسخ من الجدول يدويًا.");
  }
});

document.getElementById("code-print").addEventListener("click", printSheet);

document.getElementById("desk").addEventListener("click", async (event) => {
  const open = event.target.closest("[data-act='open-batch']");
  if (open) {
    const id = open.closest("[data-batch]")?.dataset.batch;
    try {
      await openBatch(id);
    } catch (err) {
      say(err.message);
    }
    return;
  }
  const remove = event.target.closest("[data-act='delete-batch']");
  if (remove) {
    const id = remove.closest("[data-batch]")?.dataset.batch;
    if (!id) return;
    if (!window.confirm("حذف المجموعة دي؟ الأكواد المتاحة هتتلغي والدفعة هتتمسح.")) return;
    try {
      await Elmo.api(`/api/teacher/code-batches/${id}`, { method: "DELETE" });
      if (activeBatch && String(activeBatch.id) === String(id)) {
        activeBatch = null;
        activeRows = [];
        const panel = document.getElementById("sheet");
        if (panel) panel.hidden = true;
      }
      say("اتمسحت المجموعة.", true);
      await Promise.all([loadBatches(batchPage), loadCodes(1)]);
    } catch (err) {
      say(err.message);
    }
    return;
  }
  const button = event.target.closest("[data-act='revoke']");
  if (!button) return;
  const id = button.closest("[data-id]")?.dataset.id;
  try {
    await Elmo.api(`/api/teacher/codes/${id}/revoke`, { method: "POST" });
    say("تم.", true);
    await loadCodes();
    if (activeBatch?.id) await openBatch(activeBatch.id);
    await loadBatches(batchPage);
  } catch (err) {
    say(err.message);
  }
});

document.getElementById("code-search").addEventListener("input", () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadCodes(1).catch((err) => say(err.message)), 250);
});
document.getElementById("code-status").addEventListener("change", () => loadCodes(1).catch((err) => say(err.message)));
document.getElementById("code-pager").addEventListener("click", (event) => {
  const button = event.target.closest("[data-code-page]");
  if (!button || button.disabled) return;
  loadCodes(button.dataset.codePage === "prev" ? page - 1 : page + 1).catch((err) => say(err.message));
});
document.getElementById("batch-pager").addEventListener("click", (event) => {
  const button = event.target.closest("[data-batch-page]");
  if (!button || button.disabled) return;
  loadBatches(button.dataset.batchPage === "prev" ? batchPage - 1 : batchPage + 1).catch((err) => say(err.message));
});

Elmo.onReady(async (user) => {
  if (!user || user.role !== "teacher") return;
  await Promise.all([fillMonths(), loadBatches(1), loadCodes(1)]);
  toggleKind();
});
