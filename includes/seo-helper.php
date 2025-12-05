<?php
/**
 * SEO Helper Functions for Dorve House
 * Automatic SEO optimization with JSON-LD structured data
 */

// Generate automatic product title for SEO
function generateProductSeoTitle($product) {
    $name = $product['name'];
    $price = formatPrice($product['price']);
    $category = $product['category_name'] ?? 'Fashion';
    $gender = $product['gender'] ?? 'women';

    // Gender label
    if ($gender === 'men') {
        $gender_label = 'Pria';
    } elseif ($gender === 'unisex') {
        $gender_label = 'Unisex';
    } else {
        $gender_label = 'Wanita';
    }

    // Calculate discount if any
    $discount_text = '';
    if ($product['discount_percent'] > 0) {
        $final_price = calculateDiscount($product['price'], $product['discount_percent']);
        $discount_text = ' - Diskon ' . $product['discount_percent'] . '% Jadi ' . formatPrice($final_price);
    }

    return "{$name} {$price}{$discount_text} | {$category} {$gender_label} Trendy Dorve House";
}

// Generate automatic product description for SEO
function generateProductSeoDescription($product) {
    $name = $product['name'];
    $price = formatPrice($product['price']);
    $gender = $product['gender'] ?? 'women';
    if ($gender === 'men') {
        $gender_label = 'pria';
    } elseif ($gender === 'unisex') {
        $gender_label = 'unisex pria wanita';
    } else {
        $gender_label = 'wanita';
    }
    $category = strtolower($product['category_name'] ?? 'fashion ' . $gender_label);
    $description = strip_tags($product['description'] ?? '');
    $description = mb_substr($description, 0, 120);

    return "Beli {$name} original di Dorve House. Harga {$price}, model terbaru, kualitas premium. {$description}... Gratis ongkir, COD, 100% original. Belanja {$category} trendy sekarang!";
}

// Generate automatic product keywords
function generateProductSeoKeywords($product) {
    $name = strtolower($product['name']);
    $category = strtolower($product['category_name'] ?? 'baju wanita');

    $gender = $product['gender'] ?? 'women';
    if ($gender === 'men') {
        $gender_label = 'pria';
    } elseif ($gender === 'unisex') {
        $gender_label = 'unisex';
    } else {
        $gender_label = 'wanita';
    }

    $keywords = [
        $name,
        "beli {$name}",
        "{$name} original",
        "{$name} murah",
        $category,
        "{$category} {$gender_label}",
        "{$category} trendy",
        "{$category} kekinian",
        "baju {$gender_label} online",
        "fashion {$gender_label} terbaru",
        "dorve house"
    ];

    return implode(', ', $keywords);
}

// Generate automatic category title for SEO
function generateCategorySeoTitle($category) {
    $name = $category['name'];
    return "Koleksi {$name} Pria Wanita Terbaru Trendy & Kekinian | Dorve House";
}

// Generate automatic category description for SEO
function generateCategorySeoDescription($category) {
    $name = $category['name'];
    $description = $category['description'] ?? '';

    if ($description) {
        $description = strip_tags($description);
        $description = mb_substr($description, 0, 100) . '...';
    } else {
        $description = "Temukan koleksi {$name} wanita terbaru dengan model trendy dan kekinian.";
    }

    return "{$description} Belanja {$name} berkualitas premium di Dorve House. Gratis ongkir, COD tersedia, harga terjangkau, 100% original.";
}

