document.getElementById("teacher-login")?.addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const error = document.getElementById("teacher-error");
  error.hidden = true;
  const data = new FormData(form);
  try {
    const result = await Elmo.api("/api/login", {
      method: "POST",
      body: JSON.stringify({ email: data.get("email"), password: data.get("password") }),
    });
    if (result.user?.role !== "teacher") {
      localStorage.removeItem("elmoghazy_token");
      error.hidden = false;
      error.textContent = "الحساب ده مش حساب مدرس.";
      return;
    }
    localStorage.setItem("elmoghazy_token", result.token);
    location.href = "/admin";
  } catch (err) {
    error.hidden = false;
    error.textContent = err.message;
  }
});
