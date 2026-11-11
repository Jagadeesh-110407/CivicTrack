document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.nav-links');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }
  document.querySelectorAll('form[onsubmit]').forEach(form => {
    form.addEventListener('submit', event => {
      if (!confirm('Are you sure you want to continue?')) event.preventDefault();
    });
  });
});
