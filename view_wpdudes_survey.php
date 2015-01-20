<?php
require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
global $wpdb;
if(isset($_POST['action'])) {
	if($_POST['action']=='delete') {
		$survey_id = $_POST['id'];
		$table_prefix = $wpdb->prefix;
		$main_table = $table_prefix."wpdudes_survey";
		$email_table = $table_prefix."wpdudes_survey_email_".$survey_id;
		$member_table = $table_prefix."wpdudes_survey_member_".$survey_id;
		$deleteemail = "DROP TABLE ".$email_table; 
		$deletemember = "DROP TABLE ".$member_table; 
		$deletemain = "DELETE FROM ".$main_table." WHERE id = ".$survey_id;
		$wpdb->query($deleteemail);
		$wpdb->query($deletemember);
		$wpdb->query($deletemain);
	}
}
$db_table_name = $wpdb->prefix . 'wpdudes_survey';
$sql = "SELECT * FROM ".$db_table_name;
$resultset_survey = $wpdb->get_results($sql);
$i = 1;
if($resultset_survey==NULL) {
    $no_result = TRUE;
} else {
    foreach($resultset_survey as $rs_survey) {
        $wpdudes_survey_result[$i][1] = $rs_survey->id;
        $wpdudes_survey_result[$i][2] = $rs_survey->survey_title;
        $wpdudes_survey_result[$i][3] = $rs_survey->description;
        $wpdudes_survey_result[$i][4] = $rs_survey->percentdisplay;
        $wpdudes_survey_result[$i][5] = $rs_survey->survey_date;
        $i++;
    }
}
?>
<link rel="stylesheet" href="<?php echo plugins_url( 'css/table.css', __FILE__ ) ?>">
<h2>View Surveys</h2>
<?php
if($no_result==TRUE) {
    echo "<h3>No Surveys Found</h3>";
} else {
?>
<table style="width: 98%" cellspacing="0" >
    <tr>
        <th>ID</th>
        <th>Names of Surveys</th>
        <th>Date (Server Time)</th>
        <th>Surveys submitted</th>
        <th>Status</th>
        <th>Delete</th>
    </tr>
    <?php for($j=1;$j<$i;$j++) { ?>
    <?php 
        $email_table = $wpdb->prefix."wpdudes_survey_email_".$wpdudes_survey_result[$j][1];
        $sql = "SELECT * FROM ".$email_table. " WHERE survey_id=".$wpdudes_survey_result[$j][1];
        $resultset_survey_email = $wpdb->get_results($sql);
        $total_email_count=0;
        $total_vote_submitted=0;
        foreach ($resultset_survey_email as $rs_survey_email) {
            $total_email_count++;
            if(($rs_survey_email->randomkey)==1) {
                $total_vote_submitted++;
            }
        }
        $total_email_vote_calc = $total_vote_submitted/$total_email_count * 100;
        $sql = "SELECT option_value from ".$wpdb->prefix."options WHERE option_name = 'wpdudes_page_id'";
	$optionwpsql = $wpdb->get_row( $sql );
	$wpdudes_page = $optionwpsql->option_value;
        if($total_email_vote_calc>=$wpdudes_survey_result[$j][4]) {
            $displayanswer_message = "<a href='".get_site_url()."?p=".$wpdudes_page."&action=results&id=".$wpdudes_survey_result[$j][1]."' target='_blank'><img src='" . plugins_url( 'images/publish_btn.png', __FILE__ ) . "' ></a>";
        } else {
            $displayanswer_message = "<img src='" . plugins_url( 'images/inprogress_btn.png', __FILE__ ) . "' >";;
        }
    ?>
    <tr>
        <td><?php echo $wpdudes_survey_result[$j][1]; ?></td>
        <td><?php echo $wpdudes_survey_result[$j][2]; ?></td>
        <td><?php echo date('jS \of F Y',$wpdudes_survey_result[$j][5]); ?><br><?php echo date('h:i:s A',$wpdudes_survey_result[$j][5]); ?></td>
        <td><?php echo $total_vote_submitted," of ",$total_email_count; ?></td>
        <td><?php echo $displayanswer_message; ?></td>
        <td>
        	<form id="delete<?php echo $wpdudes_survey_result[$j][1]; ?>" method="post">
        		<input type="hidden" name="action" value="delete">
        		<input type="hidden" name="id" value="<?php echo $wpdudes_survey_result[$j][1]; ?>">
        		<img style="cursor: pointer;" src="<?php echo plugins_url( 'images/delete_btn.png', __FILE__ ); ?>" onclick="deleteFunction()">
        	</form>
        	<script>
		function deleteFunction() {
		    document.getElementById("delete<?php echo $wpdudes_survey_result[$j][1]; ?>").submit();
		}
		</script>
        </td>
    </tr>
    <?php } ?>
</table>
<p id="demo"></p>
<?php } ?>