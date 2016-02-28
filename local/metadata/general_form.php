<?php
require_once '../../config.php';
require_once $CFG->dirroot.'/lib/formslib.php';
require_once $CFG->dirroot.'/lib/datalib.php';

require_once 'lib.php';

/**
 * The form to display the tab for general information.
 */
class general_form extends moodleform {
	function definition() {
		global $CFG, $DB, $USER; //Declare our globals for use
                global $course;           

                // initialize the form.
                $mform = $this->_form; //Tell this object to initialize with the properties of the Moodle form.

                $courseId = get_course_id();
		$mform->addElement('static', 'course_id', get_string('course_id', 'local_metadata'));
                $mform->setDefault('course_id', $courseId);
            
                $courseName = $course->fullname;
                $mform->addElement('static', 'course_name', get_string('course_name', 'local_metadata'));
                $mform->setDefault('course_name', $courseName);

                $courseInstructor = $USER->firstname.' '.$USER->lastname;
                $mform->addElement('static', 'course_instructor', get_string('course_instructor', 'local_metadata'));
                $mform->setDefault('course_instructor', $courseInstructor);

		// Form elements

                // Enter faculty name.
                $course_faculty = $mform->addElement('text', 'course_faculty', get_string('course_faculty', 'local_metadata'), $attributes);
                //$mform->addRule('course_faculty', get_string('required'), 'required', null, 'client');
                if($courseinfo = $DB->get_record('courseinfo', array('courseid'=>$courseId))){
                    $mform->setDefault('course_faculty', $courseinfo->coursefaculty);
                }             

                // TODO: EDITOR HAS AUTOSAVE AND AUTORESTORE DATA, WHICH WILL REMOVE THE FETCHED DATA FROM DB
                // Add editor for create or modify course description.              
                // Get default course description from DB.
                // If description does not exist in the extra table, display the default description.
                $default_description = $course->summary;
                //$course_description_editor = $mform->addElement('editor', 'course_description', get_string('course_description', 'local_metadata'));
                $mform->addElement('textarea', 'course_description', get_string("course_description", "local_metadata"), 'wrap="virtual" rows="5" cols="70"');

                if($courseinfo){
                    $current_description = $courseinfo->coursedescription;
                    $mform->setDefault('course_description', $current_description);
                    //$course_description_editor->setValue(array('text' => $current_description) );
                }else{
                    $mform->setDefault('course_description', $default_description);
                    //$course_description_editor->setValue(array('text' => $default_description) );
                }

                $mform->addRule('course_description', get_string('required'), 'required', null, 'client');
                $mform->setType('course_description', PARAM_RAW);      
                
                // Upload course type
                //$mform->addElement('filepicker', 'upload_course_type', get_string('upload_course_type', 'local_metadata'), null,array('maxbytes' => $maxbytes, 'accepted_types' => '.csv'));
                //$course_type_content = $mform->get_file_content('upload_course_type');
                $mform->addElement('file', 'upload_ctype_file', get_string('upload_ctype_file', 'local_metadata'));
                //$mform->addElement('button', 'upload_ctype', get_string("upload_ctype", "local_metadata"));
                $mform->addElement('html', '<button type="button" id="upload_ctype">Upload</button>');

		// Add selection list for course type		
		// TODO: ADD COURSE TYPE IN DB.
		$course_types = array();
		$course_types[] = 'type 1';
		$course_types[] = 'type 2';
		// -------------------------------------
		$course_type_selection = $mform->addElement('select', 'course_type', get_string('course_type', 'local_metadata'), $course_types, '');
		$mform->addRule('course_type', get_string('required'), 'required', null, 'client');

		// Add multi-selection list for course topics
		// TODO: UPLOAD FILE TO MANIPULATE THE LIST
		$course_topics = array();
		$course_topics[] = 'topic 1';
		$course_topics[] = 'topic 2';
		// -------------------------------------
		$course_topic_selection = $mform->addElement('select', 'course_topic', get_string('course_topic', 'local_metadata'), $course_topics, '');
		$course_topic_selection->setMultiple(true);
		$mform->addRule('course_topic', get_string('required'), 'required', null, 'client');
		
		// Add multi-selection list for course learning objectives
		// TODO: MERGE WITH XIAORAN
		$course_objectives = array();
		$course_objectives[] = 'objective 1';
		$course_objectives[] = 'objective 2';
		// -------------------------------------
		$course_objective_selection = $mform->addElement('select', 'course_objective', get_string('course_objective', 'local_metadata'), $course_objectives, '');
		$course_objective_selection->setMultiple(true);
		$mform->addRule('course_objective', get_string('required'), 'required', null, 'client');
		
                // Add number of assessment
                // TODO: MANIPULATE ASSESSMENT FIELD AS SPECIFIED
                $course_assessment = $mform->addElement('text', 'course_assessment', get_string('assessment_counter', 'local_metadata'), $attributes);
                $mform->addRule('course_assessment', get_string('required'), 'required', null, 'client');
                $mform->addRule('course_assessment', get_string('err_numeric', 'local_metadata'), 'numeric', null, 'client');

                if($courseinfo){
                    $mform->setDefault('course_assessment', $courseinfo->assessmentnumber);
                }

                // Add number of session
                // TODO: MANIPULATE SESSION FIELD AS SPEFICIED
                $course_assessment = $mform->addElement('text', 'course_session', get_string('session_counter', 'local_metadata'), $attributes);
                $mform->addRule('course_session', get_string('required'), 'required', null, 'client');
                $mform->addRule('course_session', get_string('err_numeric', 'local_metadata'), 'numeric', null, 'client');

                if($courseinfo){
                    $mform->setDefault('course_session', $courseinfo->sessionnumber);
                }

                // Add multi selection list for graduate attributes.
                // TODO: MANIPULATE THE LIST FROM DB
                $course_gradAtts = array();
                $course_gradAtts[] = 'attribute 1';
                $course_gradAtts[] = 'attribute 2';
                // -------------------------------------
                $course_gradAtts_selection = $mform->addElement('select', 'course_gradAtt', get_string('course_gradAtt', 'local_metadata'), $course_objectives, '');
                $course_gradAtts_selection->setMultiple(true);
                //$mform->addRule('course_gradAtt', get_string('required'), 'required', null, 'client');


		// Add form buttons
		$this->add_action_buttons();
	}
	
