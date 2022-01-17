<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woocommerce_Shuttle_Booking
 * @subpackage Woocommerce_Shuttle_Booking/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Shuttle_Booking
 * @subpackage Woocommerce_Shuttle_Booking/admin
 * @author     Woocommerce Shuttle Booking Team <wsb@mail.com>
 */
class Woocommerce_Shuttle_Booking_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'admin_menu', array( $this, 'register_shuttle_submenu_page' ), 99 );
		add_action( 'init', array( $this, 'create_shuttle_hierarchical_taxonomy' ), 0 );
		add_action( 'admin_footer', array( $this, 'shuttle_admin_footer_function' ) );
		add_action( 'woocommerce_product_options_general_product_data', array(
			$this,
			'woo_add_custom_general_fields'
		) );
		add_action( 'woocommerce_product_options_general_product_data', array(
			$this,
			'woo_add_custom_inventory_fields'
		) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'woo_add_custom_general_fields_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'shuttle_enqueue_date_picker' ) );
		//add_filter('gettext', array($this, 'shuttle_rename_header_to_logo'), 10, 3);
		add_filter( 'woocommerce_register_post_type_product', array(
			$this,
			'shuttle_woocommerce_register_post_type_product'
		), 10, 1 );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'shuttle_woocommerce_product_data_tabs' ) );
		add_action( 'init', array( $this, 'remove_product_editor' ) );
		add_action( 'admin_footer', array( $this, 'wc_default_variation_stock_quantity' ) );
		add_action( 'add_meta_boxes', array( $this, 'shuttle_add_post_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'shuttle_save_post_meta_boxes' ), 99 );
		add_filter( 'manage_product_posts_columns', array( $this, 'set_custom_edit_book_columns' ), 99, 1 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'custom_book_column' ), 10, 2 );
		add_action( 'wp_ajax_deactive_bus', array( $this, 'deactive_bus' ) );
		add_action( 'init', array( $this, 'myprefix_remove_tax' ), 99 );
	}

	public function myprefix_remove_tax() {
		register_taxonomy( 'product_tag', array() );
		register_taxonomy( 'product_cat', array() );
	}

	public function deactive_bus() {
		if ( $_POST['deactive'] == 'yes' ) {
			update_post_meta( $_POST['product_id'], 'is_shuttle_active', 'yes' );
		} else {
			update_post_meta( $_POST['product_id'], 'is_shuttle_active', 'no' );
		}
		wp_send_json( array( 'done' ) );
	}

	public function shuttle_add_post_meta_boxes() {
		add_meta_box( 'enable-disable-product', 'Active/Deactivate switcher', array(
			$this,
			'shuttle_switcher_meta_box'
		), 'product', 'side', 'high' );
		add_meta_box( 'availability-product', 'Availability', array(
			$this,
			'shuttle_availability_meta_box'
		), 'product', 'side', 'default' );
	}


	public function set_custom_edit_book_columns( $columns ) {

		$columns['book_author'] = __( 'Deactivate?', 'woocommerce-shuttle-booking' );
		$no_actives             = array(
			'thumb',
			'sku',
			'is_in_stock',
			'product_cat',
			'product_tag',
			'featured',
			'date',
			'price'
		);
		foreach ( $no_actives as $value ) {
			unset( $columns[ $value ] );
		}

		return $columns;
	}

