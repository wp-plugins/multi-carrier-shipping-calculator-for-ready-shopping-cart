<?php
/*
 * Required AuctionInc ShippingCalc API files
*/
if ( !class_exists('ShipRateAPI') ) {
	require_once('inc/shiprateapi/ShipRateAPI.inc');
}

class auctioninc_shipcalc extends shippingModule {
    
	protected $_enableCache = true;
	
	protected $_allowShipping = true;

    protected function _calcRate() {
    	// Global AuctionInc Settings
    	$auctioninc_settings = self::_get_auctioninc_global_settings();

    	$cart = frame::_()->getModule('user')->getModel('cart')->get();
    	
        if( !empty($cart) ) {
        	
        	$country_id = $this->_userData['shipping_address']['country']; // just an internal id
        	$country = fieldAdapter::displayCountry($country_id, 'iso_code_2'); // actual country abbreviation
        	$zipcode = $this->_userData['shipping_address']['zip'];
			
            $totalCartWeight = (float) frame::_()->getModule('user')->getModel('cart')->getCurrentWeight();
			// supported values: lb, oz, kg, g
            $readyWeightUnits = frame::_()->getModule('options')->get('weight_units');
			
			// supported values: inch, m, cm, mm
			$readySizeUnits = frame::_()->getModule('options')->get('size_units');
			// total cart sizes, not per product: $sizes['length'], $sizes['width'], $sizes['height']
			$cartSizes = frame::_()->getModule('user')->getModel('cart')->getCurrentSizes();

			// Country currency code
			//$base_currency = frame::_()->getModule('currency')->getDefault()->code;
			//$base_currency = frame::_()->getModule('currency')->getDefaultCode();

			$is_admin = (!empty($current_user->roles) && in_array('administrator', $current_user->roles)) ? true : false;
			
			if (!empty($auctioninc_settings['ai_api_key'])) {
				if (!empty($country) && !empty($zipcode)) {

					$rates = array();
					 
					// Instantiate the Shipping Rate API object
					$shipAPI = new ShipRateAPI($auctioninc_settings['ai_api_key']);
					 
					// SSL currently not supported
					$shipAPI->setSecureComm(false);
					 
					// Header reference code for Ready! Ecommerce
					$shipAPI->setHeaderRefCode('ready');
					 
					// Set base currency
					//$shipAPI->setCurrency($base_currency);
					// Ready! converts all currency fields from USD using a multiplier
					// Need to set incoming values into API to USD equivalent
					$shipAPI->setCurrency('USD');

					// Set the Detail Level (1, 2 or 3) (Default = 1)
					// DL 1:  minimum required data returned
					// DL 2:  shipping rate components included
					// DL 3:  package-level detail included
					$detailLevel = 3;
					$shipAPI->setDetailLevel($detailLevel);
					 
					// Show table of any errors for inspection
					$showErrors = true;
					 
					// Set Destination Address for this API call
					$destCountryCode = $country;
					$destPostalCode = $zipcode;
					$destStateCode = '';
					 
					// Specify residential delivery
					$delivery_type = $auctioninc_settings['ai_dest_type'] == 'Residential' ? true : false;
					 
					$shipAPI->setDestinationAddress($destCountryCode, $destPostalCode, $destStateCode, $delivery_type);
					 
					// Create an array of items to rate
					$items = array();
					 
					// Loop through package items
					foreach($cart as $inCartId => $cartitem) {
			
						// Skip digital items
						// Ready! has a built-in flag for product types values=[sell|simple], and cartitems are all of type 'sell',
						// meaning they are not 'simple' digital file downloads
						/*if ($cartitem->meta[0]['no_shipping'] == 1) {
							continue;
						}*/
						 
						// Get AuctionInc shipping fields
						$product_id = $cartitem['pid'];
						$sku = $cartitem['sku'];
						$pname = $cartitem['name'];
						
						// Calculation Method
						$calc_method = get_post_meta($product_id, 'auctioninc_calc_method', true);
						$calc_method = !empty($calc_method) ? $calc_method : $auctioninc_settings['ai_calc_method'];
						 
						// Fixed Fee Mode
						$fixed_mode = get_post_meta($product_id, 'auctioninc_fixed_mode', true);
						$fixed_mode = !empty($fixed_mode) ? $fixed_mode : $auctioninc_settings['ai_fixed_mode'];
						 
						// Fixed Fee Code
						$fixed_code = get_post_meta($product_id, 'auctioninc_fixed_code', true);
						$fixed_code = !empty($fixed_code) ? $fixed_code : $auctioninc_settings['ai_fixed_code'];
						 
						// Fixed Fee 1
						$fixed_fee_1 = get_post_meta($product_id, 'auctioninc_fixed_fee_1', true);
						$fixed_fee_1 = is_numeric($fixed_fee_1) ? $fixed_fee_1 : $auctioninc_settings['ai_fixed_fee1'];
						 
						// Fixed Fee 2
						$fixed_fee_2 = get_post_meta($product_id, 'auctioninc_fixed_fee_2', true);
						$fixed_fee_2 = is_numeric($fixed_fee_2) ? $fixed_fee_2 : $auctioninc_settings['ai_fixed_fee2'];
						 
						// Packaging Method
						$pack_method = get_post_meta($product_id, 'auctioninc_pack_method', true);
						$pack_method = !empty($pack_method) ? $pack_method : $auctioninc_settings['ai_packaging'];
						 
						// Insurable
						$insurable = get_post_meta($product_id, 'auctioninc_insurable', true);
						$insurable = !empty($insurable) ? $insurable : $auctioninc_settings['ai_insurance'];
						 
						// Origin Code
						$origin_code = get_post_meta($product_id, 'auctioninc_origin_code', true);
						 
						// Supplemental Item Handling Mode
						$supp_handling_mode = get_post_meta($product_id, 'auctioninc_supp_handling_mode', true);
						 
						// Supplemental Item Handling Code
						$supp_handling_code = get_post_meta($product_id, 'auctioninc_supp_handling_code', true);
						 
						// Supplemental Item Handling Fee
						$supp_handling_fee = get_post_meta($product_id, 'auctioninc_supp_handling_fee', true);
						 
						// On-Demand Service Codes
						$ondemand_codes = get_post_meta($product_id, 'auctioninc_ondemand_codes', true);
						 
						// Special Accessorial Fees
						$access_fees = get_post_meta($product_id, 'auctioninc_access_fees', true);
						 
						$item = array();
						 
						$item["refCode"] = $pname.'-'.$sku;
						$item["CalcMethod"] = $calc_method;
						$item["quantity"] = $cartitem['qty'];
						 
						if ($calc_method === 'C' || $calc_method === 'CI') {
							$item["packMethod"] = $pack_method;
						}
						 
						// Fixed Rate Shipping
						if ($calc_method === 'F') {
							 
							if (!empty($fixed_mode)) {
								if ($fixed_mode === 'code' && !empty($fixed_code)) {
									$item["FeeType"] = "C";
									$item["fixedFeeCode"] = $fixed_code;
								} elseif ($fixed_mode === 'fee' && is_numeric($fixed_fee_1) && (is_numeric($fixed_fee_2) || empty($fixed_fee_2))) {
									$item["FeeType"] = "F";
									$item["fixedAmt_1"] = $fixed_fee_1;
									if(empty($fixed_fee_2)) $fixed_fee_2 = 0;
									$item["fixedAmt_2"] = $fixed_fee_2;
								}
							}
						}
						 
						// Insurable
						if ($insurable == 1) {
							$item["value"] = $cartitem['price'];
						} else {
							$item["value"] = 0;
						}
						 
						// Origin Code
						if (!empty($origin_code)) {
							$item["originCode"] = $origin_code;
						}
						 
						if ($calc_method === 'C' || $calc_method === 'CI') {
							// per-product weight
							$item["weightUOM"] = $readyWeightUnits == 'oz' ? strtoupper($readyWeightUnits) : strtoupper($readyWeightUnits . 's'); // OZ, LBS, KGS
							$weight = $cartitem['weight'];
							if($readyWeightUnits != 'g') {
								$item["weight"] = $weight;
							} else {
								// convert grams to kilograms
								$item["weightUOM"] = 'KGS';
								$item["weight"] = (float)$weight/1000;
							}

							// per-product dimensions
							$pSizes = frame::_()->getModule('products')->getSizes($cartitem['pid'], $cartitem, $cartitem['qty'], $cartitem['options']);
							if($readySizeUnits != "m" && $readySizeUnits != "mm") {
								$item["length"] = $pSizes['length'];
								$item["height"] = $pSizes['height'];
								$item["width"] = $pSizes['width'];
								$item["dimUOM"] = $readySizeUnits == "cm" ? "CM" : "IN"; // IN, CM
							} else {
								if($readySizeUnits == "mm") {
									$item["length"] = (float)$pSizes['length']/10;
									$item["height"] = (float)$pSizes['height']/10;
									$item["width"] = (float)$pSizes['width']/10;
								} else { // default to meters
									$item["length"] = (float)$pSizes['length']*100;
									$item["height"] = (float)$pSizes['height']*100;
									$item["width"] = (float)$pSizes['width']*100;
								}
								$item["dimUOM"] = "CM";
							}
						}
						 
						// Supplemental Item Handling
						if (!empty($supp_handling_mode)) {
							if ($supp_handling_mode === 'code' && !empty($supp_handling_code)) {
								// Supplemental Item Handling Code
								$item["suppHandlingCode"] = $supp_handling_code;
							} elseif ($supp_handling_mode === 'fee' && !empty($supp_handling_fee)) {
								// Supplemental Item Handling Fee
								$item["suppHandlingFee"] = $supp_handling_fee;
							}
						}
						 
						// On-Demand Service Codes
						if (!empty($ondemand_codes)) {
							$codes_str = implode(", ", $ondemand_codes);
							$item["odServices"] = $codes_str;
						}
						 
						// Special Accessorial Fees
						if (!empty($access_fees)) {
							$codes_str = implode(", ", $access_fees);
							$item["specCarrierSvcs"] = $codes_str;
						}
						 
						// Add this item to Item Array
						$items[] = $item;
					}
					 
					// Debug output
					/*if (($auctioninc_settings['ai_debug'] == 1) && ($is_admin === true)) {
						echo 'DEBUG ITEM DATA<br>';
						echo '<pre>' . print_r($items, true) . '</pre>';
						echo 'END DEBUG ITEM DATA<br>';
					}*/
					 
					// Add Item Data from Item Array to API Object
					foreach ($items AS $val) {
						if ($val["CalcMethod"] == "C" || $val["CalcMethod"] == "CI") {
							 
							$shipAPI->addItemCalc($val["refCode"], $val["quantity"], $val["weight"], $val['weightUOM'], $val["length"], $val["width"], $val["height"], $val["dimUOM"], $val["value"], $val["packMethod"], 1, $val["CalcMethod"]);
							if (isset($val["originCode"]))
								$shipAPI->addItemOriginCode($val["originCode"]);
							if (isset($val["odServices"]))
								$shipAPI->addItemOnDemandServices($val["odServices"]);
							if (isset($val["suppHandlingCode"]))
								$shipAPI->addItemSuppHandlingCode($val["suppHandlingCode"]);
							if (isset($val["suppHandlingFee"]))
								$shipAPI->addItemHandlingFee($val["suppHandlingFee"]);
							if (isset($val["specCarrierSvcs"]))
								$shipAPI->addItemSpecialCarrierServices($val["specCarrierSvcs"]);
						} elseif ($val["CalcMethod"] == "F") {
							$shipAPI->addItemFixed($val["refCode"], $val["quantity"], $val["FeeType"], $val["fixedAmt_1"], $val["fixedAmt_2"], $val["fixedFeeCode"]);
						} elseif ($val["CalcMethod"] == "N") {
							$shipAPI->addItemFree($val["refCode"], $val["quantity"]);
						}
					}
					 
					// Unique identifier for cart items & destiniation
					$request_identifier = serialize($items) . $destCountryCode . $destPostalCode;
					 
					// Check for cached response
					$transient = 'ai_quote_' . md5($request_identifier);
					$cached_response = get_transient($transient);
					 
					$shipRates = array();
					 
					if ($cached_response !== false) {
						//Cached response
						$shipRates = unserialize($cached_response);
					} else {
						// New API call
						$ok = $shipAPI->GetItemShipRateSS($shipRates);
						if ($ok) {
							set_transient($transient, serialize($shipRates));
						}
					}
					 
					if (!empty($shipRates['ShipRate'])) {
						 
						// Store response in the current user's session
						// Used to retrieve package level details later
						//$_SESSION['auctioninc_response'] = $shipRates;
						 
						// Debug output
						/*if (($auctioninc_settings['ai_debug'] == 1) && ($is_admin === true)) {
							echo 'DEBUG API RESPONSE: SHIP RATES<br>';
							echo '<pre>' . print_r($shipRates, true) . '</pre>';
							echo 'END DEBUG API RESPONSE: SHIP RATES<br>';
						}*/
						 
						foreach ($shipRates['ShipRate'] as $shipRate) {
							// Add Rate
							$rates[$shipRate['ServiceName']] = (float) $shipRate['Rate'];
						}
					} else {
						 
					    /*
						if (($auctioninc_settings['ai_debug'] == 1) && ($is_admin === true)) {
							echo 'DEBUG API RESPONSE: SHIP RATES<br>';
							echo '<pre>' . print_r($shipRates, true) . '</pre>';
							echo 'END DEBUG API RESPONSE: SHIP RATES<br>';
						}
						*/
						 
						$use_fallback = false;
						 
						if (empty($shipRates['ErrorList'])) {
							$use_fallback = true;
						} else {
							foreach ($shipRates['ErrorList'] as $error) {
								// Check for proper error code
								if ($error['Message'] == 'Packaging Engine unable to determine any services to be rated') {
									$use_fallback = true;
									break;
								}
							}
						}
						 
						// Add fallback shipping rates, if applicable
						$fallback_type = $auctioninc_settings['ai_fallback_type'];
						$fallback_fee = $auctioninc_settings['ai_fallback_amount'];
						if (!empty($fallback_type) && $fallback_type != 'N' && !empty($fallback_fee) && $use_fallback == true) {
							 
							// Total cart quanitity
							$total_quantity = 0;
							foreach ($cart as $inCartId => $cartitem) {
								$total_quantity = $total_quantity + $cartitem['qty'];
							}
							 
							$cost = $fallback_type === 'O' ? $fallback_fee : $total_quantity * $fallback_fee;
							 
							$rates['Shipping'] = (float) $cost;
						} else {
							$this->pushError(lang::_('There do not seem to be any available shipping rates. Please double check your address, or contact us if you need any help.'));
						}
					}
					 
					// Return rates
					//return $rates;
					
					// default: set to cheapeast shipping option
					/*$cheapest = 999999;
					foreach($rates as $price) {
						if ($price < $cheapest) {
							$cheapest = $price;
						}
					}
					
					if($cheapest == 999999) {
						$cheapest = null;
						$this->pushError(lang::_('There do not seem to be any available shipping rates. Please double check your address, or contact us if you need any help.'));
					}*/

					//$this->setRate($cheapest);

					if(!$use_fallback) {
						$idx=0;
						usort($rates, array($this, 'sortServices'));
						switch($this->_params->servicePref) {
							case 'Cheaper':
								$idx=0;
								$this->setRate( (float) $rates[$idx] );
								break;
							case 'Expensive':
								$idx=count($rates)-1;
								$this->setRate( (float) $rates[$idx] );
								break;
							case 'Middle':
								$idx=ceil((count($rates)-1)/2);
								$this->setRate( (float) $rates[$idx] );
								break;						
							default:
								$idx=0;
								$this->setRate( (float) $rates[$idx] );
								break;	
						}				

						$this->_update_order_data($shipRates['ShipRate'][$idx]);
					}
					
					// display all shipping rates for user to choose from
					/*$opt = array();
					foreach($shipRates['ShipRate'] as $shipRate) {
						$opt['code'] = $shipRate['ServiceCode'];
						$opt['label'] = $shipRate['ServiceName'];
						$opt['price'] = $shipRate['Rate'];
						$this->_additionalOptions[] = $opt;
					}*/
					if($use_fallback && count($rates) == 1) {
						/*$opt['code'] = 'Fallback';
						$opt['label'] = 'Shipping Rate';
						$opt['price'] = $rates['Shipping'];
						$this->_additionalOptions[] = $opt;*/
						$fallback = array();
						$fallback['ServiceName'] = 'Fallback Rate';
						$fallback['Rate'] = $rates['Shipping'];
						$fallback['PackageDetail'] = null;
						$this->setRate($fallback['Rate']);
						$this->_update_order_data($fallback);
					}
					
					// if user has selected a new shipping option, update cart total
					/*if( isset($_POST['shipping_module_options']) ) {
						$selection = $_POST['shipping_module_options'];
						
						foreach($shipRates['ShipRate'] as $shipRate) {
							if($shipRate['ServiceCode'] == $selection) {
								//$this->_userData['comments'] = $shipRate['ServiceName'] . " [" . $shipRate['ServiceCode'] . " - " . $shipRate['Rate'] . "]";
								$this->setRate($shipRate['Rate']);
								$this->_update_order_data($shipRate);
								break;
							}
						}
					}*/

				} else {
					$this->pushError(lang::_('There do not seem to be any available shipping rates. Please double check your address, or contact us if you need any help.'));
				}
			} else {
            	$this->pushError(lang::_('There do not seem to be any available shipping rates. Please double check your address, or contact us if you need any help.'));
			}
        } else {
            $this->pushError(lang::_('There do not seem to be any available shipping rates. Please double check your address, or contact us if you need any help.'));
        }

    }

