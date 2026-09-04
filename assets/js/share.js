// XPORN LOVERS — share popover, used on the public video page and the
// admin video library (any account). Builds WhatsApp/Telegram share
// links and a copy-to-clipboard action for whichever ".share-btn" was
// clicked; a single popover in the page repositions itself each time.
document.addEventListener('DOMContentLoaded', () => {
  const popover = document.getElementById('share-popover');
  if (!popover) return;

  const waLink = document.getElementById('share-whatsapp');
  const tgLink = document.getElementById('share-telegram');
  const copyBtn = document.getElementById('share-copy');
  const statusEl = document.getElementById('share-popover-status');
  let currentUrl = '';

  function closePopover() {
    popover.hidden = true;
  }

  function openPopoverNear(btn, url, title) {
    currentUrl = url;
    waLink.href = 'https://wa.me/?text=' + encodeURIComponent(title + ' — ' + url);
    tgLink.href = 'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title);
    statusEl.textContent = '';

    popover.hidden = false;
    const btnRect = btn.getBoundingClientRect();
    const popRect = popover.getBoundingClientRect();

    let left = btnRect.left + window.scrollX;
    const maxLeft = window.scrollX + window.innerWidth - popRect.width - 12;
    if (left > maxLeft) left = Math.max(12, maxLeft);

    let top = btnRect.bottom + window.scrollY + 8;
    const maxTop = window.scrollY + window.innerHeight - popRect.height - 12;
    if (top > maxTop) top = btnRect.top + window.scrollY - popRect.height - 8; // flip above if no room below

    popover.style.left = left + 'px';
    popover.style.top = top + 'px';
  }

  document.querySelectorAll('.share-btn').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const url = btn.dataset.shareUrl;
      const title = btn.dataset.shareTitle || '';
      if (!url) return;
      if (!popover.hidden && currentUrl === url) {
        closePopover();
        return;
      }
      openPopoverNear(btn, url, title);
    });
  });

  copyBtn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(currentUrl);
      statusEl.textContent = 'Link copied!';
    } catch (err) {
      statusEl.textContent = 'Could not copy automatically — copy the link from the address bar.';
    }
  });

  document.addEventListener('click', (e) => {
    if (!popover.hidden && !popover.contains(e.target) && !e.target.closest('.share-btn')) {
      closePopover();
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePopover();
  });
  window.addEventListener('scroll', closePopover, { passive: true });
});
