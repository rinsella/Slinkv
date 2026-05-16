<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title' => 'Apa Itu URL Shortener dengan Bot Protection?',
                'excerpt' => 'Memahami konsep URL shortener modern yang dilengkapi sistem deteksi bot untuk melindungi campaign digital Anda.',
            ],
            [
                'title' => 'Cara Melindungi Iklan dari Bot Click',
                'excerpt' => 'Strategi praktis menghindari fake click yang dapat merusak performa Meta Ads, Google Ads, dan affiliate Anda.',
            ],
            [
                'title' => 'Kenapa Traffic Bot Bisa Merusak ROAS',
                'excerpt' => 'Pelajari dampak traffic bot terhadap return on ad spend dan bagaimana cara meminimalkannya.',
            ],
            [
                'title' => 'Cara Membaca Analytics Shortlink',
                'excerpt' => 'Panduan singkat memahami metrik human clicks, bot clicks, source platform, dan device tracking.',
            ],
            [
                'title' => 'Cara Audit Sumber Traffic Affiliate',
                'excerpt' => 'Teknik mengidentifikasi sumber traffic affiliate yang memberikan klik berkualitas vs sampah.',
            ],
        ];

        foreach ($articles as $i => $a) {
            Article::updateOrCreate(['slug' => Str::slug($a['title'])], [
                'title' => $a['title'],
                'excerpt' => $a['excerpt'],
                'content' => "<p>{$a['excerpt']}</p><p>Konten lengkap artikel akan segera tersedia. Tim SlinkV terus menambahkan panduan dan studi kasus baru untuk membantu Anda mengoptimalkan campaign digital.</p><h2>Mengapa Penting?</h2><p>Traffic bersih = data analytics akurat = keputusan bisnis lebih tepat. Itu sebabnya SlinkV memprioritaskan bot detection dan transparansi data.</p>",
                'meta_title' => $a['title'] . ' - SlinkV',
                'meta_description' => $a['excerpt'],
                'status' => 'published',
                'published_at' => now()->subDays(30 - $i * 5),
            ]);
        }
    }
}
