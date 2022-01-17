jQuery(document).ready(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 
	 $('.repeater').repeater({
            initEmpty: false,
            show: function () {
                $(this).slideDown();
            },
			defaultValues: {
                'start_time': '0:30',
				'end_time': '0:30'
            },
			hide: function (deleteElement) {
                if(confirm('Are you sure you want to delete this element?')) {
                    $(this).slideUp(deleteElement);
                }
            },
            isFirstItemUndeletable: false
        })

	 $(document).on( 'change', '.product-list .is_switch', function() {
	 	var product_id = $(this).attr('data-id');
	 	var deactive = '';
	 	if ( $(this).prop('checked') ) {
	 		deactive = 'yes';
	 	}
	 	var data = {
			'action': 'deactive_bus',
			'product_id': product_id,
			'deactive': deactive
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			
		});
	 } );

	  jQuery( '#menu-posts-product .wp-submenu li' ).each( function(){
	 	if ( 'edit.php?post_type=product&page=product_attributes' == jQuery( this ).find( 'a' ).attr('href') ) {
	 		jQuery( this ).remove();
	 	}
		//console.log(jQuery( this ).find( 'a' ).attr('href'));
		if ( 'post-new.php?post_type=product' == jQuery( this ).find( 'a' ).attr('href') ) {
	 		jQuery( this ).find( 'a' ).html('Add Shuttle');
	 	}
	 	if ( 'Shuttle Dashboard' == jQuery( this ).find( 'a' ).text() ) {
	 		var menud = jQuery( this ).clone();
	 		jQuery('#menu-posts-product .wp-submenu .wp-submenu-head').after( menud );
	 		jQuery('#menu-posts-product > a.wp-has-submenu').attr('href', jQuery( this ).find( 'a' ).attr('href'));
	 		jQuery( this ).remove();
	 	}
	 } );
});
