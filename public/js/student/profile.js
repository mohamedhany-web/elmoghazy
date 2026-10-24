Elmo.onReady((user) => {
  if (!user) {
    location.href = "/login";
    return;
  }
  if (user.role === "teacher") {
    location.href = "/admin";
    return;
  }
  document.getElementById("file-name").textContent = user.name || "—";
  document.getElementById("file-grade").textContent = (user.grade && user.grade.title) || "—";
  document.getElementById("file-phone").textContent = user.phone || "—";
  document.getElementById("file-email").textContent = user.email || "—";
});
