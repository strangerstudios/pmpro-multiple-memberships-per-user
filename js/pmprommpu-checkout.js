/*
 * License:

 Copyright 2016 - Stranger Studios, LLC

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
jQuery(document).ready(function () {
    "use strict";

    var main_dc_field = jQuery('#discount_code');
    var main_dc_btn = jQuery('#discount_code_button');
    var other_dc_link = jQuery('#other_discount_code_a');
    var other_dc_field = jQuery('#other_discount_code');

    // Only process/handle if  $pmpro_show_discount_code filter permits it.
    if ( true === pmprodc.settings.show_main_discount_code ) {

        //update discount code link to show field at top of form
        other_dc_link.attr('href', 'javascript:void(0);');
        other_dc_link.click(function () {
            jQuery('#other_discount_code_tr').show();
            jQuery('#other_discount_code_p').hide();
            jQuery('#other_discount_code').focus();
        });

        //update real discount code field as the other discount code field is updated
        other_dc_field.keyup(function () {
            main_dc_field.val(other_dc_field.val());
        });
        other_dc_field.blur(function () {
            main_dc_field.val(other_dc_field.val());
        });

        //update other discount code field as the real discount code field is updated
        main_dc_field.keyup(function () {
            other_dc_field.val(main_dc_field.val());
        });
        main_dc_field.blur(function () {
            other_dc_field.val(main_dc_field.val());
        });

        //applying a discount code
        var other_dc_btn = jQuery('#other_discount_code_button');

        other_dc_btn.click(function () {

            var discount_code = other_dc_field.val();
            var level_id = jQuery('#level').val();

            if (typeof discount_code !== 'undefined') {
                //hide any previous message
                jQuery('.pmpro_discount_code_msg').hide();

                //disable the apply button
                other_dc_btn.attr('disabled', 'disabled');

                jQuery.ajax({
                    url: pmprodc.settings.ajaxurl,
                    type: 'GET',
                    timeout: pmprodc.settings.timeout,
                    dataType: 'html',
                    data: {
                        action: "applydiscountcode",
                        code: discount_code,
                        level: level_id,
                        msgfield: 'pmpro_message'
                    },
                    error: function (xml) {
                        window.alert('Error applying discount code [1]');

                        //enable apply button
                        jQuery('#other_discount_code_button').removeAttr('disabled');
                    },
                    success: function (responseHTML) {

                        if (responseHTML === 'error') {
                            window.alert('Error applying discount code [2]');
                        }
                        else {
                            jQuery('#pmpro_message').html(responseHTML);
                        }

                        //enable invite button
                        jQuery('#other_discount_code_button').removeAttr('disabled');
                    }
                })
                ;
            }
        });
    }
    //checking a discount code
    main_dc_btn.click(function() {
        var discount_code = jQuery('#discount_code').val();
        var level_id = jQuery('#level').val();

        if(discount_code)
        {
            //hide any previous message
            jQuery('.pmpro_discount_code_msg').hide();

            //disable the apply button
            main_dc_btn.attr('disabled', 'disabled');

            jQuery.ajax({
                url: pmprodc.settings.ajaxurl,
                type:'GET',
                timeout: pmprodc.settings.timeout,
                dataType: 'html',
                data: {
                    action: 'applydiscountcode',
                    code: discount_code,
                    level: level_id,
                    msgfield: 'discount_code_message'
                },
                error: function(xml){
                    window.alert('Error applying discount code [1]');

                    //enable apply button
                    main_dc_btn.removeAttr('disabled');
                },
                success: function(responseHTML){
                    if (responseHTML === 'error')
                    {
                        window.alert('Error applying discount code [2]');
                    }
                    else
                    {
                        jQuery('#discount_code_message').html(responseHTML);
                    }

                    //enable invite button
                    main_dc_btn.removeAttr('disabled');
                }
            });
        }
    });

    var card_type = jQuery('#CardType');

    jQuery('#AccountNumber').validateCreditCard(function(result) {

        var cardtypenames = {
            "amex"                      : "American Express",
            "diners_club_carte_blanche" : "Diners Club Carte Blanche",
            "diners_club_international" : "Diners Club International",
            "discover"                  : "Discover",
            "jcb"                       : "JCB",
            "laser"                     : "Laser",
            "maestro"                   : "Maestro",
            "mastercard"                : "Mastercard",
            "visa"                      : "Visa",
            "visa_electron"             : "Visa Electron"
        };

        if(result.card_type) {
            card_type.val(cardtypenames[result.card_type.name]);
        } else {
            card_type.val('Unknown Card Type');
        }
    });

    // Find ALL <form> tags on your page
    jQuery('form').submit(function(){

        // On submit disable its submit button
        jQuery('input[type=submit]', this).attr('disabled', 'disabled');
        jQuery('input[type=image]', this).attr('disabled', 'disabled');
        jQuery('#pmpro_processing_message').css('visibility', 'visible');
    });

    //iOS Safari fix (see: http://stackoverflow.com/questions/20210093/stop-safari-on-ios7-prompting-to-save-card-data)
    var userAgent = window.navigator.userAgent;
    if(userAgent.match(/iPad/i) || userAgent.match(/iPhone/i)) {
        jQuery('input[type=submit]').click(function() {
            try{
                jQuery("input[type=password]").attr("type", "hidden");
            } catch(ex){
                try {
                    jQuery("input[type=password]").prop("type", "hidden");
                } catch(ex) {}
            }
        });
    }

    //add required to required fields
    if ( ! jQuery( '.pmpro_required' ).next().hasClass( "pmpro_asterisk" ) ) {
		   jQuery( '.pmpro_required' ).after( '<span class="pmpro_asterisk"> <abbr title="Required Field">*</abbr></span>' );
	  }

    //unhighlight error fields when the user edits them
    jQuery('.pmpro_error').bind("change keyup input", function() {
        jQuery(this).removeClass('pmpro_error');
    });

    //click apply button on enter in discount code box
    main_dc_field.keydown(function (e){
        if(e.keyCode === 13){
            e.preventDefault();
            main_dc_btn.click();
        }
    });

    //hide apply button if a discount code was passed in
    if( true === pmprodc.settings.processed_dc ) {

        main_dc_btn.hide();
        main_dc_field.bind('change keyup', function() {
            main_dc_btn.show();
        });
    }

    //click apply button on enter in *other* discount code box
    other_dc_field.keydown(function (e){
        if(e.keyCode === 13){
            e.preventDefault();
            other_dc_btn.click();
        }
    });

    //add javascriptok hidden field to checkout
    jQuery("input[name=submit-checkout]").after('<input type="hidden" name="javascriptok" value="1" />');
});
