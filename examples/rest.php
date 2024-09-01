<?php

$url = "https://api.restful-api.dev/objects";
$hero = [
    "name" => "The Cat",
    "data" => [
        "type"     => "animal",
        "location" => "World",
    ],
];
$curl = new \Naivic\CURL();
$hdr = [ "Content-Type: application/json" ];

# Puts The Cat to World
$res = $curl->query( "POST", $url, $hero, $hdr );
print_r( $res->data );
$id = $res->data["id"];

# Moves The Cat to Wonderland
$hero["data"] = [
    "type"     => "sapiens",
    "location" => "Wonderland",
];
$res = $curl->query( "PUT", $url."/".$id, $hero, $hdr );
print_r( $res->data );

# Swaps The Cat to Alice
$res = $curl->query( "PATCH", $url."/".$id, ["name" => "Alice"], $hdr );
print_r( $res->data );

# Wakes up Alice
$res = $curl->query( "DELETE", $url."/".$id, hdr: $hdr );
print_r( $res->data );

# Where is Alice? (who the ... is Alice)
$res = $curl->query( "GET", $url."/".$id, hdr: $hdr );
print_r( $res->data );
