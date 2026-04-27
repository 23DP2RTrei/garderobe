/* Garderobe — main.js */

/* ── DARK MODE ─────────────────────────────────────────────────────────────
   Saved in localStorage under 'garderobe-theme' ('light' | 'dark').
   Applied to <html data-bs-theme="..."> so Bootstrap 5.3 handles
   component colours automatically.
───────────────────────────────────────────────────────────────────────── */
var THEME_KEY = 'garderobe-theme';

function getTheme() {
  return localStorage.getItem(THEME_KEY) || 'light';
}

function applyTheme(theme) {
  /* 1. Bootstrap 5.3 dark mode attribute */
  document.documentElement.setAttribute('data-bs-theme', theme);
  /* 2. Persist */
  localStorage.setItem(THEME_KEY, theme);
  /* 3. Update toggle icons */
  var isDark = (theme === 'dark');
  var cls = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
  ['themeIcon', 'themeIconMobile'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.className = cls;
  });
}

function toggleTheme() {
  applyTheme(getTheme() === 'dark' ? 'light' : 'dark');
}

/* Wire up toggle buttons as soon as DOM is ready */
document.addEventListener('DOMContentLoaded', function () {
  /* Apply stored theme (icon sync) — attribute already set in <head> */
  applyTheme(getTheme());

  ['themeToggle', 'themeToggleMobile'].forEach(function (id) {
    var btn = document.getElementById(id);
    if (btn) btn.addEventListener('click', toggleTheme);
  });

  /* ── Flash auto-dismiss ─────────────────────────────────────────────── */
  setTimeout(function () {
    document.querySelectorAll('.alert').forEach(function (a) {
      try { new bootstrap.Alert(a).close(); } catch (e) {}
    });
  }, 4500);

  /* ── Image preview on file input ──────────────────────────────────── */
  var imgInput   = document.getElementById('imageInput');
  var imgPreview = document.getElementById('imagePreview');
  if (imgInput && imgPreview) {
    imgInput.addEventListener('change', function () {
      var file = this.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (e) {
        imgPreview.src = e.target.result;
        imgPreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  }

  /* ── Active nav link highlight ─────────────────────────────────────── */
  var path = window.location.pathname;
  document.querySelectorAll('#mainNav .nav-link').forEach(function (link) {
    if (link.href && path !== '/' && link.href.indexOf(path) !== -1) {
      link.classList.add('active-link');
    }
  });
});

/* ── Delete confirm ──────────────────────────────────────────────────────── */
function confirmDelete(msg) {
  return confirm(msg || 'Vai tiešām dzēst šo ierakstu?');
}
