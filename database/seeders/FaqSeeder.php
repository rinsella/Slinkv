<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['Apa itu SlinkV?', 'SlinkV adalah platform URL shortener profesional dengan bot protection dan analytics real-time. Cocok untuk advertiser, affiliate, dan agency yang ingin melindungi traffic dari bot dan klik palsu.'],
            ['Apakah SlinkV gratis?', 'Ya. Paket Free menyediakan 5 link aktif, 1.000 klik per link/bulan, bot protection basic, dan analytics 7 hari. Tidak perlu kartu kredit.'],
            ['Apa itu Bot Protection?', 'Sistem deteksi otomatis untuk memisahkan klik manusia dan klik bot/crawler/script. Bot tidak dihitung sebagai konversi dan dapat dialihkan ke fallback URL.'],
            ['Bagaimana cara kerja shortlink?', 'Setiap link Anda akan mendapat alamat pendek seperti slinkv.net/abc123. Saat diklik, SlinkV memvalidasi traffic, mencatat analytics, lalu mengarahkan ke URL tujuan.'],
            ['Apakah cocok untuk Facebook/Google Ads?', 'Sangat cocok. Bot diblokir sebelum menyentuh pixel sehingga algoritma iklan fokus ke calon pembeli nyata.'],
            ['Apakah cocok untuk affiliate Shopee/TikTok?', 'Cocok. Anda dapat mengaudit sumber traffic mana yang berkualitas dan mana yang banyak bot.'],
            ['Apakah analytics real-time?', 'Ya. Statistik diperbarui hampir seketika setelah klik terjadi.'],
            ['Apa itu fallback URL?', 'URL alternatif yang akan digunakan saat traffic dideteksi sebagai bot, traffic dari negara/device yang tidak diizinkan, atau saat link sudah expired.'],
            ['Apakah bisa filter negara?', 'Bisa. Paket Starter mendukung hingga 3 negara, Pro dan Business unlimited.'],
            ['Bagaimana cara upgrade paket?', 'Masuk ke Dashboard > Billing, pilih paket, dan ikuti instruksi pembayaran. Paket aktif setelah pembayaran dikonfirmasi.'],
        ];

        foreach ($faqs as $i => [$q, $a]) {
            Faq::updateOrCreate(['question' => $q], [
                'answer' => $a, 'sort_order' => $i + 1, 'is_active' => true,
            ]);
        }
    }
}
