jQuery(document).ready(function($) {

    toggle_methods();
    toggle_handlings();
    toggle_fixeds();

    $(document).on('change', '#auctioninc_calc_method', function(e) {
        toggle_methods();
    });

    $(document).on('change', '#auctioninc_supp_handling_mode', function(e) {
        toggle_handlings();
    });

    $(document).on('change', '#auctioninc_fixed_mode', function(e) {
        toggle_fixeds();
    });

    function toggle_methods() {
        var calc_method = $('#auctioninc_calc_method').val();

        if (calc_method === 'C' || calc_method === 'CI') {
            $('#auctioninc_fixed_mode').closest('tr').fadeOut();
            $('#auctioninc_fixed_code').closest('tr').fadeOut();
            $('#auctioninc_fixed_fee_1').closest('tr').fadeOut();
            $('#auctioninc_fixed_fee_2').closest('tr').fadeOut();
            $('#auctioninc_pack_method').closest('tr').fadeIn();
            $('#auctioninc_insurable').closest('tr').fadeIn();
            $('#auctioninc_origin_code').closest('tr').fadeIn();
            $('#auctioninc_supp_handling_mode').closest('tr').fadeIn();
            toggle_handlings();
            $('#auctioninc_ondemand_codes').closest('tr').fadeIn();
            $('#auctioninc_access_fees').closest('tr').fadeIn();
        }
        else if (calc_method === 'F') {
            $('#auctioninc_fixed_mode').closest('tr').fadeIn();
            toggle_fixeds();
            $('#auctioninc_pack_method').closest('tr').fadeOut();
            $('#auctioninc_insurable').closest('tr').fadeOut();
            $('#auctioninc_origin_code').closest('tr').fadeOut();
            $('#auctioninc_supp_handling_mode').closest('tr').fadeOut();
            $('#auctioninc_supp_handling_code').closest('tr').fadeOut();
            $('#auctioninc_supp_handling_fee').closest('tr').fadeOut();
            $('#auctioninc_ondemand_codes').closest('tr').fadeOut();
            $('#auctioninc_access_fees').closest('tr').fadeOut();
        }
        else if (calc_method === 'N') {
            $('#auctioninc_fixed_mode').closest('tr').fadeOut();
            $('#auctioninc_fixed_code').closest('tr').fadeOut();
            $('#auctioninc_fixed_fee_1').closest('tr').fadeOut();
            $('#auctioninc_fixed_fee_2').closest('tr').fadeOut();
            $('#auctioninc_pack_method').closest('tr').fadeOut();
            $('#auctioninc_insurable').closest('tr').fadeOut();
            $('#auctioninc_origin_code').closest('tr').fadeOut();
            $('#auctioninc_supp_handling_mode').closest('tr').fadeOut();
            $('#auctioninc_supp_handling_code').closest('tr').fadeOut();
            $('#auctioninc_supp_handling_fee').closest('tr').fadeOut();
            $('#auctioninc_ondemand_codes').closest('tr').fadeOut();
            $('#auctioninc_access_fees').closest('tr').fadeOut();
        }
        else {
            $('#auctioninc_fixed_mode').closest('tr').hide();
            $('#auctioninc_fixed_code').closest('tr').hide();
            $('#auctioninc_fixed_fee_1').closest('tr').hide();
            $('#auctioninc_fixed_fee_2').closest('tr').hide();
            $('#auctioninc_pack_method').closest('tr').hide();
            $('#auctioninc_insurable').closest('tr').hide();
            $('#auctioninc_origin_code').closest('tr').hide();
            $('#auctioninc_supp_handling_mode').closest('tr').hide();
            $('#auctioninc_supp_handling_code').closest('tr').hide();
            $('#auctioninc_supp_handling_fee').closest('tr').hide();
            $('#auctioninc_ondemand_codes').closest('tr').hide();
            $('#auctioninc_access_fees').closest('tr').hide();
        }
    }

    function toggle_handlings() {
        var handling_mode = $('#auctioninc_supp_handling_mode').val();
        if (handling_mode === 'code') {
            $('#auctioninc_supp_handling_code').closest('tr').fadeIn();
            $('#auctioninc_supp_handling_fee').closest('tr').fadeOut();
        }
        else if (handling_mode === 'fee') {
            $('#auctioninc_supp_handling_code').closest('tr').fadeOut();
            $('#auctioninc_supp_handling_fee').closest('tr').fadeIn();
        }
        else {
            $('#auctioninc_supp_handling_code').closest('tr').hide();
            $('#auctioninc_supp_handling_fee').closest('tr').hide();
        }
    }

    function toggle_fixeds() {
        var calc_method = $('#auctioninc_calc_method').val();
        var fixed_mode = $('#auctioninc_fixed_mode').val();
        
        if (calc_method === 'F') {
            if (fixed_mode === 'code') {
                $('#auctioninc_fixed_code').closest('tr').fadeIn();
                $('#auctioninc_fixed_fee_1').closest('tr').fadeOut();
                $('#auctioninc_fixed_fee_2').closest('tr').fadeOut();
            }
            else if (fixed_mode === 'fee') {
                $('#auctioninc_fixed_code').closest('tr').fadeOut();
                $('#auctioninc_fixed_fee_1').closest('tr').fadeIn();
                $('#auctioninc_fixed_fee_2').closest('tr').fadeIn();
            }
            else {
                $('#auctioninc_fixed_code').closest('tr').hide();
                $('#auctioninc_fixed_fee_1').closest('tr').hide();
                $('#auctioninc_fixed_fee_2').closest('tr').hide();
            }
        }

    }

});