<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woocommerce_Shuttle_BookingChoose your Arrival Station
 * 
 * @subpackage Woocommerce_Shuttle_Booking/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woocommerce_Shuttle_Booking
 * @subpackage Woocommerce_Shuttle_Booking/public
 * @author     Woocommerce Shuttle Booking Team <wsb@mail.com>
 */
class Woocommerce_Shuttle_Booking_Public {

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
	 * @param      string $plugin_name The name of the plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_shortcode( 'book_a_shuttle', array( $this, 'book_a_shuttle_callback' ) );
		add_action( 'wp_ajax_shuttle_get_passenger', array( $this, 'shuttle_get_passenger_callback' ) );
		add_action( 'wp_ajax_nopriv_shuttle_get_passenger', array( $this, 'shuttle_get_passenger_callback' ) );

		add_action( 'wp_ajax_shuttle_show_price', array( $this, 'shuttle_show_price_callback' ) );
		add_action( 'wp_ajax_nopriv_shuttle_show_price', array( $this, 'shuttle_show_price_callback' ) );

		add_action( 'wp_ajax_shuttle_add_to_cart', array( $this, 'shuttle_add_to_cart_callback' ) );
		add_action( 'wp_ajax_nopriv_shuttle_add_to_cart', array( $this, 'shuttle_add_to_cart_callback' ) );


		add_action( 'wp_ajax_get_arrivals', array( $this, 'get_arrivals_callback' ) );
		add_action( 'wp_ajax_nopriv_get_arrivals', array( $this, 'get_arrivals_callback' ) );


