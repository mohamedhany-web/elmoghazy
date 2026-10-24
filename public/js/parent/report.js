const report = document.getElementById("parent-report");

function openParent() {
  Elmo.openPop({
    title: "كود الابن",
    copy: "الكود ده للتقرير بس. مش بيفتح الحصص.",
    html: `<form class="stack">
      <label class="field"><span>الكود</span><input name="code" required autocomplete="off"></label>
      <button type="submit" class="btn btn-primary">عرض التقرير</button>
    </form>`,
    onSubmit: async (data) => {
      const sheet = await Elmo.api("/api/parent/report", {
        method: "POST",
        body: JSON.stringify({ code: data.get("code") }),
      });
      Elmo.closePop();
      ReportSheet.mount(report, sheet);
    },
  });
}

document.getElementById("open-parent").addEventListener("click", openParent);
openParent();
