<?php

$token = "gfjghjghjghjghj";

// Check if the request method is not GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit("Method Not Allowed");
}

// Set CSV headers for download
$folder_name = "facebook_additonal_feed";

// Check if folder exists, if not create it
if (!is_dir($folder_name)) {
    mkdir($folder_name, 0755, true);
}

// Dynamically generate the filename
// $date = time();
$filename = "$folder_name/facebook_additonal_feed.csv";

$fp = fopen($filename, 'w');
if (!$fp) {
    http_response_code(500); // Internal Server Error
    exit("Failed to open file for writing.");
}

// Initialize CSV data array
$csv_data = [];
$header = [
    'id', 'title', 'description', 'image_link', 'brand', 'google_product_category',
    'fb_product_category', 'gender', 'color', 'size', 'age_group', 'material', 'pattern',
    'image[0].url', 'image[0].tag[0]', 'additional_image_link[0]',
    'additional_variant_attribute[0].variant_label', 'additional_variant_attribute[0].variant_value',
    'additional_variant_attribute[1].variant_label', 'additional_variant_attribute[1].variant_value',
    'gtin', 'ordering_index', 'pre_order_fulfillment_date', 'shipping_profile_reference_id',
    'origin_country', 'importer_name', 'importer_address.street1', 'importer_address.street2',
    'importer_address.city', 'importer_address.region', 'importer_address.postal_code',
    'importer_address.country', 'manufacturer_info', 'sustainability_certification_type',
    'is_customisation_required', 'custom_text_fields[0].field_name', 'custom_text_fields[0].input_guidance',
    'custom_text_fields[0].is_required', 'custom_text_fields[0].max_value_length',
    'custom_option_fields[0].field_name', 'custom_option_fields[0].is_required',
    'custom_option_fields[0].allowed_options', 'style[0]', 'gemstone[0]', 'gemstone[1]',
    'gemstone_clarity', 'gemstone_color', 'gemstone_creation_method', 'gemstone_cut',
    'gemstone_treatment[0]', 'gemstone_treatment[1]', 'gemstone_height', 'gemstone_length',
    'gemstone_width', 'gemstone_weight', 'total_gemstone_weight', 'plating_material',
    'metal_stamp_or_purity', 'size_system', 'occasion[0]', 'additional_features[0]',
    'additional_features[1]', 'standard_features[0]', 'chain_length', 'jewelry_setting_style',
    'inscription[0]', 'clasp_type', 'earring_back_finding'
];
$csv_data[] = $header;



