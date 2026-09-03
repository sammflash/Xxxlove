// XXPORN LOVERS — admin dashboard interactions
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.mobile-only[data-sidebar-toggle]');
  const sidebar = document.querySelector('.sidebar');
  const scrim = document.querySelector('.sidebar-scrim');

  const closeSidebar = () => {
    sidebar?.classList.remove('open');
    scrim?.classList.remove('open');
  };

  toggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
    scrim?.classList.toggle('open');
  });
  scrim?.addEventListener('click', closeSidebar);
});
