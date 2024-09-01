# Naivic\CURL : PHP class to ease most of cURL-related tasks

Some examples:
- Simplest GET query - get book information from the openlibrary.org
```php
$url = "http://openlibrary.org/search.json";
$needle = "Alice Wonderland";
$curl = new \Naivic\CURL();
$res = $curl->query( "GET", $url, [ "q" => $needle ] );
echo "Total time: ".$res->info["total_time"]." sec\n";
echo "Year: ".$res->data["docs"][0]["first_publish_year"]."\n";
```
result
```
Total time: 21.788099 sec
Year: 1889
```
- Simple REST API - POST, GET, PUT, PATCH, DELETE queries

Used https://restful-api.dev/ to illustrate functionality

Lets prepare to make requests:
```php
$url = "https://api.restful-api.dev/objects";
$hdr = [ "Content-Type: application/json" ];
$hero = [
    "name" => "The Cat",
    "data" => [
        "type"     => "animal",
        "location" => "World",
    ],
];
$curl = new \Naivic\CURL();
```
Ok, now puts The Cat to World, and store Id from server response
```php
$res = $curl->query( "POST", $url, $hero, $hdr );
print_r( $res->data );
$id = $res->data["id"];
```
output:
```
Array
(
    [id] => ff80818191ad7c2f0191af6b4a550247
    [name] => The Cat
    [createdAt] => 2024-09-01T21:08:49.883+00:00
    [data] => Array
        (
            [type] => animal
            [location] => World
        )

)
```

```php

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
```
