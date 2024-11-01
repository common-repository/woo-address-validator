var WCAV = {


    init: function() {
        var self = this;
        jQuery( document.body ).bind( 'checkout_error', WCAV.checkout_error );
    },

    checkout_error: function( event ) {
        var data = WCAV.get_cookie();
        if ( data ) {
            console.log( data );
            if ( 'undefined' !== typeof( data.billing ) ) {
                WCAV.sanitize_address( data.billing, 'billing' );
            }
            if ( 'undefined' !== typeof( data.shipping ) ) {
                WCAV.sanitize_address( data.shipping, 'shipping' );
            }
            jQuery( '#billing_country, #shipping_country, .country_to_state' ).change();
        }
    },

    get_cookie: function() {
        var cookie_key = 'wcav';

        var cookies = "; " + document.cookie;
        var parts = cookies.split("; " + cookie_key + "=");
        if (parts.length == 2) {
            cookie = parts.pop().split(";").shift();
            return JSON.parse( decodeURIComponent( cookie ) );
        }
        return false;
    },

    sanitize_address: function( address, type ) {
        jQuery( '#' + type + '_address_1' ).val( address.StreetAddress );
        jQuery( '#' + type + '_address_2' ).val( address.AdditionalAddressInfo );
        jQuery( '#' + type + '_postcode' ).val( address.PostalCode );
        jQuery( '#' + type + '_city' ).val( address.City );
        jQuery( '#' + type + '_state' ).val( address.State );
        if ( jQuery( '#' + type + '_state_field' ).hasClass( 'validate-required') ) {
            jQuery('#' + type + '_country').val(address.CountryCode).trigger('change.select2');
        }
    }
};

jQuery( document ).ready( function() {
    WCAV.init();
});