<?php ini_set('max_execution_time', 0); ?>
<?php define('DRUPAL_ROOT', getcwd()); ?>
<?php include '/includes/bootstrap.inc'; ?>
<?php include '/includes/install.inc'; ?>
<?php include '/includes/password.inc'; ?>
<?php drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL); ?>

<?php

/**
 * Checking PHP module is enable or not.
 */
function check_php_module(){
    $query = db_select('system', 's')
        ->fields('s', array('status'))
        ->condition('s.name', 'php')
        ->execute()->fetchField();
    if($query == 1){
        print '<li><b>Error </b> Try to disable PHP module.</li>';
    }
}
check_php_module();

/**
 * Checking site administrator's username and password.
 */
function check_username_password(){
    $sitename = variable_get('site_name', "Default site name");
    $usernames = array('admin', 'admin123', 'siteadmin', 'siteadmin123', $sitename);

    $query = db_select('users', 'u')
        ->fields('u', array('name'))
        ->condition('u.uid', 1)
        ->execute()->fetchAssoc();

    if(in_array($query['name'], $usernames)){
        print "<li><b>Error </b>Change site administrator's username ie <b>" . $query['name'] . "</b></li>";
    }

        $account = user_load_by_name($query['name']);
        $pass = user_check_password($query['name'], $account);
        if($pass == TRUE){
            print "<li><b>Error </b>Need to change site administrator's password immediately.</li>";
        }
}
check_username_password();
?>
<table>
    <thead>
        <tr>
            <th>Drupal Current Version</th>
            <th>Available Version</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php print VERSION; ?></td>
            <td>
                <?php
                $updates = 'http://updates.drupal.org/release-history/';
                $druUrl = $updates . 'drupal/' . DRUPAL_CORE_COMPATIBILITY;
                $xml = simplexml_load_file($druUrl);
                print $xml->releases->release[0]->version;
                ?>
            </td>
        </tr>
    </tbody>
</table>
<?php
/**
 * checking updates for contributed modules.
 */
function check_updates_modules(){
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
//        $pid = pcntl_fork();
//        if($pid == -1){
//            exit('Error');
//        }else{
            $info = unserialize($val->info);
            $val_filename_exploded = explode('/', $val->filename);
            $url = $updates . $val_filename_exploded[3] . '/' . $info['core'];
            $xml = simplexml_load_file($url);

            if($info['version'] == $xml->releases->release[0]->version){
                $version = 'UPDATED';            
            }else{
                $version = $xml->releases->release[0]->version;            
            }

            $rows[$val_filename_exploded[3]] = array(
              $info['name'],
              $info['version'],
              $version,
              $val_filename_exploded[3]
            );
        //}        
    }

    $count = count($rows);
    $output = '<p>' . format_plural($count, '1 contributed module installed.', '@count contributed modules installed.') . '</p>';
    $output .= theme('table', array(
      'header' => array('Name', 'Current Version', 'Available Version', 'Extra'),
      'rows' => $rows,
      'empty' => t('No contributed modules installed.'))
    );

    //print $output;
    $fp = fopen(DRUPAL_ROOT . "/s-review.html","wb");
    fwrite($fp,$output);
    fclose($fp);
}
check_updates_modules();

?>
