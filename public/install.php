<?php
/**
 * SlinkV Installer (PILIHAN 2 — /install.php standalone).
 *
 * Standalone wizard that may run BEFORE `composer install`. After successful
 * install it writes storage/installed.lock — subsequent runs return 403.
 *
 * Wizard steps:
 *   1. System requirements
 *   2. Database connection (SQLite default, MySQL/Postgres supported)
 *   3. Site config (APP_NAME, APP_URL)
 *   4. Admin account
 *   5. Run migrate + seed (requires composer install)
 *   6. Finish — write installed.lock
 *
 * Self-contained: minimal PHP. No Laravel bootstrap needed until step 5.
 */

declare(strict_types=1);

// Bust OPcache for this file so cPanel can't serve a stale compiled copy.
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

// Visible build marker so user can verify the right file is running.
const INSTALLER_VERSION = 'v0.4.6-2026051605';

// Show all errors inside installer so we never blank-500 on shared hosting.
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$BASE = dirname(__DIR__);
$LOCK = $BASE . '/storage/installed.lock';
$ENV  = $BASE . '/.env';
$ENV_EXAMPLE = $BASE . '/.env.example';

// ---------------------------------------------------------------------------
// Hard guard: if already installed, refuse.
// ---------------------------------------------------------------------------
if (file_exists($LOCK)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Installer terkunci. Aplikasi sudah terinstall.\n";
    exit;
}

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_token'];

$step = (int)($_GET['step'] ?? 1);
if ($step < 1 || $step > 6) {
    $step = 1;
}

// On a fresh GET of step 5, clear any stale install_log from previous failed
// attempts so the user doesn't see ghost errors from an older code path.
if ($step === 5 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['install_log']);
}

// ---------------------------------------------------------------------------
// Helpers.
// ---------------------------------------------------------------------------
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function env_set(string $path, array $kv): bool {
    if (!file_exists($path)) {
        if (file_exists($path . '.example')) {
            copy($path . '.example', $path);
        } else {
            file_put_contents($path, '');
        }
    }
    $content = file_get_contents($path);
    foreach ($kv as $k => $v) {
        $line = $k . '=' . (preg_match('/\s/', (string)$v) ? '"' . addslashes((string)$v) . '"' : (string)$v);
        if (preg_match('/^' . preg_quote($k, '/') . '=.*$/m', $content)) {
            $content = preg_replace('/^' . preg_quote($k, '/') . '=.*$/m', $line, $content);
        } else {
            $content .= "\n" . $line;
        }
    }
    return file_put_contents($path, $content) !== false;
}

function check_requirements(): array {
    $out = [];
    $out[] = ['PHP >= 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION, true];
    foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'json', 'curl', 'fileinfo', 'xml', 'ctype'] as $ext) {
        $out[] = ['ext-' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'loaded' : 'missing', true];
    }
    global $BASE;
    $out[] = ['storage/ writable', is_writable($BASE . '/storage'), is_writable($BASE . '/storage') ? 'ok' : 'fix permissions', true];
    $out[] = ['bootstrap/cache writable', is_writable($BASE . '/bootstrap/cache'), is_writable($BASE . '/bootstrap/cache') ? 'ok' : 'fix permissions', true];
    // vendor is a soft warning at Step 1 — blocker only at Step 5.
    $vendorOk = file_exists($BASE . '/vendor/autoload.php');
    $out[] = ['vendor/autoload.php', $vendorOk, $vendorOk ? 'present' : 'belum ada (jalankan: composer install) — wajib sebelum Step 5', false];
    return $out;
}

function test_database(array $cfg): array {
    try {
        if ($cfg['driver'] === 'sqlite') {
            $path = $cfg['database'];
            if (!file_exists($path)) {
                @touch($path);
            }
            if (!file_exists($path)) {
                return [false, "Tidak bisa membuat file SQLite di {$path}"];
            }
            $pdo = new PDO('sqlite:' . $path);
        } elseif ($cfg['driver'] === 'mysql') {
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cfg['username'], $cfg['password']);
        } elseif ($cfg['driver'] === 'pgsql') {
            $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']}";
            $pdo = new PDO($dsn, $cfg['username'], $cfg['password']);
        } else {
            return [false, 'Driver tidak dikenal'];
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return [true, 'Koneksi sukses'];
    } catch (Throwable $e) {
        // Never leak credentials in error messages.
        return [false, 'Koneksi database gagal. Periksa host/port/credential.'];
    }
}

