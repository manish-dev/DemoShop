<?php ini_set('max_execution_time', 0); ?>
<?php
if (empty($_POST)) {
    header('location: index.php');
}else {
    $sitename = $_POST['site_name'];
    $site_folder_name = $_POST['site_folder_name'];
}

$parent = dirname(dirname(__FILE__));
$directories = scandir($parent);
$directory = $site_folder_name;
$incfile = '/includes/bootstrap.inc';

if (!in_array($directory, $directories)) {
    echo 'This Site is not on the server';
    exit;
}

if (file_exists($parent . '/' . $directory . $incfile)) {
    copy(getcwd() . '/security-review.php', $parent . '/' . $directory . '/security-review.php');    
    $checkUrl = get_headers($sitename, 1);
    if($checkUrl[0] == 'HTTP/1.1 404 Not Found'){
        echo 'Please check Sitename and try again.';
    }else{
        $notepad = file_get_contents($sitename . '/security-review.php');
        print '<a href="' . $directory . '.html">Check reviews here</a>';
        copy($parent . '/' . $directory . '/security-reviews.html', getcwd() . '/' . $directory . '.html');
        unlink($parent . '/' . $directory . '/security-reviews.html');
        unlink($parent . '/' . $directory . '/security-review.php');
    }
}else {
    echo 'This is not Drupal site.';
}
?>