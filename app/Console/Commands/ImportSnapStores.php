<?php

namespace App\Console\Commands;

use App\Models\SnapStore;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ImportSnapStores extends Command
{
    protected $signature = 'import:snap-stores {start=1} {end=63}';
    protected $description = 'Import SnapPay stores';

    public function handle()
    {
        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; MyScraper/1.0; +https://yourdomain)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ],
            'timeout' => 30,
        ]);

        // تلاش برای فراخوانی صفحات paged (اگر وجود داشته باشد)
        $start = (int)$this->argument('start');
        $end = (int)$this->argument('end');

        for ($page = $start; $page <= $end; $page++) {
            $this->info("Fetching page $page ...");
            $urlCandidates = [
                "https://snapppay.ir/stores/?paged={$page}",
                "https://snapppay.ir/stores/page/{$page}/",
                "https://snapppay.ir/stores/?page={$page}",
            ];

            $html = null;
            foreach ($urlCandidates as $url) {
                try {
                    $res = $client->get($url);
                    if ($res->getStatusCode() === 200) {
                        $html = (string) $res->getBody();
                        $this->info("Got content from: $url");
                        break;
                    }
                } catch (\Throwable $e) {
                    $this->warn("Could not fetch $url : " . $e->getMessage());
                }
            }

            // اگر هیچ کدام کار نکرد، ممکنه صفحه با JS ساخته بشه
            if (!$html) {
                $this->warn("Page $page empty — might need a headless browser. Skipping.");
                continue;
            }

            $this->parseAndSave($html);
            sleep(1); // مؤدب باشیم
        }

        $this->info('Done.');
    }

    protected function parseAndSave(string $html)
    {
        $crawler = new Crawler($html);

        // selector را بر اساس ساختار واقعی صفحه تنظیم کن
        // در صفحه‌ای که من دیدم جدول یا لیستی از ردیف‌ها به صورت متن ساده وجود داره.
        // فرض: هر ردیف به صورت خطی (مثلاً tr یا div.row) است.
        $rows = $crawler->filter('table tr')->each(function (Crawler $node) {
            $text = $node->text();
            return $text;
        });

        // اگر table پیدا نشد، fallback روی یک سلکتور عمومی‌تر:
        if (empty($rows)) {
            $rows = $crawler->filter('body')->each(function (Crawler $node) {
                return $node->html();
            });
        }

        foreach ($rows as $raw) {
            // اینجا باید با regex یا DOM پارس کنی. نمونه ساده:
            // تلاش برای استخراج نام، شماره و سایت با regex
            if (!is_string($raw)) continue;

            // یک نمونه‌ی پارس ساده — باید آن را بر اساس خروجی صفحه تغییر دهی
            preg_match('/^(.*?)\s+([0-9\-\s\(\)]+)?\s+(https?:\/\/[^\s]+)/m', strip_tags($raw), $m);
            $name = $m[1] ?? null;
            $phone = $m[2] ?? null;
            $website = $m[3] ?? null;

            $slug = $name ? Str::slug($name) : null;
            if (!$name && !$website) continue;

            SnapStore::updateOrCreate(
                ['slug' => $slug ?: Str::slug($website ?: Str::random(8))],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'website' => $website,
                    'raw' => $raw,
                ]
            );
        }
    }
}
