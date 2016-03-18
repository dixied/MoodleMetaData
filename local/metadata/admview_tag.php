<?php
global $PAGE, $CFG, $DB, $USER;
require_once('../../config.php');
require_once 'lib.php';


// Check that they can access
require_login();

// TODO: Get permissions working


//require_capability('local/metadata:ins_view', $context);

require_once($CFG->dirroot.'/local/metadata/tag_form.php');

$courseId = get_course_id();
$objectiveId = get_objective_id();
$course = $DB->get_record('course', array('id'=>$courseId), '*', MUST_EXIST);
    
// Set up page information
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('ins_pluginname', 'local_metadata'));
$heading = 'Program Learning Objectives: '.$course->shortname.': '.$course->fullname;
$PAGE->set_heading($heading);

// TODO: Improve how this is done
$PAGE->set_url($CFG->wwwroot.'/local/metadata/admview_tag.php');
$PAGE->requires->css('/local/metadata/insview_style.css');

// Create url
$base_url = new moodle_url('/local/metadata/admview_tag.php', array('id' => $courseId, 'obj' => $objectiveId));
$knowledge_url = create_manage_url('knowledge');
$policy_url = create_manage_url('policy');
$tag_url = create_manage_url('course');

// Create forms
$tag_form = new tag_form($base_url);

// Submit the data
if ($data = $tag_form->get_data()) {
	if(!empty($data->admaddobjective)) {
		$tag_form->add_tags($data);
		$tags_url = new moodle_url('/local/metadata/admview_tag.php', array('id' => $courseId, 'obj' => $objectiveId));
		redirect($tags_url);
	} elseif(!empty($data->admselcourse)) {
		$objid = $tag_form->get_obj($data);
		$tags_url = new moodle_url('/local/metadata/admview_tag.php', array('id' => $courseId, 'obj' => $objid));
		redirect($tags_url);
	} elseif (!empty($data->admdelobjective)) {
		$tag_form->remove_tags($data);
		$tags_url = new moodle_url('/local/metadata/admview_tag.php', array('id' => $courseId, 'obj' => $objectiveId));
		redirect($tags_url);
	}
	
} 

echo $OUTPUT->header();
?>

<html>
	<div class="nav_header">
		<ul>
		<li><a href=" <?php echo $knowledge_url; ?> ">Program Objectives</a></li>
		<li><a href=" <?php echo $policy_url; ?> ">Policy</a></li>
		<li class="onclick_nav"><a href=" <?php echo $tag_url; ?> ">Tags</a></li>
		</ul>
	</div>
	
	<div class="form_container">
		<?php $tag_form->display(); ?>
	</div>
</html>

<?php echo $OUTPUT->footer(); ?>
