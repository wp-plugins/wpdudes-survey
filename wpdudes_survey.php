<?php
/**
* Plugin Name: WPDudes Survey
* Plugin URI: http://www.wpdudes.com/plugins/wpdudes-survey
* Description: A voting tool for the members or employees of a group, club, community or organisation (eg. HR) to provide internal feedback anonymously.
* Version: 1.0.4
* Author: WPDudes
* Author URI: http://www.wpdudes.com/
**/

function plugin_wpdudes_survey_activation() { //This is the function for main activation process
	require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
	global $wpdb;
	$db_table_name = $wpdb->prefix . 'wpdudes_survey';
	if( $wpdb->get_var( "SHOW TABLES LIKE '$db_table_name'" ) != $db_table_name ) {
		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";
 
		$sql = "CREATE TABLE " . $db_table_name . " (
			`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`survey_title` varchar(250) NOT NULL DEFAULT '',
			`survey_date` int,
			`description` text NOT NULL,
			`percentdisplay` int default 50,
			PRIMARY KEY (`id`)
		) $charset_collate;";
		dbDelta( $sql );
	}
	$my_page = array(
		'post_title' => 'WPDudes Survey',
		'post_content' => '[wpdudes]',
		'post_status' => 'publish',
		'post_type' => 'page',
		'post_author' => 1,
		'comment_status' => 'closed'
	);
	$post_id = wp_insert_post($my_page);
	$sql = "INSERT INTO ".$wpdb->prefix."options(option_name,option_value) values('wpdudes_page_id','".$post_id."')";
	dbDelta( $sql );
}

//Action hook which fires the function above 
register_activation_hook(__FILE__, 'plugin_wpdudes_survey_activation');

//Following code will create admin menu
function wpdudes_create_survey() {
    add_menu_page( 'WPDudes Survey', 'WPDudes Survey', 'manage_options', 'wpdudes-survey/view_wpdudes_survey.php', '', '', 6 );
    add_submenu_page('wpdudes-survey/view_wpdudes_survey.php','Create Survey','Create Survey','manage_options', __FILE__.'wpdudes_create_survey_page','wpdudes_create_survey_page');
}

add_action('admin_menu', 'wpdudes_create_survey');

//This code is for implementing jS
function wpdudes_survey_custom_js() {
wp_enqueue_script( 'admin_admin_box', plugins_url( 'js/addbox.js', __FILE__ ) , false, '1.0', false); 
?>

<?php
}
add_action('admin_head', 'wpdudes_survey_custom_js');
if(isset($_POST['wpdudes_survey_title'])) {
    
    //This stores the value in the variables
    $wpdudes_survey_title = addslashes($_POST['wpdudes_survey_title']);
    $wpdudes_survey_percentage_result = filter_input(INPUT_POST, 'wpdudes_survey_percentage_result', FILTER_SANITIZE_STRING);
    if($wpdudes_survey_percentage_result=="") {
    	$wpdudes_survey_percentage_result = 50;
    }
	if(($wpdudes_survey_percentage_result > 100) OR ($wpdudes_survey_percentage_result < 0)) {
    	$error = "Percentage cannot be more than 100 or less than 0";
    }
    $wpdudes_survey_desc = addslashes($_POST['wpdudes_survey_desc']);
    $wpdudes_survey_names = filter_input(INPUT_POST, 'wpdudes_survey_names', FILTER_SANITIZE_STRING);
    $wpdudes_survey_emails = filter_input(INPUT_POST, 'wpdudes_survey_emails', FILTER_SANITIZE_STRING);
    $pointz = array(" ","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
    
    for($i=1;$i<100;$i++) {
        $j = "point_".$i;
        $k = "desc_".$i;
        if($_POST[$k]!=NULL) {
            $point[$i] = $pointz[$i];
            $desc[$i] = addslashes($_POST[$k]);
            $pointcount = $i;
        } else {
            break 1;
        }
    }
    if(count($desc)<2) {
    	$error="Please put atleast 2 Rating Type";
    }
    if($wpdudes_survey_emails=="") {
    	$error="Please put atleast one email address";
    }
    if($wpdudes_survey_names=="") {
    	$error="Please put atleast one member name";
    }
    if ($wpdudes_survey_title=="") {
        $error="Please enter the Title";
    }elseif(strlen($wpdudes_survey_title)>250) {
        $error="Title length cannot be more than 250 Characters";
    }
    if(!ctype_digit($wpdudes_survey_percentage_result)){
        $error='Percentage field should only contain digits';
    } 
    $wpdudes_survey_names_array = explode("\n", $wpdudes_survey_names);
    $wpdudes_survey_names_array_count = count($wpdudes_survey_names_array);
    $wpdudes_survey_emails_array = explode("\n", $wpdudes_survey_emails);
    $wpdudes_survey_emails_array_count = count($wpdudes_survey_emails_array);
    for($start=0;$start<$wpdudes_survey_emails_array_count-1;$start++) {
    	for($mid=$start+1;$mid<$wpdudes_survey_emails_array_count;$mid++) {
    		if(trim($wpdudes_survey_emails_array[$start])==trim($wpdudes_survey_emails_array[$mid])) {
    			if(trim($wpdudes_survey_emails_array[$start])=="") {
    				$error="Remove blank lines from email id(s)";
    			} else {
    				$error="Remove duplicated email id(s)";
    			}
    		}	
    	}
    }
    for($start=0;$start<$wpdudes_survey_names_array_count-1;$start++) {
    	for($mid=$start+1;$mid<$wpdudes_survey_names_array_count;$mid++) {
    		if(trim($wpdudes_survey_names_array[$start])==trim($wpdudes_survey_names_array[$mid])) {
    			if(trim($wpdudes_survey_names_array[$start])=="") {
    				$error="Remove blank lines from name(s)";
    			} else {
    				$error="Remove duplicated name(s)";
    			}
    		}	
    	}
    }
    
    $wpdudes_survey_mem_sql = "";
    for($i=0;$i<$wpdudes_survey_names_array_count;$i++) {
        $wpdudes_survey_mem_sql .= $wpdudes_survey_names_array[$i]." int,";
    }
    if($error=="") {
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
	global $wpdb;
        $db_table_name = $wpdb->prefix . 'wpdudes_survey';
        $db_name = $wpdb->dbname;
        $sqlinsert = "INSERT INTO ".$db_table_name."(survey_title, survey_date, description, percentdisplay) values('".$wpdudes_survey_title."','".time()."','".$wpdudes_survey_desc."','".$wpdudes_survey_percentage_result."')";
        dbDelta( $sqlinsert );
        
	if ( ! empty( $wpdb->charset ) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty( $wpdb->collate ) )
            $charset_collate .= " COLLATE $wpdb->collate";
        $nextsurveyid = $wpdb->get_var("SELECT `AUTO_INCREMENT`
                            FROM  INFORMATION_SCHEMA.TABLES
                            WHERE TABLE_SCHEMA = '".$db_name."'
                            AND   TABLE_NAME   = '".$db_table_name."';");
        $nextsurveyid = intval($nextsurveyid);
        $nextsurveyid = $nextsurveyid - 1;
        $memtable_name = $wpdb->prefix ."wpdudes_survey_member_".$nextsurveyid;
        $emailtable_name = $wpdb->prefix."wpdudes_survey_email_".$nextsurveyid;
        $memtable_point_cols = "";
        $commentsectionsql = "";
        for($i=1;$i<=$pointcount;$i++) {
            $memtable_point_cols .= "`".$point[$i]."` int NOT NULL COMMENT '".$desc[$i]."',";
            
        }
        
        $sql = "CREATE TABLE " . $memtable_name . " (
			`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`survey_id` int NOT NULL,
			`member_name` VARCHAR(100) NOT NULL,
			".$memtable_point_cols."
                        `comments` TEXT,
			PRIMARY KEY (`id`)
		) $charset_collate;";
	dbDelta( $sql );
	
        $sql = "CREATE TABLE " . $emailtable_name . " (
			`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`survey_id` int NOT NULL,
			`randomkey` VARCHAR(100) NOT NULL,
			PRIMARY KEY (`id`)
		) $charset_collate;";
	dbDelta( $sql );
	
        for($i=0;$i<$wpdudes_survey_names_array_count;$i++) {
            if(strlen(trim(preg_replace('/\xc2\xa0/',' ',$wpdudes_survey_names_array[$i]))) != 0) {
                $sqlinsert = "INSERT INTO ".$memtable_name."(survey_id,member_name) values('".$nextsurveyid."','".$wpdudes_survey_names_array[$i]."')";
                dbDelta($sqlinsert); 
            }
        }
        function randomPassword() {
            $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
            $pass = array(); //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < 30; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
            return implode($pass); //turn the array into a string
        }
        for($i=0;$i<$wpdudes_survey_emails_array_count;$i++) {
            if(strlen(trim(preg_replace('/\xc2\xa0/',' ',$wpdudes_survey_emails_array[$i]))) != 0) {
                $randomkey = randomPassword();
                $sqlinsert = "INSERT INTO ".$emailtable_name."(survey_id,randomkey) values('".$nextsurveyid."','".$randomkey."')";
                dbDelta($sqlinsert);  
                $sql = "SELECT option_value from ".$wpdb->prefix."options WHERE option_name = 'wpdudes_page_id'";
		$optionwpsql = $wpdb->get_row( $sql );
		$wpdudes_page = $optionwpsql->option_value;        
                $surveylink = get_site_url()."/?p=".$wpdudes_page."&action=vote&id=".$nextsurveyid."&key=".$randomkey;
                // Sending Mail
                $to  = $wpdudes_survey_emails_array[$i];
                // subject
                $subject = "Survey Request from: ".get_bloginfo('name');
                $message = '
                <html>
                <head>

                </head>
                <body>

                  Hi<br>
                  <br>
                  '.get_bloginfo('name').' has just created a survey and you have been requested to submit your feedback.
                  <br>
                  <br>
                  <b><a href="'.$surveylink.'">Click here to submit your feedback or view results in future</a> <br>
                  <br>
                  <br>
		  Powered by WPDudes. <a href="http://wpdudes.com">http://wpdudes.com</a><br>
                </body>
                </html>
                ';

                // To send HTML mail, the Content-type header must be set
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

                // Additional headers
                $email_id_send = get_option('admin_email');
                $site_title_send = get_bloginfo('name');
                $headers .= 'From: '.$site_title_send.' <'.$email_id_send.'>' . "\r\n";

                // Mail it
                mail($to, $subject, $message, $headers);
            }
        }
        
        echo "<div class='success'>Survey successfully created. <a href='".admin_url()."admin.php?page=wpdudes_survey/view_wpdudes_survey.php'>Click here</a> to view it</div>";
        $_POST = array();
        return;
    } else {
		echo "<div class='error'>". $error ."</div>";
	}
}
?>
<?php
//This code will create the create survey page
function wpdudes_create_survey_page() {
		wp_enqueue_style( 'admin_table_result', plugins_url( 'css/style.css', __FILE__ ), false, '1.0', 'all' ); 
		wp_enqueue_style( 'admin_table_result', plugins_url( 'css/jquery-ui.css', __FILE__ ), false, '1.0', 'all' ); 
?>

<h2>Create Survey</h2>

<form id="survey_form" method="post">
    <input type="text" placeholder="Title" value="<?php echo $_POST['wpdudes_survey_title']; ?>" name="wpdudes_survey_title"/><br>
    <input type="text" placeholder="Set the percentage of surveys submitted before publishing results. Enter between 1 and 100 only. Default is 50." name="wpdudes_survey_percentage_result" value="<?php echo $_POST['wpdudes_survey_percentage_result']; ?>"/><br>
    <textarea placeholder="Provide a description or a short write-up for the purpose of this survey. People who receive this survey should get an idea of what this survey is for by reading this information." name="wpdudes_survey_desc"><?php echo $_POST['wpdudes_survey_desc']; ?></textarea><br>
    <textarea placeholder="Add the names of the people for which this rating is being provided (Enter one name per line) " name="wpdudes_survey_names"><?php echo $_POST['wpdudes_survey_names']; ?></textarea><br>
    <div id="ratingnames">
        <input placeholder='RATING TYPE. Enter a description for the type of rating. Example: Excellent performer or Poor Communications Skills etc.' type='text' name='desc_1' value="<?php echo $_POST['desc_1']; ?>">
        <input placeholder='RATING TYPE. Enter a description for the type of rating. Example: Excellent performer or Poor Communications Skills etc.' type='text' name='desc_2' value="<?php echo $_POST['desc_2']; ?>">
    </div>
    <input class="wpdudes_survey_button blue" id="add_button" type="button" value="Click here to add additional RATING TYPES"><br><br>
    <textarea placeholder="Add the list of email addresses of the people who need to submit this survey (Enter one email address per line) " name="wpdudes_survey_emails"><?php echo $_POST['wpdudes_survey_emails']; ?></textarea><br>
    <input style="height: 50px;" type="submit" class="wpdudes_survey_button green" value="CREATE SURVEY">
</form>
<?php
	
}

function voting_function() {
	require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
	global $wpdb;
	$action = $_GET['action'];
	$sql = "SELECT option_value from ".$wpdb->prefix."options WHERE option_name = 'wpdudes_page_id'";
	$optionwpsql = $wpdb->get_row( $sql );
	$wpdudes_page = $optionwpsql->option_value;
	if($action=="vote") {
		if(!isset($_GET['id'])) {
		    echo "Invalid Survey ID";
		    return;
		}
		if(!isset($_GET['key'])) {
		    echo "Invalid Key";
		    return;
		}
		
		$survey_id = $_GET['id'];
		$key = $_GET['key'];
		$table_prefix = $wpdb->prefix;
		$key_table_name = $wpdb->prefix."wpdudes_survey_email_".$survey_id;
		$sqlkey = "SELECT randomkey from ".$key_table_name." WHERE randomkey = '".$key."'";
		$verifykeysql = $wpdb->get_row( $sqlkey );
		$verifykey = $verifykeysql->randomkey;
		if($verifykey!=$key) {
			?>
			<script type="text/javascript">
			<!--
			   window.location="<?php echo get_site_url() ?>/?p=<?php echo $wpdudes_page ?>&action=results&id=<?php echo $survey_id; ?>";
			//-->
			</script>
			<?php
		} 
		$table_name = $table_prefix."wpdudes_survey_member_".$survey_id;
		$db_name = $wpdb->dbname;
		$sqlselect = "SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$db_name."' AND TABLE_NAME = '".$table_name."'";
		$columnsql = $wpdb->get_results($sqlselect);
		$columnsqlcount = 0;
		$columnsqlcountstore = 0;
		foreach( $columnsql as $columnresults ) {
			if($columnsqlcount>2) {
	        		$columnsqlcountstore++;
				$column_names[$columnsqlcountstore] = $columnresults->COLUMN_NAME;
	        		$column_comments[$columnsqlcountstore] = $columnresults->COLUMN_COMMENT;
			}
			$columnsqlcount++;
	    	}
	        $columnsqlcountstore--;
	    	$survey_table_name = $table_prefix."wpdudes_survey";
		$sqlselect = "SELECT survey_title,description FROM ".$survey_table_name." WHERE id = '".$survey_id."'";
		$surveysql = $wpdb->get_row($sqlselect);
		$survey_title = $surveysql->survey_title;
		$survey_description = $surveysql->description;
		$sqlselect = "SELECT member_name FROM ".$table_name;
		$membersql = $wpdb->get_results($sqlselect);
		$membersqlcount = 0;
		foreach( $membersql as $memberresults ) {
			$membersqlcount++;
	        	$member_names[$membersqlcount] = $memberresults->member_name;
		        $member_names_nospace[$membersqlcount] = str_replace(' ', '', $member_names[$membersqlcount]);
		        $member_names_nospace[$membersqlcount] = trim(preg_replace('/\s+/', ' ', $member_names_nospace[$membersqlcount]));
	    	}
	    	if(isset($_POST['submit_vote'])) {
		    for($l=1;$l<=$membersqlcount;$l++) {
		        $point = "point_".$member_names_nospace[$l];
		        if($_POST[$point]==NULL){
		            $is_null = TRUE;
		        }
		    }
		    if($is_null==TRUE) {
		        $error="ERROR: Please Vote for ALL Members";
		    } else {
		        $error = "";
		    }
		    if($error=="") {
		        for($l=1;$l<=$membersqlcount;$l++) {
		            $point = "point_".$member_names_nospace[$l];
		            $comment_sql = "SELECT comments FROM ".$table_name." WHERE member_name = '".$member_names[$l]."'";
		            $commentsql = $wpdb->get_row($comment_sql);
			    $comment = $commentsql->comments;
		            $new_comment_form_name = "comment_".$member_names_nospace[$l];
		            $new_comment = addslashes($_POST[$new_comment_form_name]); 
		            if($new_comment!="") {
		            	$new_comment = $new_comment."<br><br>";
		            }
		            $new_comment = $comment.$new_comment; 
		            $pointdata = filter_input(INPUT_POST, $point, FILTER_SANITIZE_STRING);
		            $sql = "UPDATE ".$table_name." SET comments = '".$new_comment."', ".$pointdata." = ".$pointdata."+1 WHERE member_name='".$member_names[$l]."'";
		            dbDelta($sql); 
		        }
		        $form_key = filter_input(INPUT_POST, 'form_key', FILTER_SANITIZE_STRING);
		        $sql_update_key = "UPDATE ".$key_table_name." SET randomkey=1 WHERE randomkey='".$form_key."'";
		        dbDelta($sql_update_key); 
		        echo "Thank Your for submitting your Feedback";
		        return;
		    }
		}
		wp_enqueue_style( 'table_vote', plugins_url( 'css/table.css', __FILE__ ), false, '1.0', 'all' ); 
		?>
		
		<h2><?php echo $survey_title; ?></h2>
		<p style="margin-top: 20px;"><?php echo $survey_description; ?></p>
		<?php if($error!="") { ?><div class="error"><?php echo $error; ?></div><?php } ?>
		<form method="post">
		<table style="width: 100%" cellspacing="0" >
		    <tr>
		        <th>Member</th>
		        <?php for($k=1;$k<=$columnsqlcountstore;$k++) { ?>
		        <th><?php echo $column_comments[$k]; ?></th>
		        <?php } ?>
		        <th>Additional feedback, inputs or suggestions</th>
		    </tr>
		    <?php for($l=1;$l<=$membersqlcount;$l++) { 
		    $point = "point_".$member_names_nospace[$l];
		    $pointdata = filter_input(INPUT_POST, $point, FILTER_SANITIZE_STRING);
		    ?>
		    <tr>
		        <td><?php echo $member_names[$l]; ?></td>
		        <?php for($k=1;$k<=$columnsqlcountstore;$k++) { ?>
		        <td><input type="radio" name="point_<?php echo $member_names_nospace[$l]; ?>" value="<?php echo $column_names[$k]; ?>" <?php if($pointdata==$column_names[$k]) { echo "checked"; } ?>/></td>
		        <?php } 
		        $new_comment_form_name = "comment_".$member_names_nospace[$l];
		        $new_comment = $_POST[$new_comment_form_name]; 
		        ?>
		        <td><input class="comment_box" type="text" name="comment_<?php echo $member_names_nospace[$l]; ?>" value="<?php echo $new_comment; ?>"></td>
		    </tr>
		    <?php } ?>
		</table>
		    <input type="hidden" name="form_key" value="<?php echo $verifykey; ?>">
		    <input style="height: 50px;" name="submit_vote" type="submit" class="submit_vote blue" value="SUBMIT YOUR FEEDBACK">
		</form>
		<?php
	} 
	if($action=="results") {
		$siteurl = site_url();
		$siteurllen = strlen($siteurl)+1;
		$contenturl = content_url();
		$contentdir = substr($contenturl, $siteurllen);
		$upload_dir_url = wp_upload_dir();
		$uploaddir = substr($upload_dir_url['baseurl'], $siteurllen);
		include("pChart/pData.class");  
		include("pChart/pChart.class"); 
		function Delete($path)
		{
		    if (is_dir($path) === true)
		    {
		        $files = array_diff(scandir($path), array('.', '..'));
		
		        foreach ($files as $file)
		        {
		            Delete(realpath($path) . '/' . $file);
		        }
		
		        return rmdir($path);
		    }
		
		    else if (is_file($path) === true)
		    {
		        return unlink($path);
		    }
		
		    return false;
		}
		$delete = Delete($uploaddir.'/charts');
		$mask=umask(0);
		mkdir($uploaddir.'/charts',0777);
		umask($mask);
		if(!isset($_GET['id'])) {
		    echo "Something Wrong";
		    return;
		}
		$survey_id = $_GET['id'];
		$table_prefix = $wpdb->prefix;
		$email_table = $table_prefix."wpdudes_survey_email_".$survey_id;
		$sql = "SELECT randomkey FROM ".$email_table. " WHERE survey_id=".$survey_id;
		$total_email_count=0;
		$total_vote_submitted=0;
		$totalvotesql = $wpdb->get_results($sql);
		if($totalvotesql==""){ 
			echo "Something Wrong";
			return;
		}
		
		foreach( $totalvotesql as $totalvotesqls ) {
        		$total_email_count++;
	        	$randomkey = $totalvotesqls->randomkey;
	        	if($randomkey==1) {
		            $total_vote_submitted++;
		        }
	    	}
		$survey_table = $table_prefix."wpdudes_survey";
		$sql = "SELECT survey_title, description, percentdisplay FROM ".$survey_table. " WHERE id=".$survey_id;
		$surveytablesql = $wpdb->get_row($sql);
		$survey_title = $surveytablesql->survey_title;
		$survey_description = $surveytablesql->description;
		$percentdisplay = $surveytablesql->percentdisplay;
		$total_email_vote_calc = $total_vote_submitted/$total_email_count * 100;
		if($total_email_vote_calc<$percentdisplay)  {
		    $vote_needed = ceil($total_email_count*$percentdisplay/100) - $total_vote_submitted;
		    echo "<center><h2 style='color: #203700; width: 900px;'>As of now we have <span style='color: #090f00;'>".$total_vote_submitted." survey(s) submitted.<br> We need ".$voteneeded." more surveys to be submitted by the other participants for you to view the results</h2></center>";
		    return;
		}
		$table_name = $table_prefix."wpdudes_survey_member_".$survey_id;
		$db_name = $wpdb->dbname;
		$sqlselect = "SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$db_name."' AND TABLE_NAME = '".$table_name."'";
		$columnsql = $wpdb->get_results($sqlselect);
		$columnsqlcount = 0;
		$columnsqlcountstore = 0;
		foreach( $columnsql as $columnresults ) {
			if($columnsqlcount>2) {
	        		$columnsqlcountstore++;
				$column_names[$columnsqlcountstore] = $columnresults->COLUMN_NAME;
	        		$column_comments[$columnsqlcountstore] = $columnresults->COLUMN_COMMENT;
			}
			$columnsqlcount++;
	    	}
	    	$sqlselect = "SELECT id,member_name FROM ".$table_name;
		$membersql = $wpdb->get_results($sqlselect);
		$membersqlcount = 0;
		foreach( $membersql as $memberresults ) {
			$membersqlcount++;
	        	$member_names[$membersqlcount] = $memberresults->member_name;
	        	$member_ids[$membersqlcount] = $memberresults->id;
	    	}
		wp_enqueue_style( 'jquery_ui', plugins_url( 'css/jquery-ui.css', __FILE__ ), false, '1.0', 'all' ); 
		wp_enqueue_style( 'table_result', plugins_url( 'css/table.css', __FILE__ ), false, '1.0', 'all' ); 
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-dialog'); 
		wp_enqueue_script('jquery-effects-fade');	 
		wp_head();
	    	?>
		<h2><?php echo $survey_title; ?> - <?php echo $total_vote_submitted," of ",$total_email_count," surveys submitted"; ?></h2>
		<table style="width: 100%" cellspacing="0" >
		    <tr>
		        <th>Member</th>
		        <?php for($k=1;$k<$columnsqlcountstore;$k++) { ?>
		        <th><?php echo $column_comments[$k]; ?></th>
		        <?php } ?>
		        <th>Comment & Chart</th>
		        
		    </tr>
		    <?php for($l=1;$l<=$membersqlcount;$l++) {  
		    $xxx=1;?>
		    <tr>
		        <td><?php echo $member_names[$l]; ?></td>
		        <?php for($k=1;$k<$columnsqlcountstore;$k++) { ?>
		        <td>
		            <?php 
		            $sql = "SELECT ".$column_names[$k]." FROM ".$table_name." WHERE member_name = '".$member_names[$l]."'";
		            $datasql = $wpdb->get_row($sql);
		            $xxx++;
		            $data = $datasql->$column_names[$k];
		            echo $data;
		            $chartdata[$xxx] = $data;
		            $chartdatatitle[$xxx] = $column_comments[$k];
		            $dataofall[$l][$k] = $data;
		            ?>
		        </td>
		        <?php } ?>
		        <?php  
			
			// Dataset definition   
			$DataSet = new pData;  
			$DataSet->AddPoint($chartdata,"Serie1");  
			$DataSet->AddPoint($chartdatatitle,"Serie2");  
			$DataSet->AddAllSeries();  
			$DataSet->SetAbsciseLabelSerie("Serie2");  
			  
			// Initialise the graph  
			$Test = new pChart(880,200);  
			$Test->drawFilledRoundedRectangle(7,7,373,193,5,240,240,240);  
			$Test->drawRoundedRectangle(5,5,375,195,5,230,230,230);  
			  
			// Draw the pie chart  
			$Test->setFontProperties($contentdir."/plugins/wpdudes-survey/Fonts/tahoma.ttf",8);  
			$Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),150,90,110,PIE_PERCENTAGE,TRUE,50,20,5);  
			$Test->drawPieLegend(400,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250);  
			$imagelink = $contentdir."/uploads/charts/image_".$survey_id."_".$l.".png";
			
			
			$Test->Render($imagelink);  
			?> 
			
			
			
		        <td><a style="cursor: pointer;" id="opener_<?php echo $l; ?>">View Comments & Chart</a></td>
		    </tr>
		    <script>
		    	jQuery(document).ready(function($) {
			    	$( "#dialog_<?php echo $l; ?>" ).dialog({
				autoOpen: false,
				width: 930,
			        height: 280,
				show: {
				effect: "fade",
				duration: 500
				},
				hide: {
				effect: "fade",
				duration: 500
				}
				});
				$( "#opener_<?php echo $l; ?>" ).click(function() {
				$( "#dialog_<?php echo $l; ?>" ).dialog( "open" );
				});
			});
		    </script>
		    <div id="dialog_<?php echo $l; ?>" title="<?php echo $member_names[$l]; ?>">
			<img src="<?php echo get_site_url(),'/',$contentdir,'/uploads/charts/image_'.$survey_id.'_'.$l.'.png'; ?>">
			<br>
			<?php
			$sql = "SELECT comments FROM ".$table_name. " WHERE survey_id=".$survey_id." AND id=".$member_ids[$l];
		        $commentsql = $wpdb->get_row($sql);
		        $comments = $commentsql->comments;
			?>
			<?php if($comments=="") { ?>
			<h2><?php echo "No Comments for ".$member_names[$l]; ?></h2>
			<?php } else { ?>
			<h2><?php echo "Comments for ".$member_names[$l]; ?></h2>
			<?php } ?>
			<p><?php echo stripslashes($comments); ?></p>
		    </div>
		    <?php } ?>
		    <tr>
		    
		    
		    	<th>Community Score</th>
		        <?php for($k=1;$k<$columnsqlcountstore;$k++) { ?>
		        <th>
		        
		        <?php 
		        $count = 0;
		        for($l=1;$l<=$membersqlcount;$l++) {
		        	$count = $count + $dataofall[$l][$k];
		        }
		        echo $count;
		        $totaldata[$k] = $count;
		        ?>
		        </th>
		        <?php } ?>
		        <th id="opener" style="cursor: pointer;">View chart</th>
		    </tr>
		    <?php  
			
			// Dataset definition   
			$DataSet = new pData;  
			$DataSet->AddPoint($totaldata,"Serie1");  
			$DataSet->AddPoint($chartdatatitle,"Serie2");  
			$DataSet->AddAllSeries();  
			$DataSet->SetAbsciseLabelSerie("Serie2");  
			  
			// Initialise the graph  
			$Test = new pChart(880,200);  
			$Test->drawFilledRoundedRectangle(7,7,373,193,5,240,240,240);  
			$Test->drawRoundedRectangle(5,5,375,195,5,230,230,230);  
			  
			// Draw the pie chart  
			$Test->setFontProperties($contentdir."/plugins/wpdudes-survey/Fonts/tahoma.ttf",8);  
			$Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),150,90,110,PIE_PERCENTAGE,TRUE,50,20,5);  
			$Test->drawPieLegend(400,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250);  
			$imagelink = $contentdir."/uploads/charts/image_".$survey_id.".png";
			
			
			$Test->Render($imagelink);  
			?> 
		    <script>
		    	jQuery(document).ready(function($) {
				$( "#dialog" ).dialog({
				autoOpen: false,
				width: 930,
			        height: 280,
				show: {
				effect: "fade",
				duration: 500
				},
				hide: {
				effect: "fade",
				duration: 500
				}
				});
				$( "#opener" ).click(function() {
				$( "#dialog" ).dialog( "open" );
				});
			});
		    </script>
		    <div id="dialog" title="Community Total">
			<img src="<?php echo get_site_url(),'/',$contentdir,'/uploads/charts/image_'.$survey_id.'.png'; ?>">
		    </div>
		</table>
	    	<?php
		
	}
}

add_shortcode( 'wpdudes', 'voting_function' );

?>