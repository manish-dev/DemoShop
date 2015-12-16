<pre>
<?php
$url = 'http://updates.drupal.org/release-history/views/7.x';
$xml = simplexml_load_file($url);
print $xml->releases->release[0]->version;
    print_r($xml);
?>
</pre>