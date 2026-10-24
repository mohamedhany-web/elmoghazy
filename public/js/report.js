(function () {
  function esc(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[char]));
  }

  function cells(values) {
    return values.map((value) => `<td>${esc(value || "—")}</td>`).join("");
  }

  function mount(host, sheet) {
    if (!host || !sheet) return;
    const facts = sheet.facts || [];
    const stats = sheet.stats || [];
    const sections = (sheet.sections || [])
      .map((section) => {
        const columns = section.columns || [];
        const rows = section.rows || [];
        const body = rows.length
          ? `<table class="sheet__table"><thead><tr>${columns.map((label) => `<th>${esc(label)}</th>`).join("")}</tr></thead><tbody>${rows
              .map((row) => `<tr>${cells(row)}</tr>`)
              .join("")}</tbody></table>`
          : `<p class="sheet__empty">${esc(section.empty || "لسه فاضي.")}</p>`;
        return `<section class="sheet__section"><h2>${esc(section.title)}</h2>${body}</section>`;
      })
      .join("");

    host.hidden = false;
    host.innerHTML = `<article class="sheet">
      <div class="sheet__tools">
        <button type="button" class="sheet__tool" data-sheet="print">طباعة</button>
        <button type="button" class="sheet__tool" data-sheet="excel">تصدير إكسل</button>
        ${sheet.whatsapp
          ? `<a class="sheet__tool" data-sheet="whatsapp" href="${esc(whatsappLink(sheet))}" target="_blank" rel="noopener">واتساب ولي الأمر</a>`
          : `<button type="button" class="sheet__tool" data-sheet="whatsapp" disabled title="مفيش رقم ولي أمر">واتساب ولي الأمر</button>`}
      </div>
      <header class="sheet__banner">
        <div>
          <p class="sheet__brand">منصة المغازي</p>
          <h1>${esc(sheet.title)}</h1>
          <p class="sheet__subject">${esc(sheet.subject)}</p>
        </div>
        <div class="sheet__meta">
          <p>${esc(sheet.kicker || "")}</p>
          <p>تاريخ التقرير: ${esc(sheet.issued_at || "—")}</p>
        </div>
      </header>
      ${stats.length ? `<div class="sheet__meters">${stats.map((row) => `<div><span>${esc(row.label)}</span><strong>${esc(row.value || "—")}</strong></div>`).join("")}</div>` : ""}
      ${facts.length ? `<div class="sheet__facts">${facts.map((row) => `<div><span>${esc(row.label)}</span><strong>${esc(row.value || "—")}</strong></div>`).join("")}</div>` : ""}
      <div class="sheet__grid">${sections}</div>
    </article>`;
    host.querySelector('[data-sheet="print"]')?.addEventListener("click", () => window.print());
    host.querySelector('[data-sheet="excel"]')?.addEventListener("click", () => downloadExcel(sheet));
  }

  function whatsappLink(sheet) {
    const lines = [
      sheet.title,
      "منصة المغازي",
      sheet.subject,
      sheet.kicker,
      `تاريخ التقرير: ${sheet.issued_at || "—"}`,
      "",
      ...(sheet.facts || []).map((row) => `${row.label}: ${row.value || "—"}`),
      "",
      ...(sheet.stats || []).map((row) => `${row.label}: ${row.value || "—"}`),
    ];
    (sheet.sections || []).forEach((section) => {
      if (section.title === "الأكواد" || section.title === "السجل") return;
      lines.push("", section.title);
      const rows = section.rows || [];
      if (!rows.length) {
        lines.push(section.empty || "لسه فاضي.");
        return;
      }
      rows.slice(0, 8).forEach((row) => lines.push((row || []).join(" · ")));
    });
    return `https://wa.me/${sheet.whatsapp}?text=${encodeURIComponent(lines.join("\n"))}`;
  }

  function xml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&apos;",
    }[char]));
  }

  function downloadExcel(sheet) {
    const rows = [[sheet.title], [sheet.subject, sheet.kicker || ""], ["تاريخ التقرير", sheet.issued_at || ""]];
    (sheet.facts || []).forEach((row) => rows.push([row.label, row.value || "—"]));
    if ((sheet.stats || []).length) {
      rows.push([]);
      rows.push((sheet.stats || []).map((row) => row.label));
      rows.push((sheet.stats || []).map((row) => row.value || "—"));
    }
    (sheet.sections || []).forEach((section) => {
      rows.push([]);
      rows.push([section.title]);
      rows.push(section.columns || []);
      const body = section.rows || [];
      if (!body.length) rows.push([section.empty || "لسه فاضي."]);
      body.forEach((row) => rows.push(row));
    });
    const table = rows
      .map((row) => `<Row>${row.map((cell) => `<Cell><Data ss:Type="String">${xml(cell)}</Data></Cell>`).join("")}</Row>`)
      .join("");
    const book = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<Worksheet ss:Name="التقرير"><Table>${table}</Table></Worksheet>
</Workbook>`;
    const file = new Blob([book], { type: "application/vnd.ms-excel" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(file);
    link.download = `${String(sheet.subject || "تقرير").replace(/[^\p{L}\p{N}\s_-]+/gu, "").trim() || "تقرير"}.xls`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 1500);
  }

  window.ReportSheet = { mount };
})();
