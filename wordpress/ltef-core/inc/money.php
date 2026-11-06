<?php
/**
 * Money: a private admin page for Tina and Jeremy.
 *
 * Income, expenses (with private receipts), payouts, prices and the profit split.
 * Money either partner spent personally is paid back first, oldest first, out of
 * all income after the business's own costs. What's left is divided by the split
 * the partners enter themselves. Nothing here is public: no REST, no front end,
 * no search, and receipts live outside the public uploads folder when the host
 * allows it.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ data */

add_action( 'init', 'ltef_money_register' );
function ltef_money_register() {
	$args = array(
		'public'              => false,
		'show_ui'             => false,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'can_export'          => false,
		'supports'            => array( 'title', 'custom-fields' ),
		'rewrite'             => false,
		'query_var'           => false,
	);
	register_post_type( 'ltef_income', array_merge( $args, array( 'label' => 'Income' ) ) );
	register_post_type( 'ltef_expense', array_merge( $args, array( 'label' => 'Expenses' ) ) );
	register_post_type( 'ltef_payout', array_merge( $args, array( 'label' => 'Payouts' ) ) );
}

/** Where income comes from. */
function ltef_money_sources() {
	return array(
		'woopon'      => 'Woo-pon (weekly)',
		'vip'         => 'VIP Woo-pon',
		'event'       => 'Premium event listing',
		'job'         => 'Job post',
		'ad'          => 'Ad spot',
		'enhanced'    => 'Enhanced listing',
		'partner'     => 'Partner commission',
		'sponsorship' => 'Sponsorship',
		'other'       => 'Other',
	);
}

/** What money goes on. */
function ltef_money_categories() {
	return array(
		'setup'     => 'Startup and setup',
		'hosting'   => 'Hosting, domain and software',
		'food'      => 'Food (meals Tina reviews)',
		'equipment' => 'Equipment',
		'marketing' => 'Marketing and printing',
		'fees'      => 'Payment fees',
		'other'     => 'Other',
	);
}

function ltef_money_methods() {
	return array( 'stripe' => 'Stripe', 'cash' => 'Cash', 'check' => 'Check', 'venmo' => 'Venmo', 'zelle' => 'Zelle', 'paypal' => 'PayPal', 'other' => 'Other' );
}

/** Private money settings: the two partners, the split, a reserve, and who can see this page. */
function ltef_money_settings() {
	$defaults = array(
		'partners' => array(
			'p1' => array( 'name' => 'Jeremy', 'pct' => '' ),
			'p2' => array( 'name' => 'Tina', 'pct' => '' ),
		),
		'reserve'  => 0,
		'viewers'  => array(),
	);
	$saved = get_option( 'ltef_money', array() );
	$s     = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	foreach ( $defaults['partners'] as $k => $p ) {
		$s['partners'][ $k ] = wp_parse_args( isset( $s['partners'][ $k ] ) ? $s['partners'][ $k ] : array(), $p );
	}
	return $s;
}

function ltef_money_partner_names() {
	$s = ltef_money_settings();
	return array_map(
		function ( $p ) {
			return $p['name'] ? $p['name'] : 'Partner';
		},
		$s['partners']
	);
}

/** True when the split is filled in and adds up to 100. */
function ltef_money_split_ready() {
	$s     = ltef_money_settings();
	$total = 0;
	foreach ( $s['partners'] as $p ) {
		if ( '' === $p['pct'] || ! is_numeric( $p['pct'] ) ) {
			return false;
		}
		$total += (float) $p['pct'];
	}
	return abs( $total - 100 ) < 0.001;
}

/** Only administrators, and only the ones picked under Prices & split (all admins until someone is picked). */
function ltef_money_can_view() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	$viewers = ltef_money_settings()['viewers'];
	return empty( $viewers ) || in_array( get_current_user_id(), array_map( 'intval', $viewers ), true );
}

/** "$1,234.50" or "12.5" -> 123450 cents. */
function ltef_money_parse( $raw ) {
	$n = preg_replace( '/[^0-9.\-]/', '', (string) $raw );
	if ( '' === $n || ! is_numeric( $n ) ) {
		return null;
	}
	return (int) round( (float) $n * 100 );
}

function ltef_money_fmt( $cents, $signed = false ) {
	$neg = $cents < 0;
	$s   = '$' . number_format_i18n( abs( $cents ) / 100, 2 );
	if ( $neg ) {
		return '−' . $s;
	}
	return $signed && $cents > 0 ? '+' . $s : $s;
}

/** All entries of a kind, oldest first, as plain arrays. */
function ltef_money_entries( $type, $from = '', $to = '' ) {
	$q   = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'meta_key'       => 'ltef_m_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => array( 'meta_value' => 'ASC', 'ID' => 'ASC' ),
			'no_found_rows'  => true,
		)
	);
	$out = array();
	foreach ( $q as $p ) {
		$e = array( 'id' => $p->ID, 'label' => $p->post_title );
		foreach ( array( 'date', 'cents', 'source', 'category', 'method', 'paid_by', 'payback', 'who', 'kind', 'party', 'note', 'receipt', 'receipt_name' ) as $k ) {
			$e[ $k ] = get_post_meta( $p->ID, 'ltef_m_' . $k, true );
		}
		$e['cents'] = (int) $e['cents'];
		if ( ( $from && $e['date'] < $from ) || ( $to && $e['date'] > $to ) ) {
			continue;
		}
		$out[] = $e;
	}
	return $out;
}

/**
 * The money picture.
 *
 * All-time: income minus what the business paid directly is the cash on hand to divide.
 * It first pays back money the partners spent personally, oldest expense first. A reserve
 * (if set) is held back from what's left, and the rest is divided by the split.
 */
