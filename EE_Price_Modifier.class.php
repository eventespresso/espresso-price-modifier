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
 * @package				Event Espresso
 * @subpackage		espresso- price-modifier
 * @author					Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
class EE_Price_Modifier {
	
	// instance of the EE_Price_Modifier object
	private static $_instance = NULL;
	
	// price_mod version
	private static $_version = '0.0.1';	





	/**
	*	@singleton method used to instantiate class object
	*	@access public
	*	@return class instance
	*/	
	public static function instance ( ) {
		if ( defined( 'EVENT_ESPRESSO_VERSION' )) {				
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

//		if ( is_admin() ) {
			//$this->_price_mod_admin();	
			add_action( 'action_hook_espresso_generate_price_mod_form_inputs', array( $this, 'generate_price_mod_form_inputs' ), 10, 2 );
			add_filter( 'filter_hook_espresso_form_question_response', array( $this, 'parse_question_response_for_price' ), 10, 3 );
			add_filter( 'filter_hook_espresso_admin_question_response', array( $this, 'parse_admin_question_response_for_price' ), 10, 2 );
			add_filter( 'filter_hook_espresso_parse_question_answer_for_price', array( $this, 'parse_question_answer_for_price' ), 10, 2 );
			add_filter( 'filter_hook_espresso_question_cols_and_values', array( $this, 'insert_update_question_cols_and_values' ), 10, 2 );
			
//		} else {
			//$this->_price_mod_frontend();
			add_filter( 'filter_hook_espresso_form_question', array( $this, 'parse_form_question_for_price_mods' ), 10, 2 );
			add_filter( 'filter_hook_espresso_question_formatted_value', array( $this, 'parse_form_value_for_price_mod' ), 10, 2 );
//		}	
		
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
	*	generate_price_mod_form_inputs
	* 
	*	@access 		public
	*	@param 		array		$values
	*	@param 		boolean	$question
	*	@return 		string
	*/	
	public function generate_price_mod_form_inputs ( $values, $question = FALSE ) {
		
		$price_mod = isset( $question->price_mod ) ? $question->price_mod : 'N';
		$price_mod_qty = isset( $question->price_mod_qty ) ? $question->price_mod_qty : '';
		$price_mod_sold = isset( $question->price_mod_sold ) ? $question->price_mod_sold : '';		
		
		?>

		<tr id="add-price-modifier">
			<th>
			   	<label for="price_mod">
					<?php _e('Modifies Event Price', 'event_espresso');?>
				</label>
			</th>
			<td>
			   	<?php echo select_input( 'price_mod', $values, $price_mod ); ?>
				<span class="description">
					<?php _e('If this is set to "Yes", then you can add price modifers to the answer options you entered above.', 'event_espresso');?>
					<br />
					<?php _e('Price modifiers will then be added (or subtracted if negative) from the ticket price for that event.', 'event_espresso');?>
					<br />
					<?php _e('Enter price modifiers with a pipe | ( Shift+\ ) separating prices from the answer options.', 'event_espresso');?>
					<br />
					<?php _e('Eg. If the question was "Choice of Dinner Entrée", answer options might be: " steak|39.95, chicken|34.95, vegan|29.95"', 'event_espresso'); ?>
				</span>
			</td>
		</tr>

		<tr id="set-price-mod-qty">
		   	<th>
		   		<label for="price_mod_qty">
		   			<?php _e('Price Modifier Max Quantities', 'event_espresso');?>
		   		</label>
		   	</th>
		   	<td>
		   		<input name="price_mod_qty" id="price-mod-limits" class="wide-text" value="<?php echo htmlspecialchars( $price_mod_qty, ENT_QUOTES, 'UTF-8' ); ?>" type="text" /><br />
		   		<span class="description">
		   			<?php _e('If you need to set a maximum limit on the quantity of price modifer items availalbe, then enter those max quantities here.', 'event_espresso');?>
		   			<br />
		   			<?php _e('If a price modifier item has reached it\'s max quantity then no more of that item will be available for purchase.', 'event_espresso');?>
		   			<br />
		   			<?php _e('Enter price modifier quantities with a pipe | ( Shift+\ ) separating quantities from the answer options which need to match those listed above.', 'event_espresso');?>
		   			<br />
		   			<?php _e('Eg. If you only had 50 Steak, 40 Chicken and 10 Vegan dinners available, then you would enter: " steak|50, chicken|40, vegan|10"', 'event_espresso'); ?>
		   		</span>
		   	</td>
		</tr>

		<tr id="set-price-mod-sold">
		   	<th>
		   		<label for="price_mod_sold">
		   			<?php _e('Price Modifiers Items Sold', 'event_espresso');?>
		   		</label>
		   	</th>
		   	<td>
		   		<input name="price_mod_sold" id="price-mod-avail" class="wide-text" value="<?php echo htmlspecialchars( $price_mod_sold, ENT_QUOTES, 'UTF-8' ); ?>" type="text" /><br />
		   		<span class="description">
		   			<?php _e('This field will track how many price modifer items have been purchased to date.', 'event_espresso');?>
		   			<br />
		   			<?php _e('This field will be adjusted automagically as items are purchased during registration, but can also be modified manually here, in a similar fashion to above where the number after the pipe represents the number of price modifer items that have been purchased.', 'event_espresso');?>
		   			<br />
		   			<?php _e('Eg. If 15 Steak Dinners, 10 Chicken and 2 Vegan dinners had been purchased so far, then this field would display: " steak|15, chicken|10, vegan|2"', 'event_espresso'); ?>
		   		</span>
		   	</td>
		</tr>
		<?php
	}





	/**
	*	insert_update_question_cols_and_values
	* 
	*	@access 		public
	*	@param 		array		$set_cols_and_values
	*	@return 		string
	*/	
	public function insert_update_question_cols_and_values( $set_cols_and_values = FALSE, $enum_values = array() ) {
		if ( is_array( $set_cols_and_values )) {
			$set_cols_and_values['price_mod'] = isset( $_POST['price_mod'] ) && isset( $enum_values[ $_POST['price_mod'] ] ) ?  $enum_values[ $_POST['price_mod'] ]  : 'N';
			// if this IS a price modifier
			if ( $set_cols_and_values['price_mod'] == 'Y' ) {
				// let's make sure we strip out any currency signs from the dollar value
				$response = array();
				// split apart answer options
				$price_mods = explode( ',', trim( $set_cols_and_values['response'], ',' ));
				foreach ( $price_mods as $price_mod ) {
					// now separate the option from the price
					$price = explode( '|', $price_mod );
					// do we have a price ?
					if ( isset( $price[1] )) {
						global $org_options;
						//  strip out any currency signs 
						$price[1] = str_replace( array( $org_options['currency_symbol'], '$' ), '', trim( $price[1] ));
						// then put it back together
						$response[] = trim( $price[0] ) . '|' . $price[1];
					}					
				}
				// if we built a new reponse then stitch it back together and use it, or use the original response
				$set_cols_and_values['response'] = !empty( $response ) ? implode( ',', $response ) : $set_cols_and_values['response'];
			}
			$set_cols_and_values['price_mod_qty'] = isset( $_POST['price_mod_qty'] ) ? sanitize_text_field( $_POST['price_mod_qty'] )  : '';
			$set_cols_and_values['price_mod_sold'] = isset( $_POST['price_mod_sold'] ) ? sanitize_text_field( $_POST['price_mod_sold'] )  : '';
			add_filter( 'filter_hook_espresso_question_data_formats', array( $this, 'insert_update_question_data_formats' ), 10, 1 );
		}
		return $set_cols_and_values;	
	}





	/**
	*	insert__update_question_data_formats
	* 
	*	@access 		public
	*	@param 		array		$data_formats
	*	@return 		string
	*/	
	public function insert_update_question_data_formats( $data_formats = FALSE ) {
		if ( is_array( $data_formats )) {
			$data_formats[] = '%s';
			$data_formats[] = '%s';
			$data_formats[] = '%s';
		}
		return 	$data_formats;
	}





	/**
	*	parse_admin_question_response_for_price
	* 
	*	@access 		public
	*	@param 		array		$value
	*	@param 		boolean	$price_mod
	*	@return 		string
	*/	
	public function parse_admin_question_response_for_price( $value = '', $price_mod = 'N' ) {
		//->price_mod
		if ( $price_mod == 'Y' ) {
			global $org_options;
			$values = explode( '|', $value );
			$price = number_format( (float)$values[1], 2, '.', ',' );
			if ( $price != 0 ) {
				$plus_or_minus = $price > 0 ? '+' : '-';
				$price_mod = $price > 0 ? $price : $price * (-1);
				$value = $values[0] . '&nbsp;[&nbsp;' . $plus_or_minus . $org_options['currency_symbol'] . $price_mod . '&nbsp;]';					
			} else {
				$value = $values[0];	
			}
		}
		return $value;
	}





	/**
	*	parse_question_response_for_price
	* 
	*	@access 		public
	*	@param 		array		$value
	*	@param 		object		$question
	*	@param 		int			$attendee_id
	*	@return 		string
	*/	
	public function parse_question_response_for_price( $value = '', $question = FALSE, $attendee_id = FALSE ) {

		$success = TRUE;
		
		$price_mod = isset( $question->price_mod ) ? $question->price_mod : 'N';

		if ( $price_mod == 'Y' ) {
			
			//printr( $question, '$question  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

			$price_mod_qty = isset( $question->price_mod_qty ) ? $question->price_mod_qty : FALSE;
			$price_mod_sold = isset( $question->price_mod_sold ) ? $question->price_mod_sold : '';

			$values = explode( '|', $value );

			$price = isset( $values[1] ) ? $values[1] : 0;

			if ( $price != 0 ) {
				
				if ( ! $attendee_id ) {
					echo __('An error occured. The ticket price could not be modified because an attendee id was not received.', 'event_espresso');
					$success = FALSE;
					return $value;
				}
				global $wpdb, $org_options;
				
				$plus_or_minus = $price > 0 ? '+' : '-';
				$price_mod = $price > 0 ? $price : $price * (-1);
				$value = $values[0] . '&nbsp;[&nbsp;' . $plus_or_minus . $org_options['currency_symbol'] . number_format( (float)$price_mod, 2, '.', ',' ) . '&nbsp;]';				

				$SQL = 'UPDATE '. EVENTS_ATTENDEE_TABLE .' SET final_price = final_price ' . $plus_or_minus . ' %f where id = %d';	
				if ( ! $wpdb->query( $wpdb->prepare( $SQL, $price, $attendee_id ))) {
					$success = FALSE;
				}
				//echo '<h4>LQ : ' . $wpdb->last_query . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';		
				
				// now let's update any price mod qtys
				if ( $price_mod_qty && $success ) {
					$new_sold = array();
					$sold_qty = 0;
					// separate all $price_mod_qtys
					$price_mod_sold = explode( ',', $price_mod_sold );
					// cycle thru them
					foreach ( $price_mod_sold as $key_sold ) {
						// now separate the price mod key from the sold qty 
						$sold = explode( '|', $key_sold );
						// does the sold qty key match the key for the submitted value ?
						if ( $sold[0] == $values[0] && ! empty( $sold[0] ) && isset( $sold[1] )) {
							// first get qty from $values[0]
							$qty_key = explode( ' ', trim( $sold[0] ));
							$sold_qty = absint( $qty_key[0] );
							// if so, then increment the amount sold
							$qty = absint( $sold[1] ) + $sold_qty;
							// then put it back together
							$new_sold[] = $sold[0] . '|' . $qty;
						} else {
							$new_sold[] = $key_sold;
						}
					}
					$new_sold = implode( ',', $new_sold );
					$wpdb->update( 
						EVENTS_QUESTION_TABLE,
						array( 'price_mod_sold' => $new_sold ),
						array( 'id' => $question->qstn_id ),
						array( '%s' ),
						array( '%d' )
					);
					
					$SQL = 'UPDATE '. EVENTS_QUESTION_TABLE .' QST SET price_mod_total = price_mod_total + %d where QST.id = %d';	
					if ( ! $wpdb->query( $wpdb->prepare( $SQL, absint( $sold_qty ), $question->qstn_id ))) {
						$success = FALSE;
					}				
				}

			} else {
				$value = $values[0];
				$success = FALSE;
			}		
		}
		return $value;
	}





	/**
	*	parse_question_answer_for_price
	* 
	*	@access 		public
	*	@param 		array		$value
	*	@param 		boolean	$price_mod
	*	@return 		string
	*/	
	public function parse_question_answer_for_price( $value = '', $price_mod = 'N' ) {
		if ( $price_mod == 'Y' ) {
			global $org_options;
			$values = explode( '|', $value );
			$price = $values[1];
			$plus_or_minus = $price > 0 ? '+' : '-';
			$price_mod = $price > 0 ? $price : $price * (-1);
			$find = array( '&#039;', "\xC2\xA0", "\x20", "&#160;", '&nbsp;' );
			$replace = array( "'", ' ', ' ', ' ', ' '  );
			$text = trim( stripslashes( str_replace( $find, $replace, $values[0] )));
			$text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			$value =  $text . ' [ ' . $plus_or_minus . $org_options['currency_symbol'] . number_format( (float)$price_mod, 2, '.', ',' ) . ' ]';
		}
		return $value;
	}





	/**
	*	parse_form_question_for_price_mods
	* 
	*	@access 		public
	*	@param 		object		$question
	*	@return 		string
	*/	
	public function parse_form_question_for_price_mods( $question = FALSE ) {

		if ( is_object( $question )) {
			
			$price_mod = isset( $question->price_mod ) ? $question->price_mod : 'N';

			if ( $price_mod == 'Y' ) {
				// grab other price mod variables
				$price_mod_qty = isset( $question->price_mod_qty ) ? $question->price_mod_qty : '';
				$price_mod_sold = isset( $question->price_mod_sold ) ? $question->price_mod_sold : '';		
				
				$items = array();
				
				// first we need to split all of the price mod variables into the separate items
				$values = explode( ',', trim( $question->response, ',' ));
				foreach ( $values as $price ) {
					$price = explode( '|', $price );
					if ( isset( $price[1] )) {
						$items[ trim( $price[0] ) ] = array( 'price' => trim( $price[1] ));
					} else {
						$items[] = $price[0]; 
					}
				}
				// now split up qtys and add to the items array
				$qtys = explode( ',', trim( $price_mod_qty, ',' ));
				foreach ( $qtys as $qty ) {
					$qty = explode( '|', $qty );
					if ( isset( $qty[1] )) {
						$items[ trim( $qty[0] ) ]['qty'] = absint( trim( $qty[1] ));
					}
				}
				// now split up sold and add to the items array
				$solds = explode( ',', trim( $price_mod_sold, ',' ));
				foreach ( $solds as $sold ) {
					$sold = explode( '|', $sold );
					if ( isset( $sold[1] )) {
						$items[ trim( $sold[0] ) ]['sold'] = absint( trim( $sold[1] ));
					}
				}
//				printr( $items, '$items  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//				$new_items = array();
				foreach ( $items as $key => $item ) {
					// is there a max qty set for this item?
					if ( isset( $item['qty'] )) {
						// if so, how many are still available ?
						$available = $item['qty'] - $item['sold'];
						// if this item is still available
						if ( $available > 0 ) {
							// add it back into the items
							//$new_items[ $key ] = $key . '|' . $item['price'];
							$items[ $key ] = $key . '|' . $item['price'];
						} else {
							// or remove it if sold out
							unset( $items[ $key ] );
						}
					} else {
						$items[ $key ] = $key . '|' . $item['price'];
					}
				}
//				printr( $new_items, '$new_items  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//				printr( $items, '$items  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
				// reset response with only available items
				$question->response = implode( ',', $items );
				//printr( $question->response, '$question->response  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );

			}			
		}

		return $question;
	}





	/**
	*	parse_form_value_for_price_mod
	* 
	*	@access 		public
	*	@param 		array		$value
	*	@param 		object		$question
	*	@return 		string
	*/	
	public function parse_form_value_for_price_mod ( $value = '', $question = FALSE ) {

		$price_mod = isset( $question->price_mod ) ? $question->price_mod : 'N';

		if ( $price_mod == 'Y' ) {
			global $org_options;
			$values = explode( '|', $value );
			$add_or_sub = $values[1] > 0 ? __('add','event_espresso') : __('subtract','event_espresso');
			$price_mod = $values[1] > 0 ? $values[1] : $values[1] * (-1);
			if ( $values[1] != 0 ) {
				$value = $values[0] . '<span>&nbsp;[&nbsp;' . $add_or_sub . '&nbsp;'  . $org_options['currency_symbol'] . $price_mod . '&nbsp;]</span>';
			} else {
				$value = $values[0];				
			}
		}
		return $value;
	}





	/**
	*		activate EE_Price_Modifier
	* 
	*		@access 		public
	*		@return 		void
	*/	
	public function activate_price_modifier() {
	
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
					price_mod_qty text NULL,
					price_mod_sold text NULL,
					price_mod_total SMALLINT(6) NOT NULL DEFAULT '0',
					admin_only ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N',
					wp_user int(22) DEFAULT '1',
					PRIMARY KEY  (id),
					KEY wp_user (wp_user),
					KEY system_name (system_name),
					KEY admin_only (admin_only)";
			event_espresso_run_install( 'events_question', EVENT_ESPRESSO_VERSION, $sql );
		}

	}

}



	// modified files in 3.1 core :
	// includes/admin-files/form-builder/questions/new_question.php
	// includes/form-builder/questions/edit_question.php

