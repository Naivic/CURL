# Naivic\CURL : PHP class to ease most of cURL-related tasks

Some examples:
- get book information from the openlibrary.org (command line)
```php
$url = "http://openlibrary.org/search.json";
$needle = "Alice Wonderland";
$curl = new \Naivic\CURL();
$res = $curl->query( "GET", $url, [ "q" => $needle ] );
echo "Total time: ".$res->info["total_time"]." sec\n";
echo "Year: ".$res->data["docs"][0]["first_publish_year"]."\n";
```
result will looks like
```
Total time: 21.788099 sec
Year: 1889
```
