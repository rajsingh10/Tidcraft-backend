<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CmsPage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class OptimizeCmsPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize CMS pages by extracting base64 images from content and saving them to disk';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting CMS optimization...');

        $pages = CmsPage::all();
        $count = 0;

        foreach ($pages as $page) {
            $content = $page->content;
            if (is_array($content)) {
                $processedContent = $this->processBase64Images($content);
                
                // If content was modified, save it
                if ($content !== $processedContent) {
                    $page->content = $processedContent;
                    // Prevent modifying 'updated_by' if possible or let it be
                    $page->save();
                    $count++;
                    $this->line("Optimized CMS page: {$page->title} ({$page->slug})");
                }
            }
        }

        $this->info("CMS optimization completed. Optimized {$count} pages.");
    }

    private function processBase64Images(array $content): array
    {
        foreach ($content as $key => $value) {
            if (is_array($value)) {
                $content[$key] = $this->processBase64Images($value);
            } elseif (is_string($value) && preg_match('/^data:image\/(\w+);base64,/', $value, $matches)) {
                $data = substr($value, strpos($value, ',') + 1);
                $type = strtolower($matches[1]);
                $type = $type === 'jpeg' ? 'jpg' : $type;
                
                $data = base64_decode($data);
                if ($data !== false) {
                    $fileName = 'cms_images/' . Str::random(40) . '.' . $type;
                    Storage::disk('public')->put($fileName, $data);
                    $content[$key] = Storage::url($fileName);
                }
            }
        }
        return $content;
    }
}