		add_action( 'init', array( $this, 'shuttle_checkout_callback' ) );
		add_filter( 'woocommerce_get_item_data', array( $this, 'shuttle_woocommerce_get_item_data' ), 20, 2 );
		add_action( 'woocommerce_add_order_item_meta', array(
			$this,
			'shuttle_woocommerce_add_order_item_meta'
		), 10, 2 );
		add_filter( 'woocommerce_order_item_product', array( $this, 'shuttle_woocommerce_order_item_product' ), 10, 2 );
		add_filter( 'woocommerce_email_order_meta_fields', array(
			$this,
			'shuttle_woocommerce_email_order_meta_fields'
		) );
		add_action( 'woocommerce_before_calculate_totals', array(
			$this,
			'woo_product_quantity_range_prices_apply_in_cart'
		) );
		add_action( 'woosms_extends_variables', array(
			$this,
			'custom_meta_in_sms_template_sivtc'
		), 10,2 );
		add_action( 'wp', array(
			$this,
			'webby_empty_woocommerce' 
		) );
	}
	
	public function custom_meta_in_sms_template_sivtc( $variables, $db ) {
		global $wpdb,$woocommerce;
		$order_id = $variables->get('order_id');

		$destination_address  = get_post_meta($order_id, '_arrival_address', true );
		$pick_up_time  = get_post_meta($order_id, '_departure_date', true );
		$starting_address  = get_post_meta($order_id, '_departure_address', true );
		$product_name  = get_post_meta($order_id, '_product_name', true );

		if(!empty($starting_address)) {
			$variables->set("departure_address",$starting_address);
		}
		if(!empty($pick_up_time)) {
			$variables->set("departure_date",$pick_up_time);
		}
		if(!empty($product_name)) {
			$variables->set("product_name",$product_name);
		}

	}

	public function book_a_shuttle_callback( $atts ) {
		$atts = shortcode_atts( array(
			'title' => ''
		), $atts, 'book_a_shuttle_callback' );
		ob_start();
		?>
        <form id="book_a_shuttle" method="post">
            <div style="display: none;" id="loadingDiv">
                <div class="loader">Loading...</div>
            </div>
            <div class="shuttle-error"></div>
            <div class="title_trip">
                <div class="title"><h2><?php echo $atts['title']; ?></h2></div>
                <div class="trip">
                    <p><?php _e( 'Return Trip?', 'woocommerce-shuttle-booking' ); ?> &nbsp;&nbsp;</p>
                    <label class="switch"><input type="checkbox" id="togBtn" name="is_return" class="is_return"
                                                 value="yes">
                        <div class="slider round"><span
                                    class="on"><?php echo __( 'ON', 'woocommerce-shuttle-booking' ); ?></span><span
                                    class="off"><?php echo __( 'OFF', 'woocommerce-shuttle-booking' ); ?></span></div>
                    </label>
                </div>
            </div>
            <div class="full">
                <label>
                    <span><?php echo __( 'Pickup', 'woocommerce-shuttle-booking' ); ?></span>
					<?php $departures = get_terms( array( 'taxonomy' => 'departure', 'hide_empty' => true ) ); ?>
                    <select class="pickup" name="pickup_loc" id="pickup" required>
                        <option disabled="true" selected value=""
                                style="color: #abadb2;"><?php echo __( 'Choose your Departure Station', 'woocommerce-shuttle-booking' ); ?></option>
						<?php if ( ! empty( $departures ) ) { ?>
							<?php foreach ( $departures as $departure ) { ?>
                                <option value="<?php echo $departure->slug; ?>"><?php echo $departure->name; ?></option>
							<?php } ?>
						<?php } ?>
                    </select>
                </label>

                <label>
                    <span><?php echo __( 'Dropoff', 'woocommerce-shuttle-booking' ); ?></span>
					<?php $arrivals = get_terms( array( 'taxonomy' => 'arrival', 'hide_empty' => true ) ); ?>
                    <select class="dropoff" name="dropoff_loc" required>
                        <option disabled="true" selected value=""
                                style="color: #abadb2;"><?php echo __( 'Choose your Arrival Station', 'woocommerce-shuttle-booking' ); ?></option>
						<?php if ( ! empty( $arrivals ) ) { ?>
							<?php foreach ( $arrivals as $arrival ) { ?>
                                <option value="<?php echo $arrival->slug; ?>"
                                        class="<?php echo $arrival->name; ?>"><?php echo $arrival->name; ?></option>
							<?php } ?>
						<?php } ?>
                    </select>
                </label>
            </div>

            <div class="half half-perfect">
                <div class="departure">
                    <div class="innner">
                        <input type="text" name="departure_date_time" id="departure_date_time" class="date_time"
                               autocomplete="off" required placeholder="<?php echo __( 'Select Date', 'woocommerce-shuttle-booking' ); ?>"/>
                        <select class="departure_time time" name="departure_time" placeholder="Select Time" required>
                            <option disabled="true" selected=""
                                    style="color: #abadb2;"><?php echo __( 'Select Time', 'woocommerce-shuttle-booking' ); ?></option>
                        </select>
                    </div>
                </div>
                <div class="arrival" style="display:none;">
                    <div class="innner">
                        <input type="text" name="arrival_date_time" id="arrival_date_time" autocomplete="off"
                               class="date_time" placeholder="<?php  echo __( 'Select Return Date', 'woocommerce-shuttle-booking' ); ?>"/>
                        <select class="arrival_time time" name="arrival_time" placeholder="Select Time" >
                            <option disabled="true" selected=""
                                    style="color: #abadb2;"><?php echo __( 'Select Time', 'woocommerce-shuttle-booking' ); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="half">
                <label>
                    <span><i class="fa fa-users" aria-hidden="true"></i></span>
                    <select class="passenger" name="passenger" required>
                        <option disabled="true" selected style="color: #abadb2;"
                                value="0"><?php echo __( 'Select Passenger', 'woocommerce-shuttle-booking' ); ?></option>
                    </select>
                </label>

                <input type="text" name="coupon"
                       placeholder="<?php echo __( 'Coupon Code', 'woocommerce-shuttle-booking' ); ?>"/>
            </div>
            <input type="hidden" name="arrival_id" class="arrival_id" value="0"/>
            <input type="hidden" name="pickup_id" class="pickup_id" value="0"/>
            <input type="hidden" name="arrival_stock" class="arrival_stock" value="0"/>
            <input type="hidden" name="departure_price" class="departure_price" value="0"/>
            <input type="hidden" name="departure_end" class="departure_end" value="0"/>
            <input type="hidden" name="arrival_end" class="arrival_end" value="0"/>
            <input type="hidden" name="arrival_price" class="arrival_price" value="0"/>

            <input type="submit" value="<?php echo __( 'Show Price', 'woocommerce-shuttle-booking' ); ?>"
                   class="wc-submit"/>
        </form>

        <form id="checkout_a_shuttle" method="post" style="display: none;">
            <div class="left">
                <div class="price_box"></div>
            </div>
            <div class="right">
                <input type="hidden" id="c-passenger" name="c_passenger" value=""/>
                <input type="hidden" id="c-coupon" name="c_coupon" value=""/>
                <input type="hidden" id="c-departure" name="c_departure" value=""/>
                <input type="hidden" id="c-arrival" name="c_arrival" value=""/>
                <input type="hidden" id="c-departure-date" name="c_departure_date" value=""/>
                <input type="hidden" id="c-arrival-date" name="c_arrival_date" value=""/>
                <input type="hidden" name="departure_price" class="departure_price" value="0"/>
                <input type="hidden" name="departure_end" class="departure_end" value="0"/>
                <input type="hidden" name="arrival_end" class="arrival_end" value="0"/>
                <input type="hidden" name="arrival_price" class="arrival_price" value="0"/>
				<?php wp_nonce_field( 'shuttleCheckout', 'formType' ); ?>
                <input type="submit" value="<?php echo __( 'Book Now', 'woocommerce-shuttle-booking' ); ?>" class="wc-submit wc-get-price-submit" disabled="true"/>
            </div>
        </form>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
				 
                $(".is_return").change(function () {
                    if (this.checked) {
                        $('.arrival').show();
                        $('.arrival_time').attr('required', true);
                    } else {
                        $('.arrival').hide();
                        $('.arrival_time').attr('required', false);
                    }
                });

                $("#checkout_a_shuttle").submit(function (event) {

                    var data = {
                        action: 'shuttle_add_to_cart',
                        form_data: $(this).serialize()
                    }
                    jQuery.ajax({
                        data: data,
                        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        method: "POST",
                        success: function (resp) {
                            window.location.href = resp;
                        }
                    });

                    return false;
                });
                $("#book_a_shuttle").submit(function (event) {

                    $('#loadingDiv').show();

                    var data = {
                        action: 'shuttle_show_price',
                        form_data: $(this).serialize()
                    }

                    jQuery.ajax({
                        data: data,
                        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        method: "POST",
                        success: function (resp) {
                            $('#loadingDiv').hide();
                            $('.shuttle-error').empty();
                            $('#c-passenger').val('');
                            $('#c-coupon').val('');
                            $('#c-product-ids').val('');

                            var res = jQuery.parseJSON(resp);
                            $('#c-coupon').val(res.coupon);

                            if (res.error) {
                                $('.shuttle-error').append('<p>' + res.error + '</p>');
                                $('#checkout_a_shuttle').hide();
                            } else {
                                $('#checkout_a_shuttle').show();
                                $('.wc-get-price-submit').attr("disabled", true);
                                if (res.departure != '' && res.passenger != '') {
                                    $('#c-passenger').val(res.passenger);
                                    $('#c-departure').val(res.departure);
                                    $('#c-arrival').val(res.arrival);
                                    $('#c-departure-date').val(res.departure_date + ' ' + $('.departure_time').val());
                                    $('#c-arrival-date').val(res.arrival_date + ' ' + $('.arrival_time').val());
                                    $('#checkout_a_shuttle .price_box').empty();
                                    $('#checkout_a_shuttle .price_box').append(res.departure_price);
                                    $('#checkout_a_shuttle .price_box').append(res.arrival_price);
                                    $('.wc-get-price-submit').attr("disabled", false);
                                }
                            }
                        }
                    });
                    event.preventDefault();
                });

                $(document).on('change', '.dropoff, .departure_time, .arrival_time', function () {
                    get_shuttle_get_passenger();
                });

                $(document).on('change', '.departure_time', function () {
                    $('.departure_time option').each(function () {
                        if ($(this).val() == $('.departure_time').val()) {
                            $('.departure_price').val($(this).attr('data-price'));
                            $('.departure_end').val($(this).attr('data-end'));
                        }
                    });
                });
                $(document).on('change', '.pickup', function () {
                    var pickup = $('select.pickup :selected').val();
                    var data = {
                        action: 'get_arrivals',
                        pickup_location: pickup,
                    }
					$('#loadingDiv').show();
                    jQuery.ajax({
                        data: data,
                        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        method: "POST",
                        success: function (res) {
							 $('#loadingDiv').hide();
                            if (res.action == 1) {
                                if (res.dropoff_location.length > 0) {
                                    var dropoff_location = '<option disabled="true" selected="" value="" style="color: #abadb2;"><?php esc_html_e( 'Choose your Arrival Station', 'woocommerce-shuttle-booking' ); ?></option>';
                                    jQuery.each(res.dropoff_location, function (index, value) {
                                        dropoff_location += '<option value="' + value + '">' + value + '</option>';
                                    });
                                    jQuery('.dropoff').html(dropoff_location);
                                }else{
									 var dropoff_location = '<option disabled="true" selected="" value="" style="color: #abadb2;"><?php esc_html_e( 'Choose your Arrival Station', 'woocommerce-shuttle-booking' ); ?></option>';
									jQuery('.dropoff').html(dropoff_location);
								}
                            }
                            $('select.passenger').empty();
                            $('select.passenger').append('<option disabled="true" selected style="color: #abadb2;"><?php echo __( 'Select Passenger', 'woocommerce-shuttle-booking' ); ?></option>');
                        }
                    });
                });
                $(document).on('change', '.arrival_time', function () {
                    $('.arrival_time option').each(function () {
                        if ($(this).val() == $('.arrival_time').val()) {
                            $('.arrival_price').val($(this).attr('data-price'));
                            $('.arrival_end').val($(this).attr('data-end'));
                        }
                    });
                });
				//console.log($.datetimepickerFactory.default_options);
              // $.datetimepicker.default_options({i18n:'fr'});
				function get_shuttle_get_passenger() {
                    var pickup = $('select.pickup :selected').val();
                    var pickup_text = $('select.pickup :selected').text();
                    var dropoff = $('select.dropoff :selected').val();
                    var arrival_date = $('#arrival_date_time').val();
                    var date_time = $('#departure_date_time').val();
                    var pickup_time = $('select.departure_time :selected').val();
                    var dropoff_time = $('select.arrival_time').val();
                    var passenger = $('.passenger').val();
                    var is_return = false;
                    if ($('.is_return').prop('checked')) {
                        is_return = true;
                    }

                    if (pickup != null) {
                        $('.dropoff option').show();
                        $('.dropoff option.' + pickup_text).hide();
                    }

                    if (pickup != null && dropoff != null) {
                        $('#loadingDiv').show();
                        var data = {
                            action: 'shuttle_get_passenger',
                            pickup_location: pickup,
                            dropoff_location: dropoff,
                            pickup_time: pickup_time,
                            dropoff_time: dropoff_time,
                            date_time: date_time,
                            arrival_date: arrival_date,
                            passenger: passenger,
                            is_return: is_return
                        }

                        jQuery.ajax({
                            data: data,
                            url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                            method: "POST",
                            success: function (res) {
                                if (res.action == 5) {

                                }

                                if (res.dropoff) {
                                    $('#departure_date_time').datetimepicker({
                                        format: 'Y/m/d',
                                        formatTime: 'H:i',
                                        formatDate: 'Y/m/d',
                                        step: 5,
                                        timepicker: false,
                                        minDate: '<?php echo date( 'Y/m/d' ); ?>',
                                        disabledWeekDays: res.dropoff.day,
                                        allowTimes: res.dropoff.start_time,
                                        onChangeDateTime: function () {
                                            get_shuttle_get_passenger();
                                        }
                                    });
                                    var depart_tile = $('select.departure_time').val();

                                    $('select.departure_time').empty();
                                    if ( res.dropoff.start_time ) {
                                        $('.departure_time').append(res.dropoff.start_time); 										 $('.departure_time').val(depart_tile);
                                        setTimeout( function(){
                                            console.log($('.departure_time').val());
                                            if ( ! $('.departure_time').val() ) {
                                                $('select.departure_time').val(0);
                                            }
                                        }, 100 );
                                    } else {
                                        $('select.departure_time').append('<option value="0" data-price="0" data-end="0" selected="true" style="color: #abadb2;"><?php esc_html_e( 'Select Time', 'woocommerce-shuttle-booking' ); ?></option>');
                                        setTimeout( function(){
                                            $('select.departure_time').val(0);
                                        }, 100 );
                                    }
                                    $('.pickup_id').val(res.dropoff.product_id);
                                }

                                if (res.arrival) {
                                    $('#arrival_date_time').datetimepicker({
                                        format: 'Y/m/d',
                                        formatTime: 'H:i',
                                        formatDate: 'Y/m/d',
                                        step: 5,
                                        timepicker: false,
                                        minDate: '<?php echo date( 'Y/m/d' ); ?>',
                                        disabledWeekDays: res.arrival.day,
                                        allowTimes: res.arrival.start_time,
                                        onChangeDateTime: function () {
                                            get_shuttle_get_passenger();
                                        }
                                    });
                                    var depart_tile = $('select.arrival_time').val();
                                    $('select.arrival_time').empty();
                                    if ( res.arrival.start_time ) {
                                        $('.arrival_time').append(res.arrival.start_time);
                                        $('.arrival_time').val(depart_tile);
                                        setTimeout( function(){
                                            console.log($('.arrival_time').val());
                                            if ( ! $('.arrival_time').val() ) {
                                                $('select.arrival_time').val(0);
                                            }
                                        }, 100 );
                                    } else {
                                        $('select.arrival_time').append('<option value="0" data-price="0" data-end="0" selected="true" style="color: #abadb2;"><?php esc_html_e( 'Select Time', 'woocommerce-shuttle-booking' ); ?></option>');
                                        setTimeout( function(){
                                            $('select.arrival_time').val(0);
                                        }, 100 );
                                    }
                                    $('.arrival_id').val(res.arrival.product_id);
                                    $('.arrival_stock').val(res.arrival.stock);

                                }

                                $('#loadingDiv').hide();
                                var options = '';
                                if (res.action == 2) {
                                    for (i = 1; i <= res.dropoff.stock; i++) {
                                        options += '<option value="' + i + '">' + i + ' <?php echo __( 'Passenger', 'woocommerce-shuttle-booking' ); ?></option>';
                                    }
                                    $('select.passenger').empty();
                                    $('select.passenger').append(options);
                                } else {
                                    if (res.action == 1) {
                                        if (res.dropoff_location.length > 0) {
                                            var dropoff_location = '<option disabled="true" selected="" value="" style="color: #abadb2;"><?php esc_html_e( 'Choose your Arrival Station', 'woocommerce-shuttle-booking' ); ?></option>';
                                            jQuery.each(res.dropoff_location, function (index, value) {
                                                dropoff_location += '<option value="' + value + '">' + value + '</option>';
                                            });
                                            jQuery('.dropoff').html(dropoff_location);
                                        }
                                    }
                                    $('select.passenger').empty();
                                    $('select.passenger').append('<option disabled="true" selected style="color: #abadb2;"><?php esc_html_e( 'Select Passenger', 'woocommerce-shuttle-booking' ); ?></option>');
                                }
                            }
                        });
                    }
                }

            });
        </script>
		<?php
		return ob_get_clean();
	}

	public function woo_product_quantity_range_prices_apply_in_cart( $cart_object ) {

		foreach ( $cart_object->cart_contents as $key => $value ) {
			$price = 0;
			if ( isset( $value['departure_price'] ) ) {
				$price = $value['departure_price'];
			}
			if ( isset( $value['arrival_price'] ) ) {
				$price = $value['arrival_price'];
			}
			$value['data']->set_price( $price );
		}
	}
	public function webby_empty_woocommerce() {
		session_start();

	}
	public function shuttle_checkout_callback() {

		if ( isset( $_POST['formType'] ) && wp_verify_nonce( $_POST['formType'], 'shuttleCheckout' ) ) {
			global $woocommerce;
			$checkout_url     = $woocommerce->cart->get_checkout_url();
			$passenger        = $_POST['c_passenger'];
			$coupon_code      = $_POST['c_coupon'];
			$c_departure      = $_POST['c_departure'];
			$c_arrival        = $_POST['c_arrival'];
			$c_departure_date = $_POST['c_departure_date'];
			$c_arrival_date   = $_POST['c_arrival_date'];

			if ( ! empty( $c_departure ) ) {
				$extra_details = array(
					'departure_date'  => $c_departure_date,
					'departure_price' => $_POST['departure_price'],
					'departure_end'   => $_POST['departure_end']
				);
				if ( get_post_meta( $c_departure, 'departure_address', true ) ) {
					$extra_details['Departure Address'] = get_post_meta( $c_departure, 'departure_address', true );
				}
				if ( get_post_meta( $c_departure, 'arrival_address', true ) ) {
					$extra_details['Arrival Address'] = get_post_meta( $c_departure, 'arrival_address', true );
				}
				$woocommerce->cart->add_to_cart( $c_departure, $passenger, '0', array(), $extra_details );
			}

			if ( ! empty( $c_arrival ) ) {
				$extra_details = array(
					'arrival_date'  => $c_arrival_date,
					'arrival_price' => $_POST['arrival_price'],
					'arrival_end'   => $_POST['arrival_end']
				);
				if ( get_post_meta( $c_arrival, 'departure_address', true ) ) {
					$extra_details['Departure Address'] = get_post_meta( $c_arrival, 'departure_address', true );
				}
				if ( get_post_meta( $c_arrival, 'arrival_address', true ) ) {
					$extra_details['Arrival Address'] = get_post_meta( $c_arrival, 'arrival_address', true );
				}
				$woocommerce->cart->add_to_cart( $c_arrival, $passenger, '0', array(), $extra_details );
			}

			$woocommerce->cart->add_discount( sanitize_text_field( $coupon_code ) );
			wp_redirect( $checkout_url );
		}
	}

	public function shuttle_woocommerce_get_item_data( $other_data, $cart_item ) {

		if ( isset( $cart_item['departure_date'] ) ) {

			$other_data[] = array(
				'name'  => __( 'Departure Date', 'woocommerce-shuttle-booking' ),
				'value' => sanitize_text_field( $cart_item['departure_date'] )
			);
		}
		if ( isset( $cart_item['Departure Address'] ) ) {

			$other_data[] = array(
				'name'  => __( 'Departure Address', 'woocommerce-shuttle-booking' ),
				'value' => sanitize_text_field( $cart_item['Departure Address'] )
			);
		}
		if ( isset( $cart_item['Arrival Address'] ) ) {

			$other_data[] = array(
				'name'  => __( 'Arrival Address', 'woocommerce-shuttle-booking' ),
				'value' => sanitize_text_field( $cart_item['Arrival Address'] )
			);
		}

		if ( isset( $cart_item['arrival_date'] ) ) {

			$other_data[] = array(
				'name'  => __( 'Arrival Date', 'woocommerce-shuttle-booking' ),
				'value' => sanitize_text_field( $cart_item['arrival_date'] )
			);
		}

		if ( isset( $cart_item['departure_end'] ) ) {

			$other_data[] = array(
				'name'  => __( 'Arrival End Time', 'woocommerce-shuttle-booking' ),
				'value' => sanitize_text_field( $cart_item['departure_end'] )
			);
		}

		if ( isset( $cart_item['arrival_end'] ) ) {

			$other_data[] = array(
				'name'  => __( 'Arrival End Time', 'woocommerce-shuttle-booking' ),
				'value' => sanitize_text_field( $cart_item['arrival_end'] )
			);
		}

		return $other_data;
	}

	public function shuttle_woocommerce_add_order_item_meta( $item_id, $values ) {
		$extra_meta = array();
		if ( ! empty( $values['departure_date'] ) ) {
			woocommerce_add_order_item_meta( $item_id, __( 'Departure Date', 'woocommerce-shuttle-booking' ), $values['departure_date'] );
			woocommerce_add_order_item_meta( $item_id, '_departure_date', $values['departure_date'] );
			$extra_meta[] = __( 'Departure Date', 'woocommerce-shuttle-booking' );
		}
		if ( ! empty( $values['arrival_date'] ) ) {
			woocommerce_add_order_item_meta( $item_id, __( 'Arrival Date', 'woocommerce-shuttle-booking' ), $values['arrival_date'] );
			woocommerce_add_order_item_meta( $item_id, '_arrival_date', $values['arrival_date'] );
			$extra_meta[] = __( 'Arrival Date', 'woocommerce-shuttle-booking' );
		}
		if ( ! empty( $values['departure_end'] ) ) {
			woocommerce_add_order_item_meta( $item_id, __( 'Departure End', 'woocommerce-shuttle-booking' ), $values['departure_end'] );
			woocommerce_add_order_item_meta( $item_id, '_departure_end', $values['departure_end'] );
			$extra_meta[] = __( 'Departure End', 'woocommerce-shuttle-booking' );
		}
		if ( ! empty( $values['arrival_end'] ) ) {
			woocommerce_add_order_item_meta( $item_id, __( 'Arrival End', 'woocommerce-shuttle-booking' ), $values['arrival_end'] );
			woocommerce_add_order_item_meta( $item_id, '_arrival_end', $values['arrival_end'] );
			$extra_meta[] = __( 'Arrival End', 'woocommerce-shuttle-booking' );
		}
		if ( ! empty( $values['Arrival Address'] ) ) {
			woocommerce_add_order_item_meta( $item_id, __( 'Arrival Address', 'woocommerce-shuttle-booking' ), $values['Arrival Address'] );
			woocommerce_add_order_item_meta( $item_id, '_arrival_address', $values['Arrival Address'] );
			$extra_meta[] = __( 'Arrival Address', 'woocommerce-shuttle-booking' );
		}
		if ( ! empty( $values['Departure Address'] ) ) {
			woocommerce_add_order_item_meta( $item_id, __( 'Departure Address', 'woocommerce-shuttle-booking' ), $values['Departure Address'] );
			woocommerce_add_order_item_meta( $item_id, '_departure_address', $values['Departure Address'] );
			$extra_meta[] = __( 'Departure Address', 'woocommerce-shuttle-booking' );
		}
		if ( $extra_meta ) {
			woocommerce_add_order_item_meta( $item_id, '_woo_extra_meta', $extra_meta );
		}
		$this->webby_get_order_items( $item_id );
	}
	public function webby_get_order_items( $item_id ) {
		global $wpdb, $table_prefix;
		$orders     = $wpdb->get_results( "SELECT order_id FROM `{$table_prefix}woocommerce_order_items` WHERE order_item_id = {$item_id}" );
		$item_name = array();
		if ( $orders ) {
			$items     = $wpdb->get_results( "SELECT order_item_id,order_item_name FROM `{$table_prefix}woocommerce_order_items` WHERE order_id = {$orders[0]->order_id} LIMIT 1" );
			if ( $items ) {
				update_post_meta( $orders[0]->order_id, '_departure_date', woocommerce_get_order_item_meta( $items[0]->order_item_id, '_departure_date', true ) );
				
				update_post_meta( $orders[0]->order_id, '_departure_end', woocommerce_get_order_item_meta( $items[0]->order_item_id, '_departure_end', true ) );
				
				update_post_meta( $orders[0]->order_id, '_arrival_address', woocommerce_get_order_item_meta( $items[0]->order_item_id, '_arrival_address', true ) );
				
				update_post_meta( $orders[0]->order_id, '_departure_address', woocommerce_get_order_item_meta( $items[0]->order_item_id, '_departure_address', true ) );
				
				update_post_meta( $orders[0]->order_id, '_product_name', $items[0]->order_item_name );
			}
		}
	}
	public function shuttle_woocommerce_order_item_product( $cart_item, $order_item ) {

		if ( isset( $order_item['departure_date'] ) ) {
			$cart_item_meta['departure_date'] = $order_item['departure_date'];
		}

		if ( isset( $order_item['arrival_date'] ) ) {
			$cart_item_meta['arrival_date'] = $order_item['arrival_date'];
		}

		if ( isset( $order_item['departure_end'] ) ) {
			$cart_item_meta['departure_end'] = $order_item['departure_end'];
		}

		if ( isset( $order_item['arrival_end'] ) ) {
			$cart_item_meta['arrival_end'] = $order_item['arrival_end'];
		}

		return $cart_item;
	}
	
	public function shuttle_woocommerce_email_order_meta_fields( $fields ) {
		$fields['departure_date'] = __( 'Departure Date', 'woocommerce-shuttle-booking' );
		$fields['arrival_date']   = __( 'Arrival Date', 'woocommerce-shuttle-booking' );

		return $fields;
	}

	public function shuttle_add_to_cart_callback() {
		$params = array();
		parse_str( $_POST['form_data'], $params );
		global $woocommerce;
		$checkout_url     = $woocommerce->cart->get_checkout_url();
		$passenger        = $params['c_passenger'];
		$coupon_code      = $params['c_coupon'];
		$c_departure      = $params['c_departure'];
		$c_arrival        = $params['c_arrival'];
		$c_departure_date = $params['c_departure_date'];
		$c_arrival_date   = $params['c_arrival_date'];

		if ( ! empty( $c_departure ) ) {
			$extra_details = array(
				'departure_date'  => $c_departure_date,
				'departure_price' => $params['departure_price'],
				'departure_end'   => $params['departure_end']
			);
			if ( get_post_meta( $c_departure, 'departure_address', true ) ) {
				$extra_details['Departure Address'] = get_post_meta( $c_departure, 'departure_address', true );
			}
			if ( get_post_meta( $c_departure, 'arrival_address', true ) ) {
				$extra_details['Arrival Address'] = get_post_meta( $c_departure, 'arrival_address', true );
			}
			$woocommerce->cart->add_to_cart( $c_departure, $passenger, '0', array(), $extra_details );
		}

		if ( ! empty( $c_arrival ) ) {
			$extra_details = array(
				'arrival_date'  => $c_arrival_date,
				'arrival_price' => $params['arrival_price'],
				'arrival_end'   => $params['arrival_end']
			);
			if ( get_post_meta( $c_arrival, 'departure_address', true ) ) {
				$extra_details['Departure Address'] = get_post_meta( $c_arrival, 'departure_address', true );
			}
			if ( get_post_meta( $c_arrival, 'arrival_address', true ) ) {
				$extra_details['Arrival Address'] = get_post_meta( $c_arrival, 'arrival_address', true );
			}
			$woocommerce->cart->add_to_cart( $c_arrival, $passenger, '0', array(), $extra_details );
		}
		if ( $coupon_code ) {
			$woocommerce->cart->add_discount( sanitize_text_field( $coupon_code ) );
		}
		//wp_redirect($checkout_url);
		wp_send_json( $checkout_url );
	}

	public function shuttle_show_price_callback() {
		$params = array();
		parse_str( $_POST['form_data'], $params );

		$pickup_loc          = $params['pickup_loc'];
		$dropoff_loc         = $params['dropoff_loc'];
		$passenger           = $params['passenger'];
		$coupon              = $params['coupon'];
		$departure_date_time = $params['departure_date_time'];
		$arrival_date_time   = $params['arrival_date_time'];
		$is_return           = $params['is_return'];

		$response       = array();
		$showPriceError = '';
		if ( isset( $is_return ) ) {
			if ( strtotime( $departure_date_time ) > strtotime( $arrival_date_time ) ) {
				$showPriceError .= sprintf( '<p style="margin: 0" ><strong> %s </strong> %s </p>', __( 'ERROR:', 'woocommerce-shuttle-booking' ), __( 'Dropoff time should be more than the Pickup time.', 'woocommerce-shuttle-booking' ) );
			}
		}

		if ( empty( $pickup_loc ) ) {
			$showPriceError .= sprintf( '<p style="margin: 0" ><strong> %s </strong> %s </p>', __( 'ERROR:', 'woocommerce-shuttle-booking' ), __( ' Pickup is required.' ) );
		}

		if ( empty( $dropoff_loc ) ) {
			$showPriceError .= sprintf( '<p style="margin: 0" ><strong> %s </strong> %s </p>', __( 'ERROR:', 'woocommerce-shuttle-booking' ), __( ' Dropoff is required.' ) );
		}

		if ( empty( $passenger ) ) {
			$showPriceError .= sprintf( '<p style="margin: 0" ><strong> %s </strong> %s </p>', __( 'ERROR:', 'woocommerce-shuttle-booking' ), __( ' Passenger is required.' ) );
		}

		if ( empty( $departure_date_time ) ) {
			$showPriceError .= sprintf( '<p style="margin: 0" ><strong> %s </strong> %s </p>', __( 'ERROR:', 'woocommerce-shuttle-booking' ), __( ' Departure date and time is required.', 'woocommerce-shuttle-booking' ) );
		}

		if ( isset( $is_return ) && empty( $arrival_date_time ) ) {
			$showPriceError .= sprintf( '<p style="margin: 0" ><strong> %s </strong> %s </p>', __( 'ERROR:', 'woocommerce-shuttle-booking' ), __( 'Arrival date and time is required.', 'woocommerce-shuttle-booking' ) );
		}

		$showPriceError_Trim = trim( $showPriceError, ',' );
		$showPriceError_Trim = str_replace( ",", "<br/>", $showPriceError_Trim );

		if ( empty( $showPriceError_Trim ) ) {
			$product_ids = array();
			$price       = 0;
			$first       = $params['pickup_id'];
			if ( empty( trim( $first ) ) ) {
				$showPriceError .= sprintf( '<p style="margin: 0" ><strong> %s </strong> %s </p>', __( 'ERROR:', 'woocommerce-shuttle-booking' ), __( 'Departure is not available on selected date & time.', 'woocommerce-shuttle-booking' ) );
			} else {
				$product_ids[]               = $first;
				$response['departure']       = $first;
				$response['departure_date']  = $departure_date_time;
				$response['departure_price'] = '<div class="first"><div class="info"> <b>' . __( 'Departure time: ', 'woocommerce-shuttle-booking' ) .' </b>'. $params['departure_time'] . '  <b>' . __( 'Arrival Time: ', 'woocommerce-shuttle-booking' ) .' </b>'. $params['departure_end'] . ' <br/><b>' . __( 'Departure Address: ', 'woocommerce-shuttle-booking' ) .' </b>'. get_post_meta( $params['pickup_id'], 'departure_address', true ) . '<br/> <b>' . __( 'Arrival Address: ', 'woocommerce-shuttle-booking' ) .' </b>'. get_post_meta( $params['pickup_id'], 'arrival_address', true ) . '</div><div class="price"><b>' . $params['departure_price'] . get_woocommerce_currency_symbol() . __( '/ per person ', 'woocommerce-shuttle-booking' ) . '</b></div></div>';
			}

			if ( isset( $is_return ) && $is_return == 'yes' ) {
				$second = $params['arrival_id'];
				if ( $params['arrival_stock'] < $passenger ) {
					$showPriceError .= sprintf( '<p style="margin: 0" ><strong> %s </strong> %s </p>', __( 'ERROR:', 'woocommerce-shuttle-booking' ), __( 'Arrival is not available on selected date & time.', 'woocommerce-shuttle-booking' ) );
				} else {
					$product_ids[]             = $second;
					$response['arrival']       = $second;
					$response['arrival_date']  = $arrival_date_time;
					$response['arrival_price'] = '<div class="second"><div class="info"> <b>' . __( 'Departure time: ', 'woocommerce-shuttle-booking' ) .' </b>'. $params['arrival_time'] . ' <b>' . __( 'Arrival Time: ', 'woocommerce-shuttle-booking' ) .' </b>'. $params['arrival_end'] . ' <br/><b>' . __( 'Departure Address: ', 'woocommerce-shuttle-booking' ) .' </b>'. get_post_meta( $params['arrival_id'], 'departure_address', true ) . '<br/><b>' . __( 'Arrival Address: ', 'woocommerce-shuttle-booking' ) .' </b>'. get_post_meta( $params['arrival_id'], 'arrival_address', true ) . '</div><div class="price"><b>' . $params['arrival_price'] . get_woocommerce_currency_symbol() . __( '/ per person ', 'woocommerce-shuttle-booking' ) . '</b></div></div>';
				}
			}
			$showPriceError_Trim = trim( $showPriceError, ',' );
			$showPriceError_Trim = str_replace( ",", "<br/>", $showPriceError_Trim );
		}

		if ( ! empty( $product_ids ) ) {
			foreach ( $product_ids as $product_id ) {
				$_product = wc_get_product( $product_id );
				$price    = $price + $_product->get_price();
			}

			$response['product_ids'] = $product_ids_text;
			//$response['price'] = ( $price * $passenger ) . get_woocommerce_currency_symbol();
			$response['price'] = sprintf( $price . get_woocommerce_currency_symbol() . '%s', __( '/ per person', 'woocommerce-shuttle-booking' ) );
		}

		if ( ! empty( $showPriceError_Trim ) ) {
			$response['error'] = $showPriceError;
		}

		$response['coupon']    = $coupon;
		$response['passenger'] = $passenger;

		echo json_encode( $response );
		die();
	}

	public function get_product_id_by_data( $pickup_loc, $dropoff_loc, $departure_date_time, $arrival_date_time, $passenger = '' ) {
		$return_id = '';
		$args      = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'arrival',
					'field'    => 'slug',
					'terms'    => array( $dropoff_loc )
				),
				array(
					'taxonomy' => 'departure',
					'field'    => 'term_id',
					'terms'    => array( $pickup_loc ),
				),
			),
		);

		$my_query = null;
		$my_query = new WP_Query( $args );
		if ( $my_query->have_posts() ) {
			while ( $my_query->have_posts() ) : $my_query->the_post();
				$flag = 0;

				$stock      = $this->shuttle_get_orders_ids_by_product_id( get_the_ID(), $departure_date_time, $passenger );
				$start_time = get_post_meta( get_the_ID(), 'start_time', true );
				$end_time   = get_post_meta( get_the_ID(), 'end_time', true );
				$start_day  = get_post_meta( get_the_ID(), 'start_day', true );

				$departure_time = date( 'G:i', strtotime( $departure_date_time ) );
				$day            = date( 'l', strtotime( $departure_date_time ) );

				if ( $stock != 0 && $start_day == strtolower( $day ) && strtotime( $start_time ) <= strtotime( $departure_time ) ) {
					if ( ! empty( $passenger ) ) {
						if ( $stock >= $passenger ) {
							$flag = 1;
						}
					} else {
						$flag = 1;
					}
				}

				if ( $flag ) {
					$return_id = get_the_ID();
				}

			endwhile;
		}
		wp_reset_query();

		return $return_id;
	}

	public function shuttle_get_orders_ids_by_product_id(
		$product_id, $date_time, $passenger = '', $order_status = array(
		'wc-completed',
		'wc-processing'
	), $stock, $key
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
      AND ( order_item_meta2.meta_key = 'departure_date' OR order_item_meta2.meta_key = 'arrival_date'  )
      AND order_item_meta2.meta_value = '" . date( 'Y/m/d G:i', strtotime( $date_time ) ) . "'
      " );

		$count = array_filter( $results );
		$count = array_unique( $results );
		$count = $this->shuttle_get_order_product_count( $results, $product_id );
		$stock = $stock - $count;

		return $stock;
	}

	public function shuttle_get_order_product_count( $orders, $product_id ) {
		$total = 0;

		if ( isset( $orders ) && ! empty( $orders ) && is_array( $orders ) ) {
			foreach ( $orders as $order ) {
				$items = new WC_Order( $order );
				$items = $items->get_items();
				foreach ( $items as $item ) {
					if ( $product_id == $item['product_id'] ) {
						$total = (int) $item['qty'];
					}
				}
			}
		}

		return $total;
	}

	public function get_day_number_by_name( $days ) {

		$numbers     = array( 0, 1, 2, 3, 4, 5, 6 );
		$all_numbers = array();
		if ( ! empty( $days ) ) {
			foreach ( $days as $day ) {
				if ( ! empty( $day ) ) {
					foreach ( $day as $number ) {
						$all_numbers[] = $number;
					}
				}
			}
		}

		if ( ! empty( $all_numbers ) ) {
			foreach ( $all_numbers as $value ) {
				if ( ( $key = array_search( $value, $numbers ) ) !== false ) {
					unset( $numbers[ $key ] );
				}
			}
		}

		return '[' . implode( ', ', $numbers ) . ']';
	}

	public function get_full_time_array( $times_array, $price ) {
		$times_options = '<option value="0" data-price="0" data-end="0" selected="true" style="color: #abadb2;">'. esc_html__( 'Select Time', 'woocommerce-shuttle-booking' ).'</option>';
		if ( ! empty( $times_array ) ) {
			foreach ( $times_array[0] as $key => $single_time ) {
				$times_options .= '<option data-price="' . $price[0][ $key ][0] . '" data-end="' . $price[0][ $key ][1] . '" value="' . $single_time . '">' . $single_time . '</option>';
			}
		}

		$times_options = $times_options;

		return $times_options;
	}

	public function get_end_time_array( $times ) {
		$times_array   = array();
		$times_options = '';
		if ( ! empty( $times ) ) {
			foreach ( $times as $time ) {
				if ( ! empty( $time ) ) {
					foreach ( $time as $start_time ) {
						$times_array[] = $start_time;
					}
				}
			}
		}

		$times_array = json_encode( $times_array );

		return $times_array;
	}

	public function get_available_days( $post_id ) {
		$return_days = array();
		$days        = array(
			'sunday',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
		);

		foreach ( $days as $key => $day ) {
			$datas = get_post_meta( $post_id, $day, true );
			if ( ! empty( $datas ) ) {
				if ( ! empty( $datas[0]['price'] ) ) {
					$return_days[] = $key;
				}
			}
		}

		return $return_days;
	}

	public function get_available_price( $post_id, $date ) {
		$start_time = array();
		$day        = date( 'l', strtotime( $date ) );
		$day_name   = strtolower( $day );

		$datas = get_post_meta( $post_id, $day_name, true );
		if ( ! empty( $datas ) ) {
			foreach ( $datas as $data ) {
				$start_time[] = array( $data['price'], $data['end_time'] );
			}
		}

		return $start_time;
	}

	public function get_available_stock( $post_id, $date, $time ) {
		$day        = date( 'l', strtotime( $date ) );
		$day_name   = strtolower( $day );
		$start_time = 0;
		$datas      = get_post_meta( $post_id, $day_name, true );
		if ( ! empty( $datas ) ) {
			foreach ( $datas as $data ) {
				if ( $time == $data['start_time'] ) {
					$start_time = $data['tickets'];
				}
			}
		}

		return $start_time;
	}

	public function get_available_start_time( $post_id, $date_time ) {
		$day        = date( 'l', strtotime( $date_time ) );
		$day_name   = strtolower( $day );
		$start_time = array();

		$datas = get_post_meta( $post_id, $day_name, true );
		if ( ! empty( $datas ) ) {
			foreach ( $datas as $data ) {
				$start_time[] = $data['start_time'];
			}
		}

		return $start_time;
	}

	public function get_available_end_time( $post_id ) {
		$start_time = array();
		$days       = array(
			'sunday',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
		);

		foreach ( $days as $key => $day ) {
			$datas = get_post_meta( $post_id, $day, true );
			if ( ! empty( $datas ) ) {
				foreach ( $datas as $data ) {
					$start_time[ $data['start_time'] ] = $data['end_time'];
				}
			}
		}

		return $start_time;
	}

	public function get_arrivals_callback() {
		$pickup_location = $_POST['pickup_location'];
		$args            = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'departure',
					'field'    => 'slug',
					'terms'    => array( $pickup_location ),
				),
			),
		);

		$terms_data = array();
		$product_id = 0;
		$my_query   = new WP_Query( $args );
		if ( $my_query->have_posts() ) {
			while ( $my_query->have_posts() ) : $my_query->the_post();
				$terms = get_the_terms( get_the_ID(), 'arrival' );
				if ( $terms ) {
					foreach ( $terms as $value ) {
						$terms_data[] = $value->name;
					}
				}
			endwhile;
		}
		$terms_data = array_unique( $terms_data );
		wp_send_json( array( 'action' => 1, 'result' => 'yes', 'dropoff_location' => $terms_data ) );
	}

	public function shuttle_get_passenger_callback() {
		global $woocommerce;
		$woocommerce->cart->empty_cart();
		$pickup_location  = $_POST['pickup_location'];
		$dropoff_location = $_POST['dropoff_location'];
		$pickup_time      = $_POST['pickup_time'];
		$dropoff_time     = $_POST['dropoff_time'];
		$date_time        = $_POST['date_time'];
		$arrival_date     = $_POST['arrival_date'];
		$is_return        = $_POST['is_return'];
		$passenger        = $_POST['passenger'];

		$stock    = 0;
		$action   = 0;
		$stocks   = 0;
		$day      = $start_time = $end_time = $price = array();
		$day_name = array(
			'sunday',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
		);

		if ( ! empty( $pickup_location ) && ! empty( $dropoff_location ) ) {
			$args       = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'tax_query'      => array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'arrival',
						'field'    => 'slug',
						'terms'    => array( $dropoff_location ),
					),
					array(
						'taxonomy' => 'departure',
						'field'    => 'slug',
						'terms'    => array( $pickup_location ),
					),
				),
			);
			$product_id = 0;
			$my_query   = new WP_Query( $args );
			if ( $my_query->have_posts() ) {
				$terms_data = array();
				while ( $my_query->have_posts() ) : $my_query->the_post();
					$terms = get_the_terms( get_the_ID(), 'arrival' );
					if ( $terms ) {
						foreach ( $terms as $value ) {
							$terms_data[] = $value->name;
						}
					}

					$day[] = $this->get_available_days( get_the_ID() );
					if ( ! empty( $date_time ) ) {
						$start_time[] = $this->get_available_start_time( get_the_ID(), $date_time );
						$end_time[]   = $this->get_available_end_time( get_the_ID() );
						$price[]      = $this->get_available_price( get_the_ID(), $date_time );
						if ( ! empty( $date_time ) && ! empty( $pickup_time ) ) {
							$stocks = $this->get_available_stock( get_the_ID(), $date_time, $pickup_time );
						}
					}
					$product_id = get_the_ID();

				endwhile;
			}

			$dropoff = array( 'day' => $this->get_day_number_by_name( $day ), 'product_id' => $product_id );

			if ( ! empty( $start_time ) ) {
				$dropoff['start_time'] = $this->get_full_time_array( $start_time, $price );
			}

			if ( ! empty( $end_time ) ) {
				$dropoff['end_time'] = $this->get_end_time_array( $end_time );
			}

			if ( ! empty( $date_time ) && ! empty( $pickup_time ) ) {
				$stock            = $this->shuttle_get_orders_ids_by_product_id( get_the_ID(), $date_time . ' ' . $pickup_time, $passenger, $order_status = array(
					'wc-completed',
					'wc-processing'
				), $stocks, 'departure_date' );
				$dropoff['stock'] = $stock;
				$action           = 2;
			}

			wp_reset_query();
			$terms_data = array_unique( $terms_data );
			$arrival    = array();
			if ( $is_return ) {
				$day        = $start_time = $end_time = $price = array();
				$stocks     = 0;
				$args       = array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => - 1,
					'tax_query'      => array(
						'relation' => 'AND',
						array(
							'taxonomy' => 'arrival',
							'field'    => 'slug',
							'terms'    => array( $pickup_location ),
						),
						array(
							'taxonomy' => 'departure',
							'field'    => 'slug',
							'terms'    => array( $dropoff_location ),
						),
					),
				);
				$product_id = 0;
				$my_query   = new WP_Query( $args );
				if ( $my_query->have_posts() ) {
					$terms_data = array();
					while ( $my_query->have_posts() ) : $my_query->the_post();
						$terms = get_the_terms( get_the_ID(), 'arrival' );
						if ( $terms ) {
							foreach ( $terms as $value ) {
								$terms_data[] = $value->name;
							}
						}

						$day[] = $this->get_available_days( get_the_ID() );
						if ( ! empty( $arrival_date ) ) {
							$start_time[] = $this->get_available_start_time( get_the_ID(), $arrival_date );
							$end_time[]   = $this->get_available_end_time( get_the_ID() );
							$price[]      = $this->get_available_price( get_the_ID(), $arrival_date );
							if ( ! empty( $arrival_date ) && ! empty( $dropoff_time ) ) {
								$stocks = $this->get_available_stock( get_the_ID(), $arrival_date, $dropoff_time );
							}
						}
						$product_id = get_the_ID();

					endwhile;
				}

				$arrival = array( 'day' => $this->get_day_number_by_name( $day ), 'product_id' => $product_id );
				if ( ! empty( $start_time ) ) {
					$arrival['start_time'] = $this->get_full_time_array( $start_time, $price );
				}

				if ( ! empty( $end_time ) ) {
					$arrival['end_time'] = $this->get_end_time_array( $end_time );
				}

				if ( ! empty( $arrival_date ) && ! empty( $dropoff_time ) ) {
					$stock            = $this->shuttle_get_orders_ids_by_product_id( get_the_ID(), $arrival_date . ' ' . $dropoff_time, $passenger, $order_status = array(
						'wc-completed',
						'wc-processing'
					), $stocks, 'arrival_date' );
					$arrival['stock'] = $stock;
					$action           = 2;
				}

				wp_reset_query();
			}


			wp_send_json( array(
				'action'           => $action,
				'result'           => 'yes',
				'dropoff'          => $dropoff,
				'arrival'          => $arrival,
				'dropoff_location' => $terms_data
			) );
		}

		die();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-shuttle-booking-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-datetimepicker', plugin_dir_url( __FILE__ ) . 'css/jquery.datetimepicker.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-font-awesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-shuttle-booking-public.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-datetimepicker', plugin_dir_url( __FILE__ ) . 'js/jquery.datetimepicker.js', array( 'jquery' ), $this->version, false );
	}

}
