<?php if (isset($_SESSION['message'])): ?>
    <div class="alert-toast alert-success">
        <div class="toast-content">
            <i class="fas fa-check-circle"></i>
            <span><?= $_SESSION['message']; ?></span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
        <?php unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert-toast alert-danger">
        <div class="toast-content">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= $_SESSION['error']; ?></span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>