<?php
$page_title = 'System Settings';
require_once '../../includes/header.php';
require_once '../../includes/logger.php';

requireRole('admin');

// Settings file path
$settingsFile = __DIR__ . '/../../config/settings.json';

// Default settings
$defaultSettings = [
    'school' => [
        'name' => 'SMP Negeri 2 Ampek Angkek',
        'address' => 'Jl. Pendidikan No. 123, Ampek Angkek',
        'phone' => '(0751) 123456',
        'email' => 'info@smpn2ampekangkek.sch.id',
        'website' => 'https://smpn2ampekangkek.sch.id',
        'logo' => ''
    ],
    'app' => [
        'maintenance_mode' => false,
        'maintenance_message' => 'Sistem sedang dalam pemeliharaan. Silakan coba lagi nanti.',
        'session_timeout' => 7200,
        'max_login_attempts' => 5,
        'login_lockout_time' => 900,
        'auto_backup' => true,
        'backup_retention_days' => 30
    ],
    'beasiswa' => [
        'default_tahun_ajaran' => date('Y') . '/' . (date('Y') + 1),
        'min_criteria_count' => 3,
        'max_criteria_count' => 10,
        'weight_tolerance' => 0.0001,
        'calculation_precision' => 6,
        'auto_ranking' => true
    ],
    'notification' => [
        'email_enabled' => false,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'from_name' => 'SMP Negeri 2 Ampek Angkek',
        'from_email' => 'noreply@smpn2ampekangkek.sch.id'
    ],
    'security' => [
        'force_https' => false,
        'csrf_protection' => true,
        'session_regenerate' => true,
        'password_min_length' => 8,
        'password_require_special' => false,
        'login_log_enabled' => true
    ]
];

// Load current settings
function loadSettings($file, $defaults) {
    if (file_exists($file)) {
        $settings = json_decode(file_get_contents($file), true);
        if ($settings) {
            return array_merge_recursive($defaults, $settings);
        }
    }
    return $defaults;
}

