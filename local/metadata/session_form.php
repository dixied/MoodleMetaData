<?php
require_once '../../config.php';
require_once $CFG->dirroot.'/lib/formslib.php';
require_once $CFG->dirroot.'/lib/datalib.php';

require_once 'lib.php';
require_once 'recurring_element_parser.php';


/**
 * The form to display the tab for sessions
 *
 * Requires the argument 'sessions', which should be the array of sessions
 *   for the current course loaded from the database
 *
 * For an example, see how it is instantiated in insview.php
 *
 * To look at how deleting a recurring element is done, see definition_after_data and save_data.
 *   As well, see the elements was_deleted and deleteSession (the delete button) in add_session_repeat_template
 *
 */
class session_form extends moodleform {
    const NUM_PER_PAGE = 10;

    /**
     * Will set up the form elements
     * @see lib/moodleform#definition()
     */
    function definition() {
        global $CFG, $USER;

        $sessions = $this->_customdata['sessions'];
        
        $page_num = optional_param('page', 0, PARAM_INT);
        $subset_included = array_slice($sessions, $page_num * self::NUM_PER_PAGE, self::NUM_PER_PAGE);
        $count = min(count($subset_included), self::NUM_PER_PAGE);
        
        $this->add_session_repeat_template($count);
        

        $this->setup_data_for_repeat($subset_included);
        
        $this->add_page_buttons($page_num, count($sessions));
        

        $this->add_action_buttons();
    }

    /**
     *  Will set up a repeate template, with elements for each piece of required data
     *
     *  @param int $numSessions number of Sessions that have been created for the course
     */
    private function add_session_repeat_template($numSessions) {
        global $DB;
        $mform = $this->_form;

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('text', 'sessiontitle', get_string('session_title', 'local_metadata'));
        $repeatarray[] = $mform->createElement('textarea', 'sessiondescription', get_string('session_description', 'local_metadata'));
        
        $repeatarray[] = $mform->createElement('text', 'sessionguestteacher', get_string('session_guest_teacher', 'local_metadata'));
        

        $repeatarray[] = $mform->createElement('select', 'sessiontype', get_string('session_type', 'local_metadata'), session_form::get_session_types());
        
        $repeatarray[] = $mform->createElement('select', 'sessionlength', get_string('session_length', 'local_metadata'), session_form::get_session_lengths());

        $repeatarray[] = $mform->createElement('date_selector', 'sessiondate', get_string('session_date', 'local_metadata'));


        // Set up the select for learning objectives
            // Will separate them based on type
            // Then, everytime need to deal with them, will also deal with them separated by type
        $learningObjectives = get_course_learning_objectives();
        $learningObjectivesList = array();
        foreach ($learningObjectives as $learningObjective) {
            $learningObjectivesList[$learningObjective->objectivetype][$learningObjective->id] = $learningObjective->objectivename;
        }
        
        $learningObjectiveTypes = get_learning_objective_types();
        foreach ($learningObjectiveTypes as $learningObjectiveType) {
            $options = array();
            if (array_key_exists($learningObjectiveType, $learningObjectivesList)) {
                $options = $learningObjectivesList[$learningObjectiveType];
            }
            
            $learningObjectivesEl = $mform->createElement('select', 'learning_objective_'.$learningObjectiveType, get_string('learning_objective_'.$learningObjectiveType, 'local_metadata'), $options);
            $learningObjectivesEl->setMultiple(true);
            $repeatarray[] = $learningObjectivesEl;
        }
        


        // Set up the select for assessments
        $assessments = get_table_data_for_course('courseassessment');
        $assessmentsList = array();
        foreach ($assessments as $assessment) {
            $assessmentsList[$assessment->id] = $assessment->assessmentname;
        }
        $assessmentsEl = $mform->createElement('select', 'assessments', get_string('related_assessments', 'local_metadata'), $assessmentsList);
        $assessmentsEl->setMultiple(true);
        $repeatarray[] = $assessmentsEl;


        // Add needed hidden elements
        // Stores the id for each element
        $repeatarray[] = $mform->createElement('hidden', 'coursesession_id', -1);
        
        // Two elements required for deleting
        $repeatarray[] = $mform->createElement('hidden', 'was_deleted', false);
        $repeatarray[] = $mform->createElement('submit', 'deleteSession', get_string('deletesession', 'local_metadata'));
        //$mform->registerNoSubmitButton('deleteSession');
        
        
        $repeateloptions = array();
        
        // Moodle complains if some elements aren't given a type
        $repeateloptions['sessiontitle']['type'] = PARAM_TEXT;
        $repeateloptions['sessionguestteacher']['type'] = PARAM_TEXT;
        $repeateloptions['coursesession_id']['type'] = PARAM_INT;
        $repeateloptions['was_deleted']['type'] = PARAM_RAW;

        // Add the repeating elements to the form
        $this->repeat_elements($repeatarray, $numSessions,
            $repeateloptions, 'sessions_list', 'sessions_list_add_element', 1, get_string('add_session', 'local_metadata'), true);
        
        
        
    }

