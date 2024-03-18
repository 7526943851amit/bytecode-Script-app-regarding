<?php
include 'db.php';
// $shopifyApiKey = "c3060c971def7799e29cb7e43c6ec174";
// $shopifyPassword = "89615867e56809d2e8fcaf01a7916902";
// $shopifyToken = "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";
$shopifyStoreUrl = "https://anytimetoolandauto.com";
//     $startFilenumber = 161;
// $endfilenumber = 278;

for ($currentFileNumber = $startFilenumber; $currentFileNumber <= $endfilenumber; $currentFileNumber++) {
    $jsonFileUrl = "{$currentFileNumber}.json";
$jsonData = file_get_contents($jsonFileUrl);
$products = json_decode($jsonData, true);



foreach ($products as $product) {
    $title2 = html_entity_decode($product['Title'], ENT_QUOTES, 'UTF-8');
    $sku2 = $product['PART_NUMBER'];
    try {
      
        
        $title = html_entity_decode($product['Title'], ENT_QUOTES, 'UTF-8');
        
        $manufacture = $product['Manufacturer'];
        $title = $manufacture." " . $title;
        // $description = $product['long_description'];
        $description = html_entity_decode($product['long_description'], ENT_QUOTES, 'UTF-8');
        $vendor = $product['Manufacturer'];
        $tags = $product['SUBCATEGORY'];
        $image = $product['IMAGE'];
        $price = $product['ISN_RETAIL'];
        $cost = $product['COST'];
        $mapp = $product['MAPP'];
        $weight = $product['SHIPWEIGHT'];
        $barcode = $product['SUP_PART_NUMBER'];
        $sku = $product['PART_NUMBER'];
        $productType = $product['CATEGORY'];
        $date = date('Y-m-d H:i:s');
        if ($mapp > 0) {
            $compare_at_price = $mapp;
        } else {
            $compare_at_price = $price;
        }
        $productData = array(
            'product' => array(
                'title' => $title,
                'body_html' => $description,
                'vendor' => $vendor,
                'tags' => $tags,
                'images' => array(array('src' => $image)),
                'variants' => array(
                    array(
                        'price' => $compare_at_price,
                        'cost' =>$cost,
                        'weight' => $weight,
                        'barcode' => $barcode,
                          'sku' => $sku,
                    ),
                ),
                'product_type' => $productType,
                'status' => 'active',
            )
        );

        $productDataJson = json_encode($productData);

        $apiEndpoint = 'https://c3060c971def7799e29cb7e43c6ec174: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@5f9e44.myshopify.com/admin/api/2023-10/products.json';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiEndpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $productDataJson,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-Shopify-Access-Token: ' . $shopifyToken,
            ),
        ));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception('Curl error: ' . curl_error($curl));
        }

        curl_close($curl);

        
//    echo $response;
       echo "prodcut id added : " . json_decode($response, true)['product']['id'] . "\n";
        $productId = json_decode($response, true)['product']['id'];
        
    }
  catch (Exception $e) {
        echo 'Exception: ' . $e->getMessage() . "\n";
        custom_log($sku2, $title2, $e->getMessage());
    }
}
    echo "Processing complete for file $currentFileNumber. next file countinue";
}

function custom_log($sku2, $title2, $message) {
    $logFile = 'file.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $logMessage = "$timestamp Sku ID: $sku2, Title: $title2 - $message" . PHP_EOL;
    error_log($logMessage, 3, $logFile);
}

?>