// Retrieve product data from Shopify API
$last_product_id = 0;
do {
    $ch = curl_init();
    $url = "https://fafe-collection.de/admin/api/2024-01/products.json?limit=250&status=active";
    if ($last_product_id > 0) {
        $url .= "&since_id=" . $last_product_id;
    }
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'X-Shopify-Access-Token: ' . $token
        ],
    ]);

    $response = curl_exec($ch);

    if (curl_error($ch)) {
        http_response_code(500); // Internal Server Error
        exit('Error: ' . curl_error($ch));
    }

    curl_close($ch);
    
    $data = json_decode($response, true);

    if ($data !== null && isset($data['products'])) {
        foreach ($data['products'] as $product) {
            // Fetch additional details for each product, such as metafields
            $metafields_url = "https://fafe-collection.de/admin/api/2024-01/products/{$product['id']}/metafields.json";
            $ch_meta = curl_init();
            curl_setopt_array($ch_meta, [
                CURLOPT_URL => $metafields_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'X-Shopify-Access-Token: ' . $token
                ],
            ]);
            $response_meta = curl_exec($ch_meta);

            if (curl_error($ch_meta)) {
                echo 'Error fetching metafields: ' . curl_error($ch_meta);
                curl_close($ch_meta);
                continue;
            }

            curl_close($ch_meta);

            $metafields_data = json_decode($response_meta, true);
          
            if ($metafields_data !== null && isset($metafields_data['metafields'])) {
                $custom_color = '';
                $custom_image ='';
                foreach ($metafields_data['metafields'] as $metafield) {
                    switch ($metafield['key']) {
                        case 'farbe':
                            $custom_color = $metafield['value'];
                            break;
                    case 'bild_auf_beige':
                        // Fetch image URL using GraphQL API
                        $image_id = $metafield['value'];
                        $graphql_query = '{"query":"query {\\r\\n  node(id: \\"' . $image_id . '\\") {\\r\\n    id\\r\\n    ... on MediaImage {\\r\\n      image {\\r\\n        url\\r\\n      }\\r\\n    }\\r\\n  }\\r\\n}","variables":{}}';

                        $ch_graphql = curl_init();
                        curl_setopt_array($ch_graphql, [
                            CURLOPT_URL => 'https://fafecollection.myshopify.com/admin/api/2023-07/graphql.json',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => $graphql_query,
                            CURLOPT_HTTPHEADER => [
                                'X-Shopify-Access-Token: ' . $token,
                                'Content-Type: application/json'
                            ],
                        ]);
                        $graphql_response = curl_exec($ch_graphql);
                        curl_close($ch_graphql);

                        $graphql_data = json_decode($graphql_response, true);

                        if ($graphql_data !== null && isset($graphql_data['data']['node']['image']['url'])) {
                            $custom_image = $graphql_data['data']['node']['image']['url'];
                            // echo $custom_image;
                            // die();
                        } else {
                            echo "Error fetching image URL for metafield 'bild_auf_beige'";
                        }
                        break;
                    }

                }
            }

            // Construct product data array
            $productValues = [
                $product['id'], // id
                $product['title'], // title
                $product['body_html'], // description
                $custom_image , // image_link
                $product['vendor'], // brand
                $product['product_type'], // google_product_category
                '', // fb_product_category
                'female', // gender
                $custom_color, // color
                '', // size
                'adult', // age_group
                '', // material
                '', // pattern
                '', // image[0].url
                isset($product['images'][0]['tag']) ? $product['images'][0]['tag'] : '', // image[0].tag[0]
                '', // additional_image_link[0]
                '', // additional_variant_attribute[0].variant_label
                '', // additional_variant_attribute[0].variant_value
                '', // additional_variant_attribute[1].variant_label
                '', // additional_variant_attribute[1].variant_value
                '', // gtin
                '', // ordering_index
                '', // pre_order_fulfillment_date
                '', // shipping_profile_reference_id
                '', // origin_country
                '', // importer_name
                '', // importer_address.street1
                '', // importer_address.street2
                '', // importer_address.city
                '', // importer_address.region
                '', // importer_address.postal_code
                '', // importer_address.country
                '', // manufacturer_info
                '', // sustainability_certification_type
                '', // is_customisation_required
                '', // custom_text_fields[0].field_name
                '', // custom_text_fields[0].input_guidance
                '', // custom_text_fields[0].is_required
                '', // custom_text_fields[0].max_value_length
                '', // custom_option_fields[0].field_name
                '', // custom_option_fields[0].is_required
                '', // custom_option_fields[0].allowed_options
                '', // style[0]
                '', // gemstone[0]
                '', // gemstone[1]
                '', // gemstone_clarity
                '', // gemstone_color
                '', // gemstone_creation_method
                '', // gemstone_cut
                '', // gemstone_treatment[0]
                '', // gemstone_treatment[1]
                '', // gemstone_height
                '', // gemstone_length
                '', // gemstone_width
                '', // gemstone_weight
                '', // total_gemstone_weight
                '', // plating_material
                '', // metal_stamp_or_purity
                '', // size_system
                '', // occasion[0]
                '', // additional_features[0]
                '', // additional_features[1]
                '', // standard_features[0]
                '', // chain_length
                '', // jewelry_setting_style
                '', // inscription[0]
                '', // clasp_type
                '' // earring_back_finding
            ];
            $csv_data[] = $productValues;

            // Update last product ID
            $last_product_id = $product['id'];
        }
    }
} while (!empty($data['products']));

// // Output CSV data directly to the browser
// $fp = fopen('php://output', 'w');
foreach ($csv_data as $fields) {
    fputcsv($fp, $fields);
}
fclose($fp);
?>
