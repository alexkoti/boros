<?php
/**
 * TEXT
 * input text comum
 * 
 * 
 * @todo adicionar opções de mostrar vários meses, select de mês e ano, alternate field, restrict range, date range, opção de mostrar em um formato para usuário e salvar em outro em hidden(yyy-mm-dd)
 * 
 */

class BFE_date_picker extends BorosFormElement {
	
	var $valid_attrs = array(
		'name' => '',
		'value' => '',
		'id' => '',
		'class' => '',
		'rel' => '',
		'placeholder' => '',
		'size' => false,
		'disabled' => false,
		'readonly' => false,
		'maxlength' => false,
	);
	
	static $localized = false;
	
	var $enqueues = array(
		'js' => array('date_picker'),
		'css' => array('date_picker'),
	);
	
	function init(){
		add_filter( 'admin_footer', array($this, 'localize') );
	}
	
	function localize(){
		if( self::$localized == false ){
			global $wp_locale;
			$locale_arrays = array(
				'month',
				'month_abbrev',
				'weekday',
				'weekday_abbrev',
				'weekday_initial',
			);
			$locale_strings = array();
			foreach( $locale_arrays as $a ){
				$result = array();
				foreach( $wp_locale->$a as $strs ) {
					$result[] =  $strs;
				}
				$locale_strings[$a] = $result;
			}
			$aryArgs = array(
				'closeText'         => 'Fechar',
				'currentText'       => 'Hoje',
				'monthNames'        => $locale_strings['month'],
				'monthNamesShort'   => $locale_strings['month_abbrev'],
				'monthStatus'       => 'Mostrar um mês diferente',
				'dayNames'          => $locale_strings['weekday'],
				'dayNamesShort'     => $locale_strings['weekday_abbrev'],
				'dayNamesMin'       => $locale_strings['weekday_initial'],
				'firstDay'          => get_option( 'start_of_week' ),
			);
			wp_localize_script( 'form_element_date_picker_date_picker', 'datepickerL10n', $aryArgs );
			self::$localized = true;
		}
	}
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$attrs = make_attributes($this->data['attr']);
		
		$input = "{$this->input_helper_pre}<input type='text' value='{$value}'{$attrs} />{$this->input_helper}";
		
		return $input;
	}
}