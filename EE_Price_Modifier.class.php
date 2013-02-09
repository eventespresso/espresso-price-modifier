<?php
/*
  Plugin Name: Event Espresso - Price Modifier
  Plugin URI: http://eventespresso.com/
  Description: Modify the event fees that are charged by adding price modifiers to form questions

  Version: 0.0.1

  Author: Event Espresso
  Author URI: http://www.eventespresso.com

  Copyright (c) 2012 Event Espresso  All Rights Reserved.

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

 */
register_activation_hook( __FILE__, array( 'EE_Price_Modifier', 'activate_price_modifier' ));
add_action( 'plugins_loaded', array( 'EE_Price_Modifier', 'instance' ));		
/**
 * ------------------------------------------------------------------------
 *
 * EE_Price_Modifier class
 *
 * @package				Multi Event Registration
 * @subpackage		espresso-multi-registration
 * @author					Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
class EE_Price_Modifier {
	
	// was activation successfull?
	private static $_installed = FALSE;
	
	// instance of the EE_Price_Modifier object
	private static $_instance = NULL;
	
	// price_mod version
	private static $_version = '0.0.1';
	
	// price_mod version
	public $plugin_name = 'EE_Price_Modifier';
	// global org_options
	public $_org_options;





	/**
	*	@singleton method used to instantiate class object
	*	@access public
	*	@return class instance
	*/	
	public static function instance ( ) {
		if ( defined( 'EVENT_ESPRESSO_VERSION' ) && self::$_installed ) {				
			// check if class object is instantiated
			if ( self::$_instance === NULL  or ! is_object( self::$_instance ) or ! is_a( self::$_instance, __CLASS__ )) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
	}





	/**
	*	private constructor to prevent direct creation
	*	@Constructor
	*	@access private
	*	@return void
	*/	
	private function __construct() {

		define( 'PRICE_MOD_DIR_PATH', plugin_dir_path( __FILE__ ) );
		define( 'PRICE_MOD_DIR_URL', plugin_dir_url( __FILE__ ) );	

		// set PRICE_MOD active to TRUE
		add_filter( 'filter_hook_espresso_price_mod_active', '__return_true' );

		if ( is_admin() ) {
			//$this->_price_mod_admin();	
			add_action( 'action_hook_espresso_new_question_price_mod_tr', array( $this, '_generate_form_inputs' ), 10, 2 );
			add_filter( 'filter_hook_espresso_parse_question_response_for_price', array( $this, '_parse_admin_question_response_for_price' ), 10, 3 );
			add_filter( 'filter_hook_espresso_parse_admin_question_response_for_price', array( $this, '_parse_admin_question_response_for_price' ), 10, 2 );
			add_filter( 'filter_hook_espresso_parse_question_answer_for_price', array( $this, 'espresso_parse_question_answer_for_price' ), 10, 2 );
		} else {
			//$this->_price_mod_frontend();
			add_filter( 'filter_hook_espresso_parse_form_value_for_price', 'espresso_parse_form_value_for_price', 10, 2 );
		}	
		
	}




	
	/**
	*	returns EE_Price_Modifier version
	* 
	*	@access 		public
	*	@return 		string
	*/		
	public static function version() {
		return self::$_version;
	}





	/**
	*	_generate_form_inputs
	* 
	*	@access 		public
	*	@param 		array		$values
	*	@param 		boolean	$price_mod
	*	@return 		string
	*/	
	public function _generate_form_inputs ( $values, $price_mod = 'N' ) {
		ob_start();
		?>
		<tr id="add-price-modifier">
			<th> <label for="price_mod">
					<?php _e('Modifies Event Price', 'event_espresso');?>
				</label>
			</th>
			<td><?php echo select_input( 'price_mod', $values, $price_mod ); ?> <span class="description">
				<?php _e('If this is set to "Yes", then you can add price modifers to the answer options you entered above.', 'event_espresso');?>
				<br />
				<?php _e('Price modifiers will then be added (or subtracted if negative) from the ticket price for that event.', 'event_espresso');?>
				<br />
				<?php _e('Enter price modifiers with a pipe | ( Shift+\ ) separating prices from the answer options.', 'event_espresso');?>
				<br />
				<?php _e('Eg. If the question was "Choice of Dinner Entrée", answer options might be: " steak|39.95, chicken|34.95, vegan|29.95 "', 'event_espresso'); ?>
				</span></td>
		</tr>
		<?php
		$content = ob_get_clean();
		return $content;
	}





	/**
	*	_parse_admin_question_response_for_price
	* 
	*	@access 		public
	*	@param 		array		$value
	*	@param 		boolean	$price_mod
	*	@return 		string
	*/	
	public function _parse_admin_question_response_for_price( $value = '', $price_mod = 'N' ) {
		if ( $price_mod == 'Y' ) {
			global $org_options;
			$values = explode( '|', $value );
			$price = number_format( (float)$values[1], 2, '.', ',' );
			$plus_or_minus = $price > 0 ? '+' : '-';
			$price_mod = $price > 0 ? $price : $price * (-1);
			$value = $values[0] . '&nbsp;[' . $plus_or_minus . $org_options['currency_symbol'] . $price_mod . ']';	
		}
		return $value;
	}





	/**
	*	_parse_admin_question_response_for_price
	* 
	*	@access 		public
	*	@param 		array		$value
	*	@param 		boolean	$price_mod
	*	@param 		boolean	$price_mod
	*	@return 		string
	*/	
	public function _parse_question_response_for_price( $value = '', $price_mod = 'N', $attendee_id = FALSE ) {
		if ( $price_mod == 'Y' ) {
			global $org_options;
			$values = explode( '|', $value );
			$price = number_format( (float)$values[1], 2, '.', ',' );
			$plus_or_minus = $price > 0 ? '+' : '-';
			$price_mod = $price > 0 ? $price : $price * (-1);
			$value = $values[0] . '&nbsp;[' . $plus_or_minus . $org_options['currency_symbol'] . $price_mod . ']';				

			if ( $price != 0 ) {
				if ( ! $attendee_id ) {
					echo __('An error occured. The ticket price could not be modified because an attendee id was not received.', 'event_espresso');
				}
				global $wpdb;
				$SQL = 'UPDATE '. EVENTS_ATTENDEE_TABLE .' SET final_price = final_price ' . $plus_or_minus . ' %f where id = %d';	
				$wpdb->query( $wpdb->prepare( $SQL, $price, $attendee_id ));
				//echo '<h4>LQ : ' . $wpdb->last_query . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
			}			
		}
		return $value;
	}





	/**
	*	_parse_form_value_for_price
	* 
	*	@access 		public
	*	@param 		array		$value
	*	@param 		boolean	$price_mod
	*	@return 		string
	*/	
	public function _parse_form_value_for_price( $value = '', $price_mod ) {
		if ( $price_mod == 'Y' ) {
			global $org_options;
			$values = explode( '|', $value );
			$add_or_sub = $values[1] > 0 ? __('add','event_espresso') : __('subtract','event_espresso');
			$price_mod = $values[1] > 0 ? $values[1] : $values[1] * (-1);
			$value = $values[0] . '<span>&nbsp;[' . $add_or_sub . '&nbsp;'  . $org_options['currency_symbol'] . $price_mod . ']</span>';		
		}
		return $value;
	}





	/**
	*	_parse_question_answer_for_price
	* 
	*	@access 		public
	*	@param 		array		$value
	*	@param 		boolean	$price_mod
	*	@return 		string
	*/	
	public function _parse_question_answer_for_price( $value = '', $price_mod = 'N' ) {
		if ( $price_mod == 'Y' ) {
			global $org_options;
			$values = explode( '|', $value );
			$price = number_format( (float)$values[1], 2, '.', ',' );
			$plus_or_minus = $price > 0 ? '+' : '-';
			$price_mod = $price > 0 ? $price : $price * (-1);
			$find = array( '&#039;', "\xC2\xA0", "\x20", "&#160;", '&nbsp;' );
			$replace = array( "'", ' ', ' ', ' ', ' '  );
			$text = trim( stripslashes( str_replace( $find, $replace, $values[0] )));
			$text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			$value =  $text . ' [' . $plus_or_minus . $org_options['currency_symbol'] . $price_mod . ']';
		}
		return $value;
	}


	// modified files in 3.1 core :
	// includes/admin-files/form-builder/questions/new_question.php
	// includes/form-builder/questions/edit_question.php











	/**
	*		activate EE_Price_Modifier
	* 
	*		@access 		public
	*		@return 		void
	*/	
	public function activate_price_modifier() {


//		$mer_options = array();
//		$templates = array();
//		$templates['event_queue'] =  plugin_dir_path( __FILE__ ) . 'templates/event_queue.template.php';
//		$templates['view_event_queue_btn'] =  plugin_dir_path( __FILE__ ) . 'templates/view_event_queue_btn.template.php';	
//		$mer_options['templates'] = $templates;
//		update_option( 'mer_options', $mer_options ); 	
		if ( file_exists( EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/functions/database_install.php' )) {
			require_once( EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/functions/database_install.php' );		
			$sql = "id int(11) unsigned NOT NULL auto_increment,
					sequence INT(11) NOT NULL default '0',
					question_type enum('TEXT','TEXTAREA','MULTIPLE','SINGLE','DROPDOWN') NOT NULL default 'TEXT',
					question text NOT NULL,
					system_name varchar(15) DEFAULT NULL,
					response text NULL,
					required ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N',
					required_text text NULL,
					price_mod ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N',
					price_mod_limits text NULL,
					price_mod_availalbe text NULL,
					admin_only ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N',
					wp_user int(22) DEFAULT '1',
					PRIMARY KEY  (id),
					KEY wp_user (wp_user),
					KEY system_name (system_name),
					KEY admin_only (admin_only)";
			event_espresso_run_install( 'events_question', EVENT_ESPRESSO_VERSION, $sql );
			self::$_installed = TRUE;
		}

	}
	//register_activation_hook(__FILE__, 'espresso_activate_price_modifier' );

}


/**
*		run EE_Price_Modifier
* 
*		@access 		public
*		@return 		array
*/	
//function espresso_run_price_modifier() {
//	// create global var
////	global $EE_Price_Modifier;
////	// instantiate !!!	
////	$EE_MER = EE_Price_Modifier::instance();
//	EE_Price_Modifier::instance();
//}		
//add_action( 'plugins_loaded', 'espresso_run_price_modifier' );		