    /**
     *  This function MUST be called, and return true, before get_data is called on this form.
     *    The need for this function is because of how noSubmitButton is handled terribly in moodle
     *    and using it will cause a warning, that causes the tests to fail.
     *
     *  @return true iff the actual submit button was pressed
     */
    public function ensure_was_submitted() {
        return $this->_form->getSubmitValue('submitbutton') !== null ||
               $this->_form->getSubmitValue('previousPage') !== null ||
               $this->_form->getSubmitValue('nextPage') !== null;
    }

    /**
     *  Will set up the data for each of the elements in the repeat_elements
     *  
     *
     */
    private function setup_data_for_repeat($sessions) {
        $mform = $this->_form;
        $key = 0;
        

        foreach ($sessions as $session)
        {
            $index = '['.$key.']';
            
            // Add the help button for sessionguestteacher
            $mform->addHelpButton('sessionguestteacher'.$index, 'session_guest_teacher', 'local_metadata');
            
            // Easiest way to set the initial data is to set the default for each session in sessions
            $mform->setDefault('coursesession_id'.$index, $session->id);
            
            $mform->setDefault('sessiontitle'.$index, $session->sessiontitle);
            
            $mform->setDefault('sessionguestteacher'.$index, $session->sessionguestteacher);
            
            $mform->setDefault('sessiondescription'.$index, $session->sessiondescription);
            $mform->setDefault('sessiondate'.$index, $session->sessiondate);

            $mform->setDefault('sessiondate'.$index, $session->sessiondate);

            // Handled specially, because the default must be an int, which needs to be translated from string in database
            $types = session_form::get_session_types();
            $mform->setDefault('sessiontype'.$index, array_search($session->sessiontype, $types));
            
            // Handled specially, because the default must be an int, which needs to be translated from string in database
            $lengths = session_form::get_session_lengths();
            $mform->setDefault('sessionlength'.$index, array_search($session->sessionlength, $lengths));

            $this->setup_data_from_database_for_session($mform, $index, $session);


            $key += 1;
        }
    }
    
    /**
     *  Will add the buttons on the bottom
     *  
     *
     */
    private function add_page_buttons($page_num, $num_sessions) {
        $mform = $this->_form;
        
        $page_change_links=array();
        
        // Back page button
        $page_change_links[] = $mform->createElement('submit', 'previousPage', get_string('previous_page', 'local_metadata'));
        
        // If is on the first page
        if ($page_num === 0) {
            $mform->disabledIf('previousPage', null);
        }
    
        // Next page button
        $page_change_links[] = $mform->createElement('submit', 'nextPage', get_string('next_page', 'local_metadata'));
        
        // If the next page would be empty
        if (($page_num + 1) * self::NUM_PER_PAGE >= $num_sessions) {
            $mform->disabledIf('nextPage', null);
        }
        
        $mform->addGroup($page_change_links, 'buttonarray_sdafs', '', array(' '), false);
    }
    
    function setup_data_from_database_for_session($mform, $index, $session) {
        global $DB;
        // Load the learning objectives for the session
        // Template for this was found in \mod\glossary\edit.php
        if ($learningObjectivesArr = $DB->get_records_menu("sessionobjectives", array('sessionid'=>$session->id), '', 'id, objectiveid')) {
            $learningObjectiveTypes = get_learning_objective_types();
            foreach ($learningObjectiveTypes as $learningObjectiveType) {
                $mform->setDefault('learning_objective_'.$learningObjectiveType.$index, array_values($learningObjectivesArr));
            }
            
        }

        // Load the assessments for the session
        // Template for this was found in \mod\glossary\edit.php
        if ($assessmentsArr = $DB->get_records_menu("session_related_assessment", array('sessionid'=>$session->id), '', 'id, assessmentid')) {
            $mform->setDefault('assessments'.$index, array_values($assessmentsArr));
        }
    }
    
    /**
     *  This is only necessary to handle deleting elements.
     *
     *  
     *
     */
    function definition_after_data() {
        parent::definition_after_data();
        $mform =& $this->_form;
        
        $numRepeated = $mform->getElementValue('sessions_list');
        
        // Go through each session, and delete elements for ones that should be deleted
        for ($key = 0; $key < $numRepeated; ++$key) {
            $index = '['.$key.']';
            $deleted = $mform->getSubmitValue('deleteSession'.$index);
            
            // If a button is pressed, then doing $mform->getSubmitValue(buttonId) will return a non-null vaue
                // However, if other buttons are subsequently pressed, then $mform->getSubmitValue(buttonId) will return null
                // So use the element 'was_deleted' for that repeated element to store if has been deleted
            if ($deleted or $mform->getElementValue('was_deleted'.$index) == true) {
                // If deleted, just remove the visual elements
                    // Will not save to the database until the user presses submit
                $mform->removeElement('sessiontitle'.$index);
                $mform->removeElement('sessiondescription'.$index);
                $mform->removeElement('sessionguestteacher'.$index);

                $mform->removeElement('sessiontype'.$index);
                $mform->removeElement('sessionlength'.$index);

                $mform->removeElement('sessiondate'.$index);
                
                
                $learningObjectiveTypes = get_learning_objective_types();
                foreach ($learningObjectiveTypes as $learningObjectiveType) {
                    $mform->removeElement('learning_objective_'.$learningObjectiveType.$index);
                }

                $mform->removeElement('assessments'.$index);

                $mform->removeElement('deleteSession'.$index);
                
                $mform->getElement('was_deleted'.$index)->setValue(true);
            }
        }
    }

