<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotRule;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBotRuleController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index() { return view('admin.bot-rules.index', ['rules' => BotRule::orderBy('id', 'desc')->paginate(30)]); }
    public function create() { return view('admin.bot-rules.form', ['rule' => new BotRule(['is_active' => true, 'score' => 50, 'type' => 'user_agent_contains'])]); }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $rule = BotRule::create($data);
        $this->audit->log('bot_rule_create', $rule);
        return redirect()->route('admin.bot-rules.index')->with('success', 'Bot rule dibuat.');
    }

    public function edit(BotRule $bot_rule) { return view('admin.bot-rules.form', ['rule' => $bot_rule]); }

    public function update(Request $request, BotRule $bot_rule)
    {
        $data = $this->validateData($request);
        $old = $bot_rule->only(array_keys($data));
        $bot_rule->update($data);
        [$o, $n] = $this->audit->diff($old, $bot_rule->only(array_keys($data)));
        $this->audit->log('bot_rule_update', $bot_rule, $o, $n);
        return redirect()->route('admin.bot-rules.index')->with('success', 'Bot rule diperbarui.');
    }

    public function toggle(BotRule $bot_rule)
    {
        $bot_rule->update(['is_active' => !$bot_rule->is_active]);
        $this->audit->log('bot_rule_toggle', $bot_rule, null, ['is_active' => $bot_rule->is_active]);
        return back()->with('success', 'Status rule diperbarui.');
    }

    public function destroy(BotRule $bot_rule)
    {
        $this->audit->log('bot_rule_delete', $bot_rule, ['name' => $bot_rule->name]);
        $bot_rule->delete();
        return redirect()->route('admin.bot-rules.index')->with('success', 'Rule dihapus.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['user_agent_contains', 'ip_rate', 'header_missing', 'country', 'referer', 'custom'])],
            'pattern' => ['nullable', 'string', 'max:200'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        return $data;
    }
}
