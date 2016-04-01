<?php 
require_once '../../config.php';
require_once $CFG->dirroot.'/lib/formslib.php';
require_once $CFG->dirroot.'/lib/datalib.php';

class course_select_form extends moodleform {
	function definition() {
		global $CFG, $DB, $USER; //Declare our globals for use
		global $categoryId;
		
		$mform = $this->_form; //Tell this object to initialize with the properties of the Moodle form.
		
		// Pull all courses in category
		$courseall = $DB->get_records('courseinfo', array('facultyid' => $categoryId));
		
		$courselist = array();
		foreach($courseall as $record) {
			$courselist[$record->courseid] = $record->coursename;
		}
		
		$course_selection = $mform->addElement('select', 'admcourse_select', get_string('admcourse_select', 'local_metadata'), $courselist, '');
		
		$mform->addElement('submit', 'admselect_course', get_string('admselect_course', 'local_metadata'));
	}
	
	/**
	 * Returns the course ID of the selected element
	 * @param $data  the data from the form
	 * @return the course ID from course selected
	 */
	public static function get_course_id($data) {
		global $CFG, $DB, $USER;
		return $data->admcourse_select;
	}
	
}
?>