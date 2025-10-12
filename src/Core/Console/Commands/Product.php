<?php

namespace Core\Console\Commands;

use Domain\Product\Services\OxylabsService;
use Illuminate\Console\Command;

class Product extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get-data:product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch product from Oxylabs';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $oxylabsService = new OxylabsService();


        $domain = 'https://www.adidas.com.tr';
        // $url = 'https://www.adidas.com.tr/tr/copa-mundial-cim-saha-kramponu/JP6693.html';
        $url = 'https://www.adidas.com.tr/tr/essentials-3-stripes-french-terry-sweatshirt/JE6372.html';

        $filters = $oxylabsService->fetchRequest('product', $url);

        // dd($filters);

        $productData = $this->cleanProductData($filters, $domain);

        $this->info("Products: " . var_export($productData, true));


    }

    /**
     * Clean and normalize the Kaufland product data
     *
     * @param   array  $kauflandComProduct The raw product data from API
     * @param   string $domain The domain of the product
     * @throws  Exception If required product data is missing
     */
    private function cleanProductData(array $response, string $domain): array
    {
        // Extract product content from results
        $content = $response['results'][0]['content'] ?? null;

        if (!$content) {
            throw new \Exception('Invalid product data structure');
        }

        // Extract and validate title
        $productTitle = $this->extractTitle($content['title'][0] ?? null);
        if (!$productTitle) {
            throw new \Exception('Product not found');
        }
        $productExplanation = $this->extractTextContent($content['explanation'] ?? null);
        $productDetails = $this->extractTextContent($content['details'] ?? null);
        $productImages = $this->extractImages($content['images'] ?? null);
        $productSize = $this->extractSize($content['size'] ?? null);
        $productPrice = $this->extractPrice($content['price'][1] ?? null);

        // Return normalized product data
        return [
            'title' => $productTitle,
            'explanation' => $productExplanation,
            'details' => $productDetails,
            'images' => $productImages,
            'size' => $productSize,
            'price' => $productPrice,
        ];
    }

    private function extractPrice(?string $price): string
    {
        if (empty($price)) {
            return "";
        }

        // Pattern matches: <span>1.499,00 TL</span>
        preg_match('/<span>([^<]+)<\/span>/i', $price, $matches);
        if(!empty($matches[1])) {
            return $matches[1];
        }
        return "0,00";
    }

    private function extractSize(?array $array): array
    {
        if (empty($array)) {
            return [];
        }

        $sizes = [];

        foreach ($array as $item) {
            // Pattern matches:<span>40 2/3</span>
            // Pattern matches:<span>41 1/3</span>
            // Pattern matches:<span>42</span>
            preg_match('/<span>([^<]+)<\/span>/i', $item, $matches);
            if(!empty($matches[1])) {
                $sizes[] = $matches[1];
            }
        }

        return $sizes;
    }

    private function extractImages(?array $array): array
    {
        if (empty($array)) {
            return [];
        }

        $images = [];

        foreach ($array as $item) {
            // Pattern matches: <picture data-testid="pdp-gallery-picture"><source srcset="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" media="(max-width: 959)"><img src="https://assets.adidas.com/images/w_600,f_auto,q_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg" srcset="https://assets.adidas.com/images/h_320,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 320w, https://assets.adidas.com/images/h_420,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 420w, https://assets.adidas.com/images/h_600,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 600w, https://assets.adidas.com/images/h_640,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 640w, https://assets.adidas.com/images/h_840,f_auto,q_auto,fl_lossy,c_fill,g_auto/8f2204188e544933ab681c1a847f8387_9366/Copa_Mundial_Cim_Saha_Kramponu_Siyah_JP6693_22_model.jpg 840w" sizes="(max-width: 320px) 320px, (max-width: 420px) 420px, (max-width: 600px) 600px, (max-width: 640px) 640px, (max-width: 840px) 840px" alt="Siyah Copa Mundial &#199;im Saha Kramponu" data-inject_ssr_performance_instrument=""/></source></picture>
            preg_match('/<img[^>]+src=["\'](https:\/\/[^"\']+)["\']/i', $item, $matches);

            if(!empty($matches[1])) {
                $images[] = $matches[1];
            }
        }

        return $images;
    }

    /**
     * Extract clean text content from HTML
     *
     * @param string|null $html The HTML content to clean
     * @return string Clean text content
     */
    private function extractTextContent(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Decode HTML entities first
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

        // Add line breaks before list items for better formatting
        $html = preg_replace('/<li[^>]*>/i', "\n• ", $html);

        // Remove all HTML tags
        $text = strip_tags($html);

        // Clean up extra whitespace and normalize line breaks
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Extract clean text content from HTML
     *
     * @param string|null $html The HTML content to clean
     * @return string Clean text content
     */
    private function extractTitle(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Decode HTML entities first
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

        // Remove all HTML tags
        $text = strip_tags($html);
        $text = trim($text);

        return $text;
    }


}