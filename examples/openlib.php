<?php

$url = "http://openlibrary.org/search.json";
$needle = "Alice Wonderland";
$curl = new \Naivic\CURL();
$res = $curl->query( "GET", $url, [ "q" => $needle ] );
echo "Total time: ".$res->info["total_time"]." sec\n";
echo "Year: ".$res->data["docs"][0]["first_publish_year"]."\n";

