// XXPORN LOVERS — site interactions
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  const drawer = document.querySelector('.mobile-drawer');

  if (toggle && drawer) {
    toggle.addEventListener('click', () => {
      const isOpen = drawer.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(isOpen));
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    drawer.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        drawer.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }

  // Category chip active state
  document.querySelectorAll('.chip-row').forEach((row) => {
    row.addEventListener('click', (e) => {
      const chip = e.target.closest('.chip');
      if (!chip) return;
      row.querySelectorAll('.chip').forEach((c) => c.classList.remove('active'));
      chip.classList.add('active');
    });
  });

  initReportModal();
  initLikeDislike();
  initCommentForm();
});

/** Wires the like/dislike buttons on a video page to /api/like.php. */
function initLikeDislike() {
  const bar = document.getElementById('like-dislike-bar');
  if (!bar) return;

  const videoId = bar.dataset.videoId;
  const csrfToken = bar.dataset.csrf;
  const likeBtn = bar.querySelector('.like-btn');
  const dislikeBtn = bar.querySelector('.dislike-btn');
  const likeCount = document.getElementById('like-count');
  const dislikeCount = document.getElementById('dislike-count');

  async function vote(type) {
    likeBtn.disabled = true;
    dislikeBtn.disabled = true;
    try {
      const res = await fetch('/api/like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ csrf_token: csrfToken, video_id: videoId, type }),
      });
      const data = await res.json();
      if (data.ok) {
        likeCount.textContent = data.likes;
        dislikeCount.textContent = data.dislikes;
        likeBtn.classList.toggle('is-active', data.my_vote === 'like');
        dislikeBtn.classList.toggle('is-active', data.my_vote === 'dislike');
      }
    } catch (err) {
      // Silent — a failed vote just leaves the counts as they were.
    } finally {
      likeBtn.disabled = false;
      dislikeBtn.disabled = false;
    }
  }

  likeBtn.addEventListener('click', () => vote('like'));
  dislikeBtn.addEventListener('click', () => vote('dislike'));
}

/** Wires the comment form on a video page to /api/comment.php. */
function initCommentForm() {
  const form = document.getElementById('comment-form');
  if (!form) return;

  const statusField = document.getElementById('comment-form-status');
  const submitBtn = document.getElementById('comment-submit-btn');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    statusField.textContent = '';
    statusField.className = 'comment-form-status';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Posting…';

    try {
      const res = await fetch('/api/comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(form)),
      });
      const data = await res.json();

      if (data.ok) {
        statusField.textContent = data.message || 'Thanks — your comment is awaiting approval.';
        statusField.className = 'comment-form-status is-success';
        form.reset();
      } else {
        statusField.textContent = data.error || 'Something went wrong. Please try again.';
        statusField.className = 'comment-form-status is-error';
      }
    } catch (err) {
      statusField.textContent = 'Network error. Please try again.';
      statusField.className = 'comment-form-status is-error';
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Post Comment';
    }
  });
}

/**
 * Wires every ".report-btn" on the page to the shared "#report-modal".
 * Submits to /api/report.php and shows an inline success/error message.
 */
function initReportModal() {
  const modal = document.getElementById('report-modal');
  if (!modal) return;

  const form = document.getElementById('report-form');
  const videoIdField = document.getElementById('report-video-id');
  const titleField = document.getElementById('report-modal-video-title');
  const statusField = document.getElementById('report-modal-status');
  const submitBtn = document.getElementById('report-submit-btn');
  let lastFocused = null;

  function openModal(videoId, videoTitle) {
    videoIdField.value = videoId;
    titleField.textContent = videoTitle ? `“${videoTitle}”` : '';
    statusField.textContent = '';
    statusField.className = 'report-modal-status';
    form.reset();
    videoIdField.value = videoId;
    lastFocused = document.activeElement;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    document.getElementById('report-reason').focus();
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (lastFocused) lastFocused.focus();
  }

  document.querySelectorAll('.report-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      openModal(btn.dataset.videoId, btn.dataset.videoTitle || '');
    });
  });

  modal.querySelectorAll('[data-report-close]').forEach((el) => {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    statusField.textContent = '';
    statusField.className = 'report-modal-status';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting…';

    try {
      const res = await fetch('/api/report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(form)),
      });
      const data = await res.json();

      if (data.ok) {
        statusField.textContent = 'Thanks — our team will review this shortly.';
        statusField.className = 'report-modal-status is-success';
        submitBtn.textContent = 'Submitted';
        setTimeout(closeModal, 1400);
      } else {
        statusField.textContent = data.error || 'Something went wrong. Please try again.';
        statusField.className = 'report-modal-status is-error';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Report';
      }
    } catch (err) {
      statusField.textContent = 'Network error. Please try again.';
      statusField.className = 'report-modal-status is-error';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Report';
    }
  });
}