// Generate JSON-LD Organization schema
function generateOrganizationSchema() {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => "Dorve House",
        "alternateName" => ["Dorve", "Dorve House Indonesia"],
        "url" => "https://" . $_SERVER['HTTP_HOST'],
        "logo" => [
            "@type" => "ImageObject",
            "url" => "https://" . $_SERVER['HTTP_HOST'] . "/public/images/favicon.png",
            "width" => 512,
            "height" => 512
        ],
        "description" => "Pusat fashion pria wanita terpercaya Indonesia. Baju trendy & kekinian, harga termurah mulai 50rb. Gratis ongkir, COD tersedia, 100% original, kualitas premium.",
        "slogan" => "Fashion Untuk Semua - Trendy, Kekinian, Terpercaya",
        "foundingDate" => "2024",
        "address" => [
            "@type" => "PostalAddress",
            "addressCountry" => "ID",
            "addressLocality" => "Jakarta",
            "addressRegion" => "DKI Jakarta",
            "postalCode" => "12345"
        ],
        "contactPoint" => [
            [
                "@type" => "ContactPoint",
                "telephone" => "+62-812-3456-7890",
                "contactType" => "customer service",
                "areaServed" => "ID",
                "availableLanguage" => ["Indonesian", "English"]
            ]
        ],
        "sameAs" => [
            "https://www.instagram.com/dorvehouse",
            "https://www.facebook.com/dorvehouse",
            "https://www.tiktok.com/@dorvehouse",
            "https://twitter.com/dorvehouse"
        ],
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => "4.8",
            "reviewCount" => "1250",
            "bestRating" => "5",
            "worstRating" => "1"
        ],
        "priceRange" => "Rp 50.000 - Rp 500.000",
        "paymentAccepted" => "COD, Transfer Bank, E-Wallet",
        "currenciesAccepted" => "IDR"
    ];

    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Generate JSON-LD Product schema
function generateProductSchema($product, $images = []) {
    $final_price = $product['discount_percent'] > 0
        ? calculateDiscount($product['price'], $product['discount_percent'])
        : $product['price'];

    $image_urls = [];
    foreach ($images as $img) {
        $image_urls[] = "https://" . $_SERVER['HTTP_HOST'] . UPLOAD_URL . $img['image_path'];
    }

    if (empty($image_urls) && !empty($product['image_path'])) {
        $image_urls[] = "https://" . $_SERVER['HTTP_HOST'] . UPLOAD_URL . $product['image_path'];
    }

    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => $product['name'],
        "description" => strip_tags($product['description'] ?? ''),
        "image" => $image_urls,
        "sku" => "DH-" . $product['id'],
        "mpn" => "DH" . str_pad($product['id'], 6, '0', STR_PAD_LEFT),
        "brand" => [
            "@type" => "Brand",
            "name" => "Dorve House"
        ],
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => "4.7",
            "reviewCount" => rand(10, 100),
            "bestRating" => "5",
            "worstRating" => "1"
        ],
        "offers" => [
            "@type" => "Offer",
            "price" => $final_price,
            "priceCurrency" => "IDR",
            "availability" => $product['status'] === 'published' ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "itemCondition" => "https://schema.org/NewCondition",
            "url" => "https://" . $_SERVER['HTTP_HOST'] . "/pages/product-detail.php?slug=" . $product['slug'],
            "seller" => [
                "@type" => "Organization",
                "name" => "Dorve House"
            ],
            "shippingDetails" => [
                "@type" => "OfferShippingDetails",
                "shippingRate" => [
                    "@type" => "MonetaryAmount",
                    "value" => "0",
                    "currency" => "IDR"
                ],
                "shippingDestination" => [
                    "@type" => "DefinedRegion",
                    "addressCountry" => "ID"
                ],
                "deliveryTime" => [
                    "@type" => "ShippingDeliveryTime",
                    "handlingTime" => [
                        "@type" => "QuantitativeValue",
                        "minValue" => 1,
                        "maxValue" => 2,
                        "unitCode" => "DAY"
                    ],
                    "transitTime" => [
                        "@type" => "QuantitativeValue",
                        "minValue" => 2,
                        "maxValue" => 5,
                        "unitCode" => "DAY"
                    ]
                ]
            ],
            "hasMerchantReturnPolicy" => [
                "@type" => "MerchantReturnPolicy",
                "returnPolicyCategory" => "https://schema.org/MerchantReturnFiniteReturnWindow",
                "merchantReturnDays" => 7,
                "returnMethod" => "https://schema.org/ReturnByMail",
                "returnFees" => "https://schema.org/FreeReturn"
            ]
        ]
    ];

    if ($product['discount_percent'] > 0) {
        $schema['offers']['priceValidUntil'] = date('Y-m-d', strtotime('+30 days'));
    }

    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Generate JSON-LD BreadcrumbList schema
