<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminArticleController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = Article::query();
        if ($s = $request->get('q')) $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('slug', 'like', "%{$s}%"));
        if ($status = $request->get('status')) $q->where('status', $status);
        $articles = $q->latest()->paginate(20)->withQueryString();
        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article(['status' => 'draft'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $article = Article::create($data);
        $this->audit->log('article_create', $article, null, ['title' => $article->title]);
        return redirect()->route('admin.articles.index')->with('success', 'Artikel dibuat.');
    }

    public function edit(Article $article)
    {
        return view('admin.articles.form', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        $data = $this->validateData($request, $article->id);
        $old = $article->only(array_keys($data));
        $article->update($data);
        [$o, $n] = $this->audit->diff($old, $article->only(array_keys($data)));
        $this->audit->log('article_update', $article, $o, $n);
        return redirect()->route('admin.articles.index')->with('success', 'Artikel diperbarui.');
    }

    public function publish(Article $article)
    {
        $article->update(['status' => 'published', 'published_at' => $article->published_at ?? now()]);
        $this->audit->log('article_publish', $article);
        return back()->with('success', 'Artikel dipublikasikan.');
    }

    public function draft(Article $article)
    {
        $article->update(['status' => 'draft']);
        $this->audit->log('article_draft', $article);
        return back()->with('success', 'Artikel dijadikan draft.');
    }

    public function destroy(Article $article)
    {
        $this->audit->log('article_delete', $article, ['title' => $article->title]);
        $article->delete();
        return redirect()->route('admin.articles.index')->with('success', 'Artikel dihapus.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('articles', 'slug')->ignore($id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:300'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        return $data;
    }
}
