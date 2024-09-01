# Naivic\CURL : PHP class to ease most of cURL-related tasks

## Some examples:
### Simplest GET query - get book information from the openlibrary.org
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
### Simple REST API - POST, GET, PUT, PATCH, DELETE queries

Used https://restful-api.dev/ to illustrate functionality

Lets prepare to make series of REST API requests:
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
Ok, now put The Cat to World, and store ID from server response
```php
$res = $curl->query( "POST", $url, $hero, $hdr );
$id = $res->data["id"];
```
$res->data:
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
Then, move The Cat to Wonderland
```php
$hero["data"] = [
    "type"     => "sapiens",
    "location" => "Wonderland",
];
$res = $curl->query( "PUT", $url."/".$id, $hero, $hdr );
```
$res->data:
```
Array
(
    [id] => ff80818191ad7c2f0191af6b4a550247
    [name] => The Cat
    [updatedAt] => 2024-09-01T21:08:51.547+00:00
    [data] => Array
        (
            [type] => sapiens
            [location] => Wonderland
        )

)
```
And... swap The Cat to Alice!
```php
$res = $curl->query( "PATCH", $url."/".$id, ["name" => "Alice"], $hdr );
print_r( $res->data );
```
$res->data:
```
Array
(
    [id] => ff80818191ad7c2f0191af6b4a550247
    [name] => Alice
    [updatedAt] => 2024-09-01T21:08:52.623+00:00
    [data] => Array
        (
            [type] => sapiens
            [location] => Wonderland
        )

)
```
Knock-knock, wake up Alice
```php
$res = $curl->query( "DELETE", $url."/".$id, hdr: $hdr );
```
$res->data:
```
Array
(
    [message] => Object with id = ff80818191ad7c2f0191af6b4a550247 has been deleted.
)
```php
# Where is Alice? ("who the ... is Alice?")
$res = $curl->query( "GET", $url."/".$id, hdr: $hdr );
```
$res->data:
```
Array
(
    [error] => Oject with id=ff80818191ad7c2f0191af6b4a550247 was not found.
)
```