function ltef_money_summary() {
	$s        = ltef_money_settings();
	$income   = ltef_money_entries( 'ltef_income' );
	$expenses = ltef_money_entries( 'ltef_expense' );
	$payouts  = ltef_money_entries( 'ltef_payout' );
	$keys     = array_keys( $s['partners'] );
	$zero     = array_fill_keys( $keys, 0 );

	$r = array(
		'income'         => array_sum( array_column( $income, 'cents' ) ),
		'business_paid'  => 0,
		'personal_paid'  => $zero, // all money each partner put in
		'owed_total'     => $zero, // the part to be paid back
		'repaid'         => $zero, // paid back so far, on paper
		'still_owed'     => $zero,
		'reserve'        => 0,
		'pool'           => 0,
		'share'          => $zero,
		'entitled'       => $zero,
		'paid_out'       => $zero,
		'due'            => $zero,
		'split_ready'    => ltef_money_split_ready(),
	);
	$owed_list = array();
	foreach ( $expenses as $e ) {
		if ( isset( $zero[ $e['paid_by'] ] ) ) {
			$r['personal_paid'][ $e['paid_by'] ] += $e['cents'];
			if ( 'no' !== $e['payback'] ) {
				$r['owed_total'][ $e['paid_by'] ] += $e['cents'];
				$owed_list[] = $e;
			}
		} else {
			$r['business_paid'] += $e['cents'];
		}
	}
	$r['expenses'] = $r['business_paid'] + array_sum( $r['personal_paid'] );
	$r['profit']   = $r['income'] - $r['expenses'];
	$cash          = $r['income'] - $r['business_paid'];
	$r['cash']     = $cash;

	$left = max( 0, $cash );
	foreach ( $owed_list as $e ) { // oldest first
		$pay = min( $left, $e['cents'] );
		$r['repaid'][ $e['paid_by'] ] += $pay;
		$left -= $pay;
	}
	foreach ( $keys as $k ) {
		$r['still_owed'][ $k ] = $r['owed_total'][ $k ] - $r['repaid'][ $k ];
	}
	$r['reserve'] = (int) round( $left * min( 100, max( 0, (float) $s['reserve'] ) ) / 100 );
	$r['pool']    = $left - $r['reserve'];
	foreach ( $keys as $k ) {
		$r['share'][ $k ] = $r['split_ready'] ? (int) round( $r['pool'] * (float) $s['partners'][ $k ]['pct'] / 100 ) : 0;
	}
	foreach ( $payouts as $p ) {
		if ( isset( $zero[ $p['who'] ] ) ) {
			$r['paid_out'][ $p['who'] ] += $p['cents'];
		}
	}
	foreach ( $keys as $k ) {
		$r['entitled'][ $k ] = $r['repaid'][ $k ] + $r['share'][ $k ];
		$r['due'][ $k ]      = $r['entitled'][ $k ] - $r['paid_out'][ $k ];
	}
	return $r;
}

/** Income and expenses per month for the last $n months, oldest first. */
function ltef_money_monthly( $n = 12 ) {
	$months = array();
	$start  = strtotime( wp_date( 'Y-m-01' ) . ' -' . ( $n - 1 ) . ' months' );
	for ( $i = 0; $i < $n; $i++ ) {
		$ym            = gmdate( 'Y-m', strtotime( '+' . $i . ' months', $start ) );
		$months[ $ym ] = array( 'in' => 0, 'out' => 0 );
	}
	foreach ( array( 'ltef_income' => 'in', 'ltef_expense' => 'out' ) as $type => $col ) {
		foreach ( ltef_money_entries( $type ) as $e ) {
			$ym = substr( $e['date'], 0, 7 );
			if ( isset( $months[ $ym ] ) ) {
				$months[ $ym ][ $col ] += $e['cents'];
			}
		}
	}
	return $months;
}

/** Period presets for the overview. */
function ltef_money_periods() {
	$now = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	$lm  = strtotime( gmdate( 'Y-m-01', $now ) . ' -1 month' );
	return array(
		'month'      => array( 'This month', gmdate( 'Y-m-01', $now ), gmdate( 'Y-m-t', $now ) ),
		'last-month' => array( 'Last month', gmdate( 'Y-m-01', $lm ), gmdate( 'Y-m-t', $lm ) ),
		'year'       => array( 'This year', gmdate( 'Y-01-01', $now ), gmdate( 'Y-12-31', $now ) ),
		'all'        => array( 'All time', '', '' ),
	);
}

/* --------------------------------------------------------------- receipts */

/** A private folder for receipts: outside the web root on Cloudways (private_html), else a hidden folder in uploads. */
function ltef_receipts_dir() {
	$private = dirname( untrailingslashit( ABSPATH ) ) . '/private_html';
	if ( is_dir( $private ) && wp_is_writable( $private ) ) {
		$dir = $private . '/ltef-receipts';
	} else {
		$up  = wp_upload_dir();
		$dir = $up['basedir'] . '/ltef-receipts-' . substr( wp_hash( 'ltef-receipts' ), 0, 20 );
	}
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
		@file_put_contents( $dir . '/index.php', "<?php\n// Silence.\n" ); // phpcs:ignore
		@file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore
	}
	return $dir;
}

function ltef_receipt_types() {
	return array( 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'heic' => 'image/heic', 'pdf' => 'application/pdf' );
}

/** Saves an uploaded receipt. Returns [file, original name] or a WP_Error. */
function ltef_save_receipt( $file ) {
	if ( empty( $file['name'] ) || UPLOAD_ERR_NO_FILE === $file['error'] ) {
		return null;
	}
	if ( UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'upload', 'The receipt didn’t upload. Try again, or try a smaller photo.' );
	}
	if ( $file['size'] > 10 * MB_IN_BYTES ) {
		return new WP_Error( 'size', 'Receipts can be up to 10 MB.' );
	}
	$ext   = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	$types = ltef_receipt_types();
	if ( ! isset( $types[ $ext ] ) ) {
		return new WP_Error( 'type', 'Receipts can be a photo (JPG, PNG, WebP, HEIC) or a PDF.' );
	}
	if ( function_exists( 'finfo_open' ) ) {
		$fi   = finfo_open( FILEINFO_MIME_TYPE );
		$mime = finfo_file( $fi, $file['tmp_name'] );
		finfo_close( $fi );
		$ok = in_array( $mime, array_values( $types ), true ) || ( 'heic' === $ext && in_array( $mime, array( 'image/heif', 'application/octet-stream' ), true ) );
		if ( ! $ok ) {
			return new WP_Error( 'type', 'That file doesn’t look like a photo or PDF.' );
		}
	}
	$name = wp_generate_password( 32, false ) . '.' . $ext;
	if ( ! @move_uploaded_file( $file['tmp_name'], ltef_receipts_dir() . '/' . $name ) ) { // phpcs:ignore
		return new WP_Error( 'move', 'The receipt couldn’t be saved on the server.' );
	}
	return array( $name, sanitize_file_name( $file['name'] ) );
}

