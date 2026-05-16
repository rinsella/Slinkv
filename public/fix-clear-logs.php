<?php
/**
 * Slinkv hotfix — v0.4.9 — Clear Logs feature
 *
 * Adds a "Clear logs" admin action on /admin/click-logs and /admin/bot-logs
 * so growing log tables can be pruned safely from the UI.
 *
 * What it does (idempotent — safe to run multiple times):
 *   1. Overwrites app/Http/Controllers/Admin/AdminClickLogController.php
 *      (adds clear() method, chunked delete in 5k batches).
 *   2. Overwrites app/Http/Controllers/Admin/AdminBotLogController.php
 *      (adds clear() method, chunked delete in 5k batches).
 *   3. Patches routes/web.php to add:
 *        POST admin/click-logs/clear  (name: admin.click-logs.clear)
 *        POST admin/bot-logs/clear    (name: admin.bot-logs.clear)
 *   4. Overwrites resources/views/admin/click-logs.blade.php and
 *      resources/views/admin/bot-logs.blade.php to render the Clear buttons
 *      + session flash messages.
 *   5. Clears bootstrap/view caches so the changes take effect immediately.
 *
 * Backups of every original file are written as <file>.bak-clearlogs.
 *
 * Usage:
 *   1. Upload this file to public/ on the server.
 *   2. Visit https://YOURDOMAIN/fix-clear-logs.php?key=slinkv-fix-2026
 *   3. DELETE this file when the report shows all [OK].
 */

declare(strict_types=1);

if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

const FIX_TOKEN = 'slinkv-fix-2026';

@ini_set('display_errors', '1');
error_reporting(E_ALL);

if (($_GET['key'] ?? '') !== FIX_TOKEN) {
    http_response_code(403);
    exit('Forbidden.');
}

header('Content-Type: text/plain; charset=utf-8');

$BASE = dirname(__DIR__);
$OK   = '[OK]  ';
$SKIP = '[SKIP]';
$FAIL = '[FAIL]';

echo "Slinkv clear-logs hotfix v0.4.9\n";
echo "BASE = $BASE\n\n";

function backup(string $file): void {
    if (is_file($file) && !is_file($file . '.bak-clearlogs')) {
        @copy($file, $file . '.bak-clearlogs');
    }
}
function writeIfChanged(string $file, string $new): string {
    global $OK, $SKIP, $FAIL;
    $cur = is_file($file) ? (string) file_get_contents($file) : '';
    if ($cur === $new) return $SKIP . " unchanged: " . basename($file);
    backup($file);
    if (file_put_contents($file, $new) === false) return $FAIL . " write: $file";
    if (function_exists('opcache_invalidate')) @opcache_invalidate($file, true);
    return $OK . " patched: " . basename($file);
}

