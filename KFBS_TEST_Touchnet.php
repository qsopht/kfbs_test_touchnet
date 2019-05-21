<?php
/*
Plugin Name: KFBS Touchnet plugin - based on Boise State uPay Gravity Forms Extension for LSAMP
Description: Provides functions for use in uPay implementation for LSAMP.
Version: 1.7.7
Original Author: David Lentz, David Ferro
Updated by: Sam Sawyer
*/

defined( 'ABSPATH' ) or die( 'No hackers' );

if( ! class_exists( 'KFBS_TEST_Touchnet_Updater' ) ){
	include_once( plugin_dir_path( __FILE__ ) . 'updater.php' );
}

$updater = new KFBS_TEST_Touchnet_Updater( __FILE__ );
$updater->set_username( 'qsopht' );
$updater->set_repository( 'kfbs_test_touchnet' );
$updater->initialize();


	function receive_touchnet_data( $atts ) {
		//shortcode may not need attributes
		//UPAY_SITE_ID=61&EXT_TRANS_ID=12345
		$transid = $_REQUEST['EXT_TRANS_ID'];
		$tpg_trans_id = $_REQUEST['tpg_trans_id'];
		$sys_tracking_id = $_REQUEST['sys_tracking_id'];
		$upaysite = $_REQUEST['UPAY_SITE_ID'];
		$post_id = wp_insert_post(array (
			'post_type' => 'touchnet-log',
			'post_title' => 'Posted from TouchNet',
			//'post_content' => 'Transaction ID: ' . $transid . ' - sys_tracking_id: ' . $sys_tracking_id . '.',
			'post_content' => print_r($_REQUEST, true),
			'post_status' => 'publish',
			'comment_status' => 'closed',   // if you prefer
			'ping_status' => 'closed',      // if you prefer
		 ));
		 echo $post_id;
	}
	// This is triggered when the shortcode 'UPAYFORM' is added to a page in WordPress.
	// Writes a form full of hidden fields and auto-submits it (via javascript).
	// If javascript isn't available, displays the form in the browser with a submit 
	// button so the user can click to continue.
	function createForm_KFBS_TEST( $atts ) {
	
		$discount_code = $_REQUEST['discountcode'];
        $seminar_post_id = $_REQUEST['seminar_id'];

		//echo '<br/>discountcode = ' . $discount_code.'<br/>';

		// $args = array(
		// 	'post_type'  => 'discount-code',
		// 	'meta_query' => array(
        //                     array (
		// 		'key'     => 'code',
        //                         'type' => 'CHAR',
		// 		'value'   => $discount_code,
		// 		'compare' => 'LIKE',
		// 	), 
        //             ),
		// );

		$args = array(
			'post_type'  => 'discount-code'
		);

		$query = new WP_Query( $args );

		$found_post = false;
		$discount_amt = 0;

		if ( $query->have_posts() ) 
		{
			while ( $query->have_posts() ) 
				{
				$query->the_post();
				if ( get_post_meta( $query->post->ID, 'wpcf-code', true ) == $discount_code )
				{
					$found_post = true;
					//echo 'in a post: '.$query->post->ID;
					//echo '<br/>discount amt: '. get_post_meta($query->post->ID, 'wpcf-discountamount', true) . '<Br/>';
					//echo 'valid through: ' . get_post_meta($query->post->ID, 'wpcf-discountvalidthrough', true) ;
					$discount_amt = get_post_meta($query->post->ID, 'wpcf-discountamount', true);
				}
			}
			
			/* Restore original Post Data */
			wp_reset_postdata();
			} else {
				echo 'no posts<br/>';
			}

		if (!$found_post)
		{
			echo 'Your discount code is invalid. <a href="javascript:window.history.back();">Go back to try again.</a><br/><br/>';
		}


		$seminar_post = get_post( $seminar_post_id );
		$seminar_cost = get_post_meta( $seminar_post_id, 'wpcf-seminarcost', true );

		//echo '<br/>seminar: ' . $seminar_post_id;
		echo '<br/>Seminar cost: ' . $seminar_cost;
		echo '<br/>Seminar discount: ' . $discount_amt;
		

//$query = new WP_Query( array( 's' => 'announcement' ) );

//print_r( $args );
//echo $query;
//echo 'has post? '.$query->have_posts();

		//$post = $query->the_post();

//echo "post<br/>";
//echo $post.'<br/>';
//echo '<br/>post id: '.$query->post->ID.'<br/>';

		//$discount_amt = get_post_meta( $post->ID, 'discountamount', true );

		
		//echo '<br/>discountamount = ' . $discount_amt;

		// $post = get_posts(array(
		// 	'numberposts'	=> 1,
		// 	'post_type'		=> 'discount-code',
		// 	'meta_key'		=> 'code',
		// 	'meta_value'	=> $discount_code
		// ));

		// $args = array(
		// 	'post_type' => 'discount-code',
		// 	'meta_query' => 
		// 		array(
		// 			'key' => 'code',
		// 			'value' => $discount_code,
		// 			'compare' => 'LIKE'
		// 	)
		// );
		// query_posts( $args ); while ( have_posts() ) : the_post();


		// Parse the parameters sent in the shortcode (if any). Allows us to set values 
		// in WordPress rather than here in the code.
		$attributes = shortcode_atts( array(
			'upay_site_id' => -1, 
			'passed_amount_validation_key' => '', 
			'upay_url' => 'https://secure.touchnet.com:8443/C21551test_upay/web/index.jsp'
		), $atts );

		// Need to calculate amount based on what was submitted. This may change 
		// with each implementation.
		// CHANGE: the name of the element in the $_GET array will changed depending on 
		// how the GravityForm was created. Look there for the correct field ID and change
		// here as necessary.
		
		$amt = 0;

		$amt = $seminar_cost;
        $amt = $amt - $discount_amt;

		$VALIDATION_KEY = createValidationKey( $attributes[ 'passed_amount_validation_key' ], $_REQUEST['TRANSID'], $amt );

		$formString = '<form id="upay" name="upay" action="' . $attributes[ 'upay_url' ] . '" method="post">';
		$formString .= '<input type="hidden" name="UPAY_SITE_ID" VALUE="' . $attributes[ 'upay_site_id' ] . '" />';
		$formString .= '<input type="hidden" name="EXT_TRANS_ID" VALUE="'. $_REQUEST['TRANSID'] .'" />';

		$formString .= '<input type="hidden" name="DISCOUNT_AMT" VALUE="'. $discount_amt .'" />';
		$formString .= '<input type="hidden" name="DISCOUNT_CODE" VALUE="'. $discount_code .'" />';

		$formString .= '<input type="hidden" name="AMT" VALUE="'. $amt .'" />';
		$formString .= '<input type="hidden" name="VALIDATION_KEY" VALUE="'. $VALIDATION_KEY .'" />';
		$formString .= '<input type="submit" value="Click here to continue" />';
		$formString .= '</form>';
		//$formString .= 'One moment please...';
		
		
		//print "<PRE>";
		//print_r($_REQUEST);
		//print "</PRE>";
		
		// Form will auto-submit. User should never see it, but will be forwarded to upay 
		// with all the data they've already posted.
		if ($found_post)
		{
			$formString .= '<script type="text/javascript">document.forms["upay"].submit();</script>';
		}		
		echo $formString;

	} // end create form
	
	// This makes the shortcode available to WP users. When they put that string on a page, 
	// the form defined in createForm_LSAMP() appears there.
	add_shortcode('UPAYFORM', 'createForm_KFBS_TEST');
	add_shortcode('TOUCHNETLANDING', 'receive_touchnet_data');

	// Populates hidden field in the GravityForm. This'll be the same value as we populate
	// in the EXT_TRANS_ID hidden field we submit to uPay.
	function bsu_populate_transid() {
		$EXT_TRANS_ID = date('mdHis') . mt_rand();
		return $EXT_TRANS_ID;
	}

	// This hook is how we get the EXT_TRANS_ID value into the "transid" 
	// field of the gravity form. (Note the string pattern gform_field_value_FIELDNAME.)
	// See https://www.gravityhelp.com/documentation/article/allow-field-to-be-populated-dynamically/
	add_filter('gform_field_value_transid', 'bsu_populate_transid');
	
	function createValidationKey ( $Passed_Amount_Validation_Key, $EXT_TRANS_ID, $amt ) {
		// $Passed_Amount_Validation_Key holds the "Passed Amount Validation Key" discussed in the Touchnet User's Guide. 
		// This value needs to be created (a unique alphanumeric string 30 characters or less).
		// Whatever value for $var is added here must be entered in uPay Site's Payment Settings page, 
		// "Passed Amount Validation Key" field.

		$VALIDATION_KEY = base64_encode(pack('H*',md5($Passed_Amount_Validation_Key.$EXT_TRANS_ID.$amt)));
		return $VALIDATION_KEY;
	}
	

?>
