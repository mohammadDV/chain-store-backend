<?php

namespace Domain\Product\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OxylabsService
{

    /**
     * Fetch product data from Oxylabs
     *
     * @param string $identifier The ASIN (Amazon) or Product ID (Bol.com)
     * @param string $marketplace The marketplace (e.g., 'amazon', 'bol')
     * @param ?string $host The host of the marketplace (e.g., 'kaufland.de')
     * @return array|null The product data or null on failure
     */
    public function fetchRequest($key, $url): ?array
    {
        // Build the parameters for the Oxylabs API
        $params = $this->buildParams($key, $url);

        // if there
        if (!$params) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->withBasicAuth(config('oxylabs.username'), config('oxylabs.password'))
                ->timeout(30) // Add timeout to prevent hanging requests
                ->post('https://realtime.oxylabs.io/v1/queries', $params);

            // Check if the response was successful
            if ($response->successful()) {
                return $response->json();
            }

            // Handle HTTP errors (4xx, 5xx)
            $statusCode = $response->status();
            $errorBody = $response->body();
            $errorJson = $response->json();

            Log::error('Oxylabs API request failed', [
                'key' => $key,
                'url' => $url,
                'status_code' => $statusCode,
                'error_body' => $errorBody,
                'error_json' => $errorJson,
            ]);

            // You can also throw an exception or return error details if needed
            // For now, we'll just log and return null to maintain current behavior

            return null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Handle connection errors (timeout, DNS failure, etc.)
            //: Operation timed out after 30001 milliseconds with 0 bytes received (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://realtime.oxylabs.io/v1/queries
            Log::error('Oxylabs API connection error 1', [
                'key' => $key,
                'url' => $url,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            return [
                'status' => 2,
                'message' => 'Connection error',
                'error' => $e->getMessage(),
            ];

        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error('Oxylabs API request exception 2', [
                'key' => $key,
                'url' => $url,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 3,
                'message' => 'Connection error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build parameters for Oxylabs API based on marketplace
     *
     * @param string $identifier
     * @param string $marketplace
     * @param ?string $host
     * @return array|null
     */
    private function buildParams($key, $url): ?array
    {

        $config = [
            'product' => [
                'geo_location' => "TR",
                'source' => 'universal_ecommerce',
                'render' => 'html',
                "browser_instructions" => [
                    // [
                    //     "type" => "input",
                    //     "value" => "pizza boxes",
                    //     "selector" => [
                    //         "type" => "xpath",
                    //         "value" => "//button[@class='accordion_accordion__header__GK4__']"
                    //     ]
                    // ],
                    [
                        "type" => "click",
                        "selector" => [
                            "type" => "xpath",
                            "value" => "//button[@class='accordion_accordion__header__GK4__']"
                        ]
                    ],
                    [
                        "type" => "wait",
                        "wait_time_s" => 2
                    ]
                ],
                'parsing_instructions' => [
                    'title' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['.product-description_name__sg_q8 > span']]
                        ]
                    ],
                    'price' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['._mainPrice_1dnvn_52 > span']],
                        ]
                    ],
                    'discount' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['._originalPrice_1dnvn_81 > span']],
                        ]
                    ],
                    'size' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['.gl-label > span']],
                        ]
                    ],
                    'related_products' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['.color-variation_variation__jECs6 > a']],
                        ]
                    ],
                    'images' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['.desktop-zoom_content__qj_J5 > picture']],
                        ]
                    ],
                    'explanation' => [
                        '_fns' => [
                            ['_fn' => 'xpath_one', '_args' => ['/html/body/div[2]/div/div/div/div/div[3]/section[2]']],
                        ]
                    ],
                    'details' => [
                        '_fns' => [
                            ['_fn' => 'xpath_one', '_args' => ['/html/body/div[2]/div/div/div/div/div[3]/section[3]']],
                        ]
                    ],
                ],
                'url' => $url,
            ],
            'update_stock' => [
                'geo_location' => "TR",
                'source' => 'universal_ecommerce',
                'render' => 'html',
                "browser_instructions" => [
                    // [
                    //     "type" => "input",
                    //     "value" => "pizza boxes",
                    //     "selector" => [
                    //         "type" => "xpath",
                    //         "value" => "//button[@class='accordion_accordion__header__GK4__']"
                    //     ]
                    // ],
                    // [
                    //     "type" => "click",
                    //     "selector" => [
                    //         "type" => "xpath",
                    //         "value" => "//button[@class='accordion_accordion__header__GK4__']"
                    //     ]
                    // ],
                    [
                        "type" => "wait",
                        "wait_time_s" => 1
                    ]
                ],
                'parsing_instructions' => [
                    'title' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['.product-description_name__sg_q8 > span']]
                        ]
                    ],
                    'price' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['._mainPrice_1dnvn_52 > span']],
                        ]
                    ],
                    'discount' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['._originalPrice_1dnvn_81 > span']],
                        ]
                    ],
                    'stock' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['.scarcity-message_scarcity-message__7X5BG']],
                        ]
                    ],
                ],
                'url' => $url,
            ],
            'productList' => [
                'geo_location' => "TR",
                'source' => 'universal_ecommerce',
                'parsing_instructions' => [
                    'products' => [
                        "_fns" => [
                            ['_fn' => 'css', '_args' => ['.product-grid_product-card__8ufJk > div']]
                        ]
                    ],
                    'title' => [
                        "_fns" => [
                            ['_fn' => 'xpath_one', '_args' => [".//h1/text()"]],
                            ['_fn' => 'element_text']
                        ]
                    ],
                    // 'price' => [
                    //     "_fns" => [
                    //         ['_fn' => 'xpath_one', '_args' => [".//span[@class='rd-price-information__price']/text()"]],
                    //         ['_fn' => 'amount_from_string']
                    //     ]
                    // ],
                    // 'reviews' => [
                    //     '_fns' => [
                    //         ['_fn' => 'xpath_one', '_args' => ['/html/body/div[2]/main/div[2]/div[3]/a/p[2]']],
                    //     ]
                    // ],
                    // 'main_image' => [
                    //     '_fns' => [
                    //         ['_fn' => 'css', '_args' => ['._flyouts_cskbw_98 > img']]
                    //     ]
                    // ],
                    // 'stack_images' => [
                    //     '_fns' => [
                    //         ['_fn' => 'xpath', '_args' => ["/html/body/div[2]/main/div[2]/div[4]/section/ul"]]
                    //     ]
                    // ]

                ],
                'url' => $url,
            ],
        ];

        // if (!isset($config[$key])) {
        //     throw new Exception('Marketplace not supported'); // Unsupported marketplace
        // }

        $params = $config[$key];
        $params['parse'] = true;

        return $params;
    }
}
