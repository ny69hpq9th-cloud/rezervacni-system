// FAQ accordion
document.querySelectorAll('.faq-question').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var item = btn.closest('.faq-item');
    var wasOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(function(i) { i.classList.remove('open'); });
    if (!wasOpen) item.classList.add('open');
  });
});

// Mobile nav
var nav = document.querySelector('.nav');
var navToggle = document.querySelector('.nav__toggle');
if (navToggle && nav) {
  navToggle.addEventListener('click', function() { nav.classList.toggle('nav--open'); });
  document.addEventListener('click', function(e) {
    if (!nav.contains(e.target)) nav.classList.remove('nav--open');
  });
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
  a.addEventListener('click', function(e) {
    var target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// Auto-hide alerts
document.querySelectorAll('.alert').forEach(function(el) {
  setTimeout(function() {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(function() { el.remove(); }, 400);
  }, 5000);
});
