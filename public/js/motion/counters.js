(function () {
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const seen = new WeakSet();

  function paint(node) {
    const target = Number(node.dataset.count);
    if (!Number.isFinite(target)) return;
    if (reduce) {
      node.textContent = target.toLocaleString("ar-EG");
      return;
    }
    const start = performance.now();
    const duration = 700;
    function tick(now) {
      const progress = Math.min(1, (now - start) / duration);
      node.textContent = Math.round(target * progress).toLocaleString("ar-EG");
      if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        paint(entry.target);
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.6 },
  );

  function watch(root) {
    (root || document).querySelectorAll("[data-count]").forEach((node) => {
      if (seen.has(node)) return;
      seen.add(node);
      observer.observe(node);
    });
  }

  window.Elmo = window.Elmo || {};
  window.Elmo.count = watch;
  watch(document);
})();