	public function sortServices($a, $b) {
        if($a > $b)
            return 1;
        elseif($a < $b)
            return -1;
        else
            return 0;
    }
    
    /*
     * Retrieves global admin settings for shipping module(s) from DB
     */
    protected static function _get_auctioninc_global_settings() {
    	global $wpdb;
    	
		$auctioninc_active_modules = $wpdb->get_results( "SELECT * FROM wp_toe_shipping WHERE active=1 AND code LIKE 'auctioninc_shipcalc%' ORDER BY id DESC" );
		$shipcalc_module = $wpdb->num_rows > 0 ? $auctioninc_active_modules[0] : null;
		
		$strip_chars = array('[', ']');
		$shipcalc_module_params = json_decode( str_replace($strip_chars, '', $shipcalc_module->params), true );
		
		if ($shipcalc_module->code == 'auctioninc_shipcalc_carrier') {
			// auctioninc_shipcalc_carrier
			$shipcalc_module_params['ai_calc_method'] = "C";
		} elseif ($shipcalc_module->code == 'auctioninc_shipcalc_free_domestic') {
			// auctioninc_shipcalc_free_domestic
			$shipcalc_module_params['ai_calc_method'] = "CI";
		} elseif ($shipcalc_module->code == 'auctioninc_shipcalc_free') {
			// auctioninc_shipcalc_free
			$shipcalc_module_params['ai_calc_method'] = "N";
		} else {
			// auctioninc_shipcalc_fixed_fee, auctioninc_shipcalc_fixed_code
			$shipcalc_module_params['ai_calc_method'] = "F";
			
			if ($shipcalc_module->code == 'auctioninc_shipcalc_fixed_code') {
				$shipcalc_module_params['ai_fixed_mode'] = "code";
			} else {
				$shipcalc_module_params['ai_fixed_mode'] = "fee";
			}
		} 
		
		return $shipcalc_module_params;
    }

