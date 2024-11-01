var WCAV_Admin = {
    /**
     * The button.
     */
    button_selector : '.validate-address',

    /**
     * The status text
     */
    status_selector : '.wcav-status',

    /**
     * The locales.
     */
    locale : WCAVLocale,

    /**
     * Initialize the validator.
     */
    init: function() {
        jQuery( this.button_selector ).click( function( event ) {

            event.preventDefault();
            var type = jQuery( this ).data( 'type' );
            var id = jQuery( this ).data( 'id' );
            WCAV_Admin.validate( type, id );
        });
    },

    /**
     * Validate the address of {type} for the order {id}
     * @param type
     * @param id
     */
    validate: function( type, id ) {
        jQuery.get(
            this.locale.ajax,
            {
                action : this.locale.action,
                nonce  : this.locale.nonce,
                id     : id,
                type   : type
            },
            function( response ) {
                if( false === response.success ) {
                    alert( WCAV_Admin.locale.error_msg );
                    return;
                }
                WCAV_Admin.handle_response( response );


            }
        );
    },

    handle_response: function( response ) {
        data = response.data;
        jQuery( '#wcav-address-status' ).text( data.status );

        var sanitized;
        sanitized = data.sanitized.StreetAddress + '<br>';
        sanitized+= data.sanitized.AdditionalAddressInfo + '<br>';
        sanitized+= data.sanitized.PostalCode + ' ' + data.sanitized.City + '<br>';
        sanitized+= data.sanitized.State + '<br>';
        sanitized+= data.sanitized.CountryCode + '<br>';
        jQuery( '#wcav-sanitized' ).html( sanitized );
        if( 'SUSPECT' === data.status ) {
            jQuery( '#wcav-sanitized-wrapper' ).show();
        } else {
            jQuery( '#wcav-sanitized-wrapper' ).hide();
        }
        tb_show( WCAV_Admin.locale.modal_title, '#TB_inline?inlineId=wcav-modal-wrapper');
    }
};

jQuery( document ).ready( function() {
    WCAV_Admin.init();
})