<?php if ($user_type == ADMIN): ?>
  <div class="modal fade" id="banModal" tabindex="-1" aria-labelledby="banModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="banModalLabel">Ban <?php echo (isset($teacher)) ? "Teacher":"Organisation"; ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="ban_user_form">
            <div class="row">
              <div class="col">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" readonly="readonly" class="form-control" name="username" id="username" value="<?php echo $username; ?>">
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                  <label>Admin</label>
                  <input type="text" readonly="readonly" class="form-control" name="admin" id="admin" value="<?php echo $loggedin_username; ?>">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Reason</label>
              <input type="text" maxlength="64" class="form-control" name="reason" id="reason" required>
              <div class="form-text">
                Enter the reason you are banning the user with a maximum of 64 characters
              </div>
            </div>
            <div class="form-group">
              <label>Until</label>
              <div class="row">
                <div class="col">
                  <input type="date" class="form-control" name="date_to" id="date_to" required>
                </div>
                <div class="col">
                  <input type="time" class="form-control" name="time_to" id="time_to" required>
                </div>
              </div>
              <div class="form-text">
                Enter the date and time until when this user should be banned
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" onclick="ban();">Ban</button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
