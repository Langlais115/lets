<?php

if (!defined('LETS_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


//require_once LETS_ROOT."locales/{$_SESSION['lang']}/install.mo";
require_once LETS_ROOT.'includes/files_and_folders_functions.php';


echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<title>Lets-Software Setup</title>';
echo '<meta http-equiv="Content-Type" content="text/html; charset='.$_SESSION['lang'].'" />';
echo '<link href="' . URL . '/templates/' . TEMPLATE . '/styles/install.css" rel="stylesheet" type="text/css" />';
echo '</head>';
echo '<body>';


$message                                    = '';
$submitted_website_settings     = 0;
$submitted_account                  = 0;
$files_status                               = true;


if (isset($_POST['submit'])) {
    $post_post = remove_slashes($_POST);
    $okToUpdateDb = 0;                                                    // Stay at 0 if there is no error, pas it a 1 in case of any error
    if ($post_post['submit'] == 'Enter Config') {
            // strpos(shell_exec('/usr/local/apache/bin/apachectl -l'), 'mod_rewrite') !== false
            if (!in_array('mod_rewrite', apache_get_modules())) {$message .= '<li class="error">'.T_('Mod_Rewrite MUST be enable. Current status = Disable').'</li>'; } // We don't prevent you to keep going as this test will fail if you don't run Apache
            //TODO:  Generate this number randomly so user don't have to type one them selves
            // As I think most users will not know why this string is used for. 
            if (!$post_post['site_key']) {$message .= '<li class="error">'.T_("Please enter a Encryption Key.").'</li>'; $okToUpdateDb = 1;}
            if (!$post_post['site_name']) {$message .= '<li class="error">'.T_("Please enter a Site Name.").'</li>'; $okToUpdateDb = 1;}
            if (!$post_post['url']) {
                $message .= '<li class="error">'.T_('Please enter a URL.').'</li>';  $okToUpdateDb = 1;
            }elseif (!filter_var($post_post['url'], FILTER_VALIDATE_URL)){
                $message .= '<li class="error">'.T_('Please enter a correct URL.').'</li>';  $okToUpdateDb = 1;
            }
            if (!$post_post['path']) {$message .= '<li class="error">'.T_('Please enter the path').'</li>'; $okToUpdateDb = 1;}
                // filter_var compatible only with PHP version 5.2.0 or above
            if (!filter_var($post_post['admin_email'], FILTER_VALIDATE_EMAIL))  {$message .= '<li class="error">'.T_('Please enter Admin email.').'</li>'; $okToUpdateDb = 1;}
            if (!filter_var($post_post['validation_email'], FILTER_VALIDATE_EMAIL)) {$message .= '<li class="error">'.T_('Please enter Validation email.').'</li>'; $okToUpdateDb = 1;}
            if (!filter_var($post_post['technical_email'], FILTER_VALIDATE_EMAIL)) {$message .= '<li class="error">'.T_('Please enter Technical email.').'</li>'; $okToUpdateDb = 1;}
            if (!$post_post['location']) {$message .= '<li class="error">'.T_('Please enter your location.').'</li>'; $okToUpdateDb = 1;}
            $submitted_website_settings  = 1;
            if ($okToUpdateDb == 0) {
                // make sure that $post_post['url'] and $post_post['path'] end by a slash
                $post_post['url'] = preg_match('/\/$/', $post_post['url'])? $post_post['url'] : preg_replace('/$/', '/', $post_post['url']);
                $post_post['path'] = preg_match('/\/$/', $post_post['path'])? $post_post['path'] : preg_replace('/$/', '/', $post_post['path']);
                if ($mysql->query("UPDATE config SET
                site_name             = '" . mysql_escape_string($post_post['site_name']) . "',
                site_key                = '" . mysql_escape_string($post_post['site_key']) . "',
                url                         = '" . mysql_escape_string($post_post['url']) . "',
                path                      = '" . mysql_escape_string($post_post['path']) . "',
                admin_email          = '" . mysql_escape_string($post_post['admin_email']) . "',
                validation_email     = '" . mysql_escape_string($post_post['validation_email']) . "',
                technical_email     = '" . mysql_escape_string($post_post['technical_email']) . "',
                update_email        = '" . mysql_escape_string($post_post['admin_email']) . "',
                email_from_name  = '" . mysql_escape_string($post_post['site_name']) . "',
                location                = '" . mysql_escape_string($post_post['location']) . "',
                hour_offset            = '" . mysql_escape_string($post_post['hour_offset']) . "' ")) {
                    if( !isset($_SESSION['submitted_website_settings']) ){
                        $message .= '<li class="ok">'.T_('Configuration Complete!').'</li>';
                        $_SESSION['submitted_website_settings'] = 'done';
                    }
                }else {
                    echo '<li class="error">'.T_('Database update fail').'</li>';
                }
            }
        }
}
    // Creation of the admin account
    if (isset($post_post['submit']) && $post_post['submit'] == 'Create Account') {
        if (!$post_post['first_name'] or
            !$post_post['last_name'] or
            !$post_post['password'] or
            !$post_post['second_password']){
                $submitted_account = 1;
                $message .= '<li class="error">'.T_('You did not fill out all the fields!!!').'</li>';
        } elseif ($post_post['password'] != $post_post['second_password']) {
            $submitted_account = 1;
            $message .= '<li class="error">'.T_('Passwords did not match, please try again.').'</li>';
        } else {
            if (mysql_num_rows(mysql_query("SHOW TABLES LIKE 'config'")) > 0 && $mysql->result('SELECT site_key FROM config')) {
                $site_key = $mysql->result['site_key'];
                $password = crypt(md5($post_post['second_password']),md5($site_key));
                if ($mysql->query("INSERT INTO accounts SET
                    accountID = '1',
                    first_name = '".mysql_escape_string($post_post['first_name'])."',
                    last_name = '".mysql_escape_string($post_post['last_name'])."',
                    type = '2',
                    validated = '1',
                    created_day = '".$date['day']."',
                    created_month = '".$date['month']."',
                    created_year = '".$date['year']."',
                    expiry_day = '1',
                    expiry_month = '1',
                    expiry_year = '3000',
                    password = '".$password."'")) {
                        $message .= '<li class="ok">'.T_('Account Creation Complete!').'</li>';
                }
            }
        }
    }
    
    if (isset($post_post['submit']) && $post_post['submit'] == 'Set Permissions') {
        if (!$post_post['ftp_host'] or !$post_post['ftp_path'] or !$post_post['ftp_login'] or !$post_post['ftp_password'] or !$post_post['second_ftp_password']) {
            $message .= '<li class="error">'.T_("You did not fill out all the ftp fields!!!").'</li>';
        } elseif ($post_post['ftp_password'] != $post_post['second_ftp_password']) {
            $message .= '<li class="error">'.T_("Passwords did not match, please try again.").'</li>';
        } else {
            if (mysql_num_rows(mysql_query("SHOW TABLES LIKE 'config'")) > 0 && $mysql->result('SELECT * FROM config')) {
                $site_key = $mysql->result['site_key'];
                $path = $mysql->result['path'];
                $site_name = str_replace(' ','_',$mysql->result['site_name']);
                $password = md5_encrypt($post_post['second_ftp_password'],$site_key);
                if ($mysql->query("UPDATE config SET
                    ftp_host = '".mysql_escape_string($post_post['ftp_host'])."',
                    ftp_path = '".mysql_escape_string($post_post['ftp_path'])."',
                    ftp_login = '".mysql_escape_string($post_post['ftp_login'])."',
                    ftp_password = '".mysql_escape_string($password)."'")) {
                        $message .= T_("FTP information set!").'<br /><br />';
                        $message .= '----------------------------------<br /><br />';
                        $message .= T_("Attempting to connect to FTP server...");
                        
                        $conn_id = ftp_connect($post_post['ftp_host']); 
                        $login_result = ftp_login($conn_id,$post_post['ftp_login'],$post_post['second_ftp_password']); 
                        if ((!$conn_id) || (!$login_result)) { 
                               $message .= T_('<strong>FTP connection has failed</strong><br />Please check and re-enter details.<br />If this method continues to fail you will have to set permissions manually.');
                            exit; 
                        } else {
                            $message .= T_('Done!').'<br />';
                            
                            if (ftp_chdir($conn_id,$post_post['ftp_path'])) {
                                $message .= T_('Moved into directory: ').ftp_pwd($conn_id).'<br />';
                                
                                $dir_contents = ftp_rawlist($conn_id, ".");
                                $tmp_var = '';
                                foreach ($dir_contents as $item) {
                                    $tmp_var .= $item.'<br />';
                                }
                                $files_exist = true;
                                if (!strpos(' '.$tmp_var,'.htaccess')) {
                                    $files_exist = false;
                                    $message .= '<li class="error"><strong>.htaccess</strong> '.T_("NOT FOUIND!").'</li>';
                                }
                                if (!strpos(' '.$tmp_var,'images')) {
                                    $files_exist = false;
                                    $message .= '<li class="error"><strong>images</strong> '.T_("NOT FOUIND!").'</li>';
                                }
                                if (!strpos(' '.$tmp_var,'includes')) {
                                    $files_exist = false;
                                    $message .= '<li class="error"><strong>includes</strong> '.T_("NOT FOUIND!").'</li>';
                                }
                                if (!strpos(' '.$tmp_var,'templates')) {
                                    $files_exist = false;
                                    $message .= '<li class="error"><strong>templates</strong> '.T_("NOT FOUIND!").'</li>';
                                }
                                if (!strpos(' '.$tmp_var,'logs')) {
                                    $files_exist = false;
                                    $message .= '<li class="error"><strong>logs</strong> '.T_("NOT FOUIND!").'</li>';
                                }
                                if (!strpos(' '.$tmp_var,'index.php')) {
                                    $files_exist = false;
                                    $message .= '<li class="error"><strong>index.php</strong> '.T_("NOT FOUIND!").'</li>';
                                }
                                
                                if (!$files_exist) {
                                    $message .= '<li><strong>'.T_("FTP root is incorrect").'</strong></li>';
                                } else {
                                    $mode = 777; 
                                    $np = '0'.$mode;
                                    if (ftp_chmod($conn_id, eval("return({$np});"), '.htaccess')){
                                        $message .= '<li class="ok"><strong>.htaccess</strong> '.T_("Permissions Set!").'</li>';
                                    } else {
                                        $message .= '<li class="error"><strong>.htaccess</strong> '.T_("Permissions Failed!").'</li>';
                                    }
                                    if (ftp_chmod($conn_id, eval("return({$np});"), 'images')){
                                        $message .= '<li class="ok"><strong>images</strong> '.T_("Permissions Set!").'</li>';
                                    } else {
                                        $message .= '<li class="error"><strong>images</strong> '.T_("Permissions Failed!").'</li>';
                                    }
                                    if (ftp_chmod($conn_id, eval("return({$np});"), 'logs')){
                                        $message .= '<li class="ok"><strong>logs</strong> '.T_("Permissions Set!").'</li>';
                                    } else {
                                        $message .= '<li class="error"><strong>logs</strong> '.T_("Permissions Failed!").'</li>';
                                    }
                                    
                                    if (ftp_chdir($conn_id,'logs')) {
                                        $mode = 666; 
                                        $np = '0'.$mode;
                                        $message .= T_('Moved into directory: ').ftp_pwd($conn_id).'<br />';
                                        $dir_contents = ftp_rawlist($conn_id, ".");
                                        $tmp_var = '';
                                        foreach ($dir_contents as $item) {
                                            $tmp_var .= $item.'<br />';
                                        }
                                        
                                        $upload = true;
                                        if (!strpos(' '.$tmp_var,$site_name.'.log')) {
                                            $message .= '<strong>'.$site_name.'.log</strong> '.T_('not found...');
                                            if (ftp_put($conn_id, $site_name.'.log', $path.'.htaccess', FTP_ASCII)) {
                                                $message .= ' Uploaded '.$site_name.'.log!<br />';
                                                if (ftp_chmod($conn_id, eval("return({$np});"), $site_name.'.log')){
                                                    $message .= '<li class="ok"><strong>'.$site_name.'.log</strong> '.T_('Permissions Set!').'</li>';
                                                } else {
                                                    $message .= '<li class="error"><strong>'.$site_name.'.log</strong> '.T_('Permissions Failed!').'</li>';
                                                }                                                
                                            } else {
                                                $message .= ' <li class="error">'.T_('Failed to upload:').' <strong>'.$site_name.'.log</strong></li>';
                                            }                                            
                                        }

                                        if (!strpos(' '.$tmp_var,$site_name.'_Errors.log')) {
                                            $message .= '<li class="error"><strong>'.$site_name.'_Errors.log</strong> '.T_('not found...').'</li>';
                                            if (ftp_put($conn_id, $site_name.'_Errors.log', $path.'.htaccess', FTP_ASCII)) {
                                                $message .= ' <li class="ok">Uploaded <strong>'.$site_name.'_Errors.log</strong>!</li>';
                                                if (ftp_chmod($conn_id, eval("return({$np});"), $site_name.'_Errors.log')){
                                                    $message .= '<li class="ok"><strong>'.$site_name.'_Errors.log</strong> '.T_('Permissions Set!').'</li>';
                                                } else {
                                                    $message .= '<li class="error"><strong>'.$site_name.'_Errors.log</strong> '.T_('Permissions Failed!').'</li>';
                                                }                                                
                                            } else {
                                                $message .= ' <li class="error">'.T_('Failed to upload:').' <strong>'.$site_name.'_Errors.log</strong></li>';
                                            }    
                                        }
                                    }
                                }
                            } else {
                                $message .= '<li class="error">'.T_('Cannot find FTP root please check settings').'</li>';
                            }
                            $message .= '<br />----------------------------------<br />';
                            ftp_close($conn_id); 

                        }
                } else {
                    echo $mysql->error;
                }
            } else {
                echo $mysql->error;
            }
        }        
    }    


if (mysql_num_rows(mysql_query("SHOW TABLES LIKE 'config'")) == 0 && !$mysql->build_array('SELECT * FROM config')) {
    if (strpos($mysql->error,T_('config\' doesn\'t exist'))) {
        if ($mysql->query("
            CREATE TABLE IF NOT EXISTS `config` (
              `ID` int(1) NOT NULL default '0',
              `enable_noticeboard` int(11) default '1',
              `enable_articles` int(11) default '1',
              `enable_events` int(11) default '1',
              `enable_faq` int(11) default '1',
              `enable_links` int(11) default '1',
              `enable_member_list` int(11) default '1',
              `site_key` varchar(255) NOT NULL default '',
              `path` varchar(255) NOT NULL default '',
              `url` varchar(255) NOT NULL default '',
              `default_site_language` VARCHAR( 3 ) NOT NULL default '',
              `enable_url_session_ids` varchar(255) NOT NULL default '0',
              `admin_email` varchar(255) NOT NULL default '',
              `site_name` varchar(255) NOT NULL default '',
              `register_terms` longtext NOT NULL,
              `visitor_message` longtext NOT NULL,
              `member_message` longtext NOT NULL,
              `new_member_message` longtext,
              `require_transaction_description` int(1) NOT NULL default '1',
              `setup_fee` decimal(7,2) default '0.00',
              `enable_transaction_service_fee` int(1) NOT NULL default '0',
              `transaction_service_fee_seller` decimal(7,2) NOT NULL default '0.00',
              `transaction_service_fee_buyer` decimal(7,2) NOT NULL default '0.00',
              `currency_name` varchar(255) NOT NULL default '',
              `enable_auctions` int(1) default '1',
              `freeze_auction_after_bid` int(1) default '1',
              `prevent_edit_after_transaction` int(1) default '1',
              `prevent_deletion_after_transaction` int(1) default '1',
              `lock_buy_now_price` int(1) default '0',
              `enable_images` int(1) default '1',
              `enable_instant_buy` int(1) default '1',
              `hour_offset` int(3) default '0',
              `image_width_thumb_noticeboard` int(4) NOT NULL default '120',
              `image_height_thumb_noticeboard` int(4) NOT NULL default '200',
              `image_width_page_noticeboard` int(4) NOT NULL default '400',
              `image_height_page_noticeboard` int(4) NOT NULL default '600',
              `image_width_thumb_article` int(4) NOT NULL default '150',
              `image_height_thumb_article` int(4) NOT NULL default '250',
              `image_width_page_article` int(4) NOT NULL default '350',
              `image_height_page_article` int(4) NOT NULL default '500',
              `image_width_thumb_member` int(4) NOT NULL default '80',
              `image_height_thumb_member` int(4) NOT NULL default '100',
              `image_width_page_member` int(4) NOT NULL default '400',
              `image_height_page_member` int(4) NOT NULL default '600',
              `image_quality` int(3) NOT NULL default '80',
              `enable_comments` int(1) default '1',
              `require_comment_title` int(1) default '1',
              `require_comment_body` int(1) default '1',
              `location` varchar(255) NOT NULL default '0',
              `validate_members` int(1) default '1',
              `validate_content` int(1) default '1',
              `validate_articles` int(1) default '0',
              `validate_events` int(1) default '0',
              `validate_faq` int(1) default '0',
              `validate_links` int(1) default '0',
              `enable_rss` int(1) default '0',
              `validate_xhtml` int(1) default '0',
              `show_comment_edited` int(1) default '1',
              `allow_comment_deletion` int(1) default '0',
              `event_description_required` int(1) default '1',
              `twelve_hour_clock` int(1) default '1',
              `event_location_required` int(1) default '1',
              `show_faq_details` int(1) default '0',
              `show_link_details` int(1) default '1',
              `require_link_description` int(1) default '1',
              `require_link_title` int(1) default '1',
              `require_link_url` int(1) default '0',
              `negative_balance_limit` decimal(6,2) default '10000.00',
              `comment_member_images` int(1) default '1',
              `transaction_name_singular` varchar(50) NOT NULL default 'trade',
              `transaction_name_plural` varchar(50) NOT NULL default 'trades',
              `comment_name` varchar(255) NOT NULL default 'Messages',
              `comment_name_plural` varchar(255) NOT NULL default 'comments',
              `comment_name_singular` varchar(255) NOT NULL default 'comment',
              `validation_email` varchar(255) NOT NULL default '',
              `technical_email` varchar(255) NOT NULL default '',
              `currency_name_singular` varchar(255) NOT NULL default 'green dollar',
              `canadian` int(1) default '0',
              `template` varchar(255) NOT NULL default 'default',
              `dump_balance_accountID` int(9) default '1',
              `allow_view_other_transaction_history` int(1) default '1',
              `default_expiry_message` varchar(255) default NULL,
              `suspend_on_expiry` int(1) default '0',
              `time_out` int(4) default '360',
              `restrict_updown_links` int(1) default '0',
              `member_expiry_hidden` int(11) default '0',
              `bulk_trading_confirm` int(1) default '1',
              `default_bulk_transaction_descrip` varchar(255) default 'Bulk Entry',
              `article_title_required` int(11) default '1',
              `article_blurb_required` int(11) default '1',
              `article_body_required` int(11) default '1',
              `image_title_required` int(11) default '0',
              `image_blurb_required` int(11) default '0',
              `image_description_required` int(11) default '0',
              `member_address_required` int(1) default '0',
              `member_city_required` int(1) default '0',
              `member_province_required` int(1) default '0',
              `member_postal_code_required` int(1) default '0',
              `member_neighborhood_required` int(1) default '1',
              `member_home_phone_number_required` int(1) default '0',
              `member_work_phone_number_required` int(1) default '0',
              `member_mobile_phone_number_required` int(1) default '0',
              `member_email_address_required` int(1) default '0',
              `member_member_profile_required` int(1) default '1',
              `member_url_required` int(1) default '0',
              `noticeboard_title_required` int(1) default '1',
              `noticeboard_blurb_required` int(1) default '1',
              `noticeboard_description_required` int(1) default '1',
              `noticeboard_amount_required` int(1) default '0',
              `allow_member_admin_categories` int(1) default '1',
              `enable_guest_comments` int(1) default '1',
              `enable_member_edit_colours` int(1) default '0',
              `enable_log` int(1) default '1',
              `enable_error_log` int(1) default '1',
              `enable_email` int(1) default '1',
              `use_smtp` int(1) default '0',
              `email_smtp_host` varchar(255) default NULL,
              `email_smtp_host_backup` varchar(255) default NULL,
              `smtp_user_name` varchar(255) default NULL,
              `smtp_password` varchar(255) default NULL,
              `update_email` varchar(255) default NULL,
              `email_from_name` varchar(255) default NULL,
              `email_technical_errors` int(1) default '1',
              `email_validation_submissions` int(1) default '1',
              `enable_email_contact` int(1) default '1',
              `allow_duplicate_emails` int(1) default '1',
              `log_additions` int(1) default '1',
              `log_edits` int(1) default '1',
              `log_deletions` int(1) default '1',
              `log_trimmed_error` int(1) default '1',
              `log_noticeboard` int(1) default '1',
              `log_transactions` int(1) default '1',
              `log_articles` int(1) default '1',
              `log_events` int(1) default '1',
              `log_faq` int(1) default '1',
              `log_links` int(1) default '1',
              `log_comments` int(1) default '1',
              `log_post_dump` int(1) default '1',
              `homepage_html_articles` int(1) default '1',
              `homepage_html_noticeboard` int(1) default '0',
              `homepage_html_events` int(1) default '1',
              `homepage_html_faq` int(1) default '0',
              `homepage_html_links` int(1) default '0',
              `persistant_html_search` int(1) default '1',
              `persistant_html_login` int(1) default '1',
              `persistant_html_noticeboard` int(1) default '1',
              `persistant_html_articles` int(1) default '1',
               `persistant_html_events` int(1) default '1',
              `persistant_html_faq` int(1) default '0',
              `persistant_html_links` int(1) default '0',
              `show_search_link` int(1) default '0',
              `show_login_link` int(1) default '0',
              `show_help_link` int(1) default '0',
              `ftp_host` varchar(50) default NULL,
              `ftp_path` varchar(255) default NULL,
              `ftp_login` varchar(50) default NULL,
              `ftp_password` varchar(255) default NULL,
              PRIMARY KEY  (`ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;")) {
            $message .= '<li class="ok">'.T_('Table "config" has been created.').'</li>';
        } else {
            echo '<li class="error">'.$mysql->error.'</li>';
        }
        if (!$mysql->query("INSERT INTO config SET
                ID = '1',
                register_terms = '".mysql_escape_string('These are the terms of membership:')."',
                default_expiry_message = '".mysql_escape_string('This LETS suspends members automatically when their account expires. Please renew your account.')."',
                new_member_message = '".mysql_escape_string('Your account has been created. Please follow these instructions:.')."',
                default_site_language = '{$_SESSION['lang']}',
                currency_name = 'green dollars'
                ")) {
            echo '<li class="error">'.$mysql->error.'</li>';
        } else {
            $message .= '<li class="ok">'.T_('Some default config information has been added.').'</li>';
        }
    }    
}

if (mysql_num_rows(mysql_query("SHOW TABLES LIKE 'config'")) == 1 && $mysql->build_array('SELECT * FROM config WHERE 1')) {
    if (count($mysql->result)) {
        if (!$mysql->result[0]['site_name'] or
            !$mysql->result[0]['url'] or
            !$mysql->result[0]['path'] or
            !$mysql->result[0]['admin_email'] or
            !$mysql->result[0]['validation_email'] or
            !$mysql->result[0]['technical_email'] or
            !$mysql->result[0]['location']) {
            
            // We use this to keep user data and display them again if needed
            if ($submitted_website_settings) {
                unset($mysql->result[0]);
                $mysql->result[0] = $post_post;
            }
            
            echo '<div class="basic-grey">';
            echo '<div class="progress">';
            echo '<a class="stepdone"><span class="step step-inverse">1</span>'.T_('Creating Database').' <span class="glyphicon glyphicon-ok"></span></a>';
            echo '<a class="current"><span class="step step-inverse">2</span>'.T_('Website settings').'</a>';
            echo '<a><span class="step">3</span>'.T_('Admin account creation').'</a>';
            echo '<a><span class="step">4</span>'.T_('Permission setup').'</a>';
            echo '</div>';
            echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" >';
            echo T_('<h1>Setup Form');
            echo '        <span>'.T_('Please fill all the texts in the fields.').'</span>';
            echo '</h1>';
            if ($message) {
                echo '<ul>'.$message.'</ul>';
            }
            echo T_('The following fields are required:').'<br />';
            echo T_('<strong>Encryption Key:</strong><br /><em>example:').'</em> sedtb782394jhnev63dbnec4uj894tb60rfnd67y2<br />';
            echo T_('<em>Note:</em> You do not need to remember this code. By entering a large random number such as in the example all passwords will be stored in encrypted form.<br />');
            echo '<input type="text" name="site_key" value="'.$mysql->result[0]['site_key'].'" /><br /><br />';
            
            echo T_('<strong>Site Name:</strong><br /><em>example:</em> Toronto LETS<br />');
            echo T_('<em>Note:</em> This name will appear in the title (the top bar of your browser).<br />');
            echo '<input type="text" name="site_name" value="'.$mysql->result[0]['site_name'].'" /><br /><br />';
            
            echo '<strong>URL:</strong><br />'.T_('<em>example:</em> http://www.toronto-lets.ca/').'<br />';
            echo '<input type="text" name="url" value="'.$mysql->result[0]['url'].'" /><br /><br />';
            
            echo T_('<strong>File Path of index.php:</strong><br /><em>example:</em> ').$_SERVER['DOCUMENT_ROOT']    .'<br />';
            echo '<input type="text" name="path" value="'.$mysql->result[0]['path'].'" /><br /><br />';
            
            echo T_('<strong>Admin email:</strong><br /><em>example:</em> john.smith@toronto-lets.ca<br />');
            echo '<input type="text" name="admin_email" value="'.$mysql->result[0]['admin_email'].'" /><br /><br />';
            
            echo T_('<strong>Validation email:</strong><br /><em>example:</em> sue@yahoo.com<br />');
            echo '<input type="text" name="validation_email" value="'.$mysql->result[0]['validation_email'].'" /><br /><br />';
            
            echo T_('<strong>Technical email:</strong><br /><em>example:</em> techie@domain.ca<br />');
            echo '<input type="text" name="technical_email" value="'.$mysql->result[0]['technical_email'].'" /><br /><br />';
            
            echo T_('<strong>Location:</strong><br /><em>example:</em> Toronto, ON<br />');
            echo ' <input type="text" name="location" value="'.$mysql->result[0]['location'].'" /><br /><br />';
            
            echo T_('<strong>UTC Offset (Time Zone):</strong><br /><em>example:</em> Toronto would be -5, Paris 1, etc (full list on <a href="https://en.wikipedia.org/wiki/List_of_UTC_time_offsets" title="List of UTC time offsets" target="_blank">Wikipedia.org</a>)<br />');
            echo '<input type="text" name="hour_offset" value="'.$mysql->result[0]['hour_offset'].'" /><br /><br />';
            
            echo '<input type="submit" name="submit" value="' . T_('Enter Config').'" />';
            echo '</form></div></body></html>';
            exit();
        }
    }
}


// We check if the table accounts exist 
if (mysql_num_rows(mysql_query("SHOW TABLES LIKE 'accounts'")) == 0 && !$mysql->build_array('SELECT * FROM accounts WHERE 1')) {
    if ($mysql->query("CREATE TABLE IF NOT EXISTS `accounts` (
      `accountID` int(6) NOT NULL auto_increment,
      `first_name` varchar(32) NOT NULL default '',
      `last_name` varchar(32) NOT NULL default '',
      `password` varchar(13) NOT NULL default '',
      `address` varchar(32) NOT NULL default '',
      `city` varchar(32) NOT NULL default '',
      `province` varchar(50) NOT NULL default '',
      `postal_code` varchar(50) NOT NULL default '',
      `neighborhood` varchar(255) NOT NULL default '',
      `mailing_address` varchar(32) NOT NULL default '',
      `mailing_city` varchar(32) NOT NULL default '',
      `mailing_province` varchar(50) NOT NULL default '',
      `mailing_postal_code` varchar(50) NOT NULL default '',
      `home_phone_number` varchar(14) NOT NULL default '',
      `work_phone_number` varchar(14) NOT NULL default '',
      `mobile_phone_number` varchar(14) NOT NULL default '',
      `email_address` varchar(255) NOT NULL default '',
      `member_profile` longtext NOT NULL,
      `public_profile` longtext NOT NULL,
      `public_profile_enabled` int(1) NOT NULL default '0',
      `url` varchar(255) NOT NULL default '',
      `receive_email_outbid` int(1) default '0',
      `receive_email_newletter` int(1) NOT NULL default '0',
      `receive_email_events` int(1) NOT NULL default '0',
      `receive_email_url` int(1) NOT NULL default '0',
      `receive_email_faq` int(1) NOT NULL default '0',
      `receive_email_buy` int(1) NOT NULL default '0',
      `receive_email_sell` int(1) NOT NULL default '0',
      `receive_email_noticeboard` int(1) NOT NULL default '0',
      `receive_email_comment` int(1) NOT NULL default '0',
      `receive_newsletter` int(1) default '0',
      `receive_statement` int(1) default '0',
      `type` int(1) NOT NULL default '0',
      `validated` int(1) NOT NULL default '0',
      `suspended` int(1) default '0',
      `suspended_message` varchar(255) default '0',
      `deleted` int(1) default '0',
      `deleted_transactionID` int(11) NOT NULL default '0',
      `balance` decimal(7,2) NOT NULL default '0.00',
      `imageID` int(12) default '0',
      `created_day` int(2) default '0',
      `created_month` int(2) default '0',
      `created_year` int(4) default '0',
      `expiry_day` int(2) default '0',
      `expiry_month` int(2) default '0',
      `expiry_year` int(4) default '0',
      `ll_day` int(2) default '0',
      `ll_month` int(2) default '0',
      `ll_year` int(4) default '0',
      `ll_hour` int(6) default '0',
      PRIMARY KEY  (`accountID`),
      KEY `first_name` (`first_name`,`last_name`,`public_profile_enabled`,`receive_email_newletter`,`receive_email_events`,`receive_email_url`,`receive_email_faq`,`receive_email_buy`,`receive_email_sell`,`receive_email_noticeboard`,`type`,`validated`,`suspended`),
      KEY `neighborhood` (`neighborhood`),
      KEY `receive_newsletter` (`receive_newsletter`,`receive_statement`),
      KEY `created_day` (`created_day`,`created_month`,`created_year`,`expiry_day`,`expiry_month`,`expiry_year`,`ll_day`,`ll_month`,`ll_year`),
      KEY `imageID` (`imageID`),
      KEY `deleted` (`deleted`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
        $message .= T_('<li class="ok">Table "accounts" has been created.</li>');
    }
    if ($mysql->query("CREATE TABLE IF NOT EXISTS `transactions` (
      `transactionID` int(6) NOT NULL auto_increment,
      `buyerID` int(6) NOT NULL default '0',
      `sellerID` int(6) NOT NULL default '0',
      `amount` decimal(7,2) NOT NULL default '0.00',
      `description` longtext NOT NULL,
      `day` int(2) NOT NULL default '0',
      `month` int(2) NOT NULL default '0',
      `year` int(4) NOT NULL default '0',
      `hour` int(2) default '0',
      `minute` int(2) default '0',
      `second` int(2) default '0',
      `type` int(1) default '0',
      `noticeboardID` int(11) default '0',
      PRIMARY KEY  (`transactionID`),
      KEY `buyerID` (`buyerID`,`sellerID`),
      KEY `day` (`day`,`month`,`year`),
      KEY `minute` (`minute`,`second`),
      KEY `year` (`year`),
      KEY `month` (`month`),
      KEY `day_2` (`day`),
      KEY `hour` (`hour`),
      KEY `type` (`type`),
      KEY `noticeboardID` (`noticeboardID`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
        $message .= T_('<li class="ok">Table "transactions" has been created.</li>');
    }
}

$second_mysql = new mysql;
if ($second_mysql->build_array('SELECT * FROM accounts WHERE accountID = 1')) {
    if (!isset($second_mysql->result[0]['accountID'])) {
    
        // We use this to keep user data and display them again if needed
        if ($submitted_account) {
            unset($mysql->result[0]);
            $mysql->result[0] = $post_post;
        }else{
            $mysql->result[0]['first_name'] = '';
            $mysql->result[0]['last_name'] = '';
        }

        echo '<div class="basic-grey">';
        echo '<div class="progress">';
        echo '<a class="stepdone"><span class="step step-inverse">1</span>'.T_('Creating Database').' <span class="glyphicon glyphicon-ok"></span></a>';
        echo '<a class="stepdone"><span class="step step-inverse">2</span>'.T_('Website settings').' <span class="glyphicon glyphicon-ok"></span></a>';
        echo '<a class="current"><span class="step step-inverse">3</span>'.T_('Admin account creation').'</a>';
        echo '<a><span class="step">4</span>'.T_('Permission setup').'</a>';
        echo '</div>';
        if ($message) {
            echo '<ul>'.$message.'</ul>';
        }
        echo T_('<strong>Create Administrative Account</strong><br />');
        echo T_('This will create the administrator account which is essential for LETS-Software to work properly.') . '<br /><br />';
        echo T_('<strong>Important:</strong> Log-on to the site with username: "1" and the password you create here.') . '<br />';
        echo T_('Also please edit the account after finishing setup to add other important data such as address, telephone number, etc.') . '<br /><br />';
        echo T_('<strong>Note:</strong> Canadian may want to enable an option under "Website Settings" to force proper Provincial abbreviations and postal codes prior to editing the account.') . '<br />';
        
        echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
            
        echo T_('<strong>First Name:</strong><br /><em>example:</em> John<br />');
        echo '<input type="text" name="first_name" value="'.$mysql->result[0]['first_name'].'"/><br /><br />';
    
        echo T_('<strong>Last Name:</strong><br /><em>example:</em> Malkovich<br />');
        echo '<input type="text" name="last_name" value="'.$mysql->result[0]['last_name'].'"/><br /><br />';
        
        echo T_('<strong>Password:</strong><br />');
        echo '<input type="password" name="password" /><br /><br />';
        
        echo T_('<strong>Re-type Password:</strong><br />');
        echo '<input type="password" name="second_password" /><br /><br />';
            
        echo '<input type="submit" name="submit" value="'.T_('Create Account').'" />';
        echo '</form></div></body></html>';
        exit();
    }        
            
}

if (CURRENT_OS == 'UNIX') {
    $third_mysql = new mysql;
    if (mysql_num_rows(mysql_query("SHOW TABLES LIKE 'accounts'")) > 0 && $third_mysql->build_array('SELECT * FROM config WHERE ID = 1')) {
        if (count($third_mysql->result)) {
            $path = $third_mysql->result[0]['path'];
            $site_name = str_replace(' ','_',$third_mysql->result[0]['site_name']);
            $files_status_message   = '';
            
            
            if(!file_exists(".htaccess")) {
                fopen(".htaccess", "w")
                or $files_status_message .= '<li class="error">' . T_('Cannot create the file') . ' <strong>.htaccess</strong>.</li>';
            }
            chmod(".htaccess", 0664) or $files_status_message .= '<li class="error">' . T_('Unable to set <strong>.htaccess</strong> permissions') . '.</li>';
            
            if(!file_exists("images")) { mkdir("images", 0775) or $files_status_message .= '<li class="error">' . T_('Cannot create folder') . ' <strong>images</strong>.</li>'; }
            chmod("images", 0775) or $files_status_message .= '<li class="error">' . T_('Unable to set <strong>.htaccess</strong> permissions') . '.</li>';
            // Create an empty index.html file to prevent users to brows the images folder
            if(!file_exists("images/index.html")) {
                fopen("images/index.html", "w") or $files_status_message .= '<li class="error">' . T_('Cannot create the file') . ' <strong>images/index.html</strong>.</li>';
            }
            chmod("images/index.html", 0664) or $files_status_message .= '<li class="error">' . T_('Unable to set <strong>images/index.html</strong> permissions') . '.</li>';
            
            if(!file_exists("logs")) { mkdir("logs", 0770) or $files_status_message .= '<li class="error">' . T_('Cannot create folder') . ' <strong>logs</strong>.</li>'; }
            chmod("logs", 0770) or $files_status_message .= '<li class="error">' . T_('Unable to set <strong>logs</strong> permissions') . '.</li>';
            
            $directory_contents    = dir_contents($path);
            $htaccess_found         = false;
            $htaccess_perms        = false;
            $images_found           = false;
            $images_perms          = false;
            $logs_found                = false;
            $logs_perms               = false;
        
            foreach ($directory_contents as $line) {
                if ($line['name'] == '.htaccess') $htaccess_found = true;
                if ($line['name'] == '.htaccess' and $line['perms'] == '-rw-rw-r--') $htaccess_perms = true;
                if ($line['name'] == 'images') $images_found = true;
                if ($line['name'] == 'images' and $line['perms'] == 'drwxrwxr-x') $images_perms = true;
                if ($line['name'] == 'logs') $logs_found = true;
                if ($line['name'] == 'logs' and $line['perms'] == 'drwxrwx---') $logs_perms = true;
            }
            if (!$htaccess_found) {
                $files_status = false;
                $files_status_message .= '<li class="error"><strong>.htaccess</strong> '.T_('has not been found.').'</li>';
            } else {
                $files_status_message .= '<li class="ok"><strong>.htaccess</strong> '.T_('has been found.').'</li>';
            }
            if (!$htaccess_perms) {
                $files_status = false;
                $files_status_message .= '<li class="error"><strong>.htaccess</strong> '.T_('needs permissions: 664').'</li>';
            } else {
                $files_status_message .= '<li class="ok"><strong>.htaccess</strong> '.T_('has proper permissions.').'</li>';
            }
            if (!$images_found) {
                $files_status = false;
                $files_status_message .= '<li class="error">'.T_('Folder: <strong>images</strong> has not been found.').'</li>';
            } else {
                $files_status_message .= '<li class="ok">'.T_('Folder: <strong>images</strong> has been found.').'</li>';
            }
            if (!$images_perms) {
                $files_status = false;
                $files_status_message .= '<li class="error">'.T_('Folder: <strong>images</strong> needs permissions: 775').'</li>';
            } else {
                $files_status_message .= '<li class="ok">'.T_('Folder: <strong>images</strong> has proper permissions.').'</li>';
            }
            if (!$logs_found) {
                $files_status = false;
                $files_status_message .= '<li class="error">'.T_('Folder: <strong>logs</strong> has not been found.').'</li>';
            } else {
                $files_status_message .= '<li class="ok">'.T_('Folder: <strong>logs</strong> has been found.').'</li>';
            }
            if (!$logs_perms) {
                $files_status = false;
                $files_status_message .= '<li class="error">'.T_('Folder: <strong>logs</strong> needs permissions: 770').'</li>';
            } else {
                $files_status_message .= '<li class="ok">'.T_('Folder: <strong>logs</strong> has proper permissions.').'</li>';
            }
            
            $directory_contents = dir_contents($path.'/logs/');

            $log_found = false;
            $log_perms = false;
            $err_log_found = false;
            $err_log_perms = false;
            
            // Create the file log
            if(!file_exists('logs/' . $site_name . '.log')) {
                fopen('logs/' . $site_name.'.log', "w")
                or $files_status_message .= '<li class="error">' . T_('Cannot create the file') . ' <strong>logs/' . $site_name . '.log</strong></li>';
             }
            
            // Create the error log file
            if(!file_exists('logs/' . $site_name.'_Errors.log')) {
                fopen('logs/' . $site_name.'_Errors.log', "w")
                or $files_status_message .= '<li class="error">' . T_('Cannot create the file') . ' <strong>logs/' . $site_name . '_Errors.log</strong></li>';
             }
            chmod('logs/' . $site_name . '_Errors.log', 0660) or $files_status_message .= '<li class="error">'.T_('Unable to set <strong>logs/' . $site_name . '_Errors.log</strong> permissions').'.</li>';
            
            foreach ($directory_contents as $line) {
                // Check if log files exist and if they have the correct permission
                if ($line['name'] == $site_name.'.log') $log_found = true;
                if ($line['name'] == $site_name.'.log' and $line['perms'] == '-rw-rw----') $log_perms = true;
                if ($line['name'] == $site_name.'_Errors.log') $err_log_found = true;
                if ($line['name'] == $site_name.'_Errors.log' and $line['perms'] == '-rw-rw----') $err_log_perms = true;
            }
        
            if (!$log_found) {
                $files_status = false;
                $files_status_message .= '<li class="error"><strong>'.$site_name.'.log</strong> '.T_('has not been found.</li>');
            } else {
                $files_status_message .= '<li class="ok"><strong>'.$site_name.'.log</strong> '.T_('has been found.</li>');
                chmod('logs/' . $site_name . '.log', 0660) or $files_status_message .= '<li class="error">'.T_('Unable to set <strong>logs/' . $site_name . '.log</strong> permissions').'.</li>';
                if (!$log_perms) {
                    $files_status = false;
                    $files_status_message .= '<li class="error"><strong>'.$site_name.'.log</strong> '.T_('needs permissions: 660</li>');
                } else {
                    $files_status_message .= '<li class="ok"><strong>'.$site_name.'.log</strong> '.T_('has proper permissions.</li>');
                }
            }

            if (!$err_log_found) {
                $files_status = false;
                $files_status_message .= '<li class="error"><strong>'.$site_name.'_Errors.log</strong> '.T_('has not been found.</li>');
            } else {
                $files_status_message .= '<li class="ok"><strong>'.$site_name.'_Errors.log</strong> '.T_('has been found.</li>');
                if (!$err_log_perms) {
                    $files_status = false;
                    $files_status_message .= '<li class="error"><strong>'.$site_name.'_Errors.log</strong> '.T_('needs permissions: 660</li>');
                } else {
                    $files_status_message .= '<li class="ok"><strong>'.$site_name.'_Errors.log</strong> '.T_('has proper permissions.</li>');
                }
            }
        
        
            if (!$files_status) {
                echo '<div class="basic-grey">';
                echo '<div class="progress">';
                echo '<a class="stepdone"><span class="step step-inverse">1</span>'.T_('Creating Database').' <span class="glyphicon glyphicon-ok"></span></a>';
                echo '<a class="stepdone"><span class="step step-inverse">2</span>'.T_('Website settings').' <span class="glyphicon glyphicon-ok"></span></a>';
                echo '<a class="stepdone"><span class="step step-inverse">3</span>'.T_('Admin account creation').' <span class="glyphicon glyphicon-ok"></span></a>';
                echo '<a class="current"><span class="step">4</span>'.T_('Permission setup').'</a>';
                echo '</div>';
                if ($message) {
                    echo '<ul>' . $message . '</ul>';
                    echo '<u><b>Tips</b></u>: Please press <b>F5</b> to try again.';
                }
                echo T_('<strong>Checking Files and Folders....</strong><br />');
                echo T_('<strong>Attention:</strong><br />');
                echo T_('The following files and/or folders need their permissions changed:<br /><br />');
                echo '<ul>' . $files_status_message . '</ul><br />';
                echo T_('There are two ways to do this:<br />');
                echo T_('1. Login to cpanel, open file manager, select the files and/or folders and choose "change permissions".<br /><br />');
                echo T_('2. Enter FTP data below and this script will change the permissions. The username and password should be the same as the cpanel account.<br /><br />');
                echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
        
                echo T_("<strong>FTP Host:</strong><br /><em>example:</em> ftp.toronto-lets.ca<br />\n");
                echo " <input type=\"text\" name=\"ftp_host\" value=\"".$third_mysql->result[0]['ftp_host']."\" /><br /><br />\n";

                echo T_("<strong>FTP Root:</strong><br /><em>example:</em> public_html<br />\n");
                echo T_("<em>Note:</em> In a cpanel environment this should be either public_html or public_html/toronto_lets (for example).<br />\n");
                echo " <input type=\"text\" name=\"ftp_path\" value=\"".$third_mysql->result[0]['ftp_path']."\" /><br /><br />\n";

                echo T_("<strong>FTP Login:</strong><br /><br />\n");
                echo " <input type=\"text\" name=\"ftp_login\" value=\"".$third_mysql->result[0]['ftp_login']."\" /><br /><br />\n";

                echo T_("<strong>FTP Password:</strong><br /><br />\n");
                echo " <input type=\"password\" name=\"ftp_password\" value=\"".md5_decrypt($third_mysql->result[0]['ftp_password'],$third_mysql->result[0]['site_key'])."\" /><br /><br />\n";
            
                echo T_("<strong>Re-Type FTP Password:</strong><br /><br />\n");
                echo " <input type=\"password\" name=\"second_ftp_password\" value=\"".md5_decrypt($third_mysql->result[0]['ftp_password'],$third_mysql->result[0]['site_key'])."\" /><br /><br />\n";
                echo '<input type="submit" name="submit" value="'.T_('Set Permissions')."\" />\n";
                echo "</form>\n";
                echo '</div></body></html>';
                exit();
            } else {
                echo '<div class="basic-grey">';
                echo '<div class="progress">';
                echo '<a class="stepdone"><span class="step step-inverse">1</span>' . T_('Creating Database') . '</a>';
                echo '<a class="stepdone"><span class="step step-inverse">2</span>' . T_('Website settings') . '</a>';
                echo '<a class="stepdone"><span class="step step-inverse">3</span>' . T_('Admin account creation') . '</a>';
                echo '<a class="current"><span class="step">4</span>' . T_('Permission setup') . '</a>';
                echo '</div>';
                echo T_('<strong>Checking Files and Folders....</strong><br />');
                echo '<ul>' . $files_status_message . '</ul><br /><br />';
            }
        }
    }
}

$completed = true;
$last_mysql = new mysql;

if (!$last_mysql->build_array('SELECT * FROM config WHERE ID = 1')){
    exit($last_mysql->error);
}
//$path = $last_mysql->result[0]['path'];
$url = $last_mysql->result[0]['url'];
$site_name = $last_mysql->result[0]['site_name'];


if (!$last_mysql->query("--
--
-- Create structure for table `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
  `articleID` int(11) NOT NULL auto_increment,
  `accountID` int(11) NOT NULL default '0',
  `article_categoryID` int(11) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `blurb` mediumtext,
  `body` longtext NOT NULL,
  `day` int(2) NOT NULL default '0',
  `month` int(2) NOT NULL default '0',
  `year` int(4) NOT NULL default '0',
  `validated` int(1) default '0',
  `imageID` int(9) default '0',
  PRIMARY KEY  (`articleID`),
  KEY `accountID` (`accountID`,`article_categoryID`),
  KEY `title` (`title`),
  KEY `day` (`day`,`month`,`year`),
  KEY `validated` (`validated`),
  KEY `imageID` (`imageID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `bids`
--

CREATE TABLE IF NOT EXISTS `bids` (
  `bidID` int(12) NOT NULL auto_increment,
  `noticeboardID` int(12) NOT NULL default '0',
  `accountID` int(12) NOT NULL default '0',
  `amount` decimal(9,2) NOT NULL default '0.00',
  `day` int(2) NOT NULL default '0',
  `month` int(2) NOT NULL default '0',
  `year` int(4) NOT NULL default '0',
  `hour` int(2) NOT NULL default '0',
  `minute` int(4) NOT NULL default '0',
  PRIMARY KEY  (`bidID`),
  KEY `noticeboardID` (`noticeboardID`,`accountID`,`amount`,`day`,`month`,`year`,`hour`),
  KEY `minute` (`minute`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;")) {
    echo $last_mysql->error;
    $completed = false;
}


if (!$last_mysql->query("
--
-- Create structure for table `bad_logins`
--

CREATE TABLE IF NOT EXISTS `bad_logins` (
  `id` int(9) NOT NULL auto_increment,
  `ip` varchar(15) NOT NULL default '0',
  `year` int(4) NOT NULL default '0',
  `month` int(2) NOT NULL default '0',
  `day` int(2) NOT NULL default '0',
  `hour` int(2) NOT NULL default '0',
  `minute` int(2) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `ip` (`ip`,`year`,`month`,`day`,`hour`,`minute`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;")) {
    echo $last_mysql->error;
    $completed = false;
}








if (!$last_mysql->query("
--
-- Create structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `commentID` int(9) NOT NULL auto_increment,
  `accountID` int(9) default '0',
  `guest_name` varchar(255) default NULL,
  `noticeboardID` int(9) default '0',
  `articleID` int(9) default '0',
  `eventID` int(9) NOT NULL default '0',
  `title` varchar(255) default NULL,
  `comment` text,
  `created_day` int(2) NOT NULL default '0',
  `created_month` int(2) NOT NULL default '0',
  `created_year` int(4) NOT NULL default '0',
  `created_hour` int(2) default '0',
  `created_minute` int(2) default '0',
  `edited_by` int(9) default '0',
  `edited_day` int(2) default '0',
  `edited_month` int(2) default '0',
  `edited_year` int(4) default '0',
  PRIMARY KEY  (`commentID`),
  KEY `accountID` (`accountID`,`noticeboardID`,`articleID`,`title`,`created_day`,`created_month`,`created_year`,`edited_by`),
  KEY `edited_day` (`edited_day`,`edited_month`,`edited_year`),
  KEY `created_hour` (`created_hour`,`created_minute`),
  KEY `eventID` (`eventID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=38 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `eventID` int(11) NOT NULL auto_increment,
  `accountID` int(11) NOT NULL default '0',
  `event_categoryID` int(11) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `description` longtext NOT NULL,
  `location` varchar(255) NOT NULL default '0',
  `start_day` int(11) NOT NULL default '0',
  `start_month` int(11) NOT NULL default '0',
  `start_year` int(11) NOT NULL default '0',
  `start_hour` int(2) NOT NULL default '0',
  `start_minute` int(2) default '0',
  `end_day` int(4) NOT NULL default '0',
  `end_month` int(4) NOT NULL default '0',
  `end_year` int(4) NOT NULL default '0',
  `end_hour` int(2) NOT NULL default '0',
  `end_minute` int(2) default '0',
  `validated` int(1) default '0',
  PRIMARY KEY  (`eventID`),
  KEY `accountID` (`accountID`,`event_categoryID`,`title`,`start_day`,`start_month`,`start_year`),
  KEY `start_hour` (`start_hour`,`start_minute`,`end_day`,`end_month`,`end_year`),
  KEY `end_hour` (`end_hour`,`end_minute`),
  KEY `validated` (`validated`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `faq`
--

CREATE TABLE IF NOT EXISTS `faq` (
  `faqID` int(11) NOT NULL auto_increment,
  `category` int(1) default '0',
  `parent` int(6) NOT NULL default '0',
  `accountID` int(11) NOT NULL default '0',
  `question` mediumtext NOT NULL,
  `answer` longtext NOT NULL,
  `day` int(2) NOT NULL default '0',
  `month` int(2) NOT NULL default '0',
  `year` int(4) NOT NULL default '0',
  `validated` int(1) default '0',
  `position` int(6) NOT NULL default '1',
  PRIMARY KEY  (`faqID`),
  KEY `faq_categoryID` (`category`,`accountID`),
  KEY `parent` (`parent`),
  KEY `validated` (`validated`),
  KEY `position` (`position`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `images`
--

CREATE TABLE IF NOT EXISTS `images` (
  `imageID` int(12) NOT NULL auto_increment,
  `accountID` int(12) NOT NULL default '0',
  `noticeboardID` int(12) default '0',
  `articleID` int(12) default '0',
  `title` varchar(255) NOT NULL default '',
  `blurb` mediumtext NOT NULL,
  `description` longtext NOT NULL,
  `name` varchar(255) default '0',
  `thumbnail` int(1) default '0',
  `page` int(1) default '0',
  `w` int(8) NOT NULL default '0',
  `h` int(8) NOT NULL default '0',
  `t_w` int(4) NOT NULL default '0',
  `t_h` int(4) NOT NULL default '0',
  `p_w` int(4) NOT NULL default '0',
  `p_h` int(4) NOT NULL default '0',
  PRIMARY KEY  (`imageID`),
  KEY `accountID` (`accountID`,`title`),
  KEY `thumbnail` (`thumbnail`,`page`),
  KEY `noticeboardID` (`noticeboardID`),
  KEY `articleID` (`articleID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `links`
--

CREATE TABLE IF NOT EXISTS `links` (
  `linkID` int(11) NOT NULL auto_increment,
  `category` int(11) NOT NULL default '0',
  `accountID` int(11) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `description` longtext NOT NULL,
  `parent` int(1) default '0',
  `day` int(2) NOT NULL default '0',
  `month` int(2) NOT NULL default '0',
  `year` int(4) NOT NULL default '0',
  `position` int(9) NOT NULL default '0',
  `validated` int(1) default '0',
  PRIMARY KEY  (`linkID`),
  KEY `link_CategoryID` (`category`,`accountID`),
  KEY `parent` (`parent`,`position`,`validated`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `noticeboard`
--

CREATE TABLE IF NOT EXISTS `noticeboard` (
  `noticeboardID` int(12) NOT NULL auto_increment,
  `request` int(1) default '0',
  `accountID` int(12) NOT NULL default '0',
  `created_day` int(2) NOT NULL default '0',
  `created_month` int(2) NOT NULL default '0',
  `created_year` int(4) NOT NULL default '0',
  `expiry_day` int(2) default '0',
  `expiry_month` int(2) default '0',
  `expiry_year` int(4) default '0',
  `expiry_hour` int(2) default '0',
  `imageID` int(12) default '0',
  `title` varchar(255) default '0',
  `amount` decimal(9,2) default '0.00',
  `blurb` mediumtext,
  `description` longtext,
  `type` int(12) NOT NULL default '0',
  `item` int(1) default '0',
  `categoryID` int(12) NOT NULL default '0',
  `bought` int(1) default '0',
  `reserve` decimal(9,2) default '0.00',
  `expired` int(1) default '0',
  `quick_delete` int(1) default '0',
  PRIMARY KEY  (`noticeboardID`),
  KEY `accountID` (`accountID`,`created_day`,`created_month`,`created_year`,`expiry_day`,`expiry_month`,`expiry_year`,`imageID`,`title`,`type`),
  KEY `amount` (`amount`),
  KEY `item` (`item`,`categoryID`,`bought`,`reserve`),
  KEY `expiry_hour` (`expiry_hour`),
  KEY `expired` (`expired`),
  KEY `request` (`request`),
  KEY `quick_delete` (`quick_delete`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `transactionID` int(6) NOT NULL auto_increment,
  `buyerID` int(6) NOT NULL default '0',
  `sellerID` int(6) NOT NULL default '0',
  `amount` decimal(7,2) NOT NULL default '0.00',
  `description` longtext NOT NULL,
  `day` int(2) NOT NULL default '0',
  `month` int(2) NOT NULL default '0',
  `year` int(4) NOT NULL default '0',
  `hour` int(2) default '0',
  `minute` int(2) default '0',
  `second` int(2) default '0',
  `type` int(1) default '0',
  `noticeboardID` int(11) default '0',
  PRIMARY KEY  (`transactionID`),
  KEY `buyerID` (`buyerID`,`sellerID`),
  KEY `day` (`day`,`month`,`year`),
  KEY `minute` (`minute`,`second`),
  KEY `year` (`year`),
  KEY `month` (`month`),
  KEY `day_2` (`day`),
  KEY `hour` (`hour`),
  KEY `type` (`type`),
  KEY `noticeboardID` (`noticeboardID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `article_categories`
--

CREATE TABLE IF NOT EXISTS `article_categories` (
  `art_catID` int(5) NOT NULL auto_increment,
  `art_cat` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`art_catID`),
  KEY `art_cat` (`art_cat`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Insert data to table `article_categories`
--

INSERT INTO `article_categories` (`art_catID`, `art_cat`) VALUES
(1, 'LETS News'),
(2, 'Meetings'),
(3, 'From around the world'),
(4, 'Look At Me!');")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `style`
--

CREATE TABLE IF NOT EXISTS `style` (
  `styleID` int(9) NOT NULL auto_increment,
  `font_size` varchar(255) NOT NULL default '',
  `font` varchar(255) NOT NULL default '',
  `text_colour` varchar(255) NOT NULL default '',
  `background_colour` varchar(255) NOT NULL default '',
  `min_width` varchar(255) NOT NULL default '',
  `header_colour` varchar(255) NOT NULL default '',
  `tab_colour` varchar(255) NOT NULL default '',
  `text_background_colour` varchar(255) NOT NULL default '',
  `link_colour` varchar(255) NOT NULL default '',
  `link_decoration` varchar(255) NOT NULL default '',
  `visited_colour` varchar(255) NOT NULL default '',
  `visited_decoration` varchar(255) NOT NULL default '',
  `hover_colour` varchar(255) NOT NULL default '',
  `hover_decoration` varchar(255) NOT NULL default '',
  `header_border_size` varchar(255) NOT NULL default '',
  `header_border_colour` varchar(255) NOT NULL default '',
  `tab_border_size` varchar(255) NOT NULL default '',
  `tab_border_colour` varchar(255) NOT NULL default '',
  `header` longtext,
  `required_text_decoration` varchar(10) NOT NULL default '',
  `required_font_weight` varchar(10) NOT NULL default '',
  `required_color` varchar(10) NOT NULL default '',
  `required_display` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`styleID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Insert data to table `style`
--

INSERT INTO `style` (`styleID`, `font_size`, `font`, `text_colour`, `background_colour`, `min_width`, `header_colour`, `tab_colour`, `text_background_colour`, `link_colour`, `link_decoration`, `visited_colour`, `visited_decoration`, `hover_colour`, `hover_decoration`, `header_border_size`, `header_border_colour`, `tab_border_size`, `tab_border_colour`, `header`, `required_text_decoration`, `required_font_weight`, `required_color`, `required_display`) VALUES
(1, '12', 'Georgia, \"Times New Roman\", Times, serif', '#4f4f4f', '#F7F7F7', '550', '#e7e7e7', 'white', '#ffffff', '#006699', '#006699', '#006699', '#006699', '#ff6600', '#ff6600', '2', 'black', '2', 'black', '', 'none', 'bold', 'none', 'bold');")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `categoryID` int(12) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`categoryID`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Insert data for table `categories`
--

INSERT INTO `categories` (`categoryID`, `name`) VALUES
(1, 'Accomodations'),
(2, 'Arts/Crafts/Hobbies'),
(3, 'Audio-Visual/Electronics'),
(4, 'Automotive/Bicycles/Tranportation/Errands/Moving'),
(5, 'Books/Literature/Services'),
(6, 'Building/Maintenance/Tools'),
(7, 'Children''s Goods & Services'),
(8, 'Clothing/Accessories/Alterations'),
(9, 'Computer (Accessories/Support/Internet/Office)'),
(10, 'Cooking/Baking/Food/Instruction'),
(11, 'Entertainment/Music'),
(12, 'Furniture/Appliances/Household Items/Services'),
(13, 'Garden/Yard/Plants'),
(14, 'Health/Personal/Development'),
(15, 'Languages, Academic Services, Etc.'),
(16, 'Pets /Animal Care'),
(17, 'Recreation, Sports, Equipment, Etc.'),
(18, 'Miscellaneous');")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `event_categories`
--

CREATE TABLE IF NOT EXISTS `event_categories` (
  `event_categoryID` int(9) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`event_categoryID`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Insert data for table `event_categories`
--

INSERT INTO `event_categories` (`event_categoryID`, `name`) VALUES
(1, 'Meetings'),
(2, 'Movie Night'),
(3, 'Game Night'),
(4, 'Potluck'),
(5, 'Public Events'),
(6, 'Market');")) {
    echo $last_mysql->error;
    $completed = false;
}

if (!$last_mysql->query("
--
-- Create structure for table `sections`
--

CREATE TABLE IF NOT EXISTS `sections` (
  `sectionID` int(6) NOT NULL auto_increment,
  `page_type` int(6) NOT NULL default '0',
  `page_id` int(6) default '0',
  `url` varchar(25) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `plural` varchar(255) NOT NULL default '',
  `singular` varchar(255) NOT NULL default '',
  `type` int(3) NOT NULL default '0',
  `hidden` int(1) default '0',
  `position` int(9) NOT NULL default '0',
  `body` longtext,
  PRIMARY KEY  (`sectionID`),
  KEY `position` (`page_id`,`url`,`name`,`type`),
  KEY `parent` (`page_type`),
  KEY `hidden` (`hidden`),
  KEY `position_2` (`position`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=41 ;")) {
    echo $last_mysql->error;
    $completed = false;
}
$site_name_text = str_replace('_',' ',$site_name);

if (!$last_mysql->query("
--
-- Insert data to table `sections`
--

INSERT INTO `sections` (`sectionID`, `page_type`, `page_id`, `url`, `name`, `plural`, `singular`, `type`, `hidden`, `position`, `body`) VALUES
(1, 2, 0, 'listings', 'Goods & Services', 'Listings', 'Listing', 1, 0, 1, 'Welcome to the heart of ".$site_name_text."! Members buy and sell from each other using \"green dollar\" currency.\r\n\r\nClick on SEARCH to view all listings OR enter keywords or categories.\r\n'),
(2, 3, 0, 'articles', 'News & Ideas', 'articles', 'article', 1, 0, 31, ''),
(3, 4, 0, 'events', 'Events', 'events', 'Event', 1, 0, 3, 'Join us at our next event.'),
(4, 5, 0, 'faq', 'How it Works', 'faqs', 'faq', 1, 0, 33, ''),
(5, 6, 0, 'links', 'Links', 'links', 'Link', 1, 0, 36, ''),
(6, 7, 0, 'member_list', 'Member List', 'members', 'member', 1, 0, 4, ''),
(7, 8, 0, 'search', '', '', '', 1, 1, 7, ''),
(8, 9, 0, 'print', '', '', '', 1, 1, 8, ''),
(9, 1, 0, 'members', 'Member', 'members', 'member', 2, 0, 9, ''),
(10, 1, 1, 'register', 'Register', '', '', 4, 1, 10, ''),
(11, 1, 2, 'edit_account', 'Edit Account', '', '', 2, 0, 18, ''),
(12, 1, 3, 'trades', 'Trade History', '', '', 2, 0, 13, ''),
(13, 1, 4, 'trade', 'Record a Trade', '', '', 2, 0, 12, ''),
(14, 1, 5, 'listings', 'Your Listings', '', '', 2, 0, 11, ''),
(15, 1, 6, 'colours', 'Edit Colours', '', '', 2, 0, 19, ''),
(16, 1, 7, 'articles', 'Add News & Ideas', '', '', 2, 0, 15, ''),
(17, 1, 8, 'events', 'Add Events', '', '', 2, 0, 14, ''),
(18, 1, 9, 'faq', 'Add FAQs', '', '', 2, 0, 16, ''),
(19, 1, 10, 'links', 'Add Links', '', '', 2, 0, 17, ''),
(20, 1, 11, 'edit_comment', 'Edit Comment', '', '', 2, 1, 20, ''),
(21, 1, 0, 'admin', 'Admin', 'admins', 'admin', 3, 0, 21, ''),
(22, 1, 100, 'bulk_trading', 'Bulk Trading', '', '', 3, 0, 22, ''),
(23, 1, 101, 'lets_tools', 'LETS Tools', '', '', 3, 0, 23, ''),
(24, 1, 102, 'form_settings', 'Form Settings', '', '', 3, 0, 24, ''),
(25, 1, 103, 'lets_settings', 'LETS Settings', '', '', 3, 0, 25, ''),
(26, 1, 104, 'site_structure', 'Website Structure', '', '', 3, 0, 26, ''),
(27, 1, 105, 'validate_articles', 'Review News & Ideas', '', '', 3, 0, 27, ''),
(28, 1, 106, 'site_settings', 'Website Settings', '', '', 3, 0, 28, ''),
(29, 1, 107, 'validate_events', 'Validate Events', '', '', 3, 0, 35, ''),
(30, 1, 108, 'log', 'Log', '', '', 3, 1, 30, ''),
(31, 1, 109, 'send_email', 'Send Email', '', '', 3, 0, 33, 'Send an bulk email to all members selected below'),
(32, 1, 12, 'edit_categories', 'Edit Categories', '', '', 2, 0, 32, ''),
(33, 11, 0, 'contact', 'Contact Us', 'contacts', 'contact', 1, 0, 38, 'Please contact the ".$site_name_text." by filling out the following form.'),
(34, 12, 0, 'search', 'Search', 'Searches', 'Search', 1, 0, 41, '<strong>Note:</strong> This page only displays the top search results for the various sections. Explore each section individually for more search options.'),
(35, 13, 0, 'lost_password', 'Lost Password?', 'Lost Passwords', 'Lost Password', 1, 0, 39, 'Please enter your email address and your password will be mailed to you.'),
(36, 14, 0, 'login', 'Login', 'logins', 'login', 1, 0, 42, 'Please Log-In to the ".$site_name_text."'),
(37, 15, 0, 'help', 'Help', 'Helps', 'Help', 1, 0, 43, 'Please <a href=\"".$url."\">contact</a> us if your problem isn''t answered here.'),
(39, 10, 0, 'business_directory', 'Business Directory', '', '', 5, 0, 2, 'This page is a simple additional page entered through the \"website structure\" link'),
(40, 10, 0, 'more_info', 'More Info', '', '', 5, 1, 44, ' What is a LETS?\r\n\r\nLETS stands for Local Exchange Trading System. Members trade directly or use the local currency, called \"Green dollars\" (GRN $) in exchange for goods and services.\r\n\r\n\r\nWho Are LETS Members?\r\n\r\nThere are hundreds of LETS communities all around the world, circulating their own local currencies. Members are people like you, your family, friends, neighbours, your massage therapist, gardener, tutor, chef, business owners...\r\n\r\nWhy Join LETS?\r\n\r\nLETS provides an alternative economy, so everyone can value the goods and services you have to offer. Likewise you are able to value and pay for the goods and services you receive with something other than Canadian dollars. There always seems to be a shortage of Canadian dollars, but there are always enough Green dollars and they always stay in the community.\r\n\r\n\r\n\r\nEarning and spending Green dollars is also a way of building community relationships and strengthening community resources. LETS works without interest or inflation. LETS eliminates the value of hoarding, stimulates active exchange, and provides equal access to resources for everyone.\r\n\r\n\r\nHow Does LETS Work?\r\n\r\n\r\nMembers open an account by paying a registration fee.\r\n\r\nWhen members sell goods or services they earn Green dollars, and their accounts are credited. When members buy goods or services they spend Green dollars, and their accounts are debited.');")) {
    echo $last_mysql->error;
    $completed = false;
}

if ($completed) {
    $message .= T_('<li class="ok"><strong>Database Finalized!!</strong></li>');
    if ($links->initialize($nav_links_indent)) {
        $message .= T_('<li class="ok"><strong>Site Structure Initialized</strong></li>');
        if ($links->rebuild_htaccess()) {
            $message .= T_('<li class="ok"><strong>.htaccess was updated!!!</strong></li>');
        } else {
            $message .= T_('<li class="error"><strong>.htaccess could NOT be updated</strong>, make sure the file permission is set to (775)').'</li>';
            $completed = false;
        }
    } else {
        $message .= T_('<li class="error"><strong>Site Structure could NOT be Initialized</strong>').'</li>';
        $completed = false;
    }
}
    
if ($completed) {
    $message .= '<li class="ok"><strong>' . T_('Installation Complete!!!') . '</strong></li>';
    echo '<ul>' . $message . '</ul>' . T_('Clicking <a href="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '">here</a> should direct you to the home page.') . '</div>';
}else{
    $message .= '<li class="error"><strong>' . T_('Installation Failed!!!') . '</strong></li>';
    echo '<ul>' . $message . '</ul>' . T_('Check the permission of your files and folder. The user running your web server should have read and write access in files and folders in your lets-software folder.') . '</div>';
}
echo '</div></body></html>';
?>
