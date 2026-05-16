<?php
/**
 * SlinkV Installer — single-file Laravel-independent wizard.
 * Steps: welcome -> requirements -> database -> site -> admin -> install -> done
 */
declare(strict_types=1);

@session_start();

$ROOT = dirname(__DIR__);
$LOCK = $ROOT . '/storage/installed.lock';
$ENV  = $ROOT . '/.env';

if (file_exists($LOCK)) {
    http_response_code(403);
    echo render('Sudah Terpasang', '<div class="card"><h2>Aplikasi sudah terpasang</h2><p>Untuk keamanan, installer dinonaktifkan setelah instalasi selesai. Hapus file <code>public/install.php</code> dari server Anda.</p><a class="btn" href="/">Buka Aplikasi</a></div>');
    exit;
}

// Refuse to run if Laravel vendor is not installed.
if (!file_exists($ROOT . '/vendor/autoload.php')) {
    http_response_code(500);
    echo render('Dependency Belum Terinstall', '<div class="card"><h2>vendor/ belum ada</h2><p>Jalankan <code>composer install --no-dev --optimize-autoloader</code> di server terlebih dahulu, lalu refresh halaman ini.</p></div>');
    exit;
}

if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['_csrf'];

$step = $_GET['step'] ?? 'welcome';
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_csrf']) || !hash_equals($CSRF, $_POST['_csrf'])) {
        $err = 'CSRF token tidak valid. Muat ulang halaman.';
    } else {
        switch ($step) {
            case 'database':
                $cfg = [
                    'driver'   => $_POST['driver'] ?? 'mysql',
                    'host'     => trim($_POST['host'] ?? '127.0.0.1'),
                    'port'     => trim($_POST['port'] ?? '3306'),
                    'database' => trim($_POST['database'] ?? ''),
                    'username' => trim($_POST['username'] ?? ''),
                    'password' => $_POST['password'] ?? '',
                ];
                try {
                    if ($cfg['driver'] === 'sqlite') {
                        $path = $ROOT . '/database/database.sqlite';
                        if (!file_exists($path)) touch($path);
                        new PDO("sqlite:$path");
                    } else {
                        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset=utf8mb4";
                        new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    }
                    $_SESSION['cfg_db'] = $cfg;
                    header('Location: install.php?step=site'); exit;
                } catch (Throwable $e) {
                    $err = 'Koneksi database gagal: ' . $e->getMessage();
                }
                break;
            case 'site':
                $_SESSION['cfg_site'] = [
                    'site_name' => trim($_POST['site_name'] ?? 'SlinkV'),
                    'app_url'   => rtrim(trim($_POST['app_url'] ?? ''), '/'),
                    'support_email' => trim($_POST['support_email'] ?? ''),
                    'support_whatsapp' => trim($_POST['support_whatsapp'] ?? ''),
                ];
                header('Location: install.php?step=admin'); exit;
            case 'admin':
                $email = trim($_POST['email'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $pw = $_POST['password'] ?? '';
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8 || $name === '') {
                    $err = 'Lengkapi data admin dengan benar (password min 8 karakter).';
                } else {
                    $_SESSION['cfg_admin'] = ['name'=>$name,'email'=>$email,'password'=>$pw];
                    header('Location: install.php?step=install'); exit;
                }
                break;
            case 'install':
                $err = run_install($ROOT, $ENV, $LOCK);
                if ($err === null) { header('Location: install.php?step=done'); exit; }
                break;
        }
    }
}

echo render(step_title($step), step_body($step, $err, $CSRF, $ROOT));

// --- Helpers ---

function step_title(string $s): string {
    return [
        'welcome' => 'Selamat Datang',
        'requirements' => 'Cek Persyaratan',
        'database' => 'Konfigurasi Database',
        'site' => 'Pengaturan Situs',
        'admin' => 'Buat Akun Admin',
        'install' => 'Instalasi',
        'done' => 'Selesai',
    ][$s] ?? 'Installer';
}