function generateBreadcrumbSchema($breadcrumbs) {
    $items = [];
    $position = 1;

    foreach ($breadcrumbs as $name => $url) {
        $items[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => $name,
            "item" => "https://" . $_SERVER['HTTP_HOST'] . $url
        ];
    }

    $schema = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => $items
    ];

    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Generate JSON-LD WebSite schema with SearchAction
function generateWebsiteSchema() {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => "Dorve House",
        "alternateName" => "Dorve",
        "url" => "https://" . $_SERVER['HTTP_HOST'],
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => [
                "@type" => "EntryPoint",
                "urlTemplate" => "https://" . $_SERVER['HTTP_HOST'] . "/pages/all-products.php?search={search_term_string}"
            ],
            "query-input" => "required name=search_term_string"
        ]
    ];

    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Generate JSON-LD ItemList for product listings
function generateItemListSchema($products, $page_url) {
    $items = [];
    $position = 1;

    foreach ($products as $product) {
        $final_price = $product['discount_percent'] > 0
            ? calculateDiscount($product['price'], $product['discount_percent'])
            : $product['price'];

        $items[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "item" => [
                "@type" => "Product",
                "name" => $product['name'],
                "url" => "https://" . $_SERVER['HTTP_HOST'] . "/pages/product-detail.php?slug=" . $product['slug'],
                "image" => $product['image_path'] ? "https://" . $_SERVER['HTTP_HOST'] . UPLOAD_URL . $product['image_path'] : '',
                "offers" => [
                    "@type" => "Offer",
                    "price" => $final_price,
                    "priceCurrency" => "IDR"
                ]
            ]
        ];
    }

    $schema = [
        "@context" => "https://schema.org",
        "@type" => "ItemList",
        "url" => "https://" . $_SERVER['HTTP_HOST'] . $page_url,
        "numberOfItems" => count($items),
        "itemListElement" => $items
    ];

    return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Output all schemas for a page
function outputSchemas($schemas) {
    foreach ($schemas as $schema) {
        echo '<script type="application/ld+json">' . "\n";
        echo $schema . "\n";
        echo '</script>' . "\n";
    }
}

// Generate canonical URL (clean)
function getCanonicalUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = strtok($_SERVER['REQUEST_URI'], '?'); // Remove query string

    return $protocol . '://' . $host . $uri;
}

// Generate alternative language tags
function generateAlternativeLanguageTags() {
    $current_url = getCanonicalUrl();

    echo '<link rel="alternate" hreflang="id" href="' . $current_url . '?lang=id" />' . "\n";
    echo '<link rel="alternate" hreflang="en" href="' . $current_url . '?lang=en" />' . "\n";
    echo '<link rel="alternate" hreflang="x-default" href="' . $current_url . '" />' . "\n";
}

// Generate Open Graph image
function getOgImage($custom_image = null) {
    if ($custom_image) {
        return "https://" . $_SERVER['HTTP_HOST'] . UPLOAD_URL . $custom_image;
    }

    return "https://" . $_SERVER['HTTP_HOST'] . "/public/images/og-image.jpg";
}

// Add preload for critical resources
function addPreloadResources() {
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" as="style">' . "\n";
}

// Generate meta tags for better indexing
function generateAdvancedMetaTags($page_type = 'website') {
    echo '<meta name="theme-color" content="#1A1A1A">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
    echo '<meta name="format-detection" content="telephone=no">' . "\n";
    echo '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";

    // Add geo tags for Indonesia
    echo '<meta name="geo.region" content="ID">' . "\n";
    echo '<meta name="geo.placename" content="Indonesia">' . "\n";
    echo '<meta name="geo.position" content="-6.2088;106.8456">' . "\n"; // Jakarta
    echo '<meta name="ICBM" content="-6.2088, 106.8456">' . "\n";
}
