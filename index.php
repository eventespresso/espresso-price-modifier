<?php
/*
  Plugin Name: Event Espresso - Price Modifier
  Plugin URI: http://eventespresso.com/
  Description: Tool for modifying prices using the question system.

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

function espresso_price_mod_version() {
	return '0.0.1';
}

function espresso_new_question_price_mod_tr($values, $price_mod = 'N') {
	?>

	<tr id="add-price-modifier">
		<th> <label for="price_mod">
				<?php _e('Modifies Event Price', 'event_espresso');?>
			</label>
		</th>
		<td><?php echo select_input('price_mod', $values, $price_mod); ?> <span class="description">
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
}
add_action('action_hook_espresso_new_question_price_mod_tr', 'espresso_new_question_price_mod_tr', 10, 2);


function espresso_parse_admin_question_response_for_price( $value = '', $price_mod = 'N' ) {
	echo $value;
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
add_filter('filter_hook_espresso_parse_admin_question_response_for_price', 'espresso_parse_admin_question_response_for_price', 10, 2);


function espresso_parse_question_response_for_price( $value = '', $price_mod = 'N', $attendee_id = FALSE ) {
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
//Filter doesn't work for some reason, just outputs the results to the page
//add_filter('filter_hook_espresso_parse_question_response_for_price', 'espresso_parse_admin_question_response_for_price', 10, 3);


function espresso_parse_form_value_for_price( $value = '', $price_mod ) {
	if ( $price_mod == 'Y' ) {
		global $org_options;
		$values = explode( '|', $value );
		$add_or_sub = $values[1] > 0 ? __('add','event_espresso') : __('subtract','event_espresso');
		$price_mod = $values[1] > 0 ? $values[1] : $values[1] * (-1);
		$value = $values[0] . '<span>&nbsp;[' . $add_or_sub . '&nbsp;'  . $org_options['currency_symbol'] . $price_mod . ']</span>';		
	}
	return $value;
}
add_filter('filter_hook_espresso_parse_form_value_for_price', 'espresso_parse_form_value_for_price', 10, 2);

function espresso_parse_question_answer_for_price( $value = '', $price_mod = 'N' ) {
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
add_filter('filter_hook_espresso_parse_question_answer_for_price', 'espresso_parse_question_answer_for_price', 10, 2);
