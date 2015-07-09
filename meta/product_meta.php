<?php

/**
 * Adds a box to the main column on the Product edit screens.
 */
function auctioninc_ready_add_meta_box() {

	$screens = array( 'product' );

	foreach ( $screens as $screen ) {

		add_meta_box(
		'auctioninc_ready_sectionid',
		__( 'AuctionInc ShippingCalc Product Settings', 'auctioninc_ready_textdomain' ),
		'auctioninc_ready_meta_box_callback',
		$screen
		);
	}
}
add_action( 'add_meta_boxes', 'auctioninc_ready_add_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
*/
function auctioninc_ready_meta_box_callback( $post ) {

	// Default values
	//$auctioninc_settings = array();// get_option('ready_auctioninc');
	global $wpdb;
	$auctioninc_module_instances = $wpdb->get_results( "SELECT * FROM wp_toe_shipping WHERE code LIKE 'auctioninc_shipcalc%'" );
	$strip_chars = array('[', ']');
	foreach ($auctioninc_module_instances as $module_instance) {
		$auctioninc_settings = json_decode( str_replace($strip_chars, '', $module_instance->params), true );
		if(strpos($module_instance->code,'carrier') !== false) {
			$auctioninc_settings['calc_method'] = "C";
		}
		elseif(strpos($module_instance->code,'fixed_fee') !== false) {
			$auctioninc_settings['calc_method'] = "F";
			$auctioninc_settings['fixed_mode'] = "fee";
		} elseif(strpos($module_instance->code,'fixed_code') !== false) {
			$auctioninc_settings['calc_method'] = "F";
			$auctioninc_settings['fixed_mode'] = "code";
		} elseif(strpos($module_instance->code,'domestic') !== false) {
			$auctioninc_settings['calc_method'] = "CI";
		} else
			$auctioninc_settings['calc_method'] = "N";
		break;
	}

	$calc_method = get_post_meta($post->ID, 'auctioninc_calc_method', true);
	$calc_method = !empty($calc_method) ? $calc_method : $auctioninc_settings['calc_method'];

	$package = get_post_meta($post->ID, 'auctioninc_pack_method', true);
	$package = !empty($package) ? $package : $auctioninc_settings['ai_packaging'];
	$package = !empty($package) ? $package : 'T';
	
	$insurable = get_post_meta($post->ID, 'auctioninc_insurable', true);
	$insurable = !empty($insurable) ? $insurable : $auctioninc_settings['ai_insurance'];
	
	$fixed_mode = get_post_meta($post->ID, 'auctioninc_fixed_mode', true);
	$fixed_mode = !empty($fixed_mode) ? $fixed_mode : $auctioninc_settings['fixed_mode'];
	
	$fixed_code = get_post_meta($post->ID, 'auctioninc_fixed_code', true);
	$fixed_code = !empty($fixed_code) ? $fixed_code : $auctioninc_settings['ai_fixed_code'];
	
	$fixed_fee_1 = get_post_meta($post->ID, 'auctioninc_fixed_fee_1', true);
	$fixed_fee_1 = is_numeric($fixed_fee_1) ? $fixed_fee_1 : $auctioninc_settings['ai_fixed_fee1'];
	
	$fixed_fee_2 = get_post_meta($post->ID, 'auctioninc_fixed_fee_2', true);
	$fixed_fee_2 = is_numeric($fixed_fee_2) ? $fixed_fee_2 : $auctioninc_settings['ai_fixed_fee2'];
	
	echo '<a href="http://www.auctioninc.com/info/page/auctioninc_shipping_settings" target="_blank">' . __('Guide to AuctionInc Shipping Settings', 'ready_auctioninc') . '</a>';
	
	// Add an nonce field so we can check for it later.
	wp_nonce_field('auctioninc_ready_meta_box', 'auctioninc_ready_meta_box_nonce');
	
	echo '<table class="form-table meta_box">';
	
	// Calculation Methods
	$calc_methods = array(
			'' => __('-- Select -- ', 'ready_auctioninc'),
			'C' => __('Carrier Rates', 'ready_auctioninc'),
			'F' => __('Fixed Fee', 'ready_auctioninc'),
			'N' => __('Free', 'ready_auctioninc'),
			'CI' => __('Free Domestic', 'ready_auctioninc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_calc_method">' . __('Calculation Method', 'auctioninc') . '</label';
	echo '</th>';
	echo '<td>';
	echo '<select name="auctioninc_calc_method" id="auctioninc_calc_method">';
	
	foreach ($calc_methods as $k => $v) {
		$selected = $calc_method == $k ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Select base calculation method. Please consult the AuctionInc Help Guide for more information.', 'auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Fixed Mode
	$fixed_modes = array(
			'' => __('-- Select -- ', 'ready_auctioninc'),
			'code' => __('Code', 'ready_auctioninc'),
			'fee' => __('Fee', 'ready_auctioninc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="">' . __('Fixed Mode', 'auctioninc') . '</label';
	echo '</th>';
	echo '<td>';
	echo '<select name="auctioninc_fixed_mode" id="auctioninc_fixed_mode">';
	
	foreach ($fixed_modes as $k => $v) {
		$selected = $fixed_mode == $k ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '</td>';
	echo '</tr>';
	
	// Fixed Fee Code
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_fixed_code">' . __('Fixed Fee Code', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="auctioninc_fixed_code" id="auctioninc_fixed_code" value="' . esc_attr($fixed_code) . '">';
	echo '<p class="description">' . __('Enter your AuctionInc-configured fixed fee code.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Fixed Fee 1
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_fixed_fee_1">' . __('Fixed Fee 1', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="auctioninc_fixed_fee_1" id="auctioninc_fixed_fee_1" value="' . esc_attr($fixed_fee_1) . '" placeholder="0.00">';
	echo '<p class="description">' . __('Enter fee for first item.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Fixed Fee 2
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_fixed_fee_2">' . __('Fixed Fee 2', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="auctioninc_fixed_fee_2" id="auctioninc_fixed_fee_2" value="' . esc_attr($fixed_fee_2) . '" placeholder="0.00">';
	echo '<p class="description">' . __('Enter fee for additional items and quantities.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Package
	$pack_methods = array(
			'' => __('-- Select -- ', 'ready_auctioninc'),
			'T' => __('Together', 'ready_auctioninc'),
			'S' => __('Separately', 'ready_auctioninc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_pack_method">' . __('Package', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<select name="auctioninc_pack_method" id="auctioninc_pack_method">';
	
	foreach ($pack_methods as $k => $v) {
		$selected = $package == $k ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Select "Together" for items that can be packed in the same box with other items from the same origin.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Insurable
	$checked = $insurable == 1 ? 'checked' : '';
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_package">' . __('Insurable', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="checkbox" name="auctioninc_insurable" id="auctioninc_insurable" value="1" ' . $checked . '>';
	echo __('Enable Insurance');
	echo '<p class="description">' . __('Include product value for insurance calculation based on AuctionInc settings.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Origin Code
	$origin_code = get_post_meta(get_the_ID(), 'auctioninc_origin_code', true);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_origin_code">' . __('Origin Code', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="auctioninc_origin_code" id="auctioninc_origin_code" value="' . esc_attr($origin_code) . '">';
	echo '<p class="description">' . __('If item is not shipped from your default AuctionInc location, enter your AuctionInc origin code here.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Supplemental Item Handling Mode
	$supp_handling_mode = get_post_meta(get_the_ID(), 'auctioninc_supp_handling_mode', true);
	
	$supp_handling_modes = array(
			'' => __('-- Select -- ', 'ready_auctioninc'),
			'code' => __('Code', 'ready_auctioninc'),
			'fee' => __('Fee', 'ready_auctioninc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_supp_handling_mode">' . __('Supplemental Item Handling Mode', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<select name="auctioninc_supp_handling_mode" id="auctioninc_supp_handling_mode">';
	
	foreach ($supp_handling_modes as $k => $v) {
		$selected = $supp_handling_mode == $k ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Supplements your AuctionInc-configured package and order handling for this item.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Supplemental Handling Code
	$supp_handling_code = get_post_meta(get_the_ID(), 'auctioninc_supp_handling_code', true);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_supp_handling_code">' . __('Supplemental Item Handling Code', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="auctioninc_supp_handling_code" id="auctioninc_supp_handling_code" value="' . esc_attr($supp_handling_code) . '">';
	echo '<p class="description">' . __('Enter your AuctionInc-configured Supplemental Handling Code.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// Supplemental Item Handling Fee
	$supp_handling_fee = get_post_meta(get_the_ID(), 'auctioninc_supp_handling_fee', true);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_supp_handling_fee">' . __('Supplemental Item Handling Fee', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<input type="text" name="auctioninc_supp_handling_fee" id="auctioninc_supp_handling_fee" value="' . esc_attr($supp_handling_fee) . '" placeholder="0.00">';
	echo '</td>';
	echo '</tr>';
	
	// On-Demand Service Codes
	$selected_ondemand_codes = get_post_meta(get_the_ID(), 'auctioninc_ondemand_codes', true);
	
	$ondemand_codes = array(
			'DHLWPE' => __('DHL Worldwide Priority Express', 'ready_auctioninc'),
			'DHL9AM' => __('DHL Express 9 A.M.', 'ready_auctioninc'),
			'DHL10AM' => __('DHL Express 10:30 A.M.', 'ready_auctioninc'),
			'DHL12PM' => __('DHL Express 12 P.M.', 'ready_auctioninc'),
			'DHLES' => __('DHL Domestic Economy Select', 'ready_auctioninc'),
			'DHLEXA' => __('DHL Domestic Express 9 A.M.', 'ready_auctioninc'),
			'DHLEXM' => __('DHL Domestic Express 10:30 A.M.', 'ready_auctioninc'),
			'DHLEXP' => __('DHL Domestic Express 12 P.M.', 'ready_auctioninc'),
			'DHLDE' => __('DHL Domestic Express 6 P.M.', 'ready_auctioninc'),
			'FDX2D' => __('FedEx 2 Day', 'ready_auctioninc'),
			'FDX2DAM' => __('FedEx 2 Day AM', 'ready_auctioninc'),
			'FDXES' => __('FedEx Express Saver', 'ready_auctioninc'),
			'FDXFO' => __('FedEx First Overnight', 'ready_auctioninc'),
			'FDXPO' => __('FedEx Priority Overnight', 'ready_auctioninc'),
			'FDXPOS' => __('FedEx Priority Overnight Saturday Delivery', 'ready_auctioninc'),
			'FDXSO' => __('FedEx Standard Overnight', 'ready_auctioninc'),
			'FDXGND' => __('FedEx Ground', 'ready_auctioninc'),
			'FDXHD' => __('FedEx Home Delivery', 'ready_auctioninc'),
			'FDXIGND' => __('FedEx International Ground', 'ready_auctioninc'),
			'FDXIE' => __('FedEx International Economy', 'ready_auctioninc'),
			'FDXIF' => __('FedEx International First', 'ready_auctioninc'),
			'FDXIP' => __('FedEx International Priority', 'ready_auctioninc'),
			'UPSNDA' => __('UPS Next Day Air', 'ready_auctioninc'),
			'UPSNDE' => __('UPS Next Day Air Early AM', 'ready_auctioninc'),
			'UPSNDAS' => __('UPS Next Day Air Saturday Delivery', 'ready_auctioninc'),
			'UPSNDS' => __('UPS Next Day Air Saver', 'ready_auctioninc'),
			'UPS2DE' => __('UPS 2 Day Air AM', 'ready_auctioninc'),
			'UPS2ND' => __('UPS 2nd Day Air', 'ready_auctioninc'),
			'UPS3DS' => __('UPS 3 Day Select', 'ready_auctioninc'),
			'UPSGND' => __('UPS Ground', 'ready_auctioninc'),
			'UPSCAN' => __('UPS Standard', 'ready_auctioninc'),
			'UPSWEX' => __('UPS Worldwide Express', 'ready_auctioninc'),
			'UPSWSV' => __('UPS Worldwide Saver', 'ready_auctioninc'),
			'UPSWEP' => __('UPS Worldwide Expedited', 'ready_auctioninc'),
			'USPFC' => __('USPS First-Class Mail', 'ready_auctioninc'),
			'USPEXP' => __('USPS Priority Express', 'ready_auctioninc'),
			'USPLIB' => __('USPS Library', 'ready_auctioninc'),
			'USPMM' => __('USPS Media Mail', 'ready_auctioninc'),
			'USPPM' => __('USPS Priority', 'ready_auctioninc'),
			'USPPP' => __('USPS Standard Post', 'ready_auctioninc'),
			'USPFCI' => __('USPS First Class International', 'ready_auctioninc'),
			'USPPMI' => __('USPS Priority Mail International', 'ready_auctioninc'),
			'USPEMI' => __('USPS Priority Express Mail International', 'ready_auctioninc'),
			'USPGXG' => __('USPS Global Express Guaranteed', 'ready_auctioninc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_ondemand_codes">' . __('On-Demand Service Codes', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<select name="auctioninc_ondemand_codes[]" id="auctioninc_ondemand_codes" multiple>';
	
	foreach ($ondemand_codes as $k => $v) {
		$selected = in_array($k, $selected_ondemand_codes) ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Select any AuctionInc configured on-demand services for which this item is eligible. Hold [Ctrl] key for multiple selections.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	// On-Demand Service Codes
	$selected_access_fees = get_post_meta(get_the_ID(), 'auctioninc_access_fees', true);
	
	$access_fees = array(
			'AddlHandling' => __('Additional Handling Charge, All Carriers', 'ready_auctioninc'),
			'AddlHandlingUPS' => __('Additional Handling Charge, UPS', 'ready_auctioninc'),
			'AddlHandlingDHL' => __('Additional Handling Charge, DHL', 'ready_auctioninc'),
			'AddlHandlingFDX' => __('Additional Handling Charge, FedEx', 'ready_auctioninc'),
			'Hazard' => __('Hazardous Charge, All Carriers', 'ready_auctioninc'),
			'HazardUPS' => __('Hazardous Charge, UPS', 'ready_auctioninc'),
			'HazardDHL' => __('Hazardous Charge, DHL', 'ready_auctioninc'),
			'HazardFDX' => __('Hazardous Charge, FedEx', 'ready_auctioninc'),
			'SignatureReq' => __('Signature Required Charge, All Carriers', 'ready_auctioninc'),
			'SignatureReqUPS' => __('Signature Required Charge, UPS', 'ready_auctioninc'),
			'SignatureReqDHL' => __('Signature Required Charge, DHL', 'ready_auctioninc'),
			'SignatureReqFDX' => __('(Indirect) Signature Required  Charge, FedEx', 'ready_auctioninc'),
			'SignatureReqUSP' => __('Signature Required Charge, USPS', 'ready_auctioninc'),
			'UPSAdultSignature' => __('Adult Signature Required Charge, UPS', 'ready_auctioninc'),
			'DHLAdultSignature' => __('Adult Signature Required Charge, DHL', 'ready_auctioninc'),
			'FDXAdultSignature' => __('Adult Signature Required Charge, FedEx', 'ready_auctioninc'),
			'DHLPrefSignature' => __('Signature Preferred Charge, DHL', 'ready_auctioninc'),
			'FDXDirectSignature' => __('(Direct) Signature Required  Charge, FedEx', 'ready_auctioninc'),
			'FDXHomeCertain' => __('Home Date Certain Charge, FedEx Home Delivery', 'ready_auctioninc'),
			'FDXHomeEvening' => __('Home Date Evening Charge, FedEx Home Delivery', 'ready_auctioninc'),
			'FDXHomeAppmnt' => __('Home Appmt. Delivery Charge, FedEx Home Delivery', 'ready_auctioninc'),
			'Pod' => __('Proof of Delivery Charge, All Carriers', 'ready_auctioninc'),
			'PodUPS' => __('Proof of Delivery Charge, UPS', 'ready_auctioninc'),
			'PodDHL' => __('Proof of Delivery Charge, DHL', 'ready_auctioninc'),
			'PodFDX' => __('Proof of Delivery Charge, FedEx', 'ready_auctioninc'),
			'PodUSP' => __('Proof of Delivery Charge, USPS', 'ready_auctioninc'),
			'UPSDelivery' => __('Delivery Confirmation Charge, UPS', 'ready_auctioninc'),
			'USPCertified' => __('Certified Delivery Charge, USPS', 'ready_auctioninc'),
			'USPRestricted' => __('Restricted Delivery Charge, USPS', 'ready_auctioninc'),
			'USPDelivery' => __('Delivery Confirmation Charge, USPS', 'ready_auctioninc'),
			'USPReturn' => __('Return Receipt Charge, USPS', 'ready_auctioninc'),
			'USPReturnMerchandise' => __('Return Receipt for Merchandise Charge, USPS', 'ready_auctioninc'),
			'USPRegistered' => __('Registered Mail Charge, USPS', 'ready_auctioninc'),
			'IrregularUSP' => __('Irregular Package Discount,USPS', 'ready_auctioninc')
	);
	
	echo '<tr>';
	echo '<th>';
	echo '<label for="auctioninc_access_fees">' . __('Special Accessorial Fees', 'ready_auctioninc') . '</label>';
	echo '</th>';
	echo '<td>';
	echo '<select name="auctioninc_access_fees[]" id="auctioninc_access_fees" multiple>';
	
	foreach ($access_fees as $k => $v) {
		$selected = in_array($k, $selected_access_fees) ? 'selected' : '';
		echo '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
	}
	
	echo '</select>';
	echo '<p class="description">' . __('Add preferred special carrier fees. Hold [Ctrl] key for multiple selections.', 'ready_auctioninc') . '</p>';
	echo '</td>';
	echo '</tr>';
	
	echo '</table>';	
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function auctioninc_ready_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	* because the save_post action can be triggered at other times.
	*/

	// Check if our nonce is set.
	if ( ! isset( $_POST['auctioninc_ready_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['auctioninc_ready_meta_box_nonce'], 'auctioninc_ready_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */

	$calc_method = sanitize_text_field($_POST['auctioninc_calc_method']);
	update_post_meta($post_id, 'auctioninc_calc_method', $calc_method);
	
	$fixed_mode = sanitize_text_field($_POST['auctioninc_fixed_mode']);
	update_post_meta($post_id, 'auctioninc_fixed_mode', $fixed_mode);

	$fixed_code = sanitize_text_field($_POST['auctioninc_fixed_code']);
	update_post_meta($post_id, 'auctioninc_fixed_code', $fixed_code);
	
	$fixed_fee_1 = floatval($_POST['auctioninc_fixed_fee_1']);
	update_post_meta($post_id, 'auctioninc_fixed_fee_1', $fixed_fee_1);
	
	$fixed_fee_2 = floatval($_POST['auctioninc_fixed_fee_2']);
	update_post_meta($post_id, 'auctioninc_fixed_fee_2', $fixed_fee_2);
	
	$package = sanitize_text_field($_POST['auctioninc_pack_method']);
	update_post_meta($post_id, 'auctioninc_pack_method', $package);
	
	$insurable = sanitize_text_field($_POST['auctioninc_insurable']);
	update_post_meta($post_id, 'auctioninc_insurable', $insurable);
	
	$origin_code = sanitize_text_field($_POST['auctioninc_origin_code']);
	update_post_meta($post_id, 'auctioninc_origin_code', $origin_code);
	
	$supp_handling_mode = sanitize_text_field($_POST['auctioninc_supp_handling_mode']);
	update_post_meta($post_id, 'auctioninc_supp_handling_mode', $supp_handling_mode);
	
	$supp_handling_code = sanitize_text_field($_POST['auctioninc_supp_handling_code']);
	update_post_meta($post_id, 'auctioninc_supp_handling_code', $supp_handling_code);
	
	$supp_handling_fee = sanitize_text_field($_POST['auctioninc_supp_handling_fee']);
	update_post_meta($post_id, 'auctioninc_supp_handling_fee', $supp_handling_fee);
	
	$ondemand_codes_dirty = $_POST['auctioninc_ondemand_codes'];
	$ondemand_codes = is_array($ondemand_codes_dirty) ? array_map('sanitize_text_field', $ondemand_codes_dirty) : sanitize_text_field($ondemand_codes_dirty);
	update_post_meta($post_id, 'auctioninc_ondemand_codes', $ondemand_codes);

	$access_fees_dirty = $_POST['auctioninc_access_fees'];
	$access_fees = is_array($access_fees_dirty) ? array_map('sanitize_text_field', $access_fees_dirty) : sanitize_text_field($access_fees_dirty);
	update_post_meta($post_id, 'auctioninc_access_fees', $access_fees);
}
add_action( 'save_post', 'auctioninc_ready_save_meta_box_data' );