// ---------------------------------------------------------------------------
// STEP 1 — AdminClickLogController
// ---------------------------------------------------------------------------
$clickCtl = <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\ClickLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminClickLogController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = ClickLog::with('shortLink:id,slug,user_id');
        if ($t = $request->get('type')) {
            if ($t === 'bot') $q->where('is_bot', true);
            if ($t === 'human') $q->where('is_bot', false);
        }
        if ($cc = $request->get('country')) $q->where('country_code', strtoupper($cc));
        if ($s = $request->get('source')) $q->where('source_platform', $s);
        if ($slug = $request->get('short_link')) {
            $q->whereHas('shortLink', fn ($l) => $l->where('slug', 'like', "%{$slug}%"));
        }
        $logs = $q->latest('clicked_at')->paginate(30)->withQueryString();
        return view('admin.click-logs', compact('logs'));
    }

    public function blockIp(ClickLog $log)
    {
        if (empty($log->ip_hash)) {
            return back()->with('error', 'Log ini tidak punya IP hash.');
        }
        $ip = BlockedIp::updateOrCreate(
            ['ip_hash' => $log->ip_hash],
            ['reason' => "Blocked from click log #{$log->id}", 'is_active' => true]
        );
        $this->audit->log('blocked_ip_create_from_log', $ip);
        return back()->with('success', 'IP diblokir.');
    }

    /**
     * Bulk clear click logs.
     * scope = all | bots | older_30d
     * Uses chunked delete to avoid table-lock on large tables.
     */
    public function clear(Request $request)
    {
        $request->validate(['scope' => ['required', 'in:all,bots,older_30d']]);
        $scope = $request->input('scope');

        $base = DB::table('click_logs');
        $label = '';
        switch ($scope) {
            case 'bots':
                $base->where('is_bot', true);
                $label = 'bot click logs';
                break;
            case 'older_30d':
                $base->where('clicked_at', '<', now()->subDays(30));
                $label = 'click logs older than 30 days';
                break;
            case 'all':
            default:
                $label = 'ALL click logs';
                break;
        }

        $total = 0;
        do {
            $deleted = (clone $base)->limit(5000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->audit->log('click_logs_cleared', null, null, [
            'scope' => $scope,
            'deleted' => $total,
        ]);

        return back()->with('success', "Berhasil hapus {$total} {$label}.");
    }
}
PHP;
echo writeIfChanged($BASE . '/app/Http/Controllers/Admin/AdminClickLogController.php', $clickCtl) . "\n";

// ---------------------------------------------------------------------------
// STEP 2 — AdminBotLogController
// ---------------------------------------------------------------------------
$botCtl = <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\BotRule;
use App\Models\ClickLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBotLogController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = ClickLog::with('shortLink:id,slug')->where(function ($w) {
            $w->where('is_bot', true)->orWhere('bot_score', '>=', 40);
        });
        if ($s = $request->get('q')) {
            $q->whereHas('shortLink', fn ($l) => $l->where('slug', 'like', "%{$s}%"));
        }
        $logs = $q->latest('clicked_at')->paginate(30)->withQueryString();
        return view('admin.bot-logs', compact('logs'));
    }

    public function blockIp(ClickLog $log)
    {
        if (empty($log->ip_hash)) {
            return back()->with('error', 'Log ini tidak punya IP hash.');
        }
        $ip = BlockedIp::updateOrCreate(
            ['ip_hash' => $log->ip_hash],
            ['reason' => "Blocked from bot log #{$log->id}", 'is_active' => true]
        );
        $this->audit->log('blocked_ip_create_from_log', $ip);
        return back()->with('success', 'IP diblokir.');
    }

    public function createUserAgentRule(ClickLog $log)
    {
        if (empty($log->user_agent)) {
            return back()->with('error', 'Log ini tidak punya user agent.');
        }
        $pattern = substr($log->user_agent, 0, 120);
        $rule = BotRule::create([
            'name'      => "UA from log #{$log->id}",
            'type'      => 'user_agent_contains',
            'pattern'   => $pattern,
            'score'     => 70,
            'is_active' => true,
        ]);
        $this->audit->log('bot_rule_create_from_log', $rule);
        return back()->with('success', 'Rule user-agent dibuat.');
    }

    /**
     * Bulk clear bot logs (rows flagged as bot or with high bot_score).
     * scope = all | older_30d
     */
    public function clear(Request $request)
    {
        $request->validate(['scope' => ['required', 'in:all,older_30d']]);
        $scope = $request->input('scope');

        $base = DB::table('click_logs')->where(function ($w) {
            $w->where('is_bot', true)->orWhere('bot_score', '>=', 40);
        });
        $label = 'bot logs';
        if ($scope === 'older_30d') {
            $base->where('clicked_at', '<', now()->subDays(30));
            $label = 'bot logs older than 30 days';
        }

        $total = 0;
        do {
            $deleted = (clone $base)->limit(5000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->audit->log('bot_logs_cleared', null, null, [
            'scope' => $scope,
            'deleted' => $total,
        ]);

        return back()->with('success', "Berhasil hapus {$total} {$label}.");
    }
}
PHP;
echo writeIfChanged($BASE . '/app/Http/Controllers/Admin/AdminBotLogController.php', $botCtl) . "\n";

// ---------------------------------------------------------------------------
// STEP 3 — routes/web.php (add 2 POST routes)
// ---------------------------------------------------------------------------
$routesFile = $BASE . '/routes/web.php';
if (!is_file($routesFile)) {
    echo "$FAIL routes/web.php missing\n";
} else {
    $routes = (string) file_get_contents($routesFile);
    $needles = [
        "click-logs.clear" => [
            "Route::get('click-logs', [AdminClickLogController::class, 'index'])->name('click-logs');\n    Route::post('click-logs/{log}/block-ip'",
            "Route::get('click-logs', [AdminClickLogController::class, 'index'])->name('click-logs');\n    Route::post('click-logs/clear', [AdminClickLogController::class, 'clear'])->name('click-logs.clear');\n    Route::post('click-logs/{log}/block-ip'",
        ],
        "bot-logs.clear" => [
            "Route::get('bot-logs', [AdminBotLogController::class, 'index'])->name('bot-logs');\n    Route::post('bot-logs/{log}/block-ip'",
            "Route::get('bot-logs', [AdminBotLogController::class, 'index'])->name('bot-logs');\n    Route::post('bot-logs/clear', [AdminBotLogController::class, 'clear'])->name('bot-logs.clear');\n    Route::post('bot-logs/{log}/block-ip'",
        ],
    ];
    $touched = false;
    foreach ($needles as $label => [$old, $new]) {
        if (str_contains($routes, "name('$label')")) {
            echo "$SKIP route $label already present\n";
            continue;
        }
        if (!str_contains($routes, $old)) {
            echo "$FAIL anchor not found for $label — manual edit needed\n";
            continue;
        }
        $routes = str_replace($old, $new, $routes);
        $touched = true;
        echo "$OK route $label inserted\n";
    }
    if ($touched) {
        backup($routesFile);
        file_put_contents($routesFile, $routes);
        if (function_exists('opcache_invalidate')) @opcache_invalidate($routesFile, true);
    }
}

// ---------------------------------------------------------------------------
// STEP 4 — Views
// ---------------------------------------------------------------------------
$clickView = <<<'BLADE'
@extends('layouts.admin')
@section('title','Click Logs')
@section('content')
@if (session('success'))
  <div class="mb-3 p-3 rounded-xl bg-emerald-50 text-emerald-700 text-sm">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="mb-3 p-3 rounded-xl bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif
<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
  <form method="GET" class="flex flex-wrap gap-2">
    <input name="short_link" value="{{ request('short_link') }}" placeholder="Slug..." class="rounded-xl border-line text-sm">
    <select name="type" class="rounded-xl border-line text-sm"><option value="">Semua</option><option value="human" @selected(request('type')==='human')>Human</option><option value="bot" @selected(request('type')==='bot')>Bot</option></select>
    <input name="country" value="{{ request('country') }}" placeholder="Country code..." class="rounded-xl border-line text-sm uppercase" maxlength="2">
    <input name="source" value="{{ request('source') }}" placeholder="Source platform..." class="rounded-xl border-line text-sm">
    <button class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-semibold">Filter</button>
  </form>
  <div class="flex flex-wrap gap-2">
    <form method="POST" action="{{ route('admin.click-logs.clear') }}" onsubmit="return confirm('Hapus log lebih dari 30 hari?')">@csrf
      <input type="hidden" name="scope" value="older_30d">
      <button class="px-3 py-2 rounded-xl bg-amber-50 text-amber-700 text-sm font-semibold border border-amber-200 hover:bg-amber-100">Clear &gt;30 hari</button>
    </form>
    <form method="POST" action="{{ route('admin.click-logs.clear') }}" onsubmit="return confirm('Hapus SEMUA log bot? Tindakan ini tidak bisa dibatalkan.')">@csrf
      <input type="hidden" name="scope" value="bots">
      <button class="px-3 py-2 rounded-xl bg-orange-50 text-orange-700 text-sm font-semibold border border-orange-200 hover:bg-orange-100">Clear semua Bot</button>
    </form>
    <form method="POST" action="{{ route('admin.click-logs.clear') }}" onsubmit="return confirm('HAPUS SEMUA CLICK LOGS? Tindakan ini tidak bisa dibatalkan.')">@csrf
      <input type="hidden" name="scope" value="all">
      <button class="px-3 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700">Clear SEMUA</button>
    </form>
  </div>
</div>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($logs->isEmpty())<div class="p-12 text-center text-muted">Belum ada klik tercatat.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr>
    <th class="text-left p-3">Waktu</th>
    <th class="text-left p-3">Link</th>
    <th class="text-left p-3">Action</th>
    <th class="text-left p-3">Type</th>
    <th class="text-left p-3">Country</th>
    <th class="text-left p-3">Source</th>
    <th class="text-center p-3">Bot Score</th>
    <th class="text-left p-3">UA</th>
    <th class="p-3">Aksi</th>
  </tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($logs as $log)
    @php
      $score = (int) ($log->bot_score ?? 0);
      $action = $log->action ?: ($log->is_bot ? 'blocked' : 'redirected');
      $actionColor = match($action) {
        'redirected' => 'bg-emerald-50 text-emerald-700',
        'blocked' => 'bg-red-50 text-red-700',
        'expired' => 'bg-slate-100 text-slate-700',
        'quota_exceeded' => 'bg-amber-50 text-amber-700',
        'password_required' => 'bg-indigo-50 text-indigo-700',
        default => 'bg-slate-100',
      };
    @endphp
    <tr>
      <td class="p-3 text-xs">{{ optional($log->clicked_at ?? $log->created_at)->format('d/m H:i:s') }}</td>
      <td class="p-3 font-mono text-xs">{{ $log->shortLink?->slug ?? '-' }}</td>
      <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs {{ $actionColor }}">{{ $action }}</span></td>
      <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs {{ $log->is_bot ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $log->is_bot ? 'bot' : 'human' }}</span></td>
      <td class="p-3 text-xs">{{ $log->country_code ?: '-' }}</td>
      <td class="p-3 text-xs">{{ $log->source_platform ?: '-' }}</td>
      <td class="p-3 text-center text-xs font-semibold {{ $score >= 70 ? 'text-red-600' : ($score >= 40 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $score }}</td>
      <td class="p-3 text-xs text-muted">{{ \Illuminate\Support\Str::limit($log->user_agent, 40) }}</td>
      <td class="p-3 text-right whitespace-nowrap">
        @if ($log->shortLink)
          <a href="{{ route('admin.links.show', $log->shortLink) }}" class="text-xs text-primary">View</a>
        @endif
        @if ($log->ip_hash)
          <form method="POST" action="{{ route('admin.click-logs.block-ip', $log) }}" class="inline" onsubmit="return confirm('Block IP dari log ini?')">@csrf
            <button class="text-xs text-red-600 ml-2">Block IP</button>
          </form>
        @endif
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $logs->withQueryString()->links() }}</div>
@endsection
BLADE;
echo writeIfChanged($BASE . '/resources/views/admin/click-logs.blade.php', $clickView) . "\n";