    /**
     * Ensure that the data the user entered is valid
     *
     * @see lib/moodleform#validation()
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }


    /**
     * Will return all of the type options
     *   Will eventually load them from the configuration for the plugin
     *
     * @return array containing string of all types
     */
    public static function get_session_types() {
        $types = array('lecture', 'lab', 'seminar');

        return $types;
    }
    
    /**
     * Will return all of the length options
     *   May eventually load them from the configuration for the plugin
     *
     * @return array containing string of all types
     */
    public static function get_session_lengths() {
        // TODO: Will probably need to change this
        $types = array('50 minutes', '80 minutes', '110 minutes', '140 minutes', '170 minutes');

        return $types;
    }
    
    public function get_page_change() {
        if ($this->_form->getSubmitValue('previousPage') !== null) {
            return -1;
        } else if ($this->_form->getSubmitValue('nextPage') !== null) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Will save the given data, that should be from calling the get_data function. Data will be all of the sessions in the course
     *
     * Also handles removing elements that should be deleted from the form.
     *
     * @param object $data value from calling get_data on this form
     *
     */
    public function save_data($data) {
        global $DB;
        
        // Set up the recurring element parser
        $allChangedAttributes = array('sessiontitle', 'sessiondescription', 'sessionguestteacher', 'sessiontype', 'sessionlength', 'sessiondate', 'assessments', 'was_deleted');
        
        $learningObjectiveTypes = get_learning_objective_types();
        foreach ($learningObjectiveTypes as $learningObjectiveType) {
            $allChangedAttributes[] = 'learning_objective_'.$learningObjectiveType;
        }
        
        $types = session_form::get_session_types();
        $lengths = session_form::get_session_lengths();
        $convertedAttributes = array('sessiontype' => function($value) use ($types) { return $types[$value]; },
                                     'sessionlength' => function($value) use ($lengths) { return $lengths[$value]; }
                                     );

        $session_recurring_parser = new recurring_element_parser('coursesession', 'sessions_list', $allChangedAttributes, $convertedAttributes);
        
        

        // Get the tuples (one for each session) from the parser
        $tuples = $session_recurring_parser->getTuplesFromData($data);
        
        // Handles deleting a session
        foreach ($tuples as $tupleKey => $tuple) {
            // Clean out the sessionobjectives and session_related_assessment for this session
            $DB->delete_records('sessionobjectives', array('sessionid'=>$tuple['id']));
            $DB->delete_records('session_related_assessment', array('sessionid'=>$tuple['id']));
            
            // If the tuple has been deleted, then remove it from the database
            if ($tuple['was_deleted'] == true) {
                $session_recurring_parser->deleteTupleFromDB($tuple);
                
                // Finally, remove it from the tuples that will be saved, because otherwise will just be resaved anyway
                unset($tuples[$tupleKey]);
                continue;
            }
        }
        
        // Save the remaining data for the sessions/tuples
            // Will also update the id for elements that are new
        $session_recurring_parser->saveTuplesToDB($tuples);
        
        // Handles updating the objectives and related assessments
        foreach ($tuples as $tupleKey => $tuple) {
            
            // Save the learning_objective
            // Template for this was found in \mod\glossary\edit.php
            $learningObjectiveTypes = get_learning_objective_types();
            foreach ($learningObjectiveTypes as $learningObjectiveType) {
                $key = 'learning_objective_'.$learningObjectiveType;
                if (array_key_exists($key, $tuple) and is_array($tuple[$key])) {
                    foreach ($tuple[$key] as $objectiveId) {
                        $newLink = new stdClass();
                        $newLink->sessionid = $tuple['id'];
                        $newLink->objectiveid = $objectiveId;
                        $DB->insert_record('sessionobjectives', $newLink, false);
                    }
                }
            }
            
            // Save the assessments
            // Template for this was found in \mod\glossary\edit.php
            if (array_key_exists('assessments', $tuple) and is_array($tuple['assessments'])) {
                foreach ($tuple['assessments'] as $assessmentId) {
                    $newLink = new stdClass();
                    $newLink->sessionid = $tuple['id'];
                    $newLink->assessmentid = $assessmentId;
                    $DB->insert_record('session_related_assessment', $newLink, false);
                }
            }
            
        }
    }

}

?>
