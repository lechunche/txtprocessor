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
 * Short answer question definition class.
 *
 * @package    qtype
 * @subpackage shortanswer
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Represents a short answer question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_txtprocessor_question extends question_graded_by_strategy
        implements question_response_answer_comparer {
    /** @var boolean whether answers should be graded case-sensitively. */
    public $usecase;
    /** @var array of question_answer. */
    public $answers = array();

    public function __construct() {
        parent::__construct(new question_any_matching_answers_strategy($this));
        //var_dump($this->gradingstrategy);
    }
    public function get_format_renderer(moodle_page $page) {

        return $page->get_renderer('qtype_txprocessor_form', 'format_editor');
    }
    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }
  public function get_matching_answer(array $response) {
        return $this->gradingstrategy->grade($response);
    }
    public function is_complete_response(array $response) {
        return array_key_exists('answer', $response) &&
                ($response['answer'] || $response['answer'] === '0');
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseenterananswer', 'qtype_txtprocessor');
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function get_answers() {
        return $this->answers;
    }
    
    //  public function grade(array $response) {
    //     $total=0;
    //     $parcial=0;
        
    //     //var_dump($this->gradingstrategy->question->answers);
    //     foreach ($this->gradingstrategy->question->answers as $aid => $answer) {
            
    //         $total+= $answer->fraction;
    //         //var_dump($this->gradingstrategy->question->compare_response_with_answer($response, $answer));
    //         if ($this->gradingstrategy->question->compare_response_with_answer($response, $answer)) {
                
    //             $parcial+= $answer->fraction;
    //         }
    //     }
        
    //     if($parcial!=0){
    //         $answer->parcial= $parcial;
    //         $answer->total= $total;
    //         $answer->fraction =$parcial/$total;
    //            return $answer;
    //     }else{
    //            return null;
    //     }
    // }
    
    public function compare_response_with_answer(array $response, question_answer $answer) {
        if (!array_key_exists('answer', $response) || is_null($response['answer'])) {
            return false;
        }
        $respuesta=$response['answer'];
        return self::compare_string_with_wildcard(
                $respuesta, $answer->answer);
    }

   public static function evaluar($string,$pattern){
    $equals=false;
        foreach ($pattern as $key => $value) {
            if(array_key_exists($key, $string)){
//                echo "<pre>";
//                print_r($value);
//                echo "alumno";
//                print_r($string["$key"]);
//                echo "</pre>";
                $result = array_diff ($string["$key"],$value);
                if(empty($result)){
                    $equals= true;
                }else
                $equals=false;
            }
        }
        return $equals;
    }

   public static function arrayXml($path,$array=array(),$flag=false){
        $return=array();
        foreach($path->children() as $child){
            $str="";
            if($flag){
                #falta definir formato atributos
                $attr=self::xmlObject2Array($child);
            
            $str=",".$attr;
            }   
            $str=$child->getName().$str;
            if(strlen($child)<3 && preg_match("/(.\s)+$/", $child)){
                continue;
                    //echo "SI";
            }else if ($child!=""){
                $return["$child"]=array_merge($array,array($str));
            }else{
                $aux=self::arrayXml($child,array_merge($array,array($str)),$flag);
                $keys=array_keys($aux);
                foreach ($keys as $key => $value) {
                    $return["$value"]=$aux["$value"];
                }
            }
        }
        return $return;
    }
    
    public static function xmlObject2Array($child){
            $return=array();
            foreach ($child->attributes() as $key => $value) {
                    if($key=="style"){
                        echo "entro a style";
			array_push($return,$key." = ".self::sortStyle($value));
		}else{
			array_push($return,$key." = ".$value);
		}
            }
            /*echo "<pre>";
            print_r($return);
            echo "</pre>";*/
            sort($return);
            $returnJson=json_encode($return);
            /*echo $returnJson."<br />";*/
            return $returnJson;
    }
    public static function sortStyle($str){
	$str=substr($str, 0,-1);
	$str=str_replace(':', '":"', $str);
	$str=str_replace('; ', '","', $str);
	$str=str_replace(';', '","', $str);
	$str='{"'.$str.'"}';
	$array=json_decode($str,true);
	ksort($array);

	$str=json_encode($array);
	$str=substr($str, 1,-1);
	$str=str_replace('","', ';', $str);
	$str=str_replace('":"', ':', $str);
	$str=str_replace('"', '', $str);
	echo $str."<br />";
	return $str;
}
    public static function compare_string_with_wildcard($string, $pattern) {

        // Normalise any non-canonical UTF-8 characters before we start.
        $pattern = self::safe_normalize($pattern);
        $string = self::safe_normalize($string);
        $pattern= str_replace(array("\r\n", "\n", "\r","<p> </p>"), "", $pattern);
        $string= str_replace(array("\r\n", "\n", "\r","<p> </p>"), "", $string);

        

        $html="<html>".$string."</html>";
        $path=simplexml_load_string($html);
        $string=self::arrayXml($path,array(),true);

         echo "<pre>";
         print_r($string);
         echo "</pre>";

        $html2="<html>".$pattern ."</html>";
        $path2=simplexml_load_string($html2);
        $pattern=self::arrayXml($path2,array(),true);
         echo "<pre>";
         print_r($pattern);
         echo "</pre>";
        
        return self::evaluar($string,$pattern);
    }

    /**
     * Normalise a UTf-8 string to FORM_C, avoiding the pitfalls in PHP's
     * normalizer_normalize function.
     * @param string $string the input string.
     * @return string the normalised string.
     */
    protected static function safe_normalize($string) {
        if (!$string) {
            return '';
        }

        if (!function_exists('normalizer_normalize')) {
            return $string;
        }

        $normalised = normalizer_normalize($string, Normalizer::FORM_C);
        if (!$normalised) {
            // An error occurred in normalizer_normalize, but we have no idea what.
            debugging('Failed to normalise string: ' . $string, DEBUG_DEVELOPER);
            return $string; // Return the original string, since it is the best we have.
        }

        return $normalised;
    }

    public function get_correct_response() {
        $response = parent::get_correct_response();
        if ($response) {
            $response['answer'] = $this->clean_response($response['answer']);
        }
        return $response;
    }

    public function clean_response($answer) {
        // Break the string on non-escaped asterisks.
        $bits = preg_split('/(?<!\\\\)\*/', $answer);

        // Unescape *s in the bits.
        $cleanbits = array();
        foreach ($bits as $bit) {
            $cleanbits[] = str_replace('\*', '*', $bit);
        }

        // Put it back together with spaces to look nice.
        return trim(implode(' ', $cleanbits));
    }

    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'answerfeedback') {
            $currentanswer = $qa->get_last_qt_var('answer');
            $answer = $this->get_matching_answer(array('answer' => $currentanswer));
            $answerid = reset($args); // itemid is answer id.
            return $options->feedback && $answer && $answerid == $answer->id;

        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }
}
class question_any_matching_answers_strategy implements question_grading_strategy {
    /**
     * @var question_response_answer_comparer (presumably also a
     * {@link question_definition}) the question we are doing the grading for.
     */
    public $question;