    /*
     * Stores order data so that it can be retrieved later via WP admin
     */
    protected function _update_order_data($shipRate) {
    	global $wpdb;

    	//$shipping_meta = wpsc_get_purchase_meta($log_id, 'auctioninc_order_shipping_meta', true);
    	$shipping_meta = $shipRate;
    	
    	// need to keep track of each order's meta data in a db table
    	// for future package-level details in admin control panel
    	$uid = frame::_()->getModule('user')->getCurrentID();
    	$oid = frame::_()->getModule('order')->getCurrentID();
    	if (!is_numeric($oid) || $oid == 0) { $oid = (int)frame::_()->getModule('order')->getLastID(); $oid++; }
    	$data = "";
   	
    	$data .= '<div class="metabox-holder">';
    	$data .= '<div class="postbox">';
    	$data .= '<h3 class="hndle">' . __('AuctionInc Shipping Details', 'ready_auctioninc') . '</h3>';
    	$data .= '<div class="inside">';
    	$data .= $shipping_meta['ServiceName'] . " [" . $shipping_meta['Rate'] . "]";
    	$data .= '</div>';
    	$data .= '</div>';    	
    	$data .= '</div>';

    	$data .= '<div class="metabox-holder">';
    	$data .= '<div class="js meta-box-sortables ui-sortable">';
    	$data .= '<div id="ai_packaging" class="postbox closed">';
    	$data .= '<div class="handlediv">';
    	$data .= '<br></div>';
    	$data .= '<h3 class="hndle">' . __('AuctionInc Packaging Details', 'ready_auctioninc') . '</h3>';
    	$data .= '<div class="inside">';

    	if (!empty($shipping_meta['PackageDetail'])) {
    		$i = 1;
    		foreach ($shipping_meta['PackageDetail'] as $package) :
    			$flat_rate_code = !empty($package['FlatRateCode']) ? $package['FlatRateCode'] : __('NONE', 'ready_auctioninc');
    			$data .= "<strong>";
    			$data .= __('Package', 'ready_auctioninc') . "# {$i}";
    			$data .= "</strong><br>";
    			$data .= __('Flat Rate Code', 'ready_auctioninc') . ": $flat_rate_code<br>";
    			$data .= __('Quantity', 'ready_auctioninc') . ": {$package['Quantity']}<br>";
    			$data .= __('Pack Method', 'ready_auctioninc') . ": {$package['PackMethod']}<br>";
    			$data .= __('Origin', 'ready_auctioninc') . ": {$package['Origin']}<br>";
    			$data .= __('Declared Value', 'ready_auctioninc') . ": {$package['DeclaredValue']}<br>";
    			$data .= __('Weight', 'ready_auctioninc') . ": {$package['Weight']}<br>";
    			$data .= __('Length', 'ready_auctioninc') . ": {$package['Length']}<br>";
    			$data .= __('Width', 'ready_auctioninc') . ": {$package['Width']}<br>";
    			$data .= __('Height', 'ready_auctioninc') . ": {$package['Height']}<br>";
    			$data .= __('Oversize Code', 'ready_auctioninc') . ": {$package['OversizeCode']}<br>";
    			$data .= __('Carrier Rate', 'ready_auctioninc') . ": ".number_format($package['CarrierRate'],2)."<br>";
    			$data .= __('Fixed Rate', 'ready_auctioninc') . ": ".number_format($package['FixedRate'],2)."<br>";
    			$data .= __('Surcharge', 'ready_auctioninc') . ": ".number_format($package['Surcharge'],2)."<br>";
    			$data .= __('Fuel Surcharge', 'ready_auctioninc') . ": ".number_format($package['FuelSurcharge'],2)."<br>";
    			$data .= __('Insurance', 'ready_auctioninc') . ": ".number_format($package['Insurance'],2)."<br>";
    			$data .= __('Handling', 'ready_auctioninc') . ": ".number_format($package['Handling'],2)."<br>";
    			$data .= __('Total Rate', 'ready_auctioninc') . ": ".number_format($package['ShipRate'],2)."<br>";
    		
    			$j = 1;
    			foreach ($package['PkgItem'] as $pkg_item) :
    			    $data .= "<br><strong>";
    				$data .= __('Item', 'ready_auctioninc') . "# {$j}";
    				$data .= "</strong><br>";
    		    	$data .= __('Ref Code', 'ready_auctioninc') . ": {$pkg_item['RefCode']}<br>";
    		    	$data .= __('Quantity', 'ready_auctioninc') . ": {$pkg_item['Qty']}<br>";
    		    	$data .= __('Weight', 'ready_auctioninc') . ": {$pkg_item['Weight']}<br>";
    		    	$j++;
    			endforeach;
    			$data .= '<br><br>';
    			$i++;
    		endforeach;
  	  	}
    		    
      	$data .= '</div>';
      	$data .= '</div>';
      	$data .= '</div>';
      	$data .= '</div>';
      	
      	// write new entry to db or update the previous entry for this order
      	// wp_toe_log: id=null (A_I), type="order", data [HTML], date_created [int(11)], uid, oid
      	$date = date_create();
		$ts = date_format($date, 'U'); // Unix timestamp
      	// id=null, type='order', data='{$data}', date_created='{$ts}', uid={$uid}, oid={$oid}
      	$data_set = array( 'type' => 'order', 'data' => $data, 'date_created' => $ts, 'uid' => $uid, 'oid' => $oid );
		
		$result_set = $wpdb->get_results( "SELECT * FROM wp_toe_log WHERE oid={$oid}" );
		if($wpdb->num_rows == 1) {
			// found row, db update
			$result = $result_set[0];
			$where = array( 'id' => $result->id );
			$wpdb->update( 'wp_toe_log', $data_set, $where );
		} else {
			// no row yet, db insert
			$wpdb->insert( 'wp_toe_log', $data_set );
		}
		
    }



}