add_action( 'admin_post_ltef_receipt', 'ltef_money_receipt_view' );
function ltef_money_receipt_view() {
	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	check_admin_referer( 'ltef_receipt_' . $id );
	if ( ! ltef_money_can_view() || 'ltef_expense' !== get_post_type( $id ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	$name = basename( (string) get_post_meta( $id, 'ltef_m_receipt', true ) );
	$path = ltef_receipts_dir() . '/' . $name;
	if ( ! $name || ! is_file( $path ) ) {
		wp_die( 'That receipt file is missing.', 404 );
	}
	$ext   = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	$types = ltef_receipt_types();
	nocache_headers();
	header( 'Content-Type: ' . ( isset( $types[ $ext ] ) ? $types[ $ext ] : 'application/octet-stream' ) );
	header( 'Content-Disposition: inline; filename="' . sanitize_file_name( (string) get_post_meta( $id, 'ltef_m_receipt_name', true ) ) . '"' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}

/* ----------------------------------------------------------------- saving */

add_action( 'admin_post_ltef_money', 'ltef_money_handle' );
function ltef_money_handle() {
	check_admin_referer( 'ltef_money' );
	if ( ! ltef_money_can_view() ) {
		wp_die( 'Not allowed.', 403 );
	}
	$do   = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	$tab  = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'overview';
	$back = function ( $msg, $err = false ) use ( $tab ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'ltef-money', 'tab' => $tab, ( $err ? 'ltef_err' : 'ltef_ok' ) => rawurlencode( $msg ) ), admin_url( 'admin.php' ) ) );
		exit;
	};
	$txt  = function ( $k ) {
		return isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	};
	$date = function () use ( $txt ) {
		$d = $txt( 'date' );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ? $d : wp_date( 'Y-m-d' );
	};
	$names = ltef_money_partner_names();

	if ( in_array( $do, array( 'income', 'expense', 'payout' ), true ) ) {
		$cents = ltef_money_parse( $txt( 'amount' ) );
		if ( null === $cents || $cents <= 0 ) {
			$back( 'Enter an amount greater than zero.', true );
		}
		$type = 'ltef_' . $do;
		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $id && get_post_type( $id ) !== $type ) {
			$back( 'That entry no longer exists.', true );
		}
		$meta = array( 'ltef_m_date' => $date(), 'ltef_m_cents' => $cents, 'ltef_m_note' => $txt( 'note' ) );
		if ( 'income' === $do ) {
			$src   = $txt( 'source' );
			$meth  = $txt( 'method' );
			$meta += array(
				'ltef_m_source' => isset( ltef_money_sources()[ $src ] ) ? $src : 'other',
				'ltef_m_method' => isset( ltef_money_methods()[ $meth ] ) ? $meth : 'other',
				'ltef_m_party'  => $txt( 'party' ),
			);
			$title = ltef_money_sources()[ $meta['ltef_m_source'] ] . ( $meta['ltef_m_party'] ? ' · ' . $meta['ltef_m_party'] : '' );
		} elseif ( 'expense' === $do ) {
			$cat   = $txt( 'category' );
			$by    = $txt( 'paid_by' );
			$meta += array(
				'ltef_m_category' => isset( ltef_money_categories()[ $cat ] ) ? $cat : 'other',
				'ltef_m_paid_by'  => isset( $names[ $by ] ) ? $by : 'business',
				'ltef_m_payback'  => 'no' === $txt( 'payback' ) ? 'no' : 'yes',
				'ltef_m_party'    => $txt( 'party' ),
			);
			$receipt = isset( $_FILES['receipt'] ) ? ltef_save_receipt( $_FILES['receipt'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( is_wp_error( $receipt ) ) {
				$back( $receipt->get_error_message(), true );
			}
			if ( $receipt ) {
				$meta['ltef_m_receipt']      = $receipt[0];
				$meta['ltef_m_receipt_name'] = $receipt[1];
			}
			$title = ltef_money_categories()[ $meta['ltef_m_category'] ] . ( $meta['ltef_m_party'] ? ' · ' . $meta['ltef_m_party'] : '' );
		} else {
			$who   = $txt( 'who' );
			$kind  = $txt( 'kind' );
			$meta += array(
				'ltef_m_who'  => isset( $names[ $who ] ) ? $who : key( $names ),
				'ltef_m_kind' => 'share' === $kind ? 'share' : 'payback',
			);
			$title = 'Paid to ' . $names[ $meta['ltef_m_who'] ];
		}
		$args = array( 'post_type' => $type, 'post_status' => 'private', 'post_title' => $title, 'meta_input' => $meta );
		if ( $id ) {
			$args['ID'] = $id;
			if ( 'expense' === $do && ! empty( $meta['ltef_m_receipt'] ) ) {
				ltef_money_delete_receipt( $id ); // replacing an old receipt
			}
			wp_update_post( $args );
			$back( 'Saved.' );
		}
		wp_insert_post( $args );
		$back( 'expense' === $do ? 'Expense added.' : ( 'income' === $do ? 'Income added.' : 'Payout recorded.' ) );
	}

	if ( 'delete' === $do ) {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! in_array( get_post_type( $id ), array( 'ltef_income', 'ltef_expense', 'ltef_payout' ), true ) ) {
			$back( 'That entry no longer exists.', true );
		}
		ltef_money_delete_receipt( $id );
		wp_delete_post( $id, true );
		$back( 'Deleted.' );
	}

	if ( 'prices' === $do ) {
		$s = ltef_settings();
		foreach ( array_keys( ltef_price_fields() ) as $k ) {
			if ( isset( $_POST[ $k ] ) ) {
				$s[ $k ] = max( 0, (float) preg_replace( '/[^0-9.]/', '', wp_unslash( $_POST[ $k ] ) ) ); // phpcs:ignore
			}
		}
		update_option( 'ltef_settings', $s, false );
		ltef_money_clear_page_cache();
		$back( 'Prices saved. They’re live on the site now.' );
	}

	if ( 'split' === $do ) {
		$s = ltef_money_settings();
		foreach ( array_keys( $s['partners'] ) as $k ) {
			$name = $txt( $k . '_name' );
			$pct  = $txt( $k . '_pct' );
			$s['partners'][ $k ] = array(
				'name' => $name ? $name : $s['partners'][ $k ]['name'],
				'pct'  => '' === $pct ? '' : (string) max( 0, min( 100, (float) $pct ) ),
			);
		}
		$s['reserve'] = max( 0, min( 100, (float) $txt( 'reserve' ) ) );
		$viewers      = isset( $_POST['viewers'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['viewers'] ) ) : array(); // phpcs:ignore
		if ( $viewers && ! in_array( get_current_user_id(), $viewers, true ) ) {
			$viewers[] = get_current_user_id(); // never lock yourself out
		}
		$s['viewers'] = array_values( array_unique( $viewers ) );
		update_option( 'ltef_money', $s, false );
		$total = 0;
		foreach ( $s['partners'] as $p ) {
			$total += (float) $p['pct'];
		}
		$ready = ltef_money_split_ready();
		$back( $ready ? 'Saved.' : 'Saved. The split adds up to ' . $total . '%; it needs to be 100% before shares are calculated.', ! $ready );
	}

	if ( 'csv' === $do ) {
		ltef_money_csv();
	}
	$back( 'Nothing to do.', true );
}