    /**
     * @param question_response_answer_comparer $question (presumably also a
     * {@link question_definition}) the question we are doing the grading for.
     */
    public function __construct(question_response_answer_comparer $question) {
        $this->question = $question;
        
    }
public function grade(array $response) {
        $total=0;
        $parcial=0;
        
        //var_dump($this->gradingstrategy->question->answers);
        foreach ($this->question->answers as $aid => $answer) {
            
            $total+= $answer->fraction;
           // var_dump($this->question->compare_response_with_answer($response, $answer));
            if ($this->question->compare_response_with_answer($response, $answer)) {
                
                $parcial+= $answer->fraction;
            }
        }
        
        if($parcial!=0){
            $answer->parcial= $parcial;
            $answer->total= $total;
            $answer->fraction =$parcial/$total;
               return $answer;
        }else{
               return null;
        }
    }
//    public function grade(array $response) {
//        foreach ($this->question->get_answers() as $aid => $answer) {
//            if ($this->question->compare_response_with_answer($response, $answer)) {
//                $answer->id = $aid;
//                return $answer;
//            }
//        }
//        return null;
//    }

    public function get_correct_answer() {
        foreach ($this->question->get_answers() as $answer) {
            $state = question_state::graded_state_for_fraction($answer->fraction);
            if ($state == question_state::$gradedright) {
                return $answer;
            }
        }
        return null;
    }
}