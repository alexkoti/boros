<?php
/**
 * TEXT
 * input text comum
 * 
 * 
 * @todo adicionar opção de icon_trigger, restrict range e demais opções disponíveis nos pickers
 * @todo traduzir as strings de time
 * 
 * Devido ao alto processamento do timepicker, foi deixado uma opção 'split_time', que quando definida para 'true' separa os
 * campos de hora e minuto, cancelando também o uso do timepicker
 * 
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
	
	function localize_script(){
		add_filter( 'admin_footer', array($this, 'localize') );
	}
	
	/**
	 * Opções
	 * 
	 * $type                - date | time | datetime
	 * $split_time          - separar os campos de hora e minuto, cancelando também o uso do timepicker
	 * $month_select        - dropdown de mês
	 * $year_select         - dropdown de ano
	 * $multiple_months     - mostrar múltiplos meses
	 * $date_format         - formato de retorno da data
	 * $time_format         - formato de retorno da hora
	 * $step_minute         - intervalo de minutos dentro do select
	 * $showSecond          - mostrar select de segundos
	 * $date_range          - intervalo de datas
	 * 
	 */
	function add_defaults(){
		$this->defaults['options']['picker_type']         = 'date';
		$this->defaults['options']['split_time']          = false;
		$this->defaults['options']['months_number']       = 1;
		$this->defaults['options']['date_format']         = 'dd/mm/yy';
		$this->defaults['options']['time_format']         = 'hh:mm';
		$this->defaults['options']['step_minute']         = 1;
		$this->defaults['options']['month_select']        = false;
		$this->defaults['options']['year_select']         = false;
		$this->defaults['options']['show_second']         = false;
		$this->defaults['options']['date_range']          = false;
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
			$date_picker_vars = array(
				'dateFormat'        => $this->data['options']['date_format'],
				'closeText'         => 'Pronto',
				'currentText'       => 'Hoje',
				'monthNames'        => $locale_strings['month'],
				'monthNamesShort'   => $locale_strings['month_abbrev'],
				'monthStatus'       => 'Mostrar um mês diferente',
				'dayNames'          => $locale_strings['weekday'],
				'dayNamesShort'     => $locale_strings['weekday_abbrev'],
				'dayNamesMin'       => $locale_strings['weekday_initial'],
				'firstDay'          => get_option( 'start_of_week' ),
			);
			wp_localize_script( 'form_element_date_picker_date_picker', 'datepickerL10n', $date_picker_vars );
			self::$localized = true;
		}
	}
	
	/**
	 * Saída final do input
	 * 
	 */
	function set_input( $value = null ){
		$this->data['attr']['dataset']['picker_type'] = $this->data['options']['picker_type'];
		// deixar o campo como date em vez de datetime, quando for utilizar o split_time
		if( $this->data['options']['picker_type'] == 'datetime' and $this->data['options']['split_time'] === true ){
			$this->data['attr']['dataset']['picker_type'] = 'date';
		}
		$this->data['attr']['dataset']['date_format']   = $this->data['options']['date_format'];
		$this->data['attr']['dataset']['time_format']   = $this->data['options']['time_format'];
		$this->data['attr']['dataset']['step_minute']   = $this->data['options']['step_minute'];
		$this->data['attr']['dataset']['month_select']  = $this->data['options']['month_select'];
		$this->data['attr']['dataset']['year_select']   = $this->data['options']['year_select'];
		$this->data['attr']['dataset']['show_second']   = $this->data['options']['show_second'];
		$this->data['attr']['dataset']['months_number'] = $this->data['options']['months_number'];
		$this->data['attr']['dataset']['date_range']    = $this->data['options']['date_range'];
		$this->data['attr']['class'] .= ' iptw_100';
		//pre($this->data['attr']['dataset']);
		
        $value = boros_parse_args(array(
            'view' => '',
            'iso' => '',
            'hour' => '00',
            'minute' => '00',
        ), $value);
		
		ob_start();
		//pre($value);
		//pre($this->data['options'], 'options');
		//pre($this->data['attr'], 'attr');
		//$name = $this->set_name();
		//pal($attrs);
		
		echo $this->input_helper_pre;
        $name        = $this->data['attr']['name'];
        $this->data['attr']['dataset']['key'] = 'view';
        $name_view   = $this->set_name($this->data['attr']);
        
        $this->data['attr']['dataset']['key'] = 'iso';
        $name_iso    = $this->set_name($this->data['attr']);
        
        $this->data['attr']['dataset']['key'] = 'hour';
        $name_hour   = $this->set_name($this->data['attr']);
        
        $this->data['attr']['dataset']['key'] = 'minute';
        $name_minute = $this->set_name($this->data['attr']);
        
        unset($this->data['attr']['name']); // não renderizar o name para este element
        $attrs = make_attributes($this->data['attr']);
        echo "<input type='text' value='{$value['view']}' name='{$name_view}' data-key='view' {$attrs} />";
        echo "<input type='hidden' value='{$value['iso']}' name='{$name_iso}' data-name='{$name}' data-key='iso' class='boros_form_input input_date_picker_iso' />";
        if( $this->data['options']['split_time'] === true ){
            echo "<input type='number' value='{$value['hour']}' name='{$name_hour}' data-name='{$name}' data-key='hour' min='0' max='24' maxlength='2' class='boros_form_input iptw_50' /> : <input type='number' value='{$value['minute']}' name='{$name_minute}' data-name='{$name}' data-key='minute' min='0' max='59' maxlength='2' class='boros_form_input iptw_50' step='{$this->data['options']['step_minute']}' />";
        }
		echo $this->input_helper;
		
		$input = ob_get_contents();
		ob_end_clean();
		
		return $input;
	}
}