function step_body(string $step, ?string $err, string $csrf, string $root): string {
    $alert = $err ? '<div class="alert">'.h($err).'</div>' : '';

    return match ($step) {
        'welcome' => '<div class="card"><h2>SlinkV Installer</h2><p>Wizard ini akan memandu Anda memasang SlinkV. Pastikan Anda memiliki akses database (MySQL atau SQLite) dan kredensial admin.</p>'.steps_nav('welcome').'<a class="btn" href="install.php?step=requirements">Mulai →</a></div>',

        'requirements' => (function() use ($csrf) {
            $rows = [
                ['PHP >= 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION],
                ['Ekstensi PDO', extension_loaded('pdo'), ''],
                ['Ekstensi OpenSSL', extension_loaded('openssl'), ''],
                ['Ekstensi Mbstring', extension_loaded('mbstring'), ''],
                ['Ekstensi Tokenizer', extension_loaded('tokenizer'), ''],
                ['Ekstensi XML', extension_loaded('xml'), ''],
                ['Ekstensi Ctype', extension_loaded('ctype'), ''],
                ['Ekstensi JSON', extension_loaded('json'), ''],
                ['Ekstensi BCMath', extension_loaded('bcmath'), ''],
                ['Ekstensi Fileinfo', extension_loaded('fileinfo'), ''],
                ['storage/ writable', is_writable(dirname(__DIR__).'/storage'), ''],
                ['bootstrap/cache writable', is_writable(dirname(__DIR__).'/bootstrap/cache'), ''],
            ];
            $ok = true; $html = '<table class="t"><thead><tr><th>Item</th><th>Status</th></tr></thead><tbody>';
            foreach ($rows as [$l,$pass,$extra]) {
                $ok = $ok && $pass;
                $html .= '<tr><td>'.h($l).' '.($extra ? '<span class="m">'.h($extra).'</span>' : '').'</td><td>'.($pass ? '<span class="ok">OK</span>' : '<span class="bad">!</span>').'</td></tr>';
            }
            $html .= '</tbody></table>';
            $next = $ok ? '<a class="btn" href="install.php?step=database">Lanjut →</a>' : '<p class="m">Perbaiki item yang gagal lalu muat ulang.</p>';
            return '<div class="card"><h2>Persyaratan Sistem</h2>'.steps_nav('requirements').$html.$next.'</div>';
        })(),

        'database' => '<div class="card"><h2>Database</h2>'.steps_nav('database').$alert.'
<form method="POST">
<input type="hidden" name="_csrf" value="'.h($csrf).'">
<label>Driver</label><select name="driver" onchange="this.form.elements.host.disabled=this.value===\'sqlite\';this.form.elements.port.disabled=this.value===\'sqlite\';this.form.elements.database.disabled=this.value===\'sqlite\';this.form.elements.username.disabled=this.value===\'sqlite\';this.form.elements.password.disabled=this.value===\'sqlite\'"><option value="mysql">MySQL</option><option value="sqlite">SQLite (file lokal)</option></select>
<label>Host</label><input name="host" value="127.0.0.1">
<label>Port</label><input name="port" value="3306">
<label>Database</label><input name="database" required>
<label>Username</label><input name="username">
<label>Password</label><input type="password" name="password">
<button class="btn" type="submit">Test & Lanjut →</button>
</form></div>',

        'site' => (function() use ($csrf, $alert) {
            $appUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            return '<div class="card"><h2>Situs</h2>'.steps_nav('site').$alert.'
<form method="POST">
<input type="hidden" name="_csrf" value="'.h($csrf).'">
<label>Nama Situs</label><input name="site_name" value="SlinkV" required>
<label>App URL</label><input name="app_url" value="'.h($appUrl).'" required>
<label>Email Support</label><input type="email" name="support_email" value="support@example.com">
<label>WhatsApp Support (628…)</label><input name="support_whatsapp" value="6281234567890">
<button class="btn" type="submit">Lanjut →</button>
</form></div>';
        })(),

        'admin' => '<div class="card"><h2>Akun Admin</h2>'.steps_nav('admin').$alert.'
<form method="POST">
<input type="hidden" name="_csrf" value="'.h($csrf).'">
<label>Nama</label><input name="name" required>
<label>Email</label><input type="email" name="email" required>
<label>Password (min 8)</label><input type="password" name="password" required minlength="8">
<button class="btn" type="submit">Lanjut →</button>
</form></div>',

        'install' => '<div class="card"><h2>Siap Memasang</h2>'.steps_nav('install').$alert.'<p>Klik tombol untuk menulis <code>.env</code>, menjalankan migrasi, dan membuat akun admin.</p>
<form method="POST">
<input type="hidden" name="_csrf" value="'.h($csrf).'">
<button class="btn" type="submit">Jalankan Instalasi</button>
</form></div>',

        'done' => '<div class="card"><h2>🎉 Selesai</h2>'.steps_nav('done').'<p>Instalasi berhasil. File <code>storage/installed.lock</code> telah dibuat untuk mengunci installer.</p><p><strong>Hapus file <code>public/install.php</code> demi keamanan.</strong></p><a class="btn" href="/">Buka Aplikasi</a> <a class="btn" href="/login">Login Admin</a></div>',

        default => '<div class="card"><p>Step tidak dikenal.</p></div>',
    };
}

function steps_nav(string $current): string {
    $steps = ['welcome'=>'Welcome','requirements'=>'Requirements','database'=>'Database','site'=>'Site','admin'=>'Admin','install'=>'Install','done'=>'Done'];
    $html = '<div class="nav">';
    foreach ($steps as $k => $v) {
        $cls = $k === $current ? 'on' : '';
        $html .= '<span class="n '.$cls.'">'.h($v).'</span>';
    }
    return $html . '</div>';
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function render(string $title, string $body): string {
    return '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' — SlinkV Installer</title>
<style>
*{box-sizing:border-box}body{font-family:ui-sans-serif,system-ui,sans-serif;background:#F8FAFC;color:#0F172A;margin:0;padding:24px}
.wrap{max-width:720px;margin:24px auto}
h1{font-size:28px;margin:0 0 8px}h1 .v{background:linear-gradient(90deg,#2563EB,#7C3AED);-webkit-background-clip:text;background-clip:text;color:transparent}
.card{background:#fff;border:1px solid #E2E8F0;border-radius:18px;padding:28px;margin-top:16px;box-shadow:0 1px 2px rgba(15,23,42,.04),0 4px 16px rgba(15,23,42,.04)}
label{display:block;font-size:13px;font-weight:500;margin:12px 0 4px}
input,select{width:100%;padding:10px 12px;border:1px solid #E2E8F0;border-radius:12px;font-size:14px;background:#fff}
input:focus,select:focus{outline:none;border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.btn{display:inline-block;background:#2563EB;color:#fff;padding:10px 18px;border-radius:12px;font-weight:600;border:none;cursor:pointer;text-decoration:none;font-size:14px;margin-top:16px}
.btn:hover{background:#1D4ED8}
.alert{background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:12px;font-size:14px;margin:12px 0}
.nav{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.n{padding:4px 10px;border-radius:999px;background:#F1F5F9;color:#64748B}
.n.on{background:#2563EB;color:#fff}
.t{width:100%;border-collapse:collapse;font-size:14px}.t th,.t td{padding:8px 6px;border-bottom:1px solid #E2E8F0;text-align:left}
.ok{color:#16A34A;font-weight:700}.bad{color:#DC2626;font-weight:700}.m{color:#64748B;font-size:12px}
code{background:#F1F5F9;padding:2px 6px;border-radius:6px;font-size:13px}
</style></head><body><div class="wrap"><h1>slink<span class="v">v</span> Installer</h1>'.$body.'</div></body></html>';
}

function run_install(string $root, string $envPath, string $lockPath): ?string {
    $db = $_SESSION['cfg_db'] ?? null;
    $site = $_SESSION['cfg_site'] ?? null;
    $admin = $_SESSION['cfg_admin'] ?? null;
    if (!$db || !$site || !$admin) return 'Data konfigurasi tidak lengkap. Mulai dari awal.';

    // 1) Write .env
    $key = '';
    if (file_exists($envPath)) {
        $cur = file_get_contents($envPath);
        if (preg_match('/^APP_KEY=(.+)$/m', $cur, $mm)) $key = trim($mm[1]);
    }
    if ($key === '' || $key === 'base64:') {
        $key = 'base64:' . base64_encode(random_bytes(32));
    }

    $appUrl = $site['app_url'] ?: 'http://localhost';
    $lines = [
        'APP_NAME='.escape_env($site['site_name']),
        'APP_ENV=production',
        'APP_KEY='.$key,
        'APP_DEBUG=false',
        'APP_URL='.$appUrl,
        'LOG_CHANNEL=stack',
        'LOG_LEVEL=warning',
    ];
    if ($db['driver'] === 'sqlite') {
        $lines[] = 'DB_CONNECTION=sqlite';
        $lines[] = 'DB_DATABASE='.$root.'/database/database.sqlite';
    } else {
        $lines = array_merge($lines, [
            'DB_CONNECTION=mysql',
            'DB_HOST='.$db['host'],
            'DB_PORT='.$db['port'],
            'DB_DATABASE='.$db['database'],
            'DB_USERNAME='.$db['username'],
            'DB_PASSWORD='.escape_env($db['password']),
        ]);
    }
    $lines = array_merge($lines, [
        'SESSION_DRIVER=database',
        'SESSION_LIFETIME=120',
        'CACHE_STORE=database',
        'QUEUE_CONNECTION=database',
        'MAIL_MAILER=log',
        'MAIL_FROM_ADDRESS='.escape_env($site['support_email'] ?: 'noreply@slinkv.net'),
        'MAIL_FROM_NAME='.escape_env($site['site_name']),
    ]);
    file_put_contents($envPath, implode("\n", $lines) . "\n");

    // 2) Run migrate + seed
    $php = PHP_BINARY;
    $artisan = $root . '/artisan';
    $cmd1 = escapeshellcmd($php) . ' ' . escapeshellarg($artisan) . ' migrate --force 2>&1';
    $cmd2 = escapeshellcmd($php) . ' ' . escapeshellarg($artisan) . ' db:seed --force 2>&1';
    $cmd3 = escapeshellcmd($php) . ' ' . escapeshellarg($artisan) . ' config:clear 2>&1';

    chdir($root);
    exec($cmd3);
    exec($cmd1, $o1, $r1);
    if ($r1 !== 0) return "Migrasi gagal:\n" . implode("\n", $o1);
    exec($cmd2, $o2, $r2);
    if ($r2 !== 0) return "Seeder gagal:\n" . implode("\n", $o2);

    // 3) Create admin via PDO
    try {
        if ($db['driver'] === 'sqlite') {
            $pdo = new PDO('sqlite:'.$root.'/database/database.sqlite');
        } else {
            $pdo = new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4", $db['username'], $db['password']);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $freePlanId = null;
        $r = $pdo->query("SELECT id FROM plans WHERE slug='free' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($r) $freePlanId = (int) $r['id'];

        $hash = password_hash($admin['password'], PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');
        $refCode = strtolower(bin2hex(random_bytes(4)));

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, plan_id, referral_code, email_verified_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$admin['name'], $admin['email'], $hash, 'admin', 'active', $freePlanId, $refCode, $now, $now, $now]);
        // 10 placeholders matching 10 columns

        // 4) Update settings
        $sets = [
            'site_name' => $site['site_name'],
            'site_url' => $appUrl,
            'support_email' => $site['support_email'],
            'support_whatsapp' => $site['support_whatsapp'],
        ];
        $upd = $pdo->prepare("UPDATE settings SET value=?, updated_at=? WHERE `key`=?");
        foreach ($sets as $k => $v) {
            if ($v === '' || $v === null) continue;
            $upd->execute([(string)$v, $now, $k]);
        }
    } catch (Throwable $e) {
        return 'Gagal membuat akun admin: ' . $e->getMessage();
    }

    // 5) Create lock
    @mkdir(dirname($lockPath), 0755, true);
    file_put_contents($lockPath, "installed at " . date('c') . "\n");
    @chmod($lockPath, 0644);

    unset($_SESSION['cfg_db'], $_SESSION['cfg_site'], $_SESSION['cfg_admin']);
    return null;
}

function escape_env(string $v): string {
    if ($v === '') return '';
    if (preg_match('/\s|"|#/', $v)) {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $v) . '"';
    }
    return $v;
}
