<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$message = '';

// Handle task scheduling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['test_cron'])) {
        // Test the cron script
        $output = [];
        $return_var = 0;
        exec('php ../../cron/daily_reset.php', $output, $return_var);
        $message = $return_var === 0 ? '✅ Cron test successful!' : '❌ Cron test failed. Check error logs.';
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-warning">
            <h4><i class="fas fa-clock me-2"></i>Scheduled Tasks</h4>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">Daily Reset Cron Job</div>
                        <div class="card-body">
                            <p><strong>Command:</strong></p>
                            <code>php <?php echo realpath('../../cron/daily_reset.php'); ?></code>
                            
                            <p class="mt-3"><strong>Schedule:</strong> Every day at 12:00 AM</p>
                            
                            <form method="post">
                                <button type="submit" name="test_cron" class="btn btn-primary">
                                    <i class="fas fa-play me-2"></i>Test Cron Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Windows Task Scheduler Setup</div>
                        <div class="card-body">
                            <h6>Step 1: Open Task Scheduler</h6>
                            <p>Press <kbd>Win + R</kbd>, type <code>taskschd.msc</code></p>
                            
                            <h6>Step 2: Create Basic Task</h6>
                            <ul>
                                <li>Name: Kakanin Daily Reset</li>
                                <li>Trigger: Daily at 12:00 AM</li>
                                <li>Action: Start a program</li>
                                <li>Program: <code>C:\xampp\php\php.exe</code></li>
                                <li>Arguments: <code><?php echo realpath('../../cron/daily_reset.php'); ?></code></li>
                            </ul>
                            
                            <a href="https://www.windowscentral.com/how-create-automated-task-using-task-scheduler-windows-10" target="_blank" class="btn btn-info">
                                <i class="fas fa-external-link-alt me-2"></i>View Guide
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>