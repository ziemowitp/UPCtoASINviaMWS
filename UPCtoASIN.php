<?php

  require_once('.config.inc.php');
  require_once('CSVFiles.inc.php');

  $serviceUrl = "https://mws.amazonservices.com/Products/2011-10-01";

  $config = array (
    'ServiceURL' => $serviceUrl,
    'ProxyHost' => null,
    'ProxyPort' => -1,
    'ProxyUsername' => null,
    'ProxyPassword' => null,
    'MaxErrorRetry' => 3,
  );

 $service = new MarketplaceWebServiceProducts_Client(
        AWS_ACCESS_KEY_ID,
        AWS_SECRET_ACCESS_KEY,
        APPLICATION_NAME,
        APPLICATION_VERSION,
        $config);

  $request = new MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest();
  $request->setSellerId(MERCHANT_ID);
  $request->setMarketplaceId(MARKETPLACE_ID);

  // Open CSV for reading
  $filein = "taw.csv";
  $fileout = "taw_out.csv";

  $call = new CSVFiles($fileout);

  foreach ($call->ReadCSV($filein) as $order) {
    $arr_upc[] = $order[0];
    $arr_p[] = $order[1];

    if (count($arr_upc) == 5) {
      print_r($arr_upc);

      $request->setIdType("UPC");
      $IdList = new MarketplaceWebServiceProducts_Model_IdListType();
      $IdList->setId($arr_upc);
      $request->setIdList($IdList);

      $output = invokeGetMatchingProductForId($service,$request);
    
      $call->writecsv($output);

      // Merge arrays
      $final = array();
      for ($i = 0; $i < 5; $i++) {
	array_unshift($output[$i],$arr_p[$i]);
      }

      // Cleanup
      $arr_upc = array();
      $arr_p = array();

      // Wait a sec or get throttled.  One request every 2 sec = 1800 an hour.
      sleep(2);
    }
  }

  function invokeGetMatchingProductForId(MarketplaceWebServiceProducts_Interface $service,$request) {
    try {
      $response = $service->GetMatchingProductForId($request);

      $dom = new DOMDocument();
      $dom->loadXML($response->toXML());
      $dom->preserveWhiteSpace = false;
      $dom->formatOutput = true;
      //echo $dom->saveXML();

      $searchNode = $dom->getElementsByTagName("GetMatchingProductForIdResult");

      $array = array();
      foreach ($searchNode as $searchNode) {
        $upc = $searchNode->getAttribute('Id');

        $market = $searchNode->getElementsByTagName("ASIN");
	$asin = $market->item(0)->nodeValue;

	$array[] = array($upc,$asin);
      }

      echo("ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");

    } catch (MarketplaceWebServiceProducts_Exception $ex) {
      echo("Caught Exception: " . $ex->getMessage() . "\n");
      echo("Response Status Code: " . $ex->getStatusCode() . "\n");
      echo("Error Code: " . $ex->getErrorCode() . "\n");
      echo("Error Type: " . $ex->getErrorType() . "\n");
      echo("Request ID: " . $ex->getRequestId() . "\n");
      echo("XML: " . $ex->getXML() . "\n");
      echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");

      die("Something went wrong!");
    }

    return $array;
  }
