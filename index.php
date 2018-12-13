<?php

require 'vendor/autoload.php';

use App\PneumasterSync;
use App\BakiSync;
use App\ImageMatch;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Carbon\Carbon;
use App\WoocommerceSync;

$dotenv = new Dotenv(__DIR__);
$dotenv->load();

$logger = new Logger('tyressync_logger');
$logger->pushHandler(new StreamHandler(__DIR__ . "/logs/log-" . Carbon::now()->format('Y.m.d\TH.i.s') . ".log", Logger::DEBUG));

$productSyncers = [
    new PneumasterSync(getenv('PNEUMASTER_USERNAME'), getenv('PNEUMASTER_PASSWORD')),
    new BakiSync(getenv('BAKI_USERNAME'), getenv('BAKI_PASSWORD'), getenv('BAKI_SECRET'), getenv('BAKI_ENDPOINT'))
];

$products = [];

foreach($productSyncers as $productSyncer) {
    $logger->info('Started fetching products', [
        'sync_name' => $productSyncer->get_sync_name()
    ]);
    $productSyncer->fetch_products();
    $fetchedProducts = $productSyncer->get_products();
    $products = array_merge($products, $fetchedProducts);
    $logger->info('Successfully fetched products', [
        'sync_name' => $productSyncer->get_sync_name(),
        'found_products' => count($fetchedProducts)
    ]);
    unset($productSyncer);
}

$logger->info('Image matching for products started.');

$imageMatch = new ImageMatch(getenv('IMAGES_URL'));

foreach($products as $product) {
    $imageUrl = $imageMatch->get_image($product);
    $product->set_image($imageUrl);
}

$logger->info('Images matching successfully done.');

$logger->info('Starting sync with woocommerce.', [
    'total_products_to_sync' => count($products)
]);

$woocommerceSync = new WoocommerceSync(getenv('WOOCOMMERCE_URL'), getenv('WOOCOMMERCE_CONSUMER_KEY'), getenv('WOOCOMMERCE_CONSUMER_SECRET'));

$woocommerceSync->sync_products($products);