/** Pages show prices, so cached copies are cleared when prices change (Breeze and Varnish on Cloudways, plus WordPress's own cache). */
function ltef_money_clear_page_cache() {
	do_action( 'breeze_clear_all_cache' );
	do_action( 'breeze_clear_varnish' );
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}

function ltef_money_delete_receipt( $id ) {
	$name = basename( (string) get_post_meta( $id, 'ltef_m_receipt', true ) );
	if ( $name && is_file( ltef_receipts_dir() . '/' . $name ) ) {
		wp_delete_file( ltef_receipts_dir() . '/' . $name );
	}
}

/** Everything as one CSV, for an accountant. */
function ltef_money_csv() {
	$names = ltef_money_partner_names();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="live-to-eat-food-money-' . wp_date( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	fputcsv( $out, array( 'Date', 'Type', 'Amount', 'Category or source', 'From / to', 'Paid by / paid to', 'Pay back', 'Method', 'Note', 'Receipt' ) );
	foreach ( array( 'ltef_income' => 'Income', 'ltef_expense' => 'Expense', 'ltef_payout' => 'Payout' ) as $type => $label ) {
		foreach ( ltef_money_entries( $type ) as $e ) {
			$amount = number_format( ( 'ltef_income' === $type ? 1 : -1 ) * $e['cents'] / 100, 2, '.', '' );
			$cat    = 'ltef_income' === $type ? ( ltef_money_sources()[ $e['source'] ] ?? '' ) : ( 'ltef_expense' === $type ? ( ltef_money_categories()[ $e['category'] ] ?? '' ) : ( 'share' === $e['kind'] ? 'Profit share' : 'Payback' ) );
			$by     = 'ltef_expense' === $type ? ( $names[ $e['paid_by'] ] ?? 'Business' ) : ( 'ltef_payout' === $type ? ( $names[ $e['who'] ] ?? '' ) : '' );
			$row    = array( $e['date'], $label, $amount, $cat, $e['party'], $by, 'ltef_expense' === $type && isset( $names[ $e['paid_by'] ] ) ? $e['payback'] : '', ltef_money_methods()[ $e['method'] ] ?? '', $e['note'], $e['receipt'] ? 'yes' : '' );
			fputcsv( $out, array_map( 'ltef_money_csv_cell', $row ) );
		}
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}

/** Stops spreadsheet formula injection from typed-in notes. */
function ltef_money_csv_cell( $v ) {
	$v = (string) $v;
	return ( '' !== $v && in_array( $v[0], array( '=', '+', '-', '@' ), true ) && ! is_numeric( $v ) ) ? "'" . $v : $v;
}

/* ------------------------------------------------------------------ admin */

add_action( 'admin_menu', 'ltef_money_menu' );
function ltef_money_menu() {
	if ( ltef_money_can_view() ) {
		add_menu_page( 'Money', 'Money', 'manage_options', 'ltef-money', 'ltef_money_page', 'dashicons-chart-area', 4 );
	}
}

/** Price settings shown under Prices & split, and used by [ltef_price]. */
function ltef_price_fields() {
	return array(
		'woopon_free'   => array( 'Free Woo-pon trial', 'weeks', 'Once per business.' ),
		'woopon_weekly' => array( 'Woo-pon', '$ a week', '' ),
		'woopon_vip'    => array( 'VIP Woo-pon', '$', 'Presented in Tina’s monthly video.' ),
		'vip_limit'     => array( 'VIP Woo-pons per month', 'max', '' ),
		'event_premium' => array( 'Premium event listing', '$ a month', 'Listing an event stays free.' ),
		'job_post'      => array( 'Job post', '$ a month', '' ),
		'enhanced_low'  => array( 'Enhanced listing, from', '$ a month', 'Shown as a pilot range on Work with Tina.' ),
		'enhanced_high' => array( 'Enhanced listing, up to', '$ a month', '' ),
	);
}

function ltef_money_page() {
	if ( ! ltef_money_can_view() ) {
		wp_die( 'Not allowed.', 403 );
	}
	$tabs = array( 'overview' => 'Overview', 'income' => 'Income', 'expenses' => 'Expenses', 'payouts' => 'Payouts', 'settings' => 'Prices & split' );
	$tab  = isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ? sanitize_key( $_GET['tab'] ) : 'overview'; // phpcs:ignore
	$ok   = isset( $_GET['ltef_ok'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['ltef_ok'] ) ) ) : ''; // phpcs:ignore
	$err  = isset( $_GET['ltef_err'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['ltef_err'] ) ) ) : ''; // phpcs:ignore

	echo '<div class="wrap ltef-money">';
	ltef_money_styles();
	echo '<h1>Money</h1><p class="ltef-money__private">Private to you and your partner. Nothing on this page appears on the site.</p>';
	if ( $ok ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $ok ) . '</p></div>';
	}
	if ( $err ) {
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $err ) . '</p></div>';
	}
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $label ) {
		printf( '<a href="%s" class="nav-tab%s">%s</a>', esc_url( admin_url( 'admin.php?page=ltef-money&tab=' . $k ) ), $k === $tab ? ' nav-tab-active' : '', esc_html( $label ) );
	}
	echo '</nav>';
	call_user_func( 'ltef_money_tab_' . $tab );
	echo '</div>';
}

function ltef_money_form_open( $do, $tab, $multipart = false ) {
	printf( '<form method="post" action="%s"%s class="ltef-money__form">', esc_url( admin_url( 'admin-post.php' ) ), $multipart ? ' enctype="multipart/form-data"' : '' );
	echo '<input type="hidden" name="action" value="ltef_money"><input type="hidden" name="do" value="' . esc_attr( $do ) . '"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
	wp_nonce_field( 'ltef_money' );
}

function ltef_money_select( $name, $options, $current, $id = '' ) {
	$h = '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ? $id : 'ltef-' . $name ) . '">';
	foreach ( $options as $k => $label ) {
		$h .= '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
	}
	return $h . '</select>';
}

