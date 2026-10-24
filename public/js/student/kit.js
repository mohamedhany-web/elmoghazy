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

  function all() {
    try {
      return JSON.parse(localStorage.getItem("elmo-watch") || "{}");
    } catch {
      return {};
    }
  }

  function row(id) {
    const raw = all()[String(id)];
    if (raw && typeof raw === "object") {
      return {
        p: Math.max(0, Math.min(100, Math.round(Number(raw.p) || 0))),
        t: Math.max(0, Number(raw.t) || 0),
      };
    }
    return {
      p: Math.max(0, Math.min(100, Math.round(Number(raw) || 0))),
      t: 0,
    };
  }

  function percent(id) {
    return row(id).p;
  }

  function time(id) {
    return row(id).t;
  }

  function save(id, value, seconds) {
    const key = String(id);
    const rows = all();
    const prev = row(id);
    const nextPercent = Math.max(prev.p, Math.max(0, Math.min(100, Math.round(Number(value) || 0))));
    const nextTime = seconds === undefined || seconds === null ? prev.t : Math.max(0, Number(seconds) || 0);
    rows[key] = { p: nextPercent, t: nextTime };
    localStorage.setItem("elmo-watch", JSON.stringify(rows));
  }

  function done(id) {
    return percent(id) >= 90;
  }

  function videosOf(month) {
    return (month.units || []).flatMap((unit) => unit.videos || []);
  }

  function progress(month) {
    const videos = videosOf(month);
    if (!videos.length) return 0;
    const finished = videos.filter((video) => done(video.id)).length;
    return Math.round((finished / videos.length) * 100);
  }

  function state(month) {
    const value = progress(month);
    if (!videosOf(month).length || value === 0) return "wait";
    if (value >= 100) return "done";
    return "on";
  }

  function clip(text, size) {
    const value = String(text || "").trim();
    if (value.length <= size) return value;
    return `${value.slice(0, size).trim()}…`;
  }

  window.StKit = { esc, percent, time, save, done, videosOf, progress, state, clip };
})();