// Add the data to the custom columns for the book post type:

	public function custom_book_column( $column, $post_id ) {
		switch ( $column ) {

			case 'book_author' :
				?>
                <div class="trip product-list" style="position: relative;">
					<?php $status = get_post_meta( $post_id, 'is_shuttle_active', true ); ?>
                    <label class="switch">
                        <input type="checkbox" data-id="<?php echo $post_id; ?>" id="togBtn" name="is_switch"
                               class="is_switch" value="yes" <?php echo ( $status == 'yes' ) ? 'checked' : ''; ?>>
                        <div class="slider round"><span
                                    class="on"><?php echo __( 'On', 'woocommerce-shuttle-booking' ); ?></span><span
                                    class="off"><?php echo __( 'Off', 'woocommerce-shuttle-booking' ); ?></span></div>
                    </label>

                    <script type="text/javascript">
                        jQuery(document).ready(function ($) {
                            $(document).on('change', '.is_switch', function () {
                                $('#post').submit();
                            });
                        });
                    </script>
                </div>
				<?php
				break;


		}
	}

	public function shuttle_availability_meta_box() {
		
		$this->shuttle_get_day_form( 'monday', __( 'Monday', 'woocommerce-shuttle-booking' ) );
		$this->shuttle_get_day_form( 'tuesday', __( 'Tuesday', 'woocommerce-shuttle-booking' ) );
		$this->shuttle_get_day_form( 'wednesday', __( 'Wednesday', 'woocommerce-shuttle-booking' ) );
		$this->shuttle_get_day_form( 'thursday', __( 'Thursday', 'woocommerce-shuttle-booking' ) );
		$this->shuttle_get_day_form( 'friday', __( 'Friday', 'woocommerce-shuttle-booking' ) );
		$this->shuttle_get_day_form( 'saturday', __( 'Saturday', 'woocommerce-shuttle-booking' ) );
		$this->shuttle_get_day_form( 'sunday', __( 'Sunday', 'woocommerce-shuttle-booking' ) );
		echo '<div class="options_group">';
		?>
        <table>
            <tr>
                <td><label name=""><?php echo __( 'Departure Address:', 'woocommerce-shuttle-booking' ); ?> </label>
                </td>
                <td><input name="departure_address"
                           value="<?php echo get_post_meta( get_the_ID(), 'departure_address', true ) ?>"
                           class="departure_address" type="text"/></td>
            </tr>
            <tr>
                <td><label name=""><?php echo __( 'Arrival Address:', 'woocommerce-shuttle-booking' ); ?> </label></td>
                <td><input name="arrival_address"
                           value="<?php echo get_post_meta( get_the_ID(), 'arrival_address', true ) ?>"
                           class="arrival_address" type="text"/></td>
            </tr>
        </table>

        <br>


		<?php
		echo '</div>';
	}

	public function shuttle_get_day_form( $day_name, $day_title ) {
		$flag  = get_post_meta( get_the_ID(), $day_name . '_flag', true );
		$datas = get_post_meta( get_the_ID(), $day_name, true );
		?>
        <div class="<?php echo $day_name; ?> days">
            <div class="day-inner">
                <h3><?php echo $day_title; ?></h3>
                <div class="head">
                    <label><?php echo __( 'Start Time', 'woocommerce-shuttle-booking' ); ?></label>
                    <label><?php echo __( 'Seat', 'woocommerce-shuttle-booking' ); ?></label>
                    <label><?php echo __( 'Price', 'woocommerce-shuttle-booking' ); ?></label>
                    <label><?php echo __( 'End Time', 'woocommerce-shuttle-booking' ); ?></label>
					<?php if ( ! empty( $datas ) ) { ?>
                        <span style="display: inline-block; width: 33px;"></span>
					<?php } ?>
                </div>
                <div class="repeater day">
                    <div data-repeater-list="<?php echo $day_name; ?>">
						<?php if ( ! empty( $datas ) ) { ?>
							<?php foreach ( $datas as $data ) { ?>
                                <div data-repeater-item>
									<?php
									woocommerce_wp_select(
										array(
											'id'      => 'start_time',
											'options' => $this->SplitTime( "00:30", "23:30", "5" ),
											'value'   => $data['start_time']
										)
									);
									?>
                                    <input type="number" min="1" name="tickets"
                                           value="<?php echo $data['tickets']; ?>"/>
                                    <input type="number" min="0" name="price" value="<?php echo $data['price']; ?>"/>
									<?php
									woocommerce_wp_select(
										array(
											'id'      => 'end_time',
											'options' => $this->SplitTime( "00:30", "23:30", "5" ),
											'value'   => $data['end_time']
										)
									);
									?>
                                    <input data-repeater-delete type="button" class="delete" value=" - "/>
                                </div>
							<?php } ?>
						<?php } else { ?>
                            <div data-repeater-item>
								<?php
								woocommerce_wp_select(
									array(
										'id'      => 'start_time',
										'options' => $this->SplitTime( "00:30", "23:30", "5" )
									)
								);
								?>
                                <input type="number" min="1" name="tickets"/>
                                <input type="number" min="0" name="price"/>
								<?php
								woocommerce_wp_select(
									array(
										'id'      => 'end_time',
										'options' => $this->SplitTime( "00:30", "23:30", "5" )
									)
								);
								?>
                                <input data-repeater-delete type="button" class="delete" value=" - "/>
                            </div>
						<?php } ?>
                    </div>
                    <input data-repeater-create type="button"
                           value="<?php echo __( 'End Time', 'woocommerce-shuttle-booking' ); ?>+ Add new time"/>
                </div>
            </div>
        </div>
		<?php
	}

	public function shuttle_switcher_meta_box() {
		?>
        <div class="trip">
            <p><strong>Deactivate? &nbsp;&nbsp;</strong></p>
			<?php $status = get_post_meta( get_the_ID(), 'is_shuttle_active', true ); ?>
            <label class="switch"><input type="checkbox" id="togBtn" name="is_switch" class="is_switch"
                                         value="yes" <?php echo ( $status == 'yes' ) ? 'checked' : ''; ?>>
                <div class="slider round"><span
                            class="on"><?php echo __( 'On', 'woocommerce-shuttle-booking' ); ?></span><span
                            class="off"><?php echo __( 'Off', 'woocommerce-shuttle-booking' ); ?></span></div>
            </label>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $(document).on('change', '.is_switch', function () {
                        $('#post').submit();
                    });
                });
            </script>
        </div>
		<?php
	}

	public function shuttle_save_post_meta_boxes( $post_id ) {

		$is_switch = $_POST['is_switch'];
		$monday    = $_POST['monday'];
		$tuesday   = $_POST['tuesday'];
		$wednesday = $_POST['wednesday'];
		$thursday  = $_POST['thursday'];
		$friday    = $_POST['friday'];
		$saturday  = $_POST['saturday'];
		$sunday    = $_POST['sunday'];

		update_post_meta( $post_id, 'departure_address', $_POST['departure_address'] );
		update_post_meta( $post_id, 'arrival_address', $_POST['arrival_address'] );
		update_post_meta( $post_id, 'monday', $monday );
		update_post_meta( $post_id, 'monday_flag', 'yes' );
		update_post_meta( $post_id, 'tuesday', $tuesday );
		update_post_meta( $post_id, 'tuesday_flag', 'yes' );
		update_post_meta( $post_id, 'wednesday', $wednesday );
		update_post_meta( $post_id, 'wednesday_flag', 'yes' );
		update_post_meta( $post_id, 'thursday', $thursday );
		update_post_meta( $post_id, 'thursday_flag', 'yes' );
		update_post_meta( $post_id, 'friday', $friday );
		update_post_meta( $post_id, 'friday_flag', 'yes' );
		update_post_meta( $post_id, 'saturday', $saturday );
		update_post_meta( $post_id, 'saturday_flag', 'yes' );
		update_post_meta( $post_id, 'sunday', $sunday );
		update_post_meta( $post_id, 'sunday_flag', 'yes' );

		if ( $is_switch == 'yes' ) {
			update_post_meta( $post_id, 'is_shuttle_active', 'yes' );
		} else {
			update_post_meta( $post_id, 'is_shuttle_active', 'no' );
		}
		update_post_meta( $post_id, '_regular_price', 10 );
		update_post_meta( $post_id, '_price', 10 );
	}

	public function wc_default_variation_stock_quantity() {
		global $pagenow, $woocommerce;

		$default_stock_quantity = 1;
		$screen                 = get_current_screen();

		if ( ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' ) && $screen->post_type == 'product' ) {
			?>
            <script type="text/javascript">
                jQuery(document).ready(function () {

                    jQuery('.stock_fields.show_if_simple.show_if_variable').show();
                    jQuery('p.stock_status_field.hide_if_variable.hide_if_external.hide_if_grouped.form-field._stock_status_field').hide();
                    jQuery('p.form-field._stock_field label').text('Number of Tickets');
                    jQuery('p._manage_stock_field label').text('Manage Ticket?');
                    jQuery('p._manage_stock_field span.description').text('Enable ticket management at route level');
                    jQuery('body.post-type-product th#is_in_stock').text('Tickets');
                    jQuery('body.post-type-product th#name').text('Route Name');
                });
            </script>
			<?php
		}
	}

	public function shuttle_woocommerce_product_data_tabs( $tabs ) {
		$tabs['general']['label'] = 'Ticket';

		return $tabs;
	}

	public function remove_product_editor() {
		remove_post_type_support( 'product', 'editor' );
	}

	public function shuttle_woocommerce_register_post_type_product( $array ) {

		$array = array(
			'labels'              => array(
				'name'                  => __( 'Shuttle', 'woocommerce-shuttle-booking' ),
				'singular_name'         => __( 'Shuttle', 'woocommerce-shuttle-booking' ),
				'all_items'             => __( 'All Shuttle', 'woocommerce-shuttle-booking' ),
				'menu_name'             => _x( 'Shuttle', 'Admin menu name', 'woocommerce-shuttle-booking' ),
				'add_new'               => __( 'Add New', 'woocommerce-shuttle-booking' ),
				'add_new_item'          => __( 'Add new shuttle', 'woocommerce-shuttle-booking' ),
				'edit'                  => __( 'Edit', 'woocommerce-shuttle-booking' ),
				'edit_item'             => __( 'Edit Shuttle', 'woocommerce-shuttle-booking' ),
				'new_item'              => __( 'New Shuttle', 'woocommerce-shuttle-booking' ),
				'view_item'             => __( 'View Shuttle', 'woocommerce-shuttle-booking' ),
				'view_items'            => __( 'View Shuttles', 'woocommerce-shuttle-booking' ),
				'search_items'          => __( 'Search Shuttles', 'woocommerce-shuttle-booking' ),
				'not_found'             => __( 'No Shuttles found', 'woocommerce-shuttle-booking' ),
				'not_found_in_trash'    => __( 'No Shuttles found in trash', 'woocommerce-shuttle-booking' ),
				'parent'                => __( 'Parent Shuttle', 'woocommerce-shuttle-booking' ),
				'featured_image'        => __( 'Shuttle image', 'woocommerce-shuttle-booking' ),
				'set_featured_image'    => __( 'Set Shuttle image', 'woocommerce-shuttle-booking' ),
				'remove_featured_image' => __( 'Remove Shuttle image', 'woocommerce-shuttle-booking' ),
				'use_featured_image'    => __( 'Use as Shuttle image', 'woocommerce-shuttle-booking' ),
				'insert_into_item'      => __( 'Insert into Shuttle', 'woocommerce-shuttle-booking' ),
				'uploaded_to_this_item' => __( 'Uploaded to this Shuttle', 'woocommerce-shuttle-booking' ),
				'filter_items_list'     => __( 'Filter Shuttles', 'woocommerce-shuttle-booking' ),
				'items_list_navigation' => __( 'Shuttles navigation', 'woocommerce-shuttle-booking' ),
				'items_list'            => __( 'Shuttles list', 'woocommerce-shuttle-booking' ),
			),
			'description'         => __( 'This is where you can add new Shuttles to your store.', 'woocommerce' ),
			'public'              => true,
			'show_ui'             => true,
			'capability_type'     => 'product',
			'map_meta_cap'        => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
			'rewrite'             => false,
			'query_var'           => true,
			'show_in_nav_menus'   => true,
			'show_in_rest'        => true,
		);

		return $array;
	}

	public function shuttle_rename_header_to_logo( $translated, $original, $domain ) {
		$strings = array(
			'WooCommerce'   => __( 'Shuttle', 'woocommerce-shuttle-booking' ),
			'Custom Header' => __( 'Custom Shuttle', 'woocommerce-shuttle-booking' )
		);

		if ( isset( $strings[ $original ] ) && is_admin() ) {
			$translations = &get_translations_for_domain( $domain );
			$translated   = $translations->translate( $strings[ $original ] );
		}

		return $translated;
	}

	public function shuttle_enqueue_date_picker() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
	}

	public function shuttle_admin_footer_function() {
		?>
        <script>
            jQuery(document).ready(function ($) {
jQuery( '#menu-posts-product .wp-submenu li' ).each( function(){
		
		if ( 'post-new.php?post_type=product' == jQuery( this ).find( 'a' ).attr('href') ) {
	 		jQuery( this ).find( 'a' ).html('<?php echo __( 'Shuttle', 'woocommerce-shuttle-booking' ); ?>');
	 	}
	 } );
                setInterval(function () {
                    jQuery('#departurechecklist li input[type=checkbox], #arrivalchecklist li input[type=checkbox]').attr('type', 'radio');
                }, 500);
                $("#post").submit(function (event) {
                    if ($('body').hasClass('post-type-product')) {
                        var departure = $('input[name="tax_input[departure][]"]:checked').val();
                        var arrival = $('input[name="tax_input[arrival][]"]:checked').val();
                        var regular_price = $('#_regular_price').val();
                        var shuttle_number_of_tickets = $('#shuttle_number_of_tickets').val();
                        if (departure == '' || typeof departure === "undefined" || arrival == '' || typeof arrival === "undefined") {
                            alert('<?php __( 'Departure & Arrival is required' ) ?>');
                            return false;
                        }
                        /*if (regular_price == '') {
                         alert('Price is required');
                         return false;
                         }
                         if (shuttle_number_of_tickets == '') {
                         alert('Number of ticket is required');
                         return false;
                     }*/

                    }
                });
                jQuery('.wcDatepicker').datepicker({
                    dateFormat: 'yy-mm-dd'
                });
                jQuery('#departurechecklist li input[type=checkbox], #arrivalchecklist li input[type=checkbox]').attr('type', 'radio');
                $('body.post-type-product #post-body-content #titlediv input#title').empty();
                $('input[name="tax_input[departure][]"], input[name="tax_input[arrival][]"]').click(function () {
                    var departure = $('input[name="tax_input[departure][]"]:checked').parent().text();
                    var arrival = $('input[name="tax_input[arrival][]"]:checked').parent().text();
                    $('body.post-type-product #post-body-content #titlediv input#title').val(departure + ' - ' + arrival);
                });
            });</script>
        <style>
            #woocommerce-product-data ul.wc-tabs li, .form-field._sale_price_field {
                display: none !important;
            }

            #woocommerce-product-data ul.wc-tabs li:nth-child(1) {
                display: block !important;
            }

            #woocommerce-coupon-data ul.wc-tabs li.general_options a::before, #woocommerce-product-data ul.wc-tabs li.general_options a::before, .woocommerce ul.wc-tabs li.general_options a::before {
                content: '\f481';
            }
        </style>
		<?php
	}

	public function woo_add_custom_general_fields() {
		global $woocommerce, $post;

		echo '<div class="options_group">';
		/* woocommerce_wp_text_input(
		  array(
		  'id' => 'start_date',
		  'class' => 'wcDatepicker',
		  'placeholder' => 'Start� YYYY-MM-DD',
		  'label' => __('Start Date', 'woocommerce'),
		  'desc_tip' => 'true',
		  'description' => __('Enter the start date value here.', 'woocommerce')
		  )
	  ); */

		woocommerce_wp_select(
			array(
				'id'      => 'start_day',
				'label'   => __( 'Day', 'woocommerce-shuttle-booking' ),
				'options' => array(
					'sunday'    => __( 'Sunday', 'woocommerce-shuttle-booking' ),
					'monday'    => __( 'Monday', 'woocommerce-shuttle-booking' ),
					'tuesday'   => __( 'Tuesday', 'woocommerce-shuttle-booking' ),
					'wednesday' => __( 'Wednesday', 'woocommerce-shuttle-booking' ),
					'thursday'  => __( 'Thursday', 'woocommerce-shuttle-booking' ),
					'friday'    => __( 'Friday', 'woocommerce-shuttle-booking' ),
					'saturday'  => __( 'Saturday', 'woocommerce-shuttle-booking' )
				)
			)
		);

		woocommerce_wp_select(
			array(
				'id'      => 'start_time',
				'label'   => __( 'Start Time', 'woocommerce-shuttle-booking' ),
				'options' => $this->SplitTime( "00:30", "23:30", "5" )
			)
		);

		/* woocommerce_wp_text_input(
		  array(
		  'id' => 'end_date',
		  'class' => 'wcDatepicker',
		  'placeholder' => 'End�  YYYY-MM-DD',
		  'label' => __('End Date', 'woocommerce'),
		  'desc_tip' => 'true',
		  'description' => __('Enter the end date value here.', 'woocommerce')
		  )
	  ); */

		/* woocommerce_wp_select(
		  array(
		  'id' => 'end_time',
		  'label' => __('End Time', 'woocommerce'),
		  'options' => $this->SplitTime("00:30", "23:30", "60")
		  )
	  ); */

		echo '</div>';


	}

	public function woo_add_custom_inventory_fields() {
		global $woocommerce, $post;

		echo '<div class="options_group shuttle_options_group">';

		woocommerce_wp_text_input(
			array(
				'id'                => 'shuttle_number_of_tickets',
				'label'             => __( 'Number of Tickets', 'woocommerce-shuttle-booking' ),
				'placeholder'       => '',
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => 'any',
					'min'  => '0'
				)
			)
		);

		echo '</div>';
	}

	public function woo_add_custom_general_fields_save( $post_id ) {

		$start_date = $_POST['start_date'];
		if ( ! empty( $start_date ) ) {
			update_post_meta( $post_id, 'start_date', esc_attr( $start_date ) );
		}


		$start_time = $_POST['start_time'];
		if ( ! empty( $start_time ) ) {
			update_post_meta( $post_id, 'start_time', esc_attr( $start_time ) );
		}

		$end_date = $_POST['end_date'];
		if ( ! empty( $end_date ) ) {
			update_post_meta( $post_id, 'end_date', esc_attr( $end_date ) );
		}

		$end_time = strtotime( $start_time ) + strtotime( '0:05' );
		if ( ! empty( $end_time ) ) {
			update_post_meta( $post_id, 'end_time', esc_attr( $end_time ) );
		}

		$start_day = $_POST['start_day'];
		if ( ! empty( $start_day ) ) {
			update_post_meta( $post_id, 'start_day', esc_attr( $start_day ) );
		}

		$shuttle_number_of_tickets = $_POST['shuttle_number_of_tickets'];
		if ( ! empty( $shuttle_number_of_tickets ) ) {
			update_post_meta( $post_id, 'shuttle_number_of_tickets', esc_attr( $shuttle_number_of_tickets ) );
		}
	}

	public function SplitTime( $StartTime, $EndTime, $Duration = "60" ) {
		$ReturnArray = array();
		$StartTime   = strtotime( $StartTime );
		$EndTime     = strtotime( $EndTime );

		$AddMins = $Duration * 60;

		while ( $StartTime <= $EndTime ) {
			$ReturnArray[ date( "G:i", $StartTime ) ] = date( "G:i", $StartTime );
			$StartTime                                += $AddMins;
		}

		return $ReturnArray;
	}

	public function register_shuttle_submenu_page() {
		add_submenu_page( 'edit.php?post_type=product', __( 'Shuttle Dashboard', 'woocommerce-shuttle-booking' ),  __( 'Shuttle Dashboard', 'woocommerce-shuttle-booking' ), 'manage_options', 'shuttle-dashboard', array(
			$this,
			'shuttle_dashboard_callback'
		) );
		add_submenu_page( 'edit.php?post_type=product', __( 'Shuttle Calender', 'woocommerce-shuttle-booking' ), __( 'Shuttle Calender', 'woocommerce-shuttle-booking' ), 'manage_options', 'shuttle-calender', array(
			$this,
			'shuttle_calender_callback'
		) );
	}

	public function create_shuttle_hierarchical_taxonomy() {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta WHERE meta_key = 'screen_layout_product' AND meta_value = '2'", OBJECT );
		if ( $results ) {
			foreach ( $results as $result ) {
				$wpdb->update(
					$wpdb->prefix . 'usermeta',
					array(
						'meta_value' => '1',
					),
					array( 'umeta_id' => $result->umeta_id ),
					array(
						'%s'
					),
					array( '%d' )
				);
			}
		}
		$labels = array(
			'name'              => _x( 'Departure', 'taxonomy general name' ),
			'singular_name'     => _x( 'Departure', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Departure', 'woocommerce-shuttle-booking' ),
			'all_items'         => __( 'All Departure', 'woocommerce-shuttle-booking' ),
			'parent_item'       => __( 'Parent Departure', 'woocommerce-shuttle-booking' ),
			'parent_item_colon' => __( 'Parent Departure:', 'woocommerce-shuttle-booking' ),
			'edit_item'         => __( 'Edit Departure', 'woocommerce-shuttle-booking' ),
			'update_item'       => __( 'Update Departure', 'woocommerce-shuttle-booking' ),
			'add_new_item'      => __( 'Add New Departure', 'woocommerce-shuttle-booking' ),
			'new_item_name'     => __( 'New Departure Name', 'woocommerce-shuttle-booking' ),
			'menu_name'         => __( 'Departure', 'woocommerce-shuttle-booking' ),
		);

		register_taxonomy( 'departure', array( 'product' ), array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'departure' ),
		) );

		$labels = array(
			'name'              => _x( 'Arrival', 'taxonomy general name' ),
			'singular_name'     => _x( 'Arrival', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Arrival', 'woocommerce-shuttle-booking' ),
			'all_items'         => __( 'All Arrival', 'woocommerce-shuttle-booking' ),
			'parent_item'       => __( 'Parent Arrival', 'woocommerce-shuttle-booking' ),
			'parent_item_colon' => __( 'Parent Arrival:', 'woocommerce-shuttle-booking' ),
			'edit_item'         => __( 'Edit Arrival', 'woocommerce-shuttle-booking' ),
			'update_item'       => __( 'Update Arrival', 'woocommerce-shuttle-booking' ),
			'add_new_item'      => __( 'Add New Arrival', 'woocommerce-shuttle-booking' ),
			'new_item_name'     => __( 'New Arrival Name', 'woocommerce-shuttle-booking' ),
			'menu_name'         => __( 'Arrival', 'woocommerce-shuttle-booking' ),
		);

		register_taxonomy( 'arrival', array( 'product' ), array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'arrival' ),
		) );
	}

	public function shuttle_get_week_orders_ids(
		$date_time, $order_status = array(
		'wc-completed',
		'wc-processing'
	)
	) {
		global $wpdb;
		$count   = $count_p = 0;
		$results = $wpdb->get_col( "
      SELECT order_items.order_id
      FROM {$wpdb->prefix}woocommerce_order_items as order_items
      LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
      LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta2 ON order_item_meta.order_item_id = order_item_meta2.order_item_id
      LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
      WHERE posts.post_type = 'shop_order'
      AND posts.post_status IN ( '" . implode( "','", $order_status ) . "' )
      AND order_items.order_item_type = 'line_item'
      AND ( order_item_meta2.meta_key = '_departure_date' OR order_item_meta2.meta_key = '_arrival_date'  )
      AND DATE(order_item_meta2.meta_value) >= '" . date( 'Y/m/d', strtotime( $date_time[0] ) ) . "'
      AND DATE(order_item_meta2.meta_value) <= '" . date( 'Y/m/d', strtotime( $date_time[1] ) ) . "'
      " );
	
		$results = array_unique( $results );
		if ( isset( $results ) && ! empty( $results ) && is_array( $results ) ) {
			foreach ( $results as $order ) {
				$items = new WC_Order( $order );
				$items = $items->get_items();
				foreach ( $items as $item ) {

					$data   = wc_get_order_item_meta( $item->get_id(), 'departure_date', true );
					$data_p = wc_get_order_item_meta( $item->get_id(), '_line_total', true );

					if ( empty( $data ) ) {
						$data = wc_get_order_item_meta( $item->get_id(), 'arrival_date', true );
					}
					if ( $data ) {
						$date_t = date( 'd-m-Y', strtotime( $data ) );
						$s_date = strtotime( $date_time[0] );
						$e_date = strtotime( $date_time[1] );
						$c_date = strtotime( $date_t );

						if ( $s_date <= $c_date && $e_date >= $c_date ) {
							$count   += (int) $item['qty'];
							$count_p += $data_p;
							//echo '<p>'.$date_time[0] .'>='.$data.'&&'.$date_time[1] .'>='.$data.'-----'.$item['qty'].'</p>';
						} else {
							//echo '<p style="color:red;">'.$date_time[0] .'>='.$data.'&&'.$date_time[1] .'>='.$data.'-----'.$item['qty'].'</p>';
						}
					}
				}
			}
		}
		//echo $count;
		//$count = $this->shuttle_get_order_product_count($results);

		return array( $count, $count_p );
	}

	public function shuttle_get_admin_orders_ids_by_product_id(
		$product_id, $date_time, $passenger = '', $order_status = array(
		'wc-completed',
		'wc-processing'
	), $stock
	) {
		global $wpdb;

		$results = $wpdb->get_col( "
      SELECT order_items.order_id
      FROM {$wpdb->prefix}woocommerce_order_items as order_items
      LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
      LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta2 ON order_item_meta.order_item_id = order_item_meta2.order_item_id
      LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
      WHERE posts.post_type = 'shop_order'
      AND posts.post_status IN ( '" . implode( "','", $order_status ) . "' )
      AND order_items.order_item_type = 'line_item'
      AND order_item_meta.meta_key = '_product_id'
      AND order_item_meta.meta_value = '$product_id'
      AND ( order_item_meta2.meta_key = '_departure_date' OR order_item_meta2.meta_key = '_arrival_date'  )
      AND order_item_meta2.meta_value = '" . date( 'Y/m/d G:i', strtotime( $date_time ) ) . "'
      " );
	
		$count = array_filter( $results );
		$count = array_unique( $results );
		$count = $this->shuttle_get_order_product_count( $results, $product_id );

		return $count;
	}

	public function shuttle_get_order_product_count( $orders, $product_id ) {
		$total   = 0;
		$details = '<table><thead><tr><th>Name</th><th>Last Name</th><th>Number of seats</th><th>Mail</th><th>Phone Number</th></tr></thead><tbody>';

		if ( isset( $orders ) && ! empty( $orders ) && is_array( $orders ) ) {
			foreach ( $orders as $order ) {
				$items = new WC_Order( $order );
				$order = wc_get_order( $order );
				$order_data = $order->get_data();

				$items = $items->get_items();
				foreach ( $items as $item ) {
					if ( $product_id == $item['product_id'] ) {
						$total      += (int) $item['qty'];
						$details .= '<tr><td>'.$order_data['billing']['first_name'].'</td><td>'.$order_data['billing']['last_name'].'</td><td>'.$total.'</td><td>'.$order_data['billing']['email'].'</td><td>'.$order_data['billing']['phone'].'</td></tr>';
					}
				}
			}
		}
		$details .= '</body></table>';
		return array( $total, $details );
	}

	public function shuttle_dashboard_callback() {
		?>
        <div class="shuttle-dashboard wrap woocommerce">
            <h1>
				<?php
				if ( isset( $_REQUEST['date_tour'] ) ) {
					echo __( 'Booking Details', 'woocommerce-shuttle-booking' );
				}else{
					echo __( 'Shuttle Dashboard', 'woocommerce-shuttle-booking' );
                }
                ?>
            </h1>
			<?php
			$args       = array(
				'post_type'      => 'product',
				'posts_per_page' => - 1
			);
			$today_book = $today_book_price = 0;
			$class = '';
			if ( isset( $_REQUEST['date_tour'] ) ) {
				$day_name  = date( "l", strtotime( $_REQUEST['date_tour'] ) );
				$date_time = date( "Y/m/d", strtotime( $_REQUEST['date_tour'] ) );
				$class = 'click_here';
			} else {
				$day_name  = date( "l" );
				$date_time = date( "Y/m/d" );
			}

			$day_name = strtolower( $day_name );
			$loop     = new WP_Query( $args );
			if ( $loop->have_posts() ) {
				$data_count = 0;
				echo '<div class="time-dashboad" >';
				while ( $loop->have_posts() ) : $loop->the_post();

					ob_start();
					$datas     = get_post_meta( get_the_ID(), $day_name, true );
					$data_have = false;
					if ( $datas ) {
						echo '<table >';
						if ( isset( $_REQUEST['date_tour'] ) ) {
							echo '<tr><th colspan="4"> ' . get_the_title() . '</th></tr>';
						}else{
							echo '<tr><th colspan="3"> ' . get_the_title() . '</th></tr>';
                        }

						echo '<tr>';
						echo '<td> ' . __( 'Time', 'woocommerce-shuttle-booking' ) . ' </td>';
						echo '<td> ' . __( 'Seats', 'woocommerce-shuttle-booking' ) . ' </td>';
						echo '<td> ' . __( 'Price', 'woocommerce-shuttle-booking' ) . ' </td>';
						if ( isset( $_REQUEST['date_tour'] ) ) {
							echo '<td> ' . __( 'Booking Details', 'woocommerce-shuttle-booking' ) . ' </td>';
						}
						echo '</tr>';
						foreach ( $datas as $key => $value ) {
							if ( $value['price'] ) {
								$stock      = $this->shuttle_get_admin_orders_ids_by_product_id( get_the_ID(), $date_time . ' ' . $value['start_time'], '', $order_status = array(
									'wc-completed',
									'wc-processing'
								), $value['tickets'] );
								$today_book += $stock[0];
								if ( $stock ) {
									$today_book_price += ( $stock[0] * $value['price'] );
								}

								echo '<tr>';
								echo '<td>' . $value['start_time'] .' </td>';
								echo '<td>' . $stock[0] . '/' . $value['tickets'] . '</td>';
								echo '<td >' . wc_price( $value['price'] ) . '</td>';
								if ( isset( $_REQUEST['date_tour'] ) ) {
									echo '<td> <a href="#" data-from="' . get_the_title() . '" data-date="'.$date_time.'" data-qty="' . $stock[0] . '" data-time="' . $value['start_time'] . '" data-price="' . $value['price'] . '" class="'.$class.'" >' . __( 'Details', 'woocommerce-shuttle-booking' ) . '<div style="display: none;" >'.$stock[1].'</div><p class="price_data" style="display: none;" >' . wc_price( $value['price'] ) . '</p></a>  </td>';
								}
								echo '</tr>';
								$data_have = true;
							}
						}
						echo '</table>';
					}
					$output = ob_get_clean();
					if ( $data_have ) {
						$data_count ++;
						echo '<div class="list">';
						echo $output;
						echo '</div>';
					}

				endwhile;
				if ( $data_count < 1 ) {
					echo '<p style="color:red;"> ' . __( 'No Tickets Found.', 'woocommerce-shuttle-booking' ) . '</p>';
				}
				echo '</div><div style="clear: both;"></div>';
				$this_week_dates      = array();
				$this_week_dates[]    = date( 'Y-m-d', strtotime( 'monday this week' ) );
				$this_week_dates[]    = date( 'Y-m-d', strtotime( 'sunday this week' ) );
				$first_day_this_month = date( 'Y-m-01' ); // hard-coded '01' for first day
				$last_day_this_month  = date( 'Y-m-t' );
				$week_stocks          = $this->shuttle_get_week_orders_ids( $this_week_dates );
				$this_stocks          = $this->shuttle_get_week_orders_ids( array(
					$first_day_this_month,
					$last_day_this_month
				) );
				$style                = "";
				if ( isset( $_REQUEST['date_tour'] ) ) {
					$style = "style='display:none';";
				}
				?>

                <div class="time-dashboad" <?php echo $style; ?>>
                    <div class="list"
                         style="min-height: 100px;color: #fff;min-height: 100px;background: #2196f3b8;padding: 0px;">
                        <h3><?php echo __( 'Remplissage Du jour', 'woocommerce-shuttle-booking' ); ?></h3>
                        <p><?php echo $today_book; ?></p>
                    </div>
                    <div class="list" class="list"
                         style="min-height: 100px;color: #fff;min-height: 100px;background: #2196f3b8;padding: 0px;">
                        <h3><?php echo __( 'Remplissage Semaine', 'woocommerce-shuttle-booking' ); ?></h3>
                        <p><?php echo $week_stocks[0]; ?></p>
                    </div>
                    <div class="list" class="list"
                         style="min-height: 100px;color: #fff;min-height: 100px;background: #2196f3b8;padding: 0px;">
                        <h3><?php echo __( 'Remplissage Mois', 'woocommerce-shuttle-booking' ); ?></h3>
                        <p><?php echo $this_stocks[0]; ?></p>
                    </div>
                </div>
                <div class="time-dashboad" <?php echo $style; ?>>
                    <div class="list"
                         style="min-height: 100px;color: #fff;min-height: 100px;background: #f44336;padding: 0px;">
                        <h3><?php echo __( 'Amount In', 'woocommerce-shuttle-booking' ); ?><?php echo get_woocommerce_currency_symbol(); ?><?php echo __( 'Du jour', 'woocommerce-shuttle-booking' ); ?></h3>
                        <p><?php echo wc_price( $today_book_price ); ?></p>
                    </div>
                    <div class="list" class="list"
                         style="min-height: 100px;color: #fff;min-height: 100px;background: #f44336;padding: 0px;">
                        <h3><?php echo __( 'Amount In', 'woocommerce-shuttle-booking' ); ?><?php echo get_woocommerce_currency_symbol(); ?><?php echo __( 'Semaine', 'woocommerce-shuttle-booking' ); ?></h3>
                        <p><?php echo wc_price( $week_stocks[1] ); ?></p>
                    </div>
                    <div class="list" class="list"
                         style="min-height: 100px;color: #fff;min-height: 100px;background: #f44336;padding: 0px;">
                        <h3><?php echo __( 'Amount In', 'woocommerce-shuttle-booking' ); ?><?php echo get_woocommerce_currency_symbol(); ?><?php echo __( 'Mois', 'woocommerce-shuttle-booking' ); ?></h3>
                        <p><?php echo wc_price( $this_stocks[1] ); ?></p>
                    </div>
                </div>
                <script>
                    jQuery( document ).ready( function ($) {
                        jQuery(document).on('click','.click_here',function(){
                            $.magnificPopup.open({
                                items: {
                                    src: '<div id="order-data" class="white-popup"><div class="top"><div class="left"><ul><li><strong>Date:</strong> ' + jQuery(this).attr('data-date') + '</li><li><strong>For:</strong> ' + jQuery(this).attr('data-from')  + '</li><li><strong>Time:</strong> ' + jQuery(this).attr('data-time') + '</li></ul></div><div class="right"><ul><li><strong>Number of seat:</strong> ' + jQuery(this).attr('data-qty') + '</li><li><strong>Price/Seat:</strong> ' + jQuery(this).find( '.price_data' ).html() + ' </li></ul></div></div>'+jQuery(this).find('div').html()+'</div>',
                                    type: 'inline'
                                }
                            }, 0);
                        });
                    } );
                </script>
				<?php
			} else {
				echo __( 'No products found', 'woocommerce-shuttle-booking' );
			}
			wp_reset_postdata();
			?>
            <div style="clear: both;"></div>
        </div>
		<?php
	}

	public function shuttle_calender_callback() {
		?>
        <div class="shuttle-calender wrap woocommerce">
            <h1>
				<?php echo __( 'Shuttle Calender', 'woocommerce-shuttle-booking' ); ?>
            </h1>
            <div id='calendar'></div>
        </div>
		<?php
		$events = $events_list = array();
		$args   = array( 'status' => array( 'wc-processing', 'wc-completed' ) );
		$orders = wc_get_orders( $args );
		foreach ( $orders as $order ) {
			//https://stackoverflow.com/questions/39401393/how-to-get-woocommerce-order-details/44708344
			$order_data = $order->get_data();
			$order_id   = $order_data['id'];
			//$order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');
			$order_billing_first_name = $order_data['billing']['first_name'];
			$order_billing_last_name  = $order_data['billing']['last_name'];
			$order_billing_email      = $order_data['billing']['email'];
			$order_billing_phone      = $order_data['billing']['phone'];
			$order_total              = $order_data['total'] . ' ' . $order_data['currency'];
			$items                    = $order->get_item_count();

			$item_name = '';
			if ( ! empty( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item_id => $item_values ) {
					$item_name     .= $item_values->get_name() . ', ';
					$to_from       = '';
					$item_quantity = $order->get_item_meta( $item_id, '_qty', true );
					$item_total    = $order->get_item_meta( $item_id, '_line_total', true );

					$departure_date = $order->get_item_meta( $item_id, 'departure_date', true );
					$departure_end  = $order->get_item_meta( $item_id, 'departure_end', true );
					$arrival_date   = $order->get_item_meta( $item_id, 'arrival_date', true );
					$arrival_end    = $order->get_item_meta( $item_id, 'arrival_end', true );

					if ( ! empty( $departure_date ) ) {
						$order_date_created = $departure_date;
					}
					if ( ! empty( $arrival_date ) ) {
						$order_date_created = $arrival_date;
					}

					$item_data = $item_values->get_data();
					$terms     = get_the_terms( $item_data['product_id'], 'departure' );
					if ( ! empty( $terms ) ) {
						foreach ( $terms as $term ) {
							$to_from .= $term->name;
						}
					}

					$terms = get_the_terms( $item_data['product_id'], 'arrival' );
					if ( ! empty( $terms ) ) {
						foreach ( $terms as $term ) {
							$to_from .= ' - ' . $term->name;
						}
					}

					if ( ! empty( $order_date_created ) ) {
						$events_list[ $order_date_created ][ $to_from ][] = array(
							'title'       => '#' . $order_id . ' ' . $order_billing_first_name . ' ' . $order_billing_last_name,
							'start'       => $order_date_created,
							'end'         => date( 'Y-m-d H:i', strtotime( '+1 hour', strtotime( $order_date_created ) ) ),
							'fname'       => $order_billing_first_name,
							'lname'       => $order_billing_last_name,
							'email'       => $order_billing_email,
							'phone'       => $order_billing_phone,
							'items'       => $item_quantity,
							'order_total' => $item_total,
							'date'        => date( 'j F, Y', strtotime( $order_date_created ) ),
							'time'        => date( 'g:i A', strtotime( $order_date_created ) ),
							'item_name'   => trim( $item_name, ', ' ),
							'to_from'     => $to_from
						);
					}
				}
			}
		}

		if ( ! empty( $events_list ) ) {
			foreach ( $events_list as $key => $event_list ) {
				if ( ! empty( $event_list ) ) {
					foreach ( $event_list as $event_key => $event ) {
						$order_total = 0;
						$order_items = 0;

						if ( ! empty( $event ) ) {
							foreach ( $event as $event_price ) {
								$order_total = $event_price['order_total'] / $event_price['items'];
								$order_items = $order_items + $event_price['items'];
							}
						}

						$events[] = array(
							'title'       => $event_key,
							'start'       => $key,
							'end'         => date( 'Y-m-d H:i', strtotime( '+1 hour', strtotime( $key ) ) ),
							'event'       => json_encode( $event ),
							'date'        => $key,
							'time'        => date( 'g:i A', strtotime( $key ) ),
							'to_from'     => $event_key,
							'order_items' => $order_items,
							'order_total' => $order_total
						);
					}
				}
			}
		}
		?>
        <script>
            jQuery(document).ready(function ($) {
                jQuery(document).on('click', '.fc-day-number', function () {

                    jQuery( '#calendar' ).hide();
                    jQuery('.shuttle-calender.wrap.woocommerce h1').html( '<?php echo __( 'Booking Details', 'woocommerce-shuttle-booking' ); ?>' );
                    var date_d = jQuery.parseJSON(jQuery(this).attr('data-goto'));
                    console.log(date_d.date);
                    window.location.href = '<?php echo site_url( 'wp-admin/edit.php?post_type=product&page=shuttle-dashboard&date_tour=' ) ?>' + date_d.date;
                    return false;
                });
                $('#calendar').fullCalendar({
					monthNames: ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
    monthNamesShort: ["janv.","févr.","mars","avr.","mai","juin","juil.","août","sept.","oct.","nov.","déc."],
    dayNames: ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"],
    dayNamesShort: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"],
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month'
                    },
                    eventClick: function (calEventData, jsEvent, view) {

                        $('.my-overlay').show();
                        var calEventLists = jQuery.parseJSON(calEventData.event);
                        var date = calEventData.date;
                        var time = calEventData.time;
                        var to_from = calEventData.to_from;
                        var items = calEventData.order_items;
                        var order_total = calEventData.order_total;
                        var list = '';

                        $.each(calEventLists, function (key, calEvent) {

                            console.log(calEvent);
                            var fname = calEvent.fname;
                            var lname = calEvent.lname;
                            var phone = calEvent.phone;
                            var email = calEvent.email;
                            var items = calEvent.items;

                            list = list.concat('<tr><td>' + fname + '</td><td>' + lname + '</td><td>' + items + '</td><td>' + email + '</td><td>' + phone + '</td></tr>');
                        });

                        $.magnificPopup.open({
                            items: {
                                src: '<div id="order-data" class="white-popup"><div class="top"><div class="left"><ul><li><strong>Date:</strong> ' + date + '</li><li><strong>For:</strong> ' + to_from + '</li><li><strong>Time:</strong> ' + time + '</li></ul></div><div class="right"><ul><li><strong>Number of seat:</strong> ' + items + '</li><li><strong>Price/Seat:</strong> ' + order_total + '</li></ul></div></div><table><thead><tr><th>Name</th><th>Last Name</th><th>Number of seats</th><th>Mail</th><th>Phone Number</th></tr></thead><tbody>' + list + '</tbody></table></div>',
                                type: 'inline'
                            }
                        }, 0);


                    },
                    navLinks: true, // can click day/week names to navigate views
                    editable: false,
                    eventLimit: true, // allow "more" link when too many events
                    events: <?php echo json_encode( $events ); ?>,
                    //eventColor: '#4fe44f',
                    //borderColor: '#0a3e0a',
                    timeFormat: 'h:mm A'
                });
            });

        </script>
		<?php
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Shuttle_Booking_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Shuttle_Booking_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-shuttle-booking-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'fullcalendar-style', plugin_dir_url( __FILE__ ) . 'css/fullcalendar.min.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'magnific-popup', plugin_dir_url( __FILE__ ) . 'css/magnific-popup.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Shuttle_Booking_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Shuttle_Booking_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script( 'repeater-script', plugin_dir_url( __FILE__ ) . 'js/jquery.repeater.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-shuttle-booking-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'magnific-popup', plugin_dir_url( __FILE__ ) . 'js/jquery.magnific-popup.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'fullcalendar-moment', plugin_dir_url( __FILE__ ) . 'js/moment.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'fullcalendar-script', plugin_dir_url( __FILE__ ) . 'js/fullcalendar.min.js', array( 'jquery' ), $this->version, false );
	}

}