function ltef_money_tab_overview() {
	$periods = ltef_money_periods();
	$pk      = isset( $_GET['period'] ) && isset( $periods[ $_GET['period'] ] ) ? sanitize_key( $_GET['period'] ) : 'month'; // phpcs:ignore
	$per     = $periods[ $pk ];
	$in      = array_sum( array_column( ltef_money_entries( 'ltef_income', $per[1], $per[2] ), 'cents' ) );
	$out     = array_sum( array_column( ltef_money_entries( 'ltef_expense', $per[1], $per[2] ), 'cents' ) );
	$sum     = ltef_money_summary();
	$names   = ltef_money_partner_names();
	$s       = ltef_money_settings();

	echo '<div class="ltef-money__periods" role="group" aria-label="Period">';
	foreach ( $periods as $k => $p ) {
		printf( '<a class="button%s" href="%s"%s>%s</a>', $k === $pk ? ' button-primary' : '', esc_url( admin_url( 'admin.php?page=ltef-money&period=' . $k ) ), $k === $pk ? ' aria-current="true"' : '', esc_html( $p[0] ) );
	}
	echo '</div>';

	echo '<div class="ltef-money__tiles">';
	printf( '<div class="ltef-money__tile"><span>Income</span><strong>%s</strong></div>', esc_html( ltef_money_fmt( $in ) ) );
	printf( '<div class="ltef-money__tile"><span>Expenses</span><strong>%s</strong></div>', esc_html( ltef_money_fmt( $out ) ) );
	printf( '<div class="ltef-money__tile ltef-money__tile--%s"><span>Profit</span><strong>%s</strong></div>', $in - $out >= 0 ? 'up' : 'down', esc_html( ltef_money_fmt( $in - $out ) ) );
	echo '</div><p class="description">' . esc_html( $per[0] ) . ( $per[1] ? ', ' . esc_html( wp_date( 'M j', strtotime( $per[1] ) ) . ' to ' . wp_date( 'M j, Y', strtotime( $per[2] ) ) ) : '' ) . '.</p>';

	// Where the money goes, all time.
	echo '<h2>Where the money goes</h2><p class="description">All time. Money either of you spent is paid back first, oldest first. Then what’s left is divided.</p>';
	if ( ! $sum['split_ready'] ) {
		echo '<div class="notice notice-info inline"><p>Enter how profit is divided under <a href="' . esc_url( admin_url( 'admin.php?page=ltef-money&tab=settings' ) ) . '">Prices &amp; split</a>. Until the two shares add up to 100%, shares aren’t calculated.</p></div>';
	}
	echo '<table class="widefat ltef-money__flow"><tbody>';
	$row = function ( $label, $cents, $class = '' ) {
		printf( '<tr class="%s"><th scope="row">%s</th><td>%s</td></tr>', esc_attr( $class ), esc_html( $label ), esc_html( ltef_money_fmt( $cents ) ) );
	};
	$row( 'Income', $sum['income'], 'is-total' );
	$row( 'Costs the business paid', -$sum['business_paid'] );
	$row( 'Left to pay back and divide', $sum['cash'], 'is-total' );
	foreach ( $names as $k => $n ) {
		if ( $sum['owed_total'][ $k ] ) {
			$row( 'Paid back to ' . $n, -$sum['repaid'][ $k ] );
		}
	}
	if ( $sum['reserve'] ) {
		$row( 'Held back (' . (float) $s['reserve'] . '% reserve)', -$sum['reserve'] );
	}
	$row( 'Profit to divide', $sum['pool'], 'is-total' );
	foreach ( $names as $k => $n ) {
		if ( $sum['split_ready'] ) {
			$row( $n . '’s share (' . (float) $s['partners'][ $k ]['pct'] . '%)', $sum['share'][ $k ] );
		}
	}
	echo '</tbody></table>';
	if ( $sum['cash'] < 0 ) {
		echo '<p class="ltef-money__note">The business has spent ' . esc_html( ltef_money_fmt( -$sum['cash'] ) ) . ' more than it has taken in, not counting what you two paid personally.</p>';
	}

	// Per partner.
	echo '<h2>Each of you</h2><div class="ltef-money__people">';
	foreach ( $names as $k => $n ) {
		echo '<div class="ltef-money__person"><h3>' . esc_html( $n ) . '</h3><dl>';
		printf( '<dt>Spent personally</dt><dd>%s</dd>', esc_html( ltef_money_fmt( $sum['personal_paid'][ $k ] ) ) );
		printf( '<dt>Still to be paid back</dt><dd>%s</dd>', esc_html( ltef_money_fmt( $sum['still_owed'][ $k ] ) ) );
		printf( '<dt>Earned so far (paybacks + share)</dt><dd>%s</dd>', esc_html( ltef_money_fmt( $sum['entitled'][ $k ] ) ) );
		printf( '<dt>Already paid out</dt><dd>%s</dd>', esc_html( ltef_money_fmt( $sum['paid_out'][ $k ] ) ) );
		printf( '<dt class="is-due">Due now</dt><dd class="is-due">%s</dd>', esc_html( ltef_money_fmt( $sum['due'][ $k ] ) ) );
		echo '</dl></div>';
	}
	echo '</div>';

	// Last 12 months.
	echo '<h2>Last 12 months</h2>';
	ltef_money_chart( ltef_money_monthly( 12 ) );

	printf( '<p class="ltef-money__actions"><a class="button" href="%s">Add income</a> <a class="button" href="%s">Add an expense or receipt</a></p>', esc_url( admin_url( 'admin.php?page=ltef-money&tab=income' ) ), esc_url( admin_url( 'admin.php?page=ltef-money&tab=expenses' ) ) );
	ltef_money_form_open( 'csv', 'overview' );
	echo '<button class="button-link">Download everything as a spreadsheet (CSV)</button></form>';
}