$botView = <<<'BLADE'
@extends('layouts.admin')
@section('title','Bot Logs')
@section('content')
@if (session('success'))
  <div class="mb-3 p-3 rounded-xl bg-emerald-50 text-emerald-700 text-sm">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="mb-3 p-3 rounded-xl bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif
<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
  <p class="text-sm text-muted">Klik dengan skor bot tinggi atau terdeteksi sebagai bot.</p>
  <div class="flex flex-wrap gap-2">
    <form method="POST" action="{{ route('admin.bot-logs.clear') }}" onsubmit="return confirm('Hapus bot logs lebih dari 30 hari?')">@csrf
      <input type="hidden" name="scope" value="older_30d">
      <button class="px-3 py-2 rounded-xl bg-amber-50 text-amber-700 text-sm font-semibold border border-amber-200 hover:bg-amber-100">Clear &gt;30 hari</button>
    </form>
    <form method="POST" action="{{ route('admin.bot-logs.clear') }}" onsubmit="return confirm('HAPUS SEMUA BOT LOGS? Tindakan ini tidak bisa dibatalkan.')">@csrf
      <input type="hidden" name="scope" value="all">
      <button class="px-3 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700">Clear SEMUA Bot Logs</button>
    </form>
  </div>
</div>
<div class="bg-white rounded-2xl border border-line overflow-x-auto">
@if ($logs->isEmpty())<div class="p-12 text-center text-muted">Tidak ada aktivitas bot terdeteksi.</div>
@else
<table class="w-full text-sm">
  <thead class="bg-surface text-xs text-muted"><tr>
    <th class="text-left p-3">Waktu</th>
    <th class="text-left p-3">Link</th>
    <th class="text-center p-3">Skor</th>
    <th class="text-left p-3">Klasifikasi</th>
    <th class="text-left p-3">Alasan</th>
    <th class="text-left p-3">UA</th>
    <th class="text-left p-3">Country</th>
    <th class="p-3">Aksi</th>
  </tr></thead>
  <tbody class="divide-y divide-line">
  @foreach ($logs as $log)
    @php
      $reasons = is_array($log->bot_reasons)
          ? implode(', ', $log->bot_reasons)
          : ($log->bot_reasons ?: '-');
      $score = (int) ($log->bot_score ?? 0);
      if ($log->is_bot || $score >= 70) {
          $klass = 'BOT';
          $klassColor = 'bg-red-50 text-red-700';
      } elseif ($score >= 40) {
          $klass = 'Suspicious';
          $klassColor = 'bg-amber-50 text-amber-700';
      } else {
          $klass = 'Human';
          $klassColor = 'bg-emerald-50 text-emerald-700';
      }
    @endphp
    <tr>
      <td class="p-3 text-xs">{{ optional($log->clicked_at ?? $log->created_at)->format('d/m H:i:s') }}</td>
      <td class="p-3 font-mono text-xs">{{ $log->shortLink?->slug ?? '-' }}</td>
      <td class="p-3 text-center"><span class="font-bold {{ $score >= 70 ? 'text-red-600' : ($score >= 40 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $score }}</span></td>
      <td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs {{ $klassColor }}">{{ $klass }}</span></td>
      <td class="p-3 text-xs">{{ $reasons }}</td>
      <td class="p-3 text-xs text-muted">{{ \Illuminate\Support\Str::limit($log->user_agent, 50) }}</td>
      <td class="p-3 text-xs">{{ $log->country_code ?: '-' }}</td>
      <td class="p-3 text-right whitespace-nowrap">
        @if ($log->shortLink)
          <a href="{{ route('admin.links.show', $log->shortLink) }}" class="text-xs text-primary">View</a>
        @endif
        @if ($log->ip_hash)
          <form method="POST" action="{{ route('admin.bot-logs.block-ip', $log) }}" class="inline" onsubmit="return confirm('Block IP dari log ini?')">@csrf
            <button class="text-xs text-red-600 ml-2">Block IP</button>
          </form>
        @endif
        @if ($log->user_agent)
          <form method="POST" action="{{ route('admin.bot-logs.create-ua-rule', $log) }}" class="inline" onsubmit="return confirm('Buat rule user-agent dari log ini?')">@csrf
            <button class="text-xs text-indigo-600 ml-2">Add UA Rule</button>
          </form>
        @endif
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
@endif
</div>
<div class="mt-4">{{ $logs->withQueryString()->links() }}</div>
@endsection
BLADE;
echo writeIfChanged($BASE . '/resources/views/admin/bot-logs.blade.php', $botView) . "\n";

// ---------------------------------------------------------------------------
// STEP 5 — Clear bootstrap & view caches
// ---------------------------------------------------------------------------
$clears = [
    $BASE . '/bootstrap/cache/config.php',
    $BASE . '/bootstrap/cache/routes-v7.php',
    $BASE . '/bootstrap/cache/services.php',
    $BASE . '/bootstrap/cache/packages.php',
];
foreach ($clears as $f) {
    if (is_file($f)) {
        if (@unlink($f)) echo "$OK cleared " . basename($f) . "\n";
        else echo "$FAIL could not clear " . basename($f) . "\n";
    }
}
$viewsDir = $BASE . '/storage/framework/views';
if (is_dir($viewsDir)) {
    foreach (glob($viewsDir . '/*.php') ?: [] as $f) @unlink($f);
    echo "$OK compiled views cleared\n";
}

echo "\nDone.\n";
echo "IMPORTANT — DELETE this file from public/ now:\n";
echo "  rm " . __FILE__ . "\n";