// ---------------------------------------------------------------------------
// POST handling.
// ---------------------------------------------------------------------------
$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($CSRF, $token)) {
        $errors[] = 'Sesi installer tidak valid. Refresh halaman.';
        goto render;
    }
    if ($step === 2) {
        $cfg = [
            'driver'   => $_POST['driver'] ?? 'sqlite',
            'host'     => trim($_POST['host'] ?? '127.0.0.1'),
            'port'     => trim($_POST['port'] ?? '3306'),
            'database' => trim($_POST['database'] ?? ($BASE . '/database/database.sqlite')),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
        ];
        [$ok, $msg] = test_database($cfg);
        if (!$ok) {
            $errors[] = 'Database: ' . $msg;
        } else {
            $_SESSION['db'] = $cfg;
            $notice = $msg;
            header('Location: install.php?step=3'); exit;
        }
    } elseif ($step === 3) {
        $site = [
            'app_name' => trim($_POST['app_name'] ?? 'SlinkV'),
            'app_url'  => rtrim(trim($_POST['app_url'] ?? ''), '/'),
        ];
        if ($site['app_name'] === '' || !filter_var($site['app_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Nama situs wajib, APP_URL harus URL valid (https://...).';
        } else {
            $_SESSION['site'] = $site;
            header('Location: install.php?step=4'); exit;
        }
    } elseif ($step === 4) {
        $admin = [
            'name'     => trim($_POST['admin_name'] ?? ''),
            'email'    => trim($_POST['admin_email'] ?? ''),
            'password' => $_POST['admin_password'] ?? '',
            'confirm'  => $_POST['admin_password_confirmation'] ?? '',
        ];
        if ($admin['name'] === '' || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL) || strlen($admin['password']) < 8) {
            $errors[] = 'Nama wajib, email valid, password minimal 8 karakter.';
        } elseif (!hash_equals((string)$admin['password'], (string)$admin['confirm'])) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        } else {
            unset($admin['confirm']);
            $_SESSION['admin'] = $admin;
            header('Location: install.php?step=5'); exit;
        }
    } elseif ($step === 5) {
        if (empty($_SESSION['db']) || empty($_SESSION['site']) || empty($_SESSION['admin'])) {
            $errors[] = 'Sesi tidak lengkap. Ulangi dari Step 1.';
        } elseif (!file_exists($BASE . '/vendor/autoload.php')) {
            $errors[] = 'vendor/autoload.php belum ada. Jalankan: composer install --no-dev --optimize-autoloader';
        } elseif (!is_writable($BASE) && !file_exists($ENV)) {
            $errors[] = 'Folder project tidak writable — tidak bisa membuat .env. Set permission 755 pada folder project.';
        } elseif (file_exists($ENV) && !is_writable($ENV)) {
            $errors[] = '.env ada tapi tidak writable. chmod 664 .env';
        } elseif (!is_writable($BASE . '/storage') || !is_writable($BASE . '/bootstrap/cache')) {
            $errors[] = 'storage/ atau bootstrap/cache/ tidak writable. chmod -R 775 storage bootstrap/cache';
        } else {
            try {
                // Make sure all Laravel runtime directories exist and are
                // writable. Some hosts drop empty dirs during zip extraction,
                // which causes "Please provide a valid cache path" later.
                foreach ([
                    'storage/framework',
                    'storage/framework/cache',
                    'storage/framework/cache/data',
                    'storage/framework/sessions',
                    'storage/framework/views',
                    'storage/framework/testing',
                    'storage/logs',
                    'storage/app',
                    'storage/app/public',
                    'bootstrap/cache',
                ] as $rel) {
                    $abs = $BASE . '/' . $rel;
                    if (!is_dir($abs)) {
                        @mkdir($abs, 0775, true);
                    }
                    @chmod($abs, 0775);
                }

                // Write .env.
                $db = $_SESSION['db'];
                $site = $_SESSION['site'];
                $kv = [
                    'APP_NAME'      => $site['app_name'],
                    'APP_ENV'       => 'production',
                    'APP_DEBUG'     => 'false',
                    'APP_URL'       => $site['app_url'],
                    'DB_CONNECTION' => $db['driver'],
                ];
                if ($db['driver'] === 'sqlite') {
                    if (!is_dir(dirname($db['database']))) {
                        @mkdir(dirname($db['database']), 0755, true);
                    }
                    if (!file_exists($db['database'])) {
                        @touch($db['database']);
                    }
                    if (!file_exists($db['database']) || !is_writable($db['database'])) {
                        throw new RuntimeException('SQLite file tidak bisa dibuat/ditulis: ' . $db['database']);
                    }
                    $kv['DB_DATABASE'] = $db['database'];
                } else {
                    $kv['DB_HOST']     = $db['host'];
                    $kv['DB_PORT']     = $db['port'];
                    $kv['DB_DATABASE'] = $db['database'];
                    $kv['DB_USERNAME'] = $db['username'];
                    $kv['DB_PASSWORD'] = $db['password'];
                }

                // Generate APP_KEY ourselves so we never depend on `php artisan
                // key:generate` (which has caused arg-parsing issues on some
                // shared hosts). Format matches what Laravel writes.
                $kv['APP_KEY'] = 'base64:' . base64_encode(random_bytes(32));

                env_set($ENV, $kv);

                // Bootstrap Laravel.
                require $BASE . '/vendor/autoload.php';
                $app = require_once $BASE . '/bootstrap/app.php';
                $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();

                $log = [];
                $log[] = '[OK] APP_KEY generated in-PHP and written to .env';

                $runArt = function (string $command, array $params = []) use ($kernel, &$log) {
                    $output = new Symfony\Component\Console\Output\BufferedOutput();
                    try {
                        $code = $kernel->call($command, $params, $output);
                    } catch (Throwable $e) {
                        $log[] = '$ artisan ' . $command . "\nEXCEPTION: " . $e->getMessage();
                        return false;
                    }
                    $log[] = '$ artisan ' . $command . "\n" . $output->fetch();
                    return $code === 0;
                };

                // APP_KEY already written, skip key:generate.
                $okKey  = true;
                $okMig  = $runArt('migrate', ['--force' => true]);
                $okSeed = $runArt('db:seed', ['--force' => true]);

                // Create admin via Eloquent.
                $admin = $_SESSION['admin'];
                try {
                    $userClass = 'App\\Models\\User';
                    $existing = $userClass::where('email', $admin['email'])->first();
                    if ($existing) {
                        $existing->update([
                            'name'     => $admin['name'],
                            'password' => password_hash($admin['password'], PASSWORD_BCRYPT),
                            'role'     => 'admin',
                            'status'   => 'active',
                        ]);
                    } else {
                        $userClass::create([
                            'name'     => $admin['name'],
                            'email'    => $admin['email'],
                            'password' => password_hash($admin['password'], PASSWORD_BCRYPT),
                            'role'     => 'admin',
                            'status'   => 'active',
                            'email_verified_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                    $okAdmin = true;
                } catch (Throwable $e) {
                    $okAdmin = false;
                    $log[] = 'Admin create error: ' . $e->getMessage();
                }

                $_SESSION['install_log'] = preg_replace(
                    '/(password[^=]*=)\s*\S+/i',
                    '$1[redacted]',
                    implode("\n\n", $log)
                );
                if ($okKey && $okMig && $okSeed && $okAdmin) {
                    if (!is_dir(dirname($LOCK))) {
                        @mkdir(dirname($LOCK), 0755, true);
                    }
                    file_put_contents($LOCK, date('c') . "\n");
                    header('Location: install.php?step=6'); exit;
                }
                $errors[] = 'Instalasi gagal. Lihat log di bawah.';
            } catch (Throwable $e) {
                $errors[] = 'FATAL: ' . $e->getMessage();
                $_SESSION['install_log'] = ($_SESSION['install_log'] ?? '')
                    . "\n\nFATAL EXCEPTION:\n" . $e->getMessage()
                    . "\n" . $e->getFile() . ':' . $e->getLine()
                    . "\n" . $e->getTraceAsString();
            }
        }
    }
}

render:
// ---------------------------------------------------------------------------
// Render.
// ---------------------------------------------------------------------------
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SlinkV Installer — Step <?= $step ?>/6</title>
<style>
:root { color-scheme: light; }
* { box-sizing: border-box; }
body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; margin: 0; background: #F8FAFC; color: #0F172A; }
.wrap { max-width: 720px; margin: 40px auto; padding: 0 16px; }
.card { background: #fff; border: 1px solid #E2E8F0; border-radius: 18px; padding: 28px; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 16px rgba(15,23,42,.04); }
h1 { margin: 0 0 4px; font-size: 24px; }
h1 .v { background: linear-gradient(90deg,#2563EB,#7C3AED); -webkit-background-clip: text; background-clip: text; color: transparent; }
.steps { display: flex; gap: 6px; margin: 16px 0 24px; }
.steps div { flex: 1; height: 6px; background: #E2E8F0; border-radius: 99px; }
.steps div.done { background: #2563EB; }
label { display: block; font-weight: 600; margin: 12px 0 4px; font-size: 14px; }
input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 14px; font-family: inherit; }
button, .btn { display: inline-block; padding: 10px 20px; background: #2563EB; color: #fff; border: 0; border-radius: 10px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 14px; }
button:hover, .btn:hover { background: #1D4ED8; }
.muted { color: #64748B; font-size: 13px; }
.err { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; padding: 12px; border-radius: 10px; margin: 12px 0; }
.ok { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; padding: 12px; border-radius: 10px; margin: 12px 0; }
table { width: 100%; border-collapse: collapse; }
td { padding: 6px 8px; border-bottom: 1px solid #F1F5F9; font-size: 14px; }
td.bad { color: #B91C1C; }
td.good { color: #047857; }
pre { background: #0F172A; color: #F8FAFC; padding: 12px; border-radius: 10px; overflow: auto; font-size: 12px; max-height: 320px; }
.row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>Slink<span class="v">V</span> Installer</h1>
    <div class="muted">Step <?= $step ?> dari 6 · build <?= h(INSTALLER_VERSION) ?></div>
    <div class="steps">
      <?php for ($i = 1; $i <= 6; $i++): ?>
        <div class="<?= $i <= $step ? 'done' : '' ?>"></div>
      <?php endfor; ?>
    </div>

    <?php foreach ($errors as $err): ?>
      <div class="err"><?= h($err) ?></div>
    <?php endforeach; ?>
    <?php if ($notice): ?>
      <div class="ok"><?= h($notice) ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
      <h2>Step 1 — Cek Sistem</h2>
      <table>
        <?php $reqs = check_requirements(); $allOk = true; foreach ($reqs as $r): if ($r[3] && !$r[1]) $allOk = false; ?>
          <tr>
            <td><?= h($r[0]) ?></td>
            <td class="<?= $r[1] ? 'good' : ($r[3] ? 'bad' : 'muted') ?>"><?= h($r[2]) ?><?= !$r[3] && !$r[1] ? ' (warning)' : '' ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <p style="margin-top:20px;">
        <?php if ($allOk): ?>
          <a class="btn" href="install.php?step=2">Lanjut →</a>
        <?php else: ?>
          <span class="muted">Perbaiki item di atas, lalu refresh halaman ini.</span>
        <?php endif; ?>
      </p>

    <?php elseif ($step === 2): ?>
      <h2>Step 2 — Database</h2>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($CSRF) ?>">
        <label>Driver</label>
        <select name="driver" id="driver" onchange="document.getElementById('mysql_fields').style.display=this.value==='sqlite'?'none':'block';document.getElementById('sqlite_fields').style.display=this.value==='sqlite'?'block':'none';">
          <option value="sqlite">SQLite (paling mudah, single-file)</option>
          <option value="mysql">MySQL / MariaDB</option>
          <option value="pgsql">PostgreSQL</option>
        </select>
        <div id="sqlite_fields">
          <label>Path file SQLite</label>
          <input name="database" value="<?= h($BASE . '/database/database.sqlite') ?>">
        </div>
        <div id="mysql_fields" style="display:none">
          <div class="row">
            <div><label>Host</label><input name="host" value="127.0.0.1"></div>
            <div><label>Port</label><input name="port" value="3306"></div>
          </div>
          <label>Nama Database</label><input name="database" value="slinkv">
          <div class="row">
            <div><label>Username</label><input name="username" value="root"></div>
            <div><label>Password</label><input type="password" name="password"></div>
          </div>
        </div>
        <p style="margin-top:20px;"><button type="submit">Test &amp; Lanjut →</button></p>
      </form>

    <?php elseif ($step === 3): ?>
      <h2>Step 3 — Konfigurasi Situs</h2>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($CSRF) ?>">
        <label>Nama Situs (APP_NAME)</label>
        <input name="app_name" value="SlinkV" required>
        <label>URL Situs (APP_URL — wajib https di production)</label>
        <input name="app_url" placeholder="https://slinkv.example.com" required>
        <p style="margin-top:20px;"><button type="submit">Lanjut →</button></p>
      </form>

    <?php elseif ($step === 4): ?>
      <h2>Step 4 — Akun Admin Pertama</h2>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($CSRF) ?>">
        <label>Nama</label><input name="admin_name" required>
        <label>Email</label><input type="email" name="admin_email" required>
        <label>Password (minimal 8 karakter)</label><input type="password" name="admin_password" minlength="8" required>
        <label>Konfirmasi Password</label><input type="password" name="admin_password_confirmation" minlength="8" required>
        <p style="margin-top:20px;"><button type="submit">Lanjut →</button></p>
      </form>

    <?php elseif ($step === 5): ?>
      <h2>Step 5 — Migrate, Seed, &amp; Buat Admin</h2>
      <p class="muted">Aksi ini akan: menulis <code>.env</code>, <code>php artisan key:generate</code>, <code>migrate --force</code>, <code>db:seed --force</code>, lalu membuat akun admin.</p>
      <?php if (!file_exists($BASE . '/vendor/autoload.php')): ?>
        <div class="err">vendor/autoload.php WAJIB ada untuk Step 5. Jalankan dulu di terminal: <code>composer install --no-dev --optimize-autoloader &amp;&amp; npm ci &amp;&amp; npm run build</code></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['install_log'])): ?>
        <pre><?= h($_SESSION['install_log']) ?></pre>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h($CSRF) ?>">
        <p><button type="submit">Jalankan Install</button></p>
      </form>

    <?php elseif ($step === 6): ?>
      <h2>Step 6 — Selesai 🎉</h2>
      <div class="ok">SlinkV berhasil terinstall. File <code>storage/installed.lock</code> sudah dibuat — installer terkunci.</div>
      <p>Langkah berikutnya:</p>
      <ol>
        <li>Hapus <code>public/install.php</code> dari production (opsional, sudah dilindungi lock).</li>
        <li>Set permission <code>storage/</code> dan <code>bootstrap/cache/</code> writable oleh web server.</li>
        <li>Konfigurasi cron untuk <code>php artisan schedule:run</code>.</li>
        <li>Konfigurasi HTTPS &amp; reverse proxy.</li>
      </ol>
      <p><a class="btn" href="<?= h($_SESSION['site']['app_url'] ?? '/') ?>/login">Login ke Admin →</a></p>
      <?php session_destroy(); ?>
    <?php endif; ?>
  </div>
  <p class="muted" style="text-align:center; margin-top:20px;">SlinkV Installer <?= h(INSTALLER_VERSION) ?> · file mtime <?= h(date('Y-m-d H:i:s', @filemtime(__FILE__) ?: 0)) ?></p>
</div>
</body>
</html>
