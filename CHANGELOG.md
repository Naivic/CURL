# 1.2.0 - 2025-10-27

Add public class property "timeout_ms" (default - infinite).

You can set proper timeout like this:
```
$url = "http://openlibrary.org/search.json";
$needle = "Alice Wonderland";
$curl = new \Naivic\CURL();
$curl->timeout_ms = 100; // 0.1 sec
$res = $curl->query( "GET", $url, [ "q" => $needle ] );
```

Also you can check if operation was timed out:
```
if( $res["timed_out"] ) {
    // Maybe, we need to retry this query?
}
```
