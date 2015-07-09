<?php
/**
 * Plugin Name: AuctionInc ShippingCalc for Ready! Ecommerce
 * Description: Accurate multi-carrier real-time shipping rates from FedEx, USPS, UPS, and DHL. Multiple ship origins, many advanced features. Free two week trial. No carrier accounts required.
 * Plugin URI: http://auctioninc.com/
 * Author: AuctionInc
 * Author URI: http://auctioninc.com
 * Version: 1.0
 **/
	
    define('READY_SHIPPINGCALC_DIR', plugin_dir_path( __FILE__ ));
	
    /*
	 * Register ShippingCalc module with Ready! Ecommerce modInstaller
	 */
    register_activation_hook(__FILE__, array('modInstaller', 'check'));
    register_deactivation_hook(__FILE__, array('modInstaller', 'deactivate'));
    register_uninstall_hook(__FILE__, array('modInstaller', 'uninstall'));	
    
    /*
     * Meta
    */
    require_once('meta/product_meta.php');
        
    /**
     * Plugin page links, update with new links when available
     */
    function ready_auctioninc_plugin_links($links) {
    
    	$plugin_links = array(
    			'<a href="' . admin_url('admin.php?page=toeoptions#toe_opt_shipping') . '">' . __('Settings', 'ready_auctioninc') . '</a>',
    			'<a href="http://auctioninc.helpserve.com">' . __('Support', 'ready_auctioninc') . '</a>'
    	);
    
    	return array_merge($plugin_links, $links);
    }
    
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ready_auctioninc_plugin_links');
    
    /**
     * ready_auctioninc_admin_notice function.
     *
     * @access public
     * @return void
    */
    function ready_auctioninc_admin_notice() {
    
    	$post_notice = false;
    	
    	//$auctioninc_settings = get_option('ready_auctioninc');
    	global $wpdb; // pre-defined database object
    	
    	$auctioninc_module_instances = $wpdb->get_results( "SELECT * FROM wp_toe_shipping WHERE code LIKE 'auctioninc_shipcalc%'" );

    	// if there is no AuctionInc shipping module, initialize a default
		if($wpdb->num_rows == 0) {
    		$data_set = array( 'label' => 'Carrier Calculated Rates', 'description' => 'Carrier rates provided by AuctionInc ShippingCalc', 'code' => 'auctioninc_shipcalc_carrier', 'active' => 1 );
	    	$wpdb->insert( 'wp_toe_shipping', $data_set );
		}
		
    	$strip_chars = array('[', ']');
    	foreach ($auctioninc_module_instances as $module_instance) {
    		$params = json_decode( str_replace($strip_chars, '', $module_instance->params), true );
    		// test for empty api key on all AuctionInc ShippingCalc module instances
    		if ($params['ai_api_key'] == '') {
    			$post_notice = true;
    			break;
    		}
    	}

    	if ($post_notice) { // update when new link available for Ready!
    		echo '<div class="error">
             <p>' . __('An') . ' <a href="http://www.auctioninc.com/info/page/shippingcalc_for_ready_ecommerce" target="_blank">' . __('AuctionInc', 'ready_auctioninc') . '</a> ' . __('account is required to use the ShippingCalc plugin.  Please enter your AuctionInc Account ID.', 'ready_auctioninc') . '</p>
         </div>';
    	}
    }
    
    add_action('admin_notices', 'ready_auctioninc_admin_notice');
    
    /**
     * ready_auctioninc_scripts function.
     *
     * @access public
     * @return void
     */
    function ready_auctioninc_scripts() {
    	$screen = get_current_screen();
    
    	if ($screen->base == 'post' && $screen->post_type == 'product') { 
    		wp_enqueue_script('admin-auctioninc-product', plugins_url('js/auctioninc-shipcalc-product.js', __FILE__), array('jquery'), null, true);
    	}
    }
    
    add_action('admin_enqueue_scripts', 'ready_auctioninc_scripts');
    
    wp_enqueue_script('admin-auctioninc-order', plugins_url('js/auctioninc-shipcalc-order.js', __FILE__), array('jquery'), null, true);
    