// Save settings
function saveSettings($file, $settings) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    return file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$settings = loadSettings($settingsFile, $defaultSettings);
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['section'])) {
        $section = $_POST['section'];
        
        switch ($section) {
            case 'school':
                $settings['school']['name'] = cleanInput($_POST['school_name']);
                $settings['school']['address'] = cleanInput($_POST['school_address']);
                $settings['school']['phone'] = cleanInput($_POST['school_phone']);
                $settings['school']['email'] = cleanInput($_POST['school_email']);
                $settings['school']['website'] = cleanInput($_POST['school_website']);
                
                // Validate email
                if (!filter_var($settings['school']['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Format email sekolah tidak valid!';
                }
                break;
                
            case 'app':
                $settings['app']['maintenance_mode'] = isset($_POST['maintenance_mode']);
                $settings['app']['maintenance_message'] = cleanInput($_POST['maintenance_message']);
                $settings['app']['session_timeout'] = (int)$_POST['session_timeout'];
                $settings['app']['max_login_attempts'] = (int)$_POST['max_login_attempts'];
                $settings['app']['login_lockout_time'] = (int)$_POST['login_lockout_time'];
                $settings['app']['auto_backup'] = isset($_POST['auto_backup']);
                $settings['app']['backup_retention_days'] = (int)$_POST['backup_retention_days'];
                
                // Validate ranges
                if ($settings['app']['session_timeout'] < 300) {
                    $errors[] = 'Session timeout minimal 5 menit (300 detik)!';
                }
                if ($settings['app']['max_login_attempts'] < 1) {
                    $errors[] = 'Maksimal percobaan login minimal 1!';
                }
                break;
                
            case 'beasiswa':
                $settings['beasiswa']['default_tahun_ajaran'] = cleanInput($_POST['default_tahun_ajaran']);
                $settings['beasiswa']['min_criteria_count'] = (int)$_POST['min_criteria_count'];
                $settings['beasiswa']['max_criteria_count'] = (int)$_POST['max_criteria_count'];
                $settings['beasiswa']['weight_tolerance'] = (float)$_POST['weight_tolerance'];
                $settings['beasiswa']['calculation_precision'] = (int)$_POST['calculation_precision'];
                $settings['beasiswa']['auto_ranking'] = isset($_POST['auto_ranking']);
                
                // Validate criteria count
                if ($settings['beasiswa']['min_criteria_count'] < 2) {
                    $errors[] = 'Minimal kriteria tidak boleh kurang dari 2!';
                }
                if ($settings['beasiswa']['max_criteria_count'] < $settings['beasiswa']['min_criteria_count']) {
                    $errors[] = 'Maksimal kriteria harus lebih besar dari minimal kriteria!';
                }
                break;
                
            case 'notification':
                $settings['notification']['email_enabled'] = isset($_POST['email_enabled']);
                $settings['notification']['smtp_host'] = cleanInput($_POST['smtp_host']);
                $settings['notification']['smtp_port'] = (int)$_POST['smtp_port'];
                $settings['notification']['smtp_username'] = cleanInput($_POST['smtp_username']);
                if (!empty($_POST['smtp_password'])) {
                    $settings['notification']['smtp_password'] = $_POST['smtp_password'];
                }
                $settings['notification']['smtp_encryption'] = cleanInput($_POST['smtp_encryption']);
                $settings['notification']['from_name'] = cleanInput($_POST['from_name']);
                $settings['notification']['from_email'] = cleanInput($_POST['from_email']);
                
                // Validate email settings
                if ($settings['notification']['email_enabled']) {
                    if (empty($settings['notification']['smtp_host'])) {
                        $errors[] = 'SMTP Host harus diisi jika email diaktifkan!';
                    }
                    if (!filter_var($settings['notification']['from_email'], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = 'Format from email tidak valid!';
                    }
                }
                break;
                
            case 'security':
                $settings['security']['force_https'] = isset($_POST['force_https']);
                $settings['security']['csrf_protection'] = isset($_POST['csrf_protection']);
                $settings['security']['session_regenerate'] = isset($_POST['session_regenerate']);
                $settings['security']['password_min_length'] = (int)$_POST['password_min_length'];
                $settings['security']['password_require_special'] = isset($_POST['password_require_special']);
                $settings['security']['login_log_enabled'] = isset($_POST['login_log_enabled']);
                
                // Validate password length
                if ($settings['security']['password_min_length'] < 6) {
                    $errors[] = 'Panjang password minimal tidak boleh kurang dari 6 karakter!';
                }
                break;
        }
        
        // Save settings if no errors
        if (empty($errors)) {
            if (saveSettings($settingsFile, $settings)) {
                setAlert('success', 'Pengaturan berhasil disimpan!');
                logger()->info('System settings updated', ['section' => $section]);
            } else {
                setAlert('danger', 'Gagal menyimpan pengaturan!');
            }
        } else {
            foreach ($errors as $error) {
                setAlert('danger', $error);
            }
        }
        
        header('Location: index.php#' . $section);
        exit();
    }
}

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'System Settings', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-gear"></i>
        System Settings
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
        </div>
    </div>
</div>

<!-- Settings Navigation -->
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-list"></i>
                    Settings Categories
                </h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="#school" class="list-group-item list-group-item-action">
                    <i class="bi bi-building"></i> School Information
                </a>
                <a href="#app" class="list-group-item list-group-item-action">
                    <i class="bi bi-app"></i> Application Settings
                </a>
                <a href="#beasiswa" class="list-group-item list-group-item-action">
                    <i class="bi bi-trophy"></i> Beasiswa Settings
                </a>
                <a href="#notification" class="list-group-item list-group-item-action">
                    <i class="bi bi-envelope"></i> Notification Settings
                </a>
                <a href="#security" class="list-group-item list-group-item-action">
                    <i class="bi bi-shield-lock"></i> Security Settings
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <!-- School Information -->
        <div class="card mb-4" id="school">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-building"></i>
                    School Information
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="section" value="school">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="school_name" class="form-label">School Name</label>
                            <input type="text" class="form-control" id="school_name" name="school_name"
                                value="<?php echo htmlspecialchars($settings['school']['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="school_email" class="form-label">School Email</label>
                            <input type="email" class="form-control" id="school_email" name="school_email"
                                value="<?php echo htmlspecialchars($settings['school']['email']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="school_address" class="form-label">School Address</label>
                        <textarea class="form-control" id="school_address" name="school_address" rows="3"
                            required><?php echo htmlspecialchars($settings['school']['address']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="school_phone" class="form-label">School Phone</label>
                            <input type="text" class="form-control" id="school_phone" name="school_phone"
                                value="<?php echo htmlspecialchars($settings['school']['phone']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="school_website" class="form-label">School Website</label>
                            <input type="url" class="form-control" id="school_website" name="school_website"
                                value="<?php echo htmlspecialchars($settings['school']['website']); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save School Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Application Settings -->
        <div class="card mb-4" id="app">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-app"></i>
                    Application Settings
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="section" value="app">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode"
                                    name="maintenance_mode"
                                    <?php echo $settings['app']['maintenance_mode'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">
                                    Maintenance Mode
                                </label>
                                <div class="form-text">When enabled, only admins can access the system</div>
                            </div>

                            <div class="mb-3">
                                <label for="maintenance_message" class="form-label">Maintenance Message</label>
                                <textarea class="form-control" id="maintenance_message" name="maintenance_message"
                                    rows="3"><?php echo htmlspecialchars($settings['app']['maintenance_message']); ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="session_timeout" class="form-label">Session Timeout (seconds)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout"
                                    value="<?php echo $settings['app']['session_timeout']; ?>" min="300" max="86400">
                                <div class="form-text">Session will expire after this time of inactivity</div>
                            </div>

                            <div class="mb-3">
                                <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                <input type="number" class="form-control" id="max_login_attempts"
                                    name="max_login_attempts"
                                    value="<?php echo $settings['app']['max_login_attempts']; ?>" min="1" max="20">
                            </div>

                            <div class="mb-3">
                                <label for="login_lockout_time" class="form-label">Login Lockout Time (seconds)</label>
                                <input type="number" class="form-control" id="login_lockout_time"
                                    name="login_lockout_time"
                                    value="<?php echo $settings['app']['login_lockout_time']; ?>" min="60" max="3600">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup"
                                    <?php echo $settings['app']['auto_backup'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_backup">
                                    Auto Backup
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="backup_retention_days" class="form-label">Backup Retention (days)</label>
                                <input type="number" class="form-control" id="backup_retention_days"
                                    name="backup_retention_days"
                                    value="<?php echo $settings['app']['backup_retention_days']; ?>" min="1" max="365">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save App Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Beasiswa Settings -->
        <div class="card mb-4" id="beasiswa">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-trophy"></i>
                    Beasiswa Settings
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="section" value="beasiswa">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="default_tahun_ajaran" class="form-label">Default Tahun Ajaran</label>
                            <input type="text" class="form-control" id="default_tahun_ajaran"
                                name="default_tahun_ajaran"
                                value="<?php echo htmlspecialchars($settings['beasiswa']['default_tahun_ajaran']); ?>"
                                placeholder="2023/2024">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="calculation_precision" class="form-label">Calculation Precision</label>
                            <input type="number" class="form-control" id="calculation_precision"
                                name="calculation_precision"
                                value="<?php echo $settings['beasiswa']['calculation_precision']; ?>" min="2" max="10">
                            <div class="form-text">Number of decimal places for calculations</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_criteria_count" class="form-label">Minimum Criteria Count</label>
                            <input type="number" class="form-control" id="min_criteria_count" name="min_criteria_count"
                                value="<?php echo $settings['beasiswa']['min_criteria_count']; ?>" min="2" max="20">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="max_criteria_count" class="form-label">Maximum Criteria Count</label>
                            <input type="number" class="form-control" id="max_criteria_count" name="max_criteria_count"
                                value="<?php echo $settings['beasiswa']['max_criteria_count']; ?>" min="2" max="20">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="weight_tolerance" class="form-label">Weight Tolerance</label>
                            <input type="number" class="form-control" id="weight_tolerance" name="weight_tolerance"
                                value="<?php echo $settings['beasiswa']['weight_tolerance']; ?>" step="0.0001"
                                min="0.0001" max="0.1">
                            <div class="form-text">Tolerance for weight sum validation</div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="auto_ranking" name="auto_ranking"
                                    <?php echo $settings['beasiswa']['auto_ranking'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_ranking">
                                    Auto Ranking After Calculation
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Beasiswa Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="card mb-4" id="security">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-shield-lock"></i>
                    Security Settings
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="section" value="security">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="force_https" name="force_https"
                                    <?php echo $settings['security']['force_https'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="force_https">
                                    Force HTTPS
                                </label>
                                <div class="form-text">Redirect HTTP requests to HTTPS</div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="csrf_protection"
                                    name="csrf_protection"
                                    <?php echo $settings['security']['csrf_protection'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="csrf_protection">
                                    CSRF Protection
                                </label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="session_regenerate"
                                    name="session_regenerate"
                                    <?php echo $settings['security']['session_regenerate'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="session_regenerate">
                                    Session Regeneration
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                <input type="number" class="form-control" id="password_min_length"
                                    name="password_min_length"
                                    value="<?php echo $settings['security']['password_min_length']; ?>" min="6"
                                    max="50">
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="password_require_special"
                                    name="password_require_special"
                                    <?php echo $settings['security']['password_require_special'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="password_require_special">
                                    Require Special Characters in Password
                                </label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="login_log_enabled"
                                    name="login_log_enabled"
                                    <?php echo $settings['security']['login_log_enabled'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="login_log_enabled">
                                    Enable Login Logging
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Security Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Smooth scroll to sections
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Highlight active section in navigation
function updateActiveNavigation() {
    const sections = document.querySelectorAll('.card[id]');
    const navLinks = document.querySelectorAll('.list-group-item');

    sections.forEach(section => {
        const rect = section.getBoundingClientRect();
        if (rect.top <= 100 && rect.bottom >= 100) {
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + section.id) {
                    link.classList.add('active');
                }
            });
        }
    });
}

window.addEventListener('scroll', updateActiveNavigation);
window.addEventListener('load', updateActiveNavigation);
</script>

<?php require_once '../../includes/footer.php'; ?>