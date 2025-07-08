<?php
$page_title = 'System Logs';
require_once '../../includes/header.php';
require_once '../../includes/logger.php';

requireRole('admin');

// Get filter parameters
$level = $_GET['level'] ?? '';
$limit = (int)($_GET['limit'] ?? 100);
$search = $_GET['search'] ?? '';

// Validate limit
if ($limit < 10) $limit = 10;
if ($limit > 1000) $limit = 1000;

// Get log data
$logger = Logger::getInstance();
$logs = $logger->getRecentLogs($limit, $level);
$stats = $logger->getLogStats();

// Filter logs by search term
if (!empty($search)) {
    $logs = array_filter($logs, function($log) use ($search) {
        return stripos($log, $search) !== false;
    });
}

// Parse log entries for better display
function parseLogEntry($logLine) {
    // Pattern: [timestamp] LEVEL: message | User: username (id) | IP: ip | URI: uri | Context: json
    $pattern = '/\[(.*?)\] (.*?): (.*?) \| User: (.*?) \((.*?)\) \| IP: (.*?) \| URI: (.*?)(?:\s\| Context: (.*))?$/';
    
    if (preg_match($pattern, $logLine, $matches)) {
        return [
            'timestamp' => $matches[1],
            'level' => $matches[2],
            'message' => $matches[3],
            'username' => $matches[4],
            'user_id' => $matches[5],
            'ip' => $matches[6],
            'uri' => $matches[7],
            'context' => isset($matches[8]) ? $matches[8] : null,
            'raw' => $logLine
        ];
    }
    
    return [
        'timestamp' => '',
        'level' => 'UNKNOWN',
        'message' => $logLine,
        'username' => '',
        'user_id' => '',
        'ip' => '',
        'uri' => '',
        'context' => null,
        'raw' => $logLine
    ];
}

// Handle log actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'clear_logs':
                $logFile = defined('LOG_FILE') ? LOG_FILE : __DIR__ . '/../../logs/app.log';
                if (file_exists($logFile)) {
                    file_put_contents($logFile, '');
                    setAlert('success', 'Log berhasil dibersihkan!');
                    logger()->info('System logs cleared by admin');
                } else {
                    setAlert('warning', 'File log tidak ditemukan!');
                }
                break;
                
            case 'cleanup_old_logs':
                $logger->clearOldLogs(30);
                setAlert('success', 'Log lama berhasil dibersihkan!');
                logger()->info('Old logs cleaned up by admin');
                break;
                
            case 'download_logs':
                $logFile = defined('LOG_FILE') ? LOG_FILE : __DIR__ . '/../../logs/app.log';
                if (file_exists($logFile)) {
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d') . '.log"');
                    readfile($logFile);
                    exit();
                }
                break;
        }
        
        header('Location: index.php');
        exit();
    }
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'System Logs', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-journal-text"></i>
        System Logs
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="download_logs">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-download"></i> Download
                </button>
            </form>
            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal"
                data-bs-target="#clearLogsModal">
                <i class="bi bi-trash"></i> Clear Logs
            </button>
        </div>
    </div>
</div>

<!-- Log Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="bi bi-file-text text-primary" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0"><?php echo number_format($stats['total_lines']); ?></h4>
                <small class="text-muted">Total Log Entries</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="bi bi-hdd text-info" style="font-size: 2rem;"></i>
                <h6 class="mt-2 mb-0"><?php echo formatFileSize($stats['file_size']); ?></h6>
                <small class="text-muted">File Size</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="bi bi-clock text-success" style="font-size: 2rem;"></i>
                <h6 class="mt-2 mb-0">
                    <?php echo $stats['last_modified'] ? date('d/m/Y H:i', $stats['last_modified']) : 'N/A'; ?>
                </h6>
                <small class="text-muted">Last Modified</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                <h4 class="mt-2 mb-0"><?php echo $stats['levels']['ERROR'] ?? 0; ?></h4>
                <small class="text-muted">Error Count</small>
            </div>
        </div>
    </div>
</div>

