<?php
/**
 * Plugin Name: Manual B2B Orders for WooCommerce
 * Description: Create manual / B2B orders from a custom product list (products not on the website). Orders are real WooCommerce orders, so they count in sales and flow to Shadowfax and other integrations.
 * Version: 1.0.0
 * Author: Asha Webtech IT Solution Private Limited
 * Author URI: https://ashawebtechitsolutions.com/
 * Developer: Chandrabhan Singh Dhakad
 * Developer URI: https://ashawebtechitsolutions.com/
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 * Text Domain: cbd-mo
 *
 * Developed by Asha Webtech IT Solution Private Limited
 * Chandrabhan Singh Dhakad | info@chandrabhansingh.in | +91 7024315567 | +91 9755121478
 * Office No. 69, Ratan Colony, New Jail Road, Huzur, Bhopal - 462038, India
 */

defined( 'ABSPATH' ) || exit;

final class CBD_Manual_Orders {

	const OPT = 'cbd_mo_products';
	const CAP = 'manage_woocommerce';

	public static function init() {
		$i = new self();
		add_action( 'before_woocommerce_init', function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		} );
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		add_action( 'admin_menu', array( $i, 'menu' ) );
		add_filter( 'plugin_row_meta', function ( $links, $file ) {
			if ( plugin_basename( __FILE__ ) === $file ) {
				$links[] = '<a href="https://ashawebtechitsolutions.com/" target="_blank" rel="noopener">Asha Webtech</a>';
				$links[] = '<a href="mailto:info@chandrabhansingh.in">Support</a>';
			}
			return $links;
		}, 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $l ) {
			array_unshift( $l, '<a href="' . esc_url( admin_url( 'admin.php?page=cbd-mo-new' ) ) . '">Add Order</a>' );
			return $l;
		} );
		add_action( 'admin_enqueue_scripts', array( $i, 'assets' ), 20 );
		add_action( 'admin_post_cbd_mo_save_product', array( $i, 'save_product' ) );
		add_action( 'admin_post_cbd_mo_delete_product', array( $i, 'delete_product' ) );
		add_action( 'admin_post_cbd_mo_create_order', array( $i, 'create_order' ) );
		add_action( 'wp_ajax_cbd_mo_customer', array( $i, 'ajax_customer' ) );
	}

	/* ---------------------------------------------------------------- Products store */

	public static function activate() {
		if ( false === get_option( self::OPT ) ) {
			$seed = array(
				'Hemp oil 85mg / 5ml'                  => 450,
				'Hemp oil 165mg / 10ml'                => 800,
				'Vet Cure Max 10ml'                    => 999,
				'Vet Cure Max 30ml'                    => 2000,
				'Vet Cure Max Large Dogs 30ml'         => 3999,
				'Vet Cure Max Vijaya 10ml'             => 999,
				'Vet Cure Max Vijaya 30ml'             => 2499,
				'Vet Cure Max Vijaya Large Dogs 30ml'  => 4499,
				'B2B'                                  => 20000,
			);
			$out = array();
			$id  = 1;
			foreach ( $seed as $n => $p ) {
				$out[] = array( 'id' => $id++, 'name' => $n, 'price' => $p, 'active' => 1 );
			}
			update_option( self::OPT, $out, false );
		}
	}

	private function products( $only_active = false ) {
		$p = get_option( self::OPT, array() );
		if ( ! is_array( $p ) ) {
			$p = array();
		}
		if ( $only_active ) {
			$p = array_values( array_filter( $p, function ( $x ) { return ! empty( $x['active'] ); } ) );
		}
		return $p;
	}

	/* ---------------------------------------------------------------- Menu / assets */

	public function menu() {
		add_menu_page( 'B2B Orders', 'B2B Orders', self::CAP, 'cbd-mo', array( $this, 'page_list' ), 'dashicons-clipboard', 56 );
		add_submenu_page( 'cbd-mo', 'Manual Orders', 'All Manual Orders', self::CAP, 'cbd-mo', array( $this, 'page_list' ) );
		add_submenu_page( 'cbd-mo', 'Add New Order', 'Add New Order', self::CAP, 'cbd-mo-new', array( $this, 'page_new' ) );
		add_submenu_page( 'cbd-mo', 'Custom Products', 'Custom Products', self::CAP, 'cbd-mo-products', array( $this, 'page_products' ) );
		add_submenu_page( 'cbd-mo', 'About / Support', 'About / Support', self::CAP, 'cbd-mo-about', array( $this, 'page_about' ) );
	}

	public function page_about() {
		echo '<div class="wrap"><h1>Manual B2B Orders – About &amp; Support</h1><div class="cbd-box" style="max-width:640px">';
		echo '<h2>Developed by</h2>';
		echo '<p><strong>Asha Webtech IT Solution Private Limited</strong><br>Developer: Chandrabhan Singh Dhakad</p>';
		echo '<p>Website: <a href="https://ashawebtechitsolutions.com/" target="_blank" rel="noopener">ashawebtechitsolutions.com</a><br>';
		echo 'Email: <a href="mailto:info@chandrabhansingh.in">info@chandrabhansingh.in</a><br>';
		echo 'Phone: <a href="tel:+917024315567">+91 70243 15567</a> / <a href="tel:+919755121478">+91 97551 21478</a></p>';
		echo '<p>Office No. 69, Ratan Colony, New Jail Road, Huzur, Bhopal - 462038, India</p>';
		echo '<p>Version 1.0.0</p></div></div>';
	}

	public function assets( $hook ) {
		if ( false === strpos( $hook, 'cbd-mo' ) ) {
			return;
		}
		wp_enqueue_style( 'woocommerce_admin_styles' );
		if ( false !== strpos( $hook, 'cbd-mo-new' ) ) {
			wp_enqueue_script( 'wc-enhanced-select' );
		}
		wp_register_style( 'cbd-mo', false );
		wp_enqueue_style( 'cbd-mo' );
		wp_add_inline_style( 'cbd-mo', '
			.cbd-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:20px}
			@media(max-width:1200px){.cbd-grid{grid-template-columns:1fr}}
			.cbd-box{background:#fff;border:1px solid #c3c4c7;padding:16px 20px;box-shadow:0 1px 1px rgba(0,0,0,.04)}
			.cbd-box h2{margin:0 0 12px;padding:0;font-size:14px}
			.cbd-box label{display:block;font-weight:600;margin:10px 0 3px}
			.cbd-box input[type=text],.cbd-box input[type=email],.cbd-box input[type=number],.cbd-box input[type=datetime-local],.cbd-box select,.cbd-box textarea{width:100%;max-width:100%}
			.cbd-2{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}
			.cbd-items{width:100%;border-collapse:collapse}
			.cbd-items th{text-align:left;padding:8px;background:#f6f7f7;border-bottom:1px solid #c3c4c7}
			.cbd-items td{padding:6px 8px;border-bottom:1px solid #f0f0f1;vertical-align:top}
			.cbd-items input,.cbd-items select{width:100%}
			.cbd-totals{float:right;width:320px;margin-top:15px}
			.cbd-totals td{padding:4px 8px}.cbd-totals td:last-child{text-align:right}
			.cbd-totals .grand td{font-weight:700;border-top:1px solid #c3c4c7;font-size:15px}
			.cbd-modes label{display:inline-block;margin-right:16px;font-weight:400}
			.cbd-hide{display:none}
		' );
	}

	/* ---------------------------------------------------------------- Page: list */

	public function page_list() {
		$orders = wc_get_orders( array(
			'limit'      => 100,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'meta_key'   => '_cbd_manual_order',
			'meta_value' => 'yes',
		) );
		$sum = 0;
		foreach ( $orders as $o ) {
			if ( ! in_array( $o->get_status(), array( 'cancelled', 'failed', 'refunded' ), true ) ) {
				$sum += (float) $o->get_total();
			}
		}
		echo '<div class="wrap"><h1 class="wp-heading-inline">Manual B2B Orders</h1> <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=cbd-mo-new' ) ) . '">Add New Order</a><hr class="wp-header-end">';
		echo '<p>Showing the latest ' . count( $orders ) . ' manual orders. Total value (excl. cancelled/failed/refunded): <strong>' . wp_kses_post( wc_price( $sum ) ) . '</strong></p>';
		echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Phone</th><th>Status</th><th>PO / Ref</th><th>Total</th></tr></thead><tbody>';
		if ( ! $orders ) {
			echo '<tr><td colspan="7">No manual orders yet.</td></tr>';
		}
		foreach ( $orders as $o ) {
			$name = trim( $o->get_billing_company() . ' ' . ( $o->get_billing_company() ? '— ' : '' ) . $o->get_formatted_billing_full_name() );
			printf(
				'<tr><td><a href="%s"><strong>#%s</strong></a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_url( $o->get_edit_order_url() ),
				esc_html( $o->get_order_number() ),
				esc_html( wc_format_datetime( $o->get_date_created(), 'd M Y, h:i A' ) ),
				esc_html( $name ),
				esc_html( $o->get_billing_phone() ),
				esc_html( wc_get_order_status_name( $o->get_status() ) ),
				esc_html( $o->get_meta( '_cbd_po_number' ) ),
				wp_kses_post( $o->get_formatted_order_total() )
			);
		}
		echo '</tbody></table></div>';
	}

	/* ---------------------------------------------------------------- Page: products */

	public function page_products() {
		$products = $this->products();
		$edit     = null;
		if ( isset( $_GET['edit'] ) ) {
			foreach ( $products as $p ) {
				if ( (int) $p['id'] === (int) $_GET['edit'] ) {
					$edit = $p;
				}
			}
		}
		echo '<div class="wrap"><h1>Custom Products</h1><p>These products are only available in the manual order screen. They are <strong>not</strong> shown on the website.</p>';
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>Saved.</p></div>';
		}
		echo '<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start">';

		echo '<div class="cbd-box" style="min-width:300px;max-width:360px"><h2>' . ( $edit ? 'Edit product' : 'Add product' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'cbd_mo_product' );
		echo '<input type="hidden" name="action" value="cbd_mo_save_product"><input type="hidden" name="id" value="' . esc_attr( $edit['id'] ?? 0 ) . '">';
		echo '<label>Name</label><input type="text" name="name" required value="' . esc_attr( $edit['name'] ?? '' ) . '">';
		echo '<label>Default price (₹)</label><input type="number" step="0.01" min="0" name="price" required value="' . esc_attr( $edit['price'] ?? '' ) . '">';
		echo '<label><input type="checkbox" name="active" value="1" ' . checked( ! $edit || ! empty( $edit['active'] ), true, false ) . '> Active (show in order screen)</label>';
		echo '<p><button class="button button-primary">' . ( $edit ? 'Update' : 'Add product' ) . '</button> ';
		if ( $edit ) {
			echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=cbd-mo-products' ) ) . '">Cancel</a>';
		}
		echo '</p></form></div>';

		echo '<div style="flex:1;min-width:400px"><table class="wp-list-table widefat fixed striped"><thead><tr><th>Name</th><th style="width:110px">Price</th><th style="width:80px">Active</th><th style="width:130px">Actions</th></tr></thead><tbody>';
		foreach ( $products as $p ) {
			$del = wp_nonce_url( admin_url( 'admin-post.php?action=cbd_mo_delete_product&id=' . (int) $p['id'] ), 'cbd_mo_del_' . (int) $p['id'] );
			printf(
				'<tr><td>%s</td><td>%s</td><td>%s</td><td><a href="%s">Edit</a> | <a href="%s" onclick="return confirm(\'Delete this product?\')" style="color:#b32d2e">Delete</a></td></tr>',
				esc_html( $p['name'] ),
				wp_kses_post( wc_price( $p['price'] ) ),
				! empty( $p['active'] ) ? 'Yes' : 'No',
				esc_url( admin_url( 'admin.php?page=cbd-mo-products&edit=' . (int) $p['id'] ) ),
				esc_url( $del )
			);
		}
		echo '</tbody></table></div></div></div>';
	}

	public function save_product() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'cbd_mo_product' );
		$products = $this->products();
		$id       = (int) ( $_POST['id'] ?? 0 );
		$row      = array(
			'id'     => $id,
			'name'   => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'price'  => (float) wc_format_decimal( wp_unslash( $_POST['price'] ?? 0 ) ),
			'active' => empty( $_POST['active'] ) ? 0 : 1,
		);
		if ( $id ) {
			foreach ( $products as $k => $p ) {
				if ( (int) $p['id'] === $id ) {
					$products[ $k ] = $row;
				}
			}
		} else {
			$max        = $products ? max( wp_list_pluck( $products, 'id' ) ) : 0;
			$row['id']  = $max + 1;
			$products[] = $row;
		}
		update_option( self::OPT, array_values( $products ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=cbd-mo-products&saved=1' ) );
		exit;
	}

	public function delete_product() {
		$id = (int) ( $_GET['id'] ?? 0 );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'cbd_mo_del_' . $id );
		$products = array_values( array_filter( $this->products(), function ( $p ) use ( $id ) { return (int) $p['id'] !== $id; } ) );
		update_option( self::OPT, $products, false );
		wp_safe_redirect( admin_url( 'admin.php?page=cbd-mo-products&saved=1' ) );
		exit;
	}

	/* ---------------------------------------------------------------- Page: new order */

	private function addr_fields( $type ) {
		$countries = WC()->countries->get_countries();
		$default   = 'IN';
		$fields    = array(
			'first_name' => 'First name',
			'last_name'  => 'Last name',
			'company'    => 'Company',
			'address_1'  => 'Address line 1',
			'address_2'  => 'Address line 2',
			'city'       => 'City',
			'postcode'   => 'Pincode / ZIP',
		);
		echo '<div class="cbd-2">';
		foreach ( array( 'first_name', 'last_name' ) as $f ) {
			echo '<div><label>' . esc_html( $fields[ $f ] ) . '</label><input type="text" name="' . esc_attr( "{$type}_{$f}" ) . '" id="' . esc_attr( "{$type}_{$f}" ) . '"></div>';
		}
		echo '</div>';
		foreach ( array( 'company', 'address_1', 'address_2' ) as $f ) {
			echo '<label>' . esc_html( $fields[ $f ] ) . '</label><input type="text" name="' . esc_attr( "{$type}_{$f}" ) . '" id="' . esc_attr( "{$type}_{$f}" ) . '">';
		}
		echo '<div class="cbd-2"><div><label>City</label><input type="text" name="' . esc_attr( "{$type}_city" ) . '" id="' . esc_attr( "{$type}_city" ) . '"></div>';
		echo '<div><label>Pincode / ZIP</label><input type="text" name="' . esc_attr( "{$type}_postcode" ) . '" id="' . esc_attr( "{$type}_postcode" ) . '"></div></div>';
		echo '<label>Country</label><select name="' . esc_attr( "{$type}_country" ) . '" id="' . esc_attr( "{$type}_country" ) . '" class="cbd-country" data-type="' . esc_attr( $type ) . '">';
		foreach ( $countries as $code => $label ) {
			echo '<option value="' . esc_attr( $code ) . '" ' . selected( $code, $default, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select><label>State</label><span id="' . esc_attr( "{$type}_state_wrap" ) . '"></span>';
		if ( 'billing' === $type ) {
			echo '<label>Email</label><input type="email" name="billing_email" id="billing_email">';
		}
		echo '<label>Phone</label><input type="text" name="' . esc_attr( "{$type}_phone" ) . '" id="' . esc_attr( "{$type}_phone" ) . '">';
		if ( 'billing' === $type ) {
			echo '<label>GSTIN (optional)</label><input type="text" name="billing_gstin" id="billing_gstin">';
		}
	}

	public function page_new() {
		$products = $this->products( true );
		$js_prod  = array();
		foreach ( $products as $p ) {
			$js_prod[] = array( 'id' => $p['id'], 'name' => $p['name'], 'price' => (float) $p['price'] );
		}
		$statuses = wc_get_order_statuses();
		$now      = current_time( 'Y-m-d\TH:i' );

		echo '<div class="wrap"><h1>Add New Manual Order</h1>';
		if ( ! empty( $_GET['cbd_error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( wp_unslash( $_GET['cbd_error'] ) ) . '</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="cbd-form">';
		wp_nonce_field( 'cbd_mo_create' );
		echo '<input type="hidden" name="action" value="cbd_mo_create_order"><div class="cbd-grid">';

		// General + customer
		echo '<div class="cbd-box"><h2>General</h2>';
		echo '<label>Date created</label><input type="datetime-local" name="order_date" value="' . esc_attr( $now ) . '">';
		echo '<label>Status</label><select name="status">';
		foreach ( $statuses as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, 'wc-processing', false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select><p class="description">"Processing" sends the order to Shadowfax and your other integrations.</p>';
		echo '<label>Customer</label><div class="cbd-modes">';
		echo '<label><input type="radio" name="cust_mode" value="guest" checked> Guest</label>';
		echo '<label><input type="radio" name="cust_mode" value="existing"> Existing</label>';
		echo '<label><input type="radio" name="cust_mode" value="new"> New account</label></div>';
		echo '<div id="cbd-existing" class="cbd-hide"><select class="wc-customer-search" name="customer_id" id="cbd_customer_id" data-placeholder="Search customer by name / email…" data-allow_clear="true"></select></div>';
		echo '<p class="description cbd-hide" id="cbd-new-note">A WooCommerce customer account will be created using the billing email.</p>';
		echo '<label>PO number / reference (optional)</label><input type="text" name="po_number">';
		echo '<label>Payment method</label><select name="payment"><option>Bank Transfer</option><option>UPI</option><option>Cash</option><option>Cheque</option><option>Cash on Delivery</option><option>Credit (pay later)</option><option>Other</option></select>';
		echo '<label>Transaction ID (optional)</label><input type="text" name="txn_id">';
		echo '<label>Customer note (optional)</label><textarea name="customer_note" rows="2"></textarea>';
		echo '<label>Internal note (optional)</label><textarea name="internal_note" rows="2"></textarea>';
		echo '<label><input type="checkbox" name="send_emails" value="1"> Send order emails to customer</label>';
		echo '</div>';

		// Billing
		echo '<div class="cbd-box"><h2>Billing details</h2>';
		$this->addr_fields( 'billing' );
		echo '</div>';

		// Shipping
		echo '<div class="cbd-box"><h2>Shipping details</h2><label><input type="checkbox" name="same_shipping" value="1" id="cbd-same" checked> Same as billing</label><div id="cbd-ship" class="cbd-hide">';
		$this->addr_fields( 'shipping' );
		echo '</div></div></div>';

		// Items
		echo '<div class="cbd-box"><h2>Items</h2><table class="cbd-items"><thead><tr><th style="width:28%">Product</th><th>Name</th><th style="width:110px">Price (₹)</th><th style="width:80px">Qty</th><th style="width:110px">Total</th><th style="width:30px"></th></tr></thead><tbody id="cbd-rows"></tbody></table>';
		echo '<p><button type="button" class="button" id="cbd-add">+ Add item</button></p>';
		echo '<table class="cbd-totals"><tr><td>Items subtotal</td><td id="t-sub">₹0.00</td></tr>';
		echo '<tr><td>Shipping charge (₹)</td><td><input type="number" step="0.01" min="0" name="ship_amount" id="ship_amount" value="0" style="width:110px"></td></tr>';
		echo '<tr><td>Discount (₹)</td><td><input type="number" step="0.01" min="0" name="discount" id="discount" value="0" style="width:110px"></td></tr>';
		echo '<tr><td>GST % (on subtotal − discount)</td><td><input type="number" step="0.01" min="0" name="gst_pct" id="gst_pct" value="0" style="width:110px"></td></tr>';
		echo '<tr class="grand"><td>Order total</td><td id="t-total">₹0.00</td></tr></table><div style="clear:both"></div></div>';

		echo '<p><button class="button button-primary button-hero">Create order</button></p></form></div>';

		$data = array(
			'products' => $js_prod,
			'states'   => WC()->countries->get_states(),
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cbd_mo_customer' ),
		);
		?>
<script>
(function($){
	var D = <?php echo wp_json_encode( $data ); ?>;
	var idx = 0;

	function money(n){ return '₹' + (n||0).toFixed(2); }

	/* ---- items ---- */
	function addRow(){
		var i = idx++;
		var opts = '<option value="">Custom item…</option>';
		D.products.forEach(function(p){ opts += '<option value="'+p.id+'" data-price="'+p.price+'" data-name="'+$('<i>').text(p.name).html()+'">'+$('<i>').text(p.name).html()+' — ₹'+p.price+'</option>'; });
		var tr = $('<tr>'+
			'<td><select name="items['+i+'][pid]" class="r-pid">'+opts+'</select></td>'+
			'<td><input type="text" name="items['+i+'][name]" class="r-name" placeholder="Item name"></td>'+
			'<td><input type="number" step="0.01" min="0" name="items['+i+'][price]" class="r-price" value="0"></td>'+
			'<td><input type="number" step="1" min="1" name="items['+i+'][qty]" class="r-qty" value="1"></td>'+
			'<td class="r-total">₹0.00</td>'+
			'<td><a href="#" class="r-del" style="color:#b32d2e;text-decoration:none;font-size:18px">&times;</a></td></tr>');
		$('#cbd-rows').append(tr);
	}
	function recalc(){
		var sub = 0;
		$('#cbd-rows tr').each(function(){
			var p = parseFloat($(this).find('.r-price').val())||0, q = parseFloat($(this).find('.r-qty').val())||0;
			$(this).find('.r-total').text(money(p*q)); sub += p*q;
		});
		var ship = parseFloat($('#ship_amount').val())||0, disc = parseFloat($('#discount').val())||0, gst = parseFloat($('#gst_pct').val())||0;
		var base = Math.max(sub-disc,0), tax = Math.round(base*gst)/100;
		$('#t-sub').text(money(sub)); $('#t-total').text(money(base+tax+ship));
	}
	$('#cbd-add').on('click', function(){ addRow(); });
	$('#cbd-rows').on('change', '.r-pid', function(){
		var o = $(this).find(':selected'), tr = $(this).closest('tr');
		if (o.val()) { tr.find('.r-name').val(o.data('name')).prop('readonly', true); tr.find('.r-price').val(o.data('price')); }
		else { tr.find('.r-name').val('').prop('readonly', false); }
		recalc();
	});
	$('#cbd-rows').on('input', 'input', recalc);
	$('#cbd-rows').on('click', '.r-del', function(e){ e.preventDefault(); $(this).closest('tr').remove(); recalc(); });
	$('#ship_amount,#discount,#gst_pct').on('input', recalc);
	addRow();

	/* ---- states ---- */
	function buildState(type, val){
		var c = $('#'+type+'_country').val(), wrap = $('#'+type+'_state_wrap'), st = D.states[c], name = type+'_state';
		if (st && !Array.isArray(st) && Object.keys(st).length){
			var s = '<select name="'+name+'" id="'+name+'" style="width:100%"><option value="">Select state…</option>';
			$.each(st, function(k,v){ s += '<option value="'+k+'"'+(k===val?' selected':'')+'>'+v+'</option>'; });
			wrap.html(s+'</select>');
		} else {
			wrap.html('<input type="text" name="'+name+'" id="'+name+'" value="'+(val||'')+'" style="width:100%">');
		}
	}
	buildState('billing'); buildState('shipping');
	$('.cbd-country').on('change', function(){ buildState($(this).data('type')); });

	/* ---- shipping toggle ---- */
	$('#cbd-same').on('change', function(){ $('#cbd-ship').toggleClass('cbd-hide', this.checked); }).trigger('change');

	/* ---- customer mode ---- */
	$('input[name=cust_mode]').on('change', function(){
		var m = $('input[name=cust_mode]:checked').val();
		$('#cbd-existing').toggleClass('cbd-hide', m!=='existing');
		$('#cbd-new-note').toggleClass('cbd-hide', m!=='new');
	});

	function fill(type, a){
		['first_name','last_name','company','address_1','address_2','city','postcode','phone'].forEach(function(f){ $('#'+type+'_'+f).val(a[f]||''); });
		if (a.country) { $('#'+type+'_country').val(a.country); }
		buildState(type, a.state||'');
		if (type==='billing') { $('#billing_email').val(a.email||''); }
	}
	$('#cbd_customer_id').on('change', function(){
		var id = $(this).val(); if (!id) return;
		$.post(D.ajax, {action:'cbd_mo_customer', nonce:D.nonce, id:id}, function(r){
			if (!r.success) return;
			fill('billing', r.data.billing); fill('shipping', r.data.shipping);
			if (r.data.shipping.address_1 && r.data.shipping.address_1 !== r.data.billing.address_1) { $('#cbd-same').prop('checked', false).trigger('change'); }
		});
	});
	$('#cbd-form').on('submit', function(){
		if (!$('#cbd-rows tr').length) { alert('Add at least one item.'); return false; }
	});
})(jQuery);
</script>
		<?php
	}

	public function ajax_customer() {
		check_ajax_referer( 'cbd_mo_customer', 'nonce' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error();
		}
		$c = new WC_Customer( (int) $_POST['id'] );
		if ( ! $c->get_id() ) {
			wp_send_json_error();
		}
		$keys = array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'phone' );
		$b    = array();
		$s    = array();
		foreach ( $keys as $k ) {
			$b[ $k ] = (string) $c->{"get_billing_$k"}();
			$s[ $k ] = (string) $c->{"get_shipping_$k"}();
		}
		$b['email'] = $c->get_billing_email() ? $c->get_billing_email() : $c->get_email();
		wp_send_json_success( array( 'billing' => $b, 'shipping' => $s ) );
	}

	/* ---------------------------------------------------------------- Create order */

	private function addr_from_post( $type, $p ) {
		$out = array();
		foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'phone' ) as $k ) {
			$out[ $k ] = sanitize_text_field( $p[ "{$type}_{$k}" ] ?? '' );
		}
		if ( 'billing' === $type ) {
			$out['email'] = sanitize_email( $p['billing_email'] ?? '' );
		}
		return $out;
	}

	private function fail( $msg ) {
		wp_safe_redirect( add_query_arg( 'cbd_error', rawurlencode( $msg ), admin_url( 'admin.php?page=cbd-mo-new' ) ) );
		exit;
	}

	public function create_order() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'cbd_mo_create' );
		$p = wp_unslash( $_POST );

		$billing  = $this->addr_from_post( 'billing', $p );
		$shipping = empty( $p['same_shipping'] ) ? $this->addr_from_post( 'shipping', $p ) : array_diff_key( $billing, array( 'email' => 1 ) );

		// Customer
		$mode        = $p['cust_mode'] ?? 'guest';
		$customer_id = 0;
		if ( 'existing' === $mode ) {
			$customer_id = (int) ( $p['customer_id'] ?? 0 );
		} elseif ( 'new' === $mode ) {
			if ( ! is_email( $billing['email'] ) ) {
				$this->fail( 'A valid billing email is required to create a new customer.' );
			}
			$existing = email_exists( $billing['email'] );
			if ( $existing ) {
				$customer_id = $existing;
			} else {
				$new = wc_create_new_customer( $billing['email'], '', wp_generate_password(), array(
					'first_name' => $billing['first_name'],
					'last_name'  => $billing['last_name'],
				) );
				if ( is_wp_error( $new ) ) {
					$this->fail( $new->get_error_message() );
				}
				$customer_id = $new;
			}
		}

		// Items
		$rows = array();
		foreach ( (array) ( $p['items'] ?? array() ) as $r ) {
			$name = sanitize_text_field( $r['name'] ?? '' );
			$qty  = max( 0, (int) ( $r['qty'] ?? 0 ) );
			if ( '' === $name || ! $qty ) {
				continue;
			}
			$rows[] = array( 'name' => $name, 'qty' => $qty, 'price' => (float) wc_format_decimal( $r['price'] ?? 0 ), 'pid' => (int) ( $r['pid'] ?? 0 ) );
		}
		if ( ! $rows ) {
			$this->fail( 'Add at least one item with a name and quantity.' );
		}

		if ( empty( $p['send_emails'] ) ) {
			foreach ( array( 'customer_processing_order', 'customer_completed_order', 'customer_on_hold_order', 'customer_invoice', 'customer_new_account' ) as $e ) {
				add_filter( "woocommerce_email_enabled_{$e}", '__return_false', 99 );
			}
		}

		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => $customer_id, 'created_via' => 'cbd_manual_b2b' ) );
		if ( is_wp_error( $order ) ) {
			$this->fail( $order->get_error_message() );
		}

		$subtotal = 0;
		foreach ( $rows as $r ) {
			$line = $r['price'] * $r['qty'];
			$subtotal += $line;
			$item = new WC_Order_Item_Product();
			$item->set_name( $r['name'] );
			$item->set_quantity( $r['qty'] );
			$item->set_subtotal( $line );
			$item->set_total( $line );
			if ( $r['pid'] ) {
				$item->add_meta_data( '_cbd_custom_product_id', $r['pid'], true );
			}
			$order->add_item( $item );
		}

		$ship = (float) wc_format_decimal( $p['ship_amount'] ?? 0 );
		if ( $ship > 0 ) {
			$s = new WC_Order_Item_Shipping();
			$s->set_method_title( 'Shipping' );
			$s->set_method_id( 'cbd_manual' );
			$s->set_total( $ship );
			$order->add_item( $s );
		}

		$disc = min( (float) wc_format_decimal( $p['discount'] ?? 0 ), $subtotal );
		if ( $disc > 0 ) {
			$f = new WC_Order_Item_Fee();
			$f->set_name( 'Discount' );
			$f->set_amount( -$disc );
			$f->set_total( -$disc );
			$f->set_tax_status( 'none' );
			$order->add_item( $f );
		}

		$gst = (float) wc_format_decimal( $p['gst_pct'] ?? 0 );
		if ( $gst > 0 ) {
			$tax = round( ( $subtotal - $disc ) * $gst / 100, 2 );
			$f   = new WC_Order_Item_Fee();
			$f->set_name( sprintf( 'GST (%s%%)', rtrim( rtrim( number_format( $gst, 2, '.', '' ), '0' ), '.' ) ) );
			$f->set_amount( $tax );
			$f->set_total( $tax );
			$f->set_tax_status( 'none' );
			$order->add_item( $f );
		}

		$order->set_address( $billing, 'billing' );
		$order->set_address( $shipping, 'shipping' );
		$order->set_currency( get_woocommerce_currency() );
		$order->set_payment_method_title( sanitize_text_field( $p['payment'] ?? '' ) );
		if ( ! empty( $p['txn_id'] ) ) {
			$order->set_transaction_id( sanitize_text_field( $p['txn_id'] ) );
		}
		if ( ! empty( $p['customer_note'] ) ) {
			$order->set_customer_note( sanitize_textarea_field( $p['customer_note'] ) );
		}
		if ( ! empty( $p['order_date'] ) ) {
			$ts = (int) get_gmt_from_date( str_replace( 'T', ' ', sanitize_text_field( $p['order_date'] ) ), 'U' );
			if ( $ts ) {
				$order->set_date_created( $ts );
			}
		}

		$order->update_meta_data( '_cbd_manual_order', 'yes' );
		$order->update_meta_data( '_cbd_po_number', sanitize_text_field( $p['po_number'] ?? '' ) );
		if ( ! empty( $billing['company'] ) || ! empty( $p['billing_gstin'] ) ) {
			$order->update_meta_data( '_billing_gstin', sanitize_text_field( $p['billing_gstin'] ?? '' ) );
		}

		$order->calculate_totals( false );
		$order->save();

		$order->add_order_note( 'Manual B2B order created by ' . wp_get_current_user()->display_name . '.' );
		if ( ! empty( $p['internal_note'] ) ) {
			$order->add_order_note( sanitize_textarea_field( $p['internal_note'] ) );
		}

		// Set final status last, so integrations (Shadowfax etc.) fire on a fully built order.
		$status = sanitize_key( $p['status'] ?? 'wc-processing' );
		$order->update_status( $status, '', true );

		wp_safe_redirect( $order->get_edit_order_url() );
		exit;
	}
}

add_action( 'plugins_loaded', function () {
	if ( class_exists( 'WooCommerce' ) ) {
		CBD_Manual_Orders::init();
	} else {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>Manual B2B Orders requires WooCommerce.</p></div>';
		} );
	}
} );
