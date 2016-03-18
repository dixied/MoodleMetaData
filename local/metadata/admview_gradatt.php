<?php
global $PAGE, $CFG, $DB, $USER;
require_once('../../config.php');
require_once 'lib.php';


// Check that they can access
require_login();

// TODO: Get permissions working


//require_capability('local/metadata:ins_view', $context);

require_once($CFG->dirroot.'/local/metadata/gradatt_form.php');
    
// Set up page information
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('ins_pluginname', 'local_metadata'));
$heading = "Faculty Policy Management";
$PAGE->set_heading($heading);

// TODO: Improve how this is done
$PAGE->set_url($CFG->wwwroot.'/local/metadata/admview_policy.php');
$PAGE->requires->css('/local/metadata/insview_style.css');

// Create url
$base_url = create_manage_url('gradatt');
$knowledge_url = create_manage_url('knowledge');
$policy_url = create_manage_url('policy');
$course_url = create_manage_url('course');

// Create forms
$gradatt_form = new gradatt_form($base_url);


// Submitted the data
if ($data = $gradatt_form->get_data()) {
	if (!empty($data->delete_gradatt)) {
		gradatt_form::delete_data($data);
	} elseif (!empty($data->create_gradatt)) {
    	gradatt_form::save_data($data);
	} 
	redirect($base_url);
} 

echo $OUTPUT->header();
?>

<html>
	<div class="nav_header">
		<ul>
		<li><a href=" <?php echo $knowledge_url; ?> ">Program Objectives</a></li>
		<li class="onclick_nav"><a href=" <?php echo $base_url; ?> ">Graduate Attribute</a></li>
		<li><a href=" <?php echo $policy_url; ?> ">Policy</a></li>
		<li><a href=" <?php echo $course_url; ?> ">Tags</a></li>
		</ul>
	</div>
	
	<div class="form_container">
		<?php $gradatt_form->display(); ?>
	</div>
</html>

<?php echo $OUTPUT->footer(); ?>
