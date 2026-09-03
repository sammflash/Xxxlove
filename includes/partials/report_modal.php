<div class="report-modal" id="report-modal" hidden aria-hidden="true">
  <div class="report-modal-backdrop" data-report-close></div>
  <div class="report-modal-card" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
    <button type="button" class="report-modal-x" data-report-close aria-label="Close">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
    <h3 id="report-modal-title">Report this video</h3>
    <p class="report-modal-sub" id="report-modal-video-title"></p>

    <form id="report-form">
      <?= csrf_field() ?>
      <input type="hidden" name="video_id" id="report-video-id" value="">

      <div class="field">
        <label for="report-reason">Reason</label>
        <select id="report-reason" name="reason" required>
          <option value="">Select a reason…</option>
          <option value="non_consensual">Non-consensual content</option>
          <option value="underage_concern">Underage concern</option>
          <option value="stolen_content">Stolen / unauthorized upload</option>
          <option value="wrong_category">Mislabeled or spam</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div class="field">
        <label for="report-details">Details <span style="text-transform:none; font-weight:400; color:var(--text-muted);">(optional)</span></label>
        <textarea id="report-details" name="details" rows="3" maxlength="1000" placeholder="Anything that helps our review…"></textarea>
      </div>

      <p class="report-modal-status" id="report-modal-status" role="status" aria-live="polite"></p>

      <div class="report-modal-actions">
        <button type="button" class="btn btn-secondary" data-report-close>Cancel</button>
        <button type="submit" class="btn btn-primary" id="report-submit-btn">Submit Report</button>
      </div>
    </form>
  </div>
</div>