<!-- Log Level Distribution -->
<?php if (!empty($stats['levels'])): ?>
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-bar-chart"></i>
            Log Level Distribution
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($stats['levels'] as $logLevel => $count): ?>
            <div class="col-md-2 col-sm-4 mb-2">
                <div class="text-center">
                    <div class="badge <?php 
                        echo $logLevel === 'ERROR' ? 'bg-danger' : 
                             ($logLevel === 'WARNING' ? 'bg-warning' : 
                             ($logLevel === 'INFO' ? 'bg-info' : 'bg-secondary')); 
                    ?> fs-6 mb-1">
                        <?php echo $logLevel; ?>
                    </div>
                    <div class="h5"><?php echo number_format($count); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-funnel"></i>
            Filter Logs
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="level" class="form-label">Log Level</label>
                <select class="form-select" id="level" name="level">
                    <option value="">All Levels</option>
                    <option value="DEBUG" <?php echo $level === 'DEBUG' ? 'selected' : ''; ?>>Debug</option>
                    <option value="INFO" <?php echo $level === 'INFO' ? 'selected' : ''; ?>>Info</option>
                    <option value="WARNING" <?php echo $level === 'WARNING' ? 'selected' : ''; ?>>Warning</option>
                    <option value="ERROR" <?php echo $level === 'ERROR' ? 'selected' : ''; ?>>Error</option>
                    <option value="CRITICAL" <?php echo $level === 'CRITICAL' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="limit" class="form-label">Limit</label>
                <select class="form-select" id="limit" name="limit">
                    <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50 entries</option>
                    <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100 entries</option>
                    <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200 entries</option>
                    <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500 entries</option>
                    <option value="1000" <?php echo $limit === 1000 ? 'selected' : ''; ?>>1000 entries</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search"
                    value="<?php echo htmlspecialchars($search); ?>" placeholder="Search in log messages...">
            </div>

            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Log Entries -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h6 class="mb-0">
                    <i class="bi bi-list"></i>
                    Log Entries
                    <?php if (!empty($search)): ?>
                    <span class="badge bg-primary">Filtered: "<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="col-auto">
                <small class="text-muted">Showing <?php echo count($logs); ?> entries</small>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
        <div class="text-center py-5">
            <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
            <h6 class="text-muted mt-2">No log entries found</h6>
            <?php if (!empty($search) || !empty($level)): ?>
            <p class="text-muted">Try adjusting your filters or search terms.</p>
            <a href="index.php" class="btn btn-outline-secondary">Clear Filters</a>
            <?php else: ?>
            <p class="text-muted">The system hasn't generated any logs yet.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="12%">Timestamp</th>
                        <th width="8%">Level</th>
                        <th>Message</th>
                        <th width="10%">User</th>
                        <th width="10%">IP</th>
                        <th width="15%">URI</th>
                        <th width="5%">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $index => $logLine): ?>
                    <?php $entry = parseLogEntry($logLine); ?>
                    <tr>
                        <td>
                            <small class="text-muted"><?php echo htmlspecialchars($entry['timestamp']); ?></small>
                        </td>
                        <td>
                            <span class="badge <?php 
                                echo $entry['level'] === 'ERROR' ? 'bg-danger' : 
                                     ($entry['level'] === 'WARNING' ? 'bg-warning text-dark' : 
                                     ($entry['level'] === 'INFO' ? 'bg-info' : 
                                     ($entry['level'] === 'DEBUG' ? 'bg-secondary' : 'bg-primary'))); 
                            ?> badge-sm">
                                <?php echo htmlspecialchars($entry['level']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="fw-semibold"><?php echo htmlspecialchars($entry['message']); ?></span>
                            <?php if ($entry['context']): ?>
                            <br><small class="text-muted">Context:
                                <?php echo htmlspecialchars($entry['context']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small>
                                <?php echo htmlspecialchars($entry['username']); ?>
                                <?php if ($entry['user_id'] !== 'guest'): ?>
                                <br><span class="text-muted">(<?php echo htmlspecialchars($entry['user_id']); ?>)</span>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <small class="font-monospace"><?php echo htmlspecialchars($entry['ip']); ?></small>
                        </td>
                        <td>
                            <small class="text-truncate d-block" style="max-width: 150px;"
                                title="<?php echo htmlspecialchars($entry['uri']); ?>">
                                <?php echo htmlspecialchars($entry['uri']); ?>
                            </small>
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#logDetailModal"
                                data-log="<?php echo htmlspecialchars($entry['raw']); ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i>
                    Clear System Logs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Warning:</strong> This action will permanently delete all log entries.</p>
                <p>Are you sure you want to continue?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-trash"></i> Clear Logs
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle"></i>
                    Log Entry Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="log-detail-content" class="border p-3 bg-light"
                    style="white-space: pre-wrap; font-family: monospace; font-size: 0.875rem;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Handle log detail modal
document.getElementById('logDetailModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const logContent = button.getAttribute('data-log');
    document.getElementById('log-detail-content').textContent = logContent;
});

// Auto-refresh page every 30 seconds (optional)
// setInterval(function() {
//     window.location.reload();
// }, 30000);
</script>

<?php require_once '../../includes/footer.php'; ?>