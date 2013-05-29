<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package    qtype
 * @subpackage txtprocessor
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Short answer question editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_txtprocessor_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
//var_dump($this->question->options->usecase);
global $CFG;
                $mform->addElement('html', "</br>");
                $mform->addElement('editor', 'usecase', get_string('prellenado', 'qtype_txtprocessor'),
                array('rows' => 20 ,'class'=>'mceEditor'), $this->editoroptions);

        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_txtprocessor', '{no}'),
                question_bank::fraction_options());

        $this->add_interactive_settings();
    }
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = $mform->createElement('header', 'answerhdr', $label);
        //var_dump( $this->editoroptions);
        $repeated[] =$mform->createElement('editor', 'answer',
                get_string('answer', 'question'), array('rows' =>10,'class'=>'mceEditor'), $this->editoroptions);
        $repeated[] = $mform->createElement('select', 'fraction',
                get_string('grade'), $gradeoptions);
        $repeated[] = $mform->createElement('editor', 'feedback',
                get_string('feedback', 'question'), array('rows' => 1), $this->editoroptions);
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';
        return $repeated;
    }


    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question,true);
        $question = $this->data_preprocessing_hints($question);
        if(isset($question->options->usecase)){
            $draftid = file_get_submitted_draft_itemid('usecase');
            $question->usecase = array();
            $question->usecase['text'] = file_prepare_draft_area(
                $draftid,           // draftid
                $this->context->id, // context
                'qtype_txtprocessor',      // component
                'usecase',       // filarea
                !empty($question->id) ? (int) $question->id : null, // itemid
                $this->fileoptions, // options
                $question->options->usecase // text
            );
            $question->usecase['format'] = 1;
            $question->usecase['itemid'] = $draftid;
        }        
        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer['text']);
            if ($trimmedanswer !== '') {
                $answercount++;
                if ($data['fraction'][$key] == 1) {
                    $maxgrade = true;
                }
            } else if ($data['fraction'][$key] != 0 ||
                    !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_txtprocessor');
                $answercount++;
            }
        }
        if ($answercount==0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_txtprocessor', 1);
        }
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }
        return $errors;
    }

    public function qtype() {
        return 'txtprocessor';
    }
}