	//If you need to validate your form information, you can override  the parent's validation method and write your own.	
	function validation($data, $files) {
		$errors = parent::validation($data, $files);
		global $DB, $CFG, $USER; //Declare them if you need them

		//if ($data['data_name'] Some condition here)  {
		//	$errors['element_to_display_error'] = get_string('error', 'local_demo_plug-in');
		//}
		return $errors;
        }

        /**
         * Will save the given data.
         * @param $data data generated by the form
         */
        public static function save_data($data) {
                global $CFG, $DB, $USER; //Declare our globals for use
                global $course;

                $course_info = new stdClass();
                $course_info->courseid = $course->id;
                $course_info->coursename = $course->fullname;
                $course_info->coursetopic = $data->course_topic;
                //$course_info->coursedescription = $data->course_description['text'];
                $course_info->coursedescription = $data->course_description;
                $course_info->coursefaculty = $data->course_faculty;
                $course_info->assessmentnumber = $data->course_assessment;
                $course_info->sessionnumber = $data->course_session;

                if($existRecord = $DB->get_record('courseinfo', array('courseid'=>$course->id)) ){
                // Must have an entry for 'id' to map the table specified.
                    $course_info->id = $existRecord->id;
                    $update_courseinfo = $DB->update_record('courseinfo', $course_info, false);
                    echo 'Existing data is updated.';
                }else{
                    $insert_courseinfo = $DB->insert_record('courseinfo', $course_info, false);
                    echo 'New data is added.';
                }
        }


}

?>

