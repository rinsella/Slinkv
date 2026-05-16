<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Plan;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('public.home', compact('plans'));
    }

    public function quickShorten(Request $request)
    {
        $data = $request->validate([
            'destination_url' => ['required', 'url', 'max:2048'],
        ]);
        $request->session()->put('pending_destination_url', $data['destination_url']);
        if ($request->user()) {
            return redirect()->route('dashboard.links.create')->with('prefill_url', $data['destination_url']);
        }
        return redirect()->route('register')->with('info', 'Daftar gratis untuk membuat shortlink.');
    }

    public function solutions() { return view('public.solutions'); }

    public function howItWorks() { return view('public.how-it-works'); }

    public function pricing()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('public.pricing', compact('plans'));
    }

    public function articles()
    {
        $articles = Article::published()->orderByDesc('published_at')->paginate(9);
        return view('public.articles', compact('articles'));
    }

    public function articleShow(string $slug)
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();
        return view('public.article-show', compact('article'));
    }

    public function faq()
    {
        $faqs = Faq::where('is_active', true)->orderBy('sort_order')->get();
        return view('public.faq', compact('faqs'));
    }

    public function about() { return view('public.about'); }

    public function contact() { return view('public.contact'); }

    public function contactStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create($data + ['status' => 'unread']);
        return back()->with('success', 'Pesan terkirim. Tim kami akan menghubungi Anda.');
    }

    public function terms() { return view('public.legal', ['type' => 'terms', 'title' => 'Syarat & Ketentuan']); }
    public function privacy() { return view('public.legal', ['type' => 'privacy', 'title' => 'Kebijakan Privasi']); }
    public function refund() { return view('public.legal', ['type' => 'refund', 'title' => 'Kebijakan Refund']); }
    public function aup() { return view('public.legal', ['type' => 'aup', 'title' => 'Acceptable Use Policy']); }

    public function robots()
    {
        $content = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /dashboard\nDisallow: /install.php\nDisallow: /login\nDisallow: /register\n\nSitemap: " . url('/sitemap.xml') . "\n";
        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap()
    {
        $urls = [
            url('/'), url('/solusi'), url('/cara-kerja'), url('/paket'),
            url('/artikel'), url('/faq'), url('/tentang'), url('/kontak'),
            url('/terms'), url('/privacy'), url('/refund-policy'), url('/acceptable-use-policy'),
        ];
        foreach (Article::published()->pluck('slug') as $slug) {
            $urls[] = url('/artikel/' . $slug);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url><loc>" . htmlspecialchars($u, ENT_XML1) . "</loc></url>\n";
        }
        $xml .= '</urlset>';
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