/** Monthly income vs expenses as a small bar chart. */
function ltef_money_chart( $months ) {
	$max = 1;
	foreach ( $months as $m ) {
		$max = max( $max, $m['in'], $m['out'] );
	}
	$w     = 720;
	$h     = 220;
	$pad_l = 64;
	$pad_b = 28;
	$pad_t = 12;
	$gw    = ( $w - $pad_l - 8 ) / count( $months );
	$scale = ( $h - $pad_b - $pad_t ) / $max;
	$svg   = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Income and expenses by month">';
	foreach ( array( 0, 0.5, 1 ) as $f ) {
		$y    = $h - $pad_b - $f * $max * $scale;
		$svg .= '<line x1="' . $pad_l . '" x2="' . ( $w - 8 ) . '" y1="' . $y . '" y2="' . $y . '" class="g"/><text x="' . ( $pad_l - 8 ) . '" y="' . ( $y + 4 ) . '" text-anchor="end" class="t">' . esc_html( ltef_money_fmt( (int) round( $f * $max ) ) ) . '</text>';
	}
	$i = 0;
	foreach ( $months as $ym => $m ) {
		$x    = $pad_l + $i * $gw;
		$bw   = $gw * 0.32;
		$hi   = $m['in'] * $scale;
		$ho   = $m['out'] * $scale;
		$svg .= '<rect x="' . ( $x + $gw * 0.16 ) . '" y="' . ( $h - $pad_b - $hi ) . '" width="' . $bw . '" height="' . max( 0, $hi ) . '" class="in"><title>' . esc_html( ltef_month_label( $ym ) . ' income ' . ltef_money_fmt( $m['in'] ) ) . '</title></rect>';
		$svg .= '<rect x="' . ( $x + $gw * 0.16 + $bw + 2 ) . '" y="' . ( $h - $pad_b - $ho ) . '" width="' . $bw . '" height="' . max( 0, $ho ) . '" class="out"><title>' . esc_html( ltef_month_label( $ym ) . ' expenses ' . ltef_money_fmt( $m['out'] ) ) . '</title></rect>';
		$svg .= '<text x="' . ( $x + $gw / 2 ) . '" y="' . ( $h - 9 ) . '" text-anchor="middle" class="t">' . esc_html( date_i18n( 'M', strtotime( $ym . '-01' ) ) ) . '</text>';
		++$i;
	}
	$svg .= '</svg>';
	echo '<div class="ltef-money__chart">' . $svg . '<p class="ltef-money__legend"><span class="k in"></span>Income <span class="k out"></span>Expenses</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts
}

function ltef_money_edit_entry( $type ) {
	$id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore
	if ( ! $id || get_post_type( $id ) !== $type ) {
		return null;
	}
	foreach ( ltef_money_entries( $type ) as $e ) {
		if ( $e['id'] === $id ) {
			return $e;
		}
	}
	return null;
}

