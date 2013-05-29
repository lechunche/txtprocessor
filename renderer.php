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
 * Short answer question renderer class.
 *
 * @package    qtype
 * @subpackage txtprocessor
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for short answer questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_txtprocessor_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();				//Recibe el objeto de la pregunta
//var_dump($options);

		$currentanswer = $qa->get_last_qt_var('answer');//Extrae la ultima respuesta a la pregunta
                        $step = $qa->get_last_step_with_qt_var('answer');

                //var_dump($step->get_qt_var('answer'));
                
                if(empty($currentanswer)){
           $currentanswer = $question->usecase;
       }
	   $inputname = $qa->get_qt_field_name('answer');	//Obtiene el nombre del campo en este caso es respuesta
			//var_dump($inputname);
		

           


        $feedbackimg = '';								//Se inicializa la imagen de retroalimentacion
        if ($options->correctness) {					//Si en opciones esta señalado que se debe comparar la respuesta correcta entra.
            $answer = $question->get_matching_answer(array('answer' => $currentanswer));//trae la respuesta que encajo con la pregunta
            //var_dump($answer);
           
            if ($answer) {								//Si obtiene respuesta
                $fraction = $answer->fraction;			//Asigna el valor de la respuesta correspondiente
            } else {
                $fraction = 0;							//Si no hay respuesta asigna 0
            }
						//var_dump($inputname);
            $inputattributes['class'] = $this->feedback_class($fraction);//Determina el valor de la respuesta para asignar un color de fondo
            $feedbackimg = $this->feedback_image($fraction);//Determina si habrá o no imagen de palomita o de cruz basado en el fraction
        }

        $questiontext = $question->format_questiontext($qa);//Le da formato apropiado para ser mostrado a la respuesta.
        $placeholder = false;								//calienta bancas. Creo que tiene que ver con el idioma
        if (preg_match('/_____+/', $questiontext, $matches)) {//Busca question text sin formato  aunque no conozco el valor inicial de  matches
            $placeholder = $matches[0];							//Solo la primer incidencia
            //$inputattributes['size'] = round(strlen($placeholder) * 1.1);//Se agrega a los atributos el place holder
        }

            
                     if (empty($options->readonly)) {
                        $editor = editors_get_preferred_editor();
                        $editOpt=array('context'=>$options->context);
                        $editor->use_editor($inputname, $editOpt,
                        array('return_types'  => FILE_INTERNAL | FILE_EXTERNAL));
                        $input= html_writer::tag('textarea', $currentanswer, array('name'=>$inputname,'id'=>$inputname,'rows' => 25, 'cols' => 80));
//                                               var_dump($input);

                    }else{
                       $input= $this->response_area_read_only('answer', $qa,
                               $step,$currentanswer, $options->context). $feedbackimg;
//                                              var_dump($input);

                    }

           
        //$input = html_writer::tag('input', $inputattributes) . $feedbackimg;//Regresa un tag input vacio con los atributos antes mencionados
        if ($placeholder) {									//Si hay placeholder entra
            $inputinplace = html_writer::tag('label', get_string('answer'), //Se crea un label con la respuesta
                    array('for' => $inputname, 'class' => 'accesshide'));//atributos especificos id y clase
            $inputinplace .= $input;						//Se anexa el input vacio que anteriormente se genero
            var_dump($inputinplace);
			
			$questiontext = substr_replace($questiontext, $inputinplace,//Reemplaza lo anterior en question text
                    strpos($questiontext, $placeholder), strlen($placeholder));//encuentra la primer ocurrencia de placeholder en questiontext empezando desde el tamaño de placeholder
        }

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));//Agrega un tag div con questiontext Osea texto de pregunta

        if (!$placeholder) {								//Si no hay placeholder Siempre entra a este if.
            $result .= html_writer::start_tag('div', array('class' => 'ablock')); //Al resultado anterior se anexa a un div inicial
            $result .= html_writer::tag('label', get_string('answer', 'qtype_shortanswer',//Se anexa "Respuesta"
                    html_writer::tag('span', $input, array('class' => 'answer'))),//Se anexa el valor de la respuesta en un input
                    array('for' => $inputname));
					
            $result .= html_writer::end_tag('div');			//Se cierra el div
        }

        if ($qa->get_state() == question_state::$invalid) {	//Si el estado de la pregunta resulta invalido entra
            $result .= html_writer::nonempty_tag('div',		//crea un div con el error de validacion que ocurrio.
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }
		return $result;										//retorna el resultado
    }

    protected function get_editor_options($context) {
        return array('context' => $context);
    }
    public function response_area_read_only($name, $qa, $step,$currentanswer, $context) {
        return html_writer::tag('div', $currentanswer,
                array('class' => ' qtype_essay_response readonly', 'style'=>'background-color:#fff; padding:10px; width:500px;'));
    }
    protected function prepare_response($name, question_attempt $qa,
            question_attempt_step $step, $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        return format_text($step->get_qt_var($name), $step->get_qt_var($name . 'format'),
                $formatoptions);
    }
}
