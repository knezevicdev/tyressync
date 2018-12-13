<?php

namespace App;

use Automattic\WooCommerce\Client;
use App\Product;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class WoocommerceSync {

    private $url;
    private $key;
    private $secret;
    private $woocommerce;
    private $products;

    function __construct($url, $key, $secret)
    {
        $this->url = $url;
        $this->key = $key;
        $this->secret = $secret;

        $this->authenticate_client();
    }

    private function authenticate_client()
    {
        $this->woocommerce = new Client(
            $this->url,
            $this->key,
            $this->secret,
            [
                'wp_api' => true,
                'version' => 'wc/v2',
                'timeout' => 1200
            ]
        );
    }

    private function get_woocommerce_product_attribute($product, $attribute_name)
    {
        $attribute_value = "";

        foreach($product->attributes as $attribute) {
            if($attribute->name === $attribute_name && count($attribute->options) > 0) {
                $attribute_value = $attribute->options[0];
                break;
            }
        }

        return $attribute_value;
    }

    private function transform_product($product)
    {
        $code = $product->sku;
        $program = count($product->categories) > 0 ? $product->categories[0]->name : "";
        $width = $this->get_woocommerce_product_attribute($product, "širina");
        $height = $this->get_woocommerce_product_attribute($product, "visina");
        $radius = $this->get_woocommerce_product_attribute($product, "radius");
        $speedIndex = $this->get_woocommerce_product_attribute($product, "indeks brzine");
        $loadIndex = $this->get_woocommerce_product_attribute($product, "indeks opterećenja");
        $brand = $this->get_woocommerce_product_attribute($product, "proizvođač");
        $season = $this->get_woocommerce_product_attribute($product, "sezona");
        $xl = $this->get_woocommerce_product_attribute($product, "xl");
        $rof = $this->get_woocommerce_product_attribute($product, "rof");
        $price = number_format(round(floatval($product->price), 6), 6, '.', ',');
        $title = $product->name;
        $source = $this->get_woocommerce_product_attribute($product, "izvor");
        $weight = $product->weight;
        $image = count($product->images) > 0 ? $product->images[0]->src : "";

        return new Product($code, $program, $width, $height, $radius, $speedIndex, $loadIndex, $brand, $season, $xl, $rof, $price, $title, $source, $weight, $image, $product->id);
    }

    private function read_products()
    {
        $products = [];
        $fetchMore = true;
        $page = 1;

        while($fetchMore) {
            $results = $this->woocommerce->get('products', [
                'page'      => $page,
                'per_page'  => 100
            ]);
            
            $products = array_merge($products, $results);

            $responseHeaders = $this->woocommerce->http->getResponse()->getHeaders();
            if(isset($responseHeaders['X-WP-TotalPages']) && $page < $total_pages = intval($responseHeaders['X-WP-TotalPages'])) {
                $page++;
            } else {
                $fetchMore = false;
            }
        }

        $this->products = array_map(function($product) {
            return $this->transform_product($product);
        }, $products);
    }   

    private function get_batch_create_data($products)
    {
        return array_chunk(array_map(function($product) {
            return [
                'name' => $product->title,
                'type' => 'simple',
                'regular_price' => str_replace(",", "", $product->price),
                'sku' => $product->code,
                'weight' => $product->weight,
                'images' => [
                    [
                        'src' => $product->image
                    ]
                ],
                'categories' => [
                    [
                        'id' => Product::category_id($product->program)
                    ]
                ],
                'attributes' => [
                    [
                        'name' => 'širina',
                        'options' => [$product->width]
                    ],
                    [
                        'name' => 'visina',
                        'options' => [$product->height]
                    ],
                    [
                        'name' => 'sezona',
                        'options' => [$product->season]
                    ],
                    [
                        'name' => 'rof',
                        'options' => [$product->rof]
                    ],
                    [
                        'name' => 'radius',
                        'options' => [$product->radius]
                    ],
                    [
                        'name' => 'proizvođač',
                        'options' => [$product->brand]
                    ],
                    [
                        'name' => 'ojačana guma',
                        'options' => [$product->xl]
                    ],
                    [
                        'name' => 'indeks opterećenja',
                        'options' => [$product->loadIndex]
                    ],
                    [
                        'name' => 'indeks brzine',
                        'options' => [$product->speedIndex]
                    ],
                    [
                        'name' => 'izvor',
                        'options' => [$product->source]
                    ]
                ]
            ];
        }, $products), 10);
    }

    private function get_batch_update_data($products)
    {
        return array_chunk(array_map(function($product) {
            $updateData = [
                'id' => $product->wooId
            ];
    
            $updateFields = $product->updateFields;
    
            if(in_array("width", $updateFields) ||
                in_array("height", $updateFields) ||
                in_array("radius", $updateFields) ||
                in_array("speedIndex", $updateFields) ||
                in_array("loadIndex", $updateFields) ||
                in_array("brand", $updateFields) ||
                in_array("season", $updateFields) ||
                in_array("xl", $updateFields) ||
                in_array("rof", $updateFields)) {
    
                    $attributes = [];
    
                    if(in_array("width", $updateFields)) {
                        $attributes[] = [
                            'name' => 'širina',
                            'options' => [$product->width]
                        ];
                    }
                    if(in_array("height", $updateFields)) {
                        $attributes[] = [
                            'name' => 'visina',
                            'options' => [$product->height]
                        ];
                    }
                    if(in_array("season", $updateFields)) {
                        $attributes[] = [
                            'name' => 'sezona',
                            'options' => [$product->season]
                        ];
                    }
                    if(in_array("rof", $updateFields)) {
                        $attributes[] = [
                            'name' => 'rof',
                            'options' => [$product->rof]
                        ];
                    }
                    if(in_array("radius", $updateFields)) {
                        $attributes[] = [
                            'name' => 'radius',
                            'options' => [$product->radius]
                        ];
                    }
                    if(in_array("brand", $updateFields)) {
                        $attributes[] = [
                            'name' => 'proizvođač',
                            'options' => [$product->brand]
                        ];
                    }
                    if(in_array("xl", $updateFields)) {
                        $attributes[] = [
                            'name' => 'ojačana guma',
                            'options' => [$product->xl]
                        ];
                    }
                    if(in_array("loadIndex", $updateFields)) {
                        $attributes[] = [
                            'name' => 'indeks opterećenja',
                            'options' => [$product->loadIndex]
                        ];
                    }
                    if(in_array("speedIndex", $updateFields)) {
                        $attributes[] = [
                            'name' => 'indeks brzine',
                            'options' => [$product->speedIndex]
                        ];
                    }
    
                    $updateData['attributes'] = $attributes;
            }
    
            if(in_array("price", $updateFields)) $updateData['regular_price'] = str_replace(",", "", $product->price);
            if(in_array("title", $updateFields)) $updateData['name'] = $product->title;
            if(in_array("weight", $updateFields)) $updateData['weight'] = $product->weight;
            if(in_array("image", $updateFields)) $updateData['images'] = [
                [
                    'src' => $product->image
                ]
            ];
            if(in_array("program", $updateFields)) $updateData['categories'] = [
                [
                    'id' => Product::category_id($product->program)
                ]
            ];
           
    
            return $updateData;
        }, $products), 99);
    }

    private function get_batch_delete_data($products)
    {
        return array_chunk(array_map(function($product) {
            return $product->wooId;
        }, $products), 99);
    }

    public function sync_products($products)
    {
        global $logger;

        $used_products = [];
        $update_products = [];
        $delete_products = [];
        $new_products = [];
        $not_changed = 0;

        $logger->info('Starting fetching current products from database.');
        $this->read_products();
        $logger->info('Current products fetching from database done.', [
            'total_products_fetched' => count($this->products)
        ]);

        
        $logger->info('Starting products comparator.');
        foreach ($this->products as $current_product) {
            $working_product = false;

            if($current_product->code === "") {
                $logger->warn('NO SKU for current product. Will be deleted.', [
                    'product_source' => $current_product->source,
                    'DATABASE_ID' => $current_product->wooId
                ]);
                $delete_products[] = $current_product;
                continue;
            }

            foreach ($products as $product) {
                if($product->same_as($current_product)) {
                    $working_product = $product;
                    $working_product->set_woo_id($current_product->wooId);
                    $used_products[] = $product;
                    break;
                }
            }

            if($working_product === false) {
                $logger->warn('Product not found and will be deleted.', [
                    'product_source' => $current_product->source,
                    'product_code' => $current_product->code
                ]);
                $delete_products[] = $current_product;
                continue;
            }

            $differences = $working_product->get_differences($current_product);

            if(count($differences)) {
                $logger->notice('Product changed and will be updated.', [
                    'product_source' => $current_product->source,
                    'product_code' => $current_product->code,
                    'differences' => $differences
                ]);

                $working_product->set_update_fields(array_keys($differences));
                
                $update_products[] = $working_product;
            } else {
                $not_changed++;
            }
        }

        foreach ($products as $product) {
            $found = false;

            foreach($used_products as $user_product) {
                if($user_product->same_as($product)) {
                    $found = true;
                    break;
                }
            }

            if(!$found) {
                $new_products[] = $product;
            }
        }

        $logger->info('Products comparator finished.', [
            'new_products' => count($new_products),
            'to_delete_products' => count($delete_products),
            'to_update_products' => count($update_products),
            'not_changed_products' => $not_changed
        ]);

        
        $logger->info('Starting products update process.');
        foreach($this->get_batch_update_data($update_products) as $data) {
            $this->woocommerce->post('products/batch', [
                'update' => $data
            ]);
        }
        $logger->info('Products update process successfully completed.');

        
        $logger->info('Starting products create process.');
        $updateChunks = $this->get_batch_create_data($new_products);
        echo "Total create iterations:" . count($updateChunks) . "\n";
        foreach($updateChunks as $key => $data) {
            try {
                $this->woocommerce->post('products/batch', [
                    'create' => $data
                ]);
                echo "Create iteration " . $key . " - done!\n";
            } catch(HttpClientException $e) { 
                $err = $e->getMessage(); 
                echo $err;
                file_put_contents('filename.txt', print_r($e, true));
            }
        }
        $logger->info('Products create process successfully completed.');
        
        $logger->info('Starting products delete process.');
        foreach($this->get_batch_delete_data($delete_products) as $data) {
            $this->woocommerce->post('products/batch', [
                'delete' => $data
            ]);
        }
        $logger->info('Products delete process successfully completed.');
    }

}