function ltef_money_list( $type, $tab, $cols ) {
	$rows = array_reverse( ltef_money_entries( $type ) );
	if ( ! $rows ) {
		echo '<p class="ltef-money__empty">Nothing here yet.</p>';
		return;
	}
	echo '<div class="ltef-money__scroll"><table class="widefat striped"><thead><tr><th>Date</th><th>Amount</th>';
	foreach ( $cols as $label ) {
		echo '<th>' . esc_html( $label ) . '</th>';
	}
	echo '<th><span class="screen-reader-text">Actions</span></th></tr></thead><tbody>';
	foreach ( $rows as $e ) {
		echo '<tr><td>' . esc_html( wp_date( 'M j, Y', strtotime( $e['date'] ) ) ) . '</td><td class="num">' . esc_html( ltef_money_fmt( $e['cents'] ) ) . '</td>';
		foreach ( array_keys( $cols ) as $k ) {
			echo '<td>' . call_user_func( $k, $e ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput -- column callbacks escape
		}
		echo '<td class="ltef-money__row-actions"><a href="' . esc_url( admin_url( 'admin.php?page=ltef-money&tab=' . $tab . '&edit=' . $e['id'] ) ) . '">Edit</a> ';
		ltef_money_form_open( 'delete', $tab );
		echo '<input type="hidden" name="id" value="' . (int) $e['id'] . '"><details class="ltef-money__confirm"><summary>Delete</summary><button class="button-link button-link-delete">Yes, delete it</button></details></form></td></tr>';
	}
	echo '</tbody></table></div>';
}

function ltef_money_tab_income() {
	$e = ltef_money_edit_entry( 'ltef_income' );
	echo '<h2>' . ( $e ? 'Edit income' : 'Add income' ) . '</h2>';
	ltef_money_form_open( 'income', 'income' );
	if ( $e ) {
		echo '<input type="hidden" name="id" value="' . (int) $e['id'] . '">';
	}
	echo '<div class="ltef-money__fields">';
	printf( '<p><label for="ltef-date">Date</label><input type="date" id="ltef-date" name="date" value="%s" required></p>', esc_attr( $e ? $e['date'] : wp_date( 'Y-m-d' ) ) );
	printf( '<p><label for="ltef-amount">Amount</label><input type="text" inputmode="decimal" id="ltef-amount" name="amount" value="%s" placeholder="100.00" required></p>', esc_attr( $e ? number_format( $e['cents'] / 100, 2, '.', '' ) : '' ) );
	echo '<p><label for="ltef-source">What it’s for</label>' . ltef_money_select( 'source', ltef_money_sources(), $e ? $e['source'] : 'woopon' ) . '</p>'; // phpcs:ignore
	printf( '<p><label for="ltef-party">From</label><input type="text" id="ltef-party" name="party" value="%s" placeholder="Business name"></p>', esc_attr( $e ? $e['party'] : '' ) );
	echo '<p><label for="ltef-method">Paid by</label>' . ltef_money_select( 'method', ltef_money_methods(), $e ? $e['method'] : 'stripe' ) . '</p>'; // phpcs:ignore
	printf( '<p class="wide"><label for="ltef-note">Note</label><input type="text" id="ltef-note" name="note" value="%s"></p>', esc_attr( $e ? $e['note'] : '' ) );
	echo '</div>';
	submit_button( $e ? 'Save' : 'Add income', 'primary', 'submit', false );
	if ( $e ) {
		echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=ltef-money&tab=income' ) ) . '">Cancel</a>';
	}
	echo '</form><p class="description">Card payments will be added here automatically once Stripe is connected.</p><h2>All income</h2>';
	ltef_money_list(
		'ltef_income',
		'income',
		array(
			'ltef_money_col_source' => 'For',
			'ltef_money_col_party'  => 'From',
			'ltef_money_col_method' => 'Paid by',
			'ltef_money_col_note'   => 'Note',
		)
	);
}

function ltef_money_tab_expenses() {
	$e     = ltef_money_edit_entry( 'ltef_expense' );
	$names = ltef_money_partner_names();
	echo '<h2>' . ( $e ? 'Edit expense' : 'Add an expense' ) . '</h2>';
	ltef_money_form_open( 'expense', 'expenses', true );
	if ( $e ) {
		echo '<input type="hidden" name="id" value="' . (int) $e['id'] . '">';
	}
	echo '<div class="ltef-money__fields">';
	printf( '<p><label for="ltef-date">Date</label><input type="date" id="ltef-date" name="date" value="%s" required></p>', esc_attr( $e ? $e['date'] : wp_date( 'Y-m-d' ) ) );
	printf( '<p><label for="ltef-amount">Amount</label><input type="text" inputmode="decimal" id="ltef-amount" name="amount" value="%s" placeholder="42.50" required></p>', esc_attr( $e ? number_format( $e['cents'] / 100, 2, '.', '' ) : '' ) );
	echo '<p><label for="ltef-category">What for</label>' . ltef_money_select( 'category', ltef_money_categories(), $e ? $e['category'] : 'food' ) . '</p>'; // phpcs:ignore
	printf( '<p><label for="ltef-party">Where</label><input type="text" id="ltef-party" name="party" value="%s" placeholder="Restaurant, store or company"></p>', esc_attr( $e ? $e['party'] : '' ) );
	echo '<p><label for="ltef-paid_by">Who paid</label>' . ltef_money_select( 'paid_by', array_merge( $names, array( 'business' => 'The business' ) ), $e ? $e['paid_by'] : key( $names ) ) . '</p>'; // phpcs:ignore
	echo '<p><label for="ltef-payback">Pay it back?</label>' . ltef_money_select( 'payback', array( 'yes' => 'Yes, pay it back first', 'no' => 'No, it was a gift to the business' ), $e ? $e['payback'] : 'yes' ) . '</p>'; // phpcs:ignore
	echo '<p class="wide"><label for="ltef-receipt">Receipt</label><input type="file" id="ltef-receipt" name="receipt" accept="image/*,application/pdf"><span class="description">' . ( $e && $e['receipt'] ? 'A receipt is saved. Choosing a new file replaces it. ' : '' ) . 'Photo or PDF, up to 10 MB. On a phone you can take the photo right here.</span></p>';
	printf( '<p class="wide"><label for="ltef-note">Note</label><input type="text" id="ltef-note" name="note" value="%s" placeholder="What Tina ordered, what it was for…"></p>', esc_attr( $e ? $e['note'] : '' ) );
	echo '</div>';
	submit_button( $e ? 'Save' : 'Add expense', 'primary', 'submit', false );
	if ( $e ) {
		echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=ltef-money&tab=expenses' ) ) . '">Cancel</a>';
	}
	echo '</form><p class="description">“Who paid” matters: money one of you paid personally is paid back before profit is divided. Pick “The business” once there’s a business account or card.</p><h2>All expenses</h2>';
	ltef_money_list(
		'ltef_expense',
		'expenses',
		array(
			'ltef_money_col_category' => 'For',
			'ltef_money_col_party'    => 'Where',
			'ltef_money_col_paid_by'  => 'Who paid',
			'ltef_money_col_receipt'  => 'Receipt',
			'ltef_money_col_note'     => 'Note',
		)
	);
}

function ltef_money_tab_payouts() {
	$e     = ltef_money_edit_entry( 'ltef_payout' );
	$names = ltef_money_partner_names();
	$sum   = ltef_money_summary();
	echo '<p>Record money actually moved to either of you. The overview subtracts it from what’s due.</p><ul class="ltef-money__due">';
	foreach ( $names as $k => $n ) {
		echo '<li>' . esc_html( $n ) . ' is due <strong>' . esc_html( ltef_money_fmt( $sum['due'][ $k ] ) ) . '</strong></li>';
	}
	echo '</ul><h2>' . ( $e ? 'Edit payout' : 'Record a payout' ) . '</h2>';
	ltef_money_form_open( 'payout', 'payouts' );
	if ( $e ) {
		echo '<input type="hidden" name="id" value="' . (int) $e['id'] . '">';
	}
	echo '<div class="ltef-money__fields">';
	printf( '<p><label for="ltef-date">Date</label><input type="date" id="ltef-date" name="date" value="%s" required></p>', esc_attr( $e ? $e['date'] : wp_date( 'Y-m-d' ) ) );
	printf( '<p><label for="ltef-amount">Amount</label><input type="text" inputmode="decimal" id="ltef-amount" name="amount" value="%s" required></p>', esc_attr( $e ? number_format( $e['cents'] / 100, 2, '.', '' ) : '' ) );
	echo '<p><label for="ltef-who">Paid to</label>' . ltef_money_select( 'who', $names, $e ? $e['who'] : key( $names ) ) . '</p>'; // phpcs:ignore
	echo '<p><label for="ltef-kind">For</label>' . ltef_money_select( 'kind', array( 'payback' => 'Paying back money spent', 'share' => 'Profit share' ), $e ? $e['kind'] : 'payback' ) . '</p>'; // phpcs:ignore
	printf( '<p class="wide"><label for="ltef-note">Note</label><input type="text" id="ltef-note" name="note" value="%s"></p>', esc_attr( $e ? $e['note'] : '' ) );
	echo '</div>';
	submit_button( $e ? 'Save' : 'Record payout', 'primary', 'submit', false );
	echo '</form><h2>All payouts</h2>';
	ltef_money_list( 'ltef_payout', 'payouts', array( 'ltef_money_col_who' => 'To', 'ltef_money_col_kind' => 'For', 'ltef_money_col_note' => 'Note' ) );
}

function ltef_money_tab_settings() {
	$prices = ltef_settings();
	$s      = ltef_money_settings();
	echo '<h2>Prices</h2><p>These are the prices on the site. Saving updates every page that shows them.</p>';
	ltef_money_form_open( 'prices', 'settings' );
	echo '<div class="ltef-money__fields">';
	foreach ( ltef_price_fields() as $k => $f ) {
		printf( '<p><label for="ltef-%1$s">%2$s <span class="unit">(%3$s)</span></label><input type="text" inputmode="decimal" id="ltef-%1$s" name="%1$s" value="%4$s">%5$s</p>', esc_attr( $k ), esc_html( $f[0] ), esc_html( $f[1] ), esc_attr( isset( $prices[ $k ] ) ? $prices[ $k ] : '' ), $f[2] ? '<span class="description">' . esc_html( $f[2] ) . '</span>' : '' );
	}
	echo '</div>';
	submit_button( 'Save prices', 'primary', 'submit', false );
	echo '</form>';

	echo '<h2>How profit is divided</h2><p>After costs and paybacks. Private: only people picked below can see this page.</p>';
	ltef_money_form_open( 'split', 'settings' );
	echo '<div class="ltef-money__fields">';
	foreach ( $s['partners'] as $k => $p ) {
		printf( '<p><label for="ltef-%1$s_name">Partner</label><input type="text" id="ltef-%1$s_name" name="%1$s_name" value="%2$s"></p>', esc_attr( $k ), esc_attr( $p['name'] ) );
		printf( '<p><label for="ltef-%1$s_pct">Share <span class="unit">(%%)</span></label><input type="text" inputmode="decimal" id="ltef-%1$s_pct" name="%1$s_pct" value="%2$s" placeholder="Enter a percentage"></p>', esc_attr( $k ), esc_attr( $p['pct'] ) );
	}
	printf( '<p><label for="ltef-reserve">Hold back before dividing <span class="unit">(%%)</span></label><input type="text" inputmode="decimal" id="ltef-reserve" name="reserve" value="%s"><span class="description">Optional, for taxes or upcoming costs. 0 holds nothing back.</span></p>', esc_attr( $s['reserve'] ) );
	echo '</div><fieldset class="ltef-money__viewers"><legend>Who can see Money</legend>';
	foreach ( get_users( array( 'role' => 'administrator', 'orderby' => 'display_name' ) ) as $u ) {
		printf( '<label><input type="checkbox" name="viewers[]" value="%d"%s> %s <span class="description">%s</span></label>', (int) $u->ID, checked( in_array( $u->ID, array_map( 'intval', $s['viewers'] ), true ), true, false ), esc_html( $u->display_name ), esc_html( $u->user_email ) );
	}
	echo '<p class="description">Leave everyone unticked to let every administrator see it. You stay on the list when you save.</p></fieldset>';
	submit_button( 'Save split', 'primary', 'submit', false );
	echo '</form>';
}

/* Column callbacks (each returns escaped HTML). */
function ltef_money_col_source( $e ) {
	return esc_html( ltef_money_sources()[ $e['source'] ] ?? '' );
}
function ltef_money_col_category( $e ) {
	return esc_html( ltef_money_categories()[ $e['category'] ] ?? '' );
}
function ltef_money_col_method( $e ) {
	return esc_html( ltef_money_methods()[ $e['method'] ] ?? '' );
}
function ltef_money_col_party( $e ) {
	return esc_html( $e['party'] );
}
function ltef_money_col_note( $e ) {
	return esc_html( $e['note'] );
}
function ltef_money_col_paid_by( $e ) {
	$names = ltef_money_partner_names();
	if ( ! isset( $names[ $e['paid_by'] ] ) ) {
		return 'The business';
	}
	return esc_html( $names[ $e['paid_by'] ] ) . ( 'no' === $e['payback'] ? ' <span class="description">(gift)</span>' : '' );
}
function ltef_money_col_receipt( $e ) {
	if ( ! $e['receipt'] ) {
		return '<span class="description">None</span>';
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=ltef_receipt&id=' . $e['id'] ), 'ltef_receipt_' . $e['id'] );
	return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">View</a>';
}
function ltef_money_col_who( $e ) {
	return esc_html( ltef_money_partner_names()[ $e['who'] ] ?? '' );
}
function ltef_money_col_kind( $e ) {
	return 'share' === $e['kind'] ? 'Profit share' : 'Payback';
}

function ltef_money_styles() {
	?>
	<style>
		.ltef-money { max-width: 1100px; }
		.ltef-money__private { color: #50575e; margin-top: 0; }
		.ltef-money h2 { margin-top: 2em; }
		.ltef-money__periods { display: flex; flex-wrap: wrap; gap: 6px; margin: 16px 0 12px; }
		.ltef-money__tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
		.ltef-money__tile { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 14px 16px; display: grid; gap: 4px; }
		.ltef-money__tile span { color: #50575e; font-size: 13px; }
		.ltef-money__tile strong { font-size: 26px; line-height: 1.2; font-variant-numeric: tabular-nums; }
		.ltef-money__tile--up strong { color: #1a6b3c; }
		.ltef-money__tile--down strong { color: #b32d2e; }
		.ltef-money__flow { max-width: 560px; }
		.ltef-money__flow th { font-weight: 400; }
		.ltef-money__flow td, .ltef-money .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
		.ltef-money__flow .is-total th, .ltef-money__flow .is-total td { font-weight: 600; border-top: 1px solid #c3c4c7; }
		.ltef-money__note { color: #8a2424; }
		.ltef-money__people { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; max-width: 760px; }
		.ltef-money__person { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 4px 16px 12px; }
		.ltef-money__person dl { display: grid; grid-template-columns: 1fr auto; gap: 6px 12px; margin: 0; }
		.ltef-money__person dd { margin: 0; text-align: right; font-variant-numeric: tabular-nums; }
		.ltef-money__person .is-due { font-weight: 600; border-top: 1px solid #dcdcde; padding-top: 6px; }
		.ltef-money__chart { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 12px; max-width: 760px; }
		.ltef-money__chart svg { width: 100%; height: auto; display: block; }
		.ltef-money__chart .g { stroke: #e6e6e8; }
		.ltef-money__chart .t { fill: #50575e; font-size: 11px; }
		.ltef-money__chart .in, .ltef-money__legend .in { fill: #2c6e49; background: #2c6e49; }
		.ltef-money__chart .out, .ltef-money__legend .out { fill: #d98a54; background: #d98a54; }
		.ltef-money__legend { display: flex; align-items: center; gap: 6px; margin: 8px 0 0; color: #50575e; }
		.ltef-money__legend .k { display: inline-block; width: 12px; height: 12px; border-radius: 2px; margin-left: 10px; }
		.ltef-money__legend .k:first-child { margin-left: 0; }
		.ltef-money__fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 4px 16px; max-width: 900px; }
		.ltef-money__fields p { margin: 6px 0; display: grid; gap: 4px; }
		.ltef-money__fields .wide { grid-column: 1 / -1; }
		.ltef-money__fields label { font-weight: 600; }
		.ltef-money__fields .unit { font-weight: 400; color: #50575e; }
		.ltef-money__fields input[type=text], .ltef-money__fields input[type=date], .ltef-money__fields select { width: 100%; max-width: 100%; }
		.ltef-money__form { margin-bottom: 8px; }
		.ltef-money__scroll { overflow-x: auto; position: relative; }
		.ltef-money__row-actions { white-space: nowrap; }
		.ltef-money__row-actions form { display: inline; }
		.ltef-money__confirm { display: inline-block; }
		.ltef-money__confirm summary { color: #b32d2e; cursor: pointer; display: inline; }
		.ltef-money__confirm[open] summary { margin-right: 6px; }
		.ltef-money__empty { color: #50575e; }
		.ltef-money__viewers { margin: 14px 0; display: grid; gap: 6px; }
		.ltef-money__viewers legend { font-weight: 600; margin-bottom: 4px; }
		.ltef-money__due { display: flex; flex-wrap: wrap; gap: 8px 24px; font-size: 14px; }
		.ltef-money__actions { margin-top: 20px; }
	</style>
	<?php
}
