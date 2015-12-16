<?php

ini_set('max_execution_time', 0);
define('DRUPAL_ROOT', getcwd());
include '/includes/bootstrap.inc';
include '/includes/install.inc';
include '/includes/password.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
?>

<?php

$GLOBALS['newFile'] = DRUPAL_ROOT . '/security-reviews.html';
$GLOBALS['createdFile'] = fopen($GLOBALS['newFile'], 'w');
$css = '<link href="style.css" rel="stylesheet" type="text/css" />';
fwrite($GLOBALS['createdFile'], $css);

/**
 * Checking PHP module is enable or not.
 */
function check_php_module() {
    $query = db_select('system', 's')
            ->fields('s', array('status'))
            ->condition('s.name', 'php')
            ->execute()->fetchField();
    if ($query == 1) {
        $data = '<li><b>Error </b> Try to disable PHP module.</li>';
        fwrite($GLOBALS['createdFile'], $data);
    }
}

check_php_module();

/**
 * Checking site administrator's username and password.
 */
function check_username_password() {
    $sitename = variable_get('site_name', "Default site name");
    $usernames = array('admin', 'admin123', 'siteadmin', 'siteadmin123', $sitename);

    $query = db_select('users', 'u')
            ->fields('u', array('name'))
            ->condition('u.uid', 1)
            ->execute()->fetchAssoc();

    if (in_array($query['name'], $usernames)) {
        $data = "<li><b>Error </b>Change site administrator's username ie <b>" . $query['name'] . "</b></li>";
        fwrite($GLOBALS['createdFile'], $data);
    }
    
    $passwords = array('admin', 'admin123', 'siteadmin', 'siteadmin123', $sitename);
    $passwords[] = $query['name'];
    $account = user_load_by_name($query['name']);
    foreach ($passwords as $password) {
        $pass = user_check_password($password, $account);
        if ($pass == TRUE) {
            $data = "<li><b>Error </b>Need to change site administrator's password immediately.</li>";
            fwrite($GLOBALS['createdFile'], $data);
            break;
        }
    }
}

check_username_password();

/**
 * Checking permissions on files and folders.
 */
function check_permission() {
    $conf_dir = drupal_verify_install_file(conf_path(), FILE_NOT_WRITABLE, 'dir');
    if (!$conf_dir) {
        $data = '<li>' . t('The directory %file is not protected from modifications and poses a security risk. You must change the directory\'s permissions to be non-writable. ', array('%file' => conf_path())) . '</li>';
        fwrite($GLOBALS['createdFile'], $data);
    }

    $conf_file = drupal_verify_install_file(conf_path() . '/settings.php', FILE_EXIST | FILE_READABLE | FILE_NOT_WRITABLE);
    if (!$conf_file) {
        $data = '<li>' . t('The file %file is not protected from modifications and poses a security risk. You must change the file\'s permissions to be non-writable.', array('%file' => conf_path() . '/settings.php')) . '</li>';
        fwrite($GLOBALS['createdFile'], $data);
    }
}

check_permission();

function dVersion() {
    $updates = 'http://updates.drupal.org/release-history/';
    $druUrl = $updates . 'drupal/' . DRUPAL_CORE_COMPATIBILITY;
    $xml = simplexml_load_file($druUrl);
    $rows = array();
    $rows['dversion'] = array(VERSION, $xml->releases->release[0]->version);
    $output = theme('table', array(
      'header' => array('Drupal Current Version', 'Available Version'),
      'rows' => $rows,
    ));
    fwrite($GLOBALS['createdFile'], $output);
}

dVersion();

/**
 * checking updates for contributed modules.
 */
function check_updates_modules() {
    $updates = 'http://updates.drupal.org/release-history/';
    $results = db_select('system', 's')
        ->fields('s', array('filename', 'name', 'info'))
        ->condition('s.status', 1)
        ->condition('s.type', 'module')
        ->condition('s.filename', db_like('sites/all/') . '%', 'LIKE')
        ->orderBy('s.name', 'ASC')
        ->execute();

    $rows = array();

    foreach ($results as $key => $val) {
        $info = unserialize($val->info);
        $val_filename_exploded = explode('/', $val->filename);
        $url = $updates . $val_filename_exploded[3] . '/' . $info['core'];
        $xml = simplexml_load_file($url);

        if ($info['version'] == $xml->releases->release[0]->version) {
            $version = 'UPDATED';
        }
        else {
            $version = $xml->releases->release[0]->version;
        }

        $rows[$val_filename_exploded[3]] = array(
          $info['name'],
          $info['version'],
          $version,
          $val_filename_exploded[3]
        );
    }

    $count = count($rows);
    $output = '<p>' . format_plural($count, '1 contributed module installed.', '@count contributed modules installed.') . '</p>';
    $output .= theme('table', array(
      'header' => array('Name', 'Current Version', 'Available Version', 'Extra'),
      'rows' => $rows,
      'empty' => t('No contributed modules installed.'))
    );

    $data = $output;
    fwrite($GLOBALS['createdFile'], $data);
}

fclose($GLOBALS['newFile']);
check_updates_modules();
?>
