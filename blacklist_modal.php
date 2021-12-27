<?php if ($user_type == ADMIN): ?>
  <div class="modal fade" id="blacklistModal" tabindex="-1" aria-labelledby="blacklistModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="blacklistModalLabel">Blacklist <?php echo (isset($teacher)) ? "Teacher":"Organisation"; ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to blacklist this user? They will not be able to login/register with their e-mail address
          anymore
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-dark" onclick="blacklist();">Blacklist</button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
