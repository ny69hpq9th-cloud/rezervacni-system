/**
 * Rezervly — Dark / Light mode
 * Loaded in <head> before stylesheets to prevent flash of wrong theme.
 */
(function () {
  var saved = localStorage.getItem('rezervly_theme');
  var pref  = saved || (window.matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', pref);
})();

function toggleTheme() {
  var html = document.documentElement;
  var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('rezervly_theme', next);
}
