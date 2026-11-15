<?php
/**
 * Places to try (Places → Places to try): every "Suggest a spot" sent from the site, and tips Tina adds by hand
 * (DMs, comments, email), in one list. The same place suggested more than once shows once, with a count.
 *
 * Suggestions are never shown on the site, so they skip the review queue. Each is a Message (ltef_submission,
 * form "suggest"); its place on the list is the ltef_try meta: '' to try, 'been' when Tina has gone, 'skip'
 * when it's not for her.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** The list's three tabs. */
function ltef_totry_views() {
	return array( '' => 'To try', 'been' => 'Been there', 'skip' => 'Not for us' );
}

/** Every suggestion, newest first. */
function ltef_totry_items() {
	return get_posts(
		array(
			'post_type'   => 'ltef_submission',
			'post_status' => array( 'publish', 'pending', 'private' ),
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'meta_key'    => 'ltef_form', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => 'suggest', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

/** A suggestion's fields: place, why, email, from. */
function ltef_totry_fields( $id ) {
	$f = get_post_meta( $id, 'ltef_fields', true );
	return array_merge( array( 'place' => '', 'why' => '', 'email' => '', 'from' => '' ), is_array( $f ) ? $f : array() );
}

/** The place on the site a suggestion names, or 0. Tries the name as typed, then without "in Rutland!" and the like. */
function ltef_totry_match( $name ) {
	if ( ! function_exists( 'ltef_owner_match_place' ) ) {
		return 0;
	}
	$name = trim( (string) $name );
	$id   = ltef_owner_match_place( $name );
	if ( $id ) {
		return $id;
	}
	$base = trim( preg_replace( '/\s*(?:[,(–—-]|\bin\b|\bon\b|\bat\b)\s.*$/iu', '', $name ), " \t!.?" );
	return $base && $base !== $name ? ltef_owner_match_place( $base ) : 0;
}

/** Suggestions grouped by place: [ key => [ 'name', 'place', 'ids', 'try' ] ], newest group first. */
function ltef_totry_groups() {
	$groups = array();
	foreach ( ltef_totry_items() as $p ) {
		$f     = ltef_totry_fields( $p->ID );
		$name  = '' !== trim( $f['place'] ) ? trim( $f['place'] ) : preg_replace( '/^Suggested spot:\s*/', '', $p->post_title );
		$place = ltef_totry_match( $name );
		$key   = $place ? 'p' . $place : 'n' . ltef_place_name_key( trim( preg_replace( '/\s*(?:[,(–—-]|\bin\b)\s.*$/iu', '', $name ), " \t!.?" ) );
		if ( ! isset( $groups[ $key ] ) ) {
			$groups[ $key ] = array( 'name' => $name, 'place' => $place, 'ids' => array(), 'try' => (string) get_post_meta( $p->ID, 'ltef_try', true ) );
		}
		$groups[ $key ]['ids'][] = $p->ID;
	}
	return $groups;
}

/* ------------------------------------------------------------------ The screen */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=ltef_place', 'Places to try', 'Places to try', 'edit_others_posts', 'ltef-to-try', 'ltef_totry_page' );
	},
	20
);

function ltef_totry_page() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$views  = ltef_totry_views();
	$view   = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$view   = isset( $views[ $view ] ) ? $view : '';
	$groups = ltef_totry_groups();
	$counts = array_fill_keys( array_keys( $views ), 0 );
	foreach ( $groups as $g ) {
		$counts[ isset( $views[ $g['try'] ] ) ? $g['try'] : '' ]++;
	}
	$base = admin_url( 'edit.php?post_type=ltef_place&page=ltef-to-try' );

	echo '<div class="wrap ltef-review ltef-totry"><h1>Places to try</h1>';
	echo '<p class="ltef-lede">Where people want Tina to eat next: every “Suggest a spot” from the site, plus tips you add below. The same place sent more than once shows once, with a count. None of this is shown on the site, and a suggestion can’t be paid for.</p>';
	$done = isset( $_GET['ltef_done'] ) ? sanitize_text_field( wp_unslash( $_GET['ltef_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $done ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( array( 'added' => 'Added to the list.', 'moved' => 'Moved.' )[ $done ] ?? 'Done.' ) . '</p></div>';
	}

	// Add a tip from a DM, a comment or an email.
	echo '<details class="ltef-totry-add"' . ( $groups ? '' : ' open' ) . '><summary class="button">Add a tip</summary>'
		. '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ltef_totry_add">' . wp_nonce_field( 'ltef_totry_add', '_wpnonce', true, false )
		. '<table class="form-table" role="presentation">'
		. '<tr><th scope="row"><label for="ltef-try-place">The place</label></th><td><input class="regular-text" id="ltef-try-place" name="place" required placeholder="Maple Ave Diner, Rutland"><p class="description">Name and town, so it’s easy to find later.</p></td></tr>'
		. '<tr><th scope="row"><label for="ltef-try-why">What to order, or why</label></th><td><textarea class="large-text" rows="2" id="ltef-try-why" name="why"></textarea></td></tr>'
		. '<tr><th scope="row"><label for="ltef-try-from">Who told you</label></th><td><input class="regular-text" id="ltef-try-from" name="from" placeholder="@someone on Instagram"><p class="description">Optional. Private.</p></td></tr>'
		. '</table><p><button class="button button-primary">Add to the list</button></p></form></details>';

	echo '<ul class="subsubsub">';
	$i = 0;
	foreach ( $views as $k => $label ) {
		printf( '<li>%s<a href="%s"%s>%s <span class="count">(%d)</span></a></li>', $i++ ? ' | ' : '', esc_url( $k ? add_query_arg( 'view', $k, $base ) : $base ), $k === $view ? ' class="current" aria-current="page"' : '', esc_html( $label ), (int) $counts[ $k ] );
	}
	echo '</ul><div style="clear: both;"></div>';

	$shown = array_filter( $groups, function ( $g ) use ( $view, $views ) { return ( isset( $views[ $g['try'] ] ) ? $g['try'] : '' ) === $view; } );
	if ( ! $shown ) {
		echo '<p class="ltef-totry-empty">' . esc_html( array( '' => 'Nothing waiting. New suggestions from the site land here.', 'been' => 'Nothing marked yet. Use “Been there” on a place once Tina goes.', 'skip' => 'Nothing here.' )[ $view ] ) . '</p></div>';
		return;
	}

	echo '<table class="widefat striped ltef-totry-table"><thead><tr><th scope="col">Place</th><th scope="col">What to order, or why</th><th scope="col">From</th><th scope="col">On the site</th><th scope="col"><span class="screen-reader-text">Move it</span></th></tr></thead><tbody>';
	foreach ( $shown as $g ) {
		$whys   = array();
		$froms  = array();
		$emails = array();
		$dates  = array();
		$block  = false;
		foreach ( $g['ids'] as $id ) {
			$f = ltef_totry_fields( $id );
			if ( '' !== trim( $f['why'] ) ) {
				$whys[] = trim( $f['why'] );
			}
			if ( is_email( $f['email'] ) ) {
				$emails[] = strtolower( $f['email'] );
			}
			$froms[] = '' !== trim( $f['from'] ) ? trim( $f['from'] ) : ( is_email( $f['email'] ) ? '' : 'Someone on the site' );
			$dates[] = get_the_date( 'M j, Y', $id );
			$block   = $block || get_post_meta( $id, 'ltef_flag_blocklist', true );
		}
		$emails = array_unique( $emails );
		$n      = count( $g['ids'] );

		$name = '<strong>' . esc_html( $g['name'] ) . '</strong>';
		$name .= '<br><span class="description">' . esc_html( 1 === $n ? 'Suggested ' . $dates[0] : 'Suggested ' . $n . ' times, last on ' . $dates[0] ) . '</span>';
		if ( $block ) {
			$name .= '<br><span class="ltef-chip ltef-chip--warn">On your don’t-work-with list</span>';
		}

		$why  = $whys ? '<ul class="ltef-totry-whys">' . implode( '', array_map( function ( $w ) { return '<li>' . esc_html( $w ) . '</li>'; }, array_unique( $whys ) ) ) . '</ul>' : '<span class="description">—</span>';
		$from = array();
		foreach ( array_unique( array_filter( $froms ) ) as $fr ) {
			$from[] = esc_html( $fr );
		}
		foreach ( $emails as $em ) {
			$from[] = '<a href="mailto:' . esc_attr( $em ) . '">' . esc_html( $em ) . '</a>';
		}
		$from_h = implode( '<br>', $from );
		if ( $emails ) {
			$from_h .= '<br><span class="description">' . esc_html( 1 === count( $emails ) ? 'Asked to hear when she goes.' : 'They asked to hear when she goes.' ) . '</span>';
		}

		$site = '<span class="description">Not listed yet</span>';
		if ( $g['place'] ) {
			$tina  = (string) ltef_meta( $g['place'], 'tina_video' );
			$month = (string) ltef_meta( $g['place'], 'tina_date' );
			$site  = '<a href="' . esc_url( get_permalink( $g['place'] ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $g['place'] ) ) . '<span class="screen-reader-text"> (opens a new tab)</span></a><br>'
				. ( '' !== $tina ? '<span class="ltef-chip">Tina’s been' . ( $month ? ': ' . esc_html( wp_date( 'M Y', strtotime( $month . '-15' ) ) ) : '' ) . '</span>' : '<span class="description">Listed, no video yet</span>' );
		}

		$acts = '';
		foreach ( $views as $k => $label ) {
			if ( $k === $g['try'] || ( '' === $k && ! isset( $views[ $g['try'] ] ) ) ) {
				continue;
			}
			$acts .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display: inline-block; margin: 0 6px 6px 0;"><input type="hidden" name="action" value="ltef_totry_set"><input type="hidden" name="to" value="' . esc_attr( $k ) . '"><input type="hidden" name="view" value="' . esc_attr( $view ) . '">'
				. implode( '', array_map( function ( $id ) { return '<input type="hidden" name="ids[]" value="' . (int) $id . '">'; }, $g['ids'] ) )
				. wp_nonce_field( 'ltef_totry_set', '_wpnonce', true, false )
				. '<button class="button' . ( 'been' === $k ? ' button-primary' : '' ) . '">' . esc_html( '' === $k ? 'Back to To try' : $label ) . '<span class="screen-reader-text">: ' . esc_html( $g['name'] ) . '</span></button></form>';
		}

		printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $name, $why, $from_h, $site, $acts ); // each piece escaped above
	}
	echo '</tbody></table></div>';
}

/* ------------------------------------------------------------------ Saving */

add_action( 'admin_post_ltef_totry_add', 'ltef_totry_handle_add' );
function ltef_totry_handle_add() {
	check_admin_referer( 'ltef_totry_add' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Not allowed.' );
	}
	$place = isset( $_POST['place'] ) ? sanitize_text_field( wp_unslash( $_POST['place'] ) ) : '';
	if ( '' !== $place ) {
		$fields = array(
			'place' => $place,
			'why'   => isset( $_POST['why'] ) ? sanitize_textarea_field( wp_unslash( $_POST['why'] ) ) : '',
			'from'  => isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '',
		);
		$id     = wp_insert_post( array( 'post_type' => 'ltef_submission', 'post_status' => 'publish', 'post_title' => 'Suggested spot: ' . $place ), true );
		if ( ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'ltef_form', 'suggest' );
			update_post_meta( $id, 'ltef_fields', $fields );
			update_post_meta( $id, 'ltef_sent_at', current_time( 'mysql' ) );
			update_post_meta( $id, 'ltef_added_by', get_current_user_id() );
			if ( function_exists( 'ltef_on_blocklist' ) && ltef_on_blocklist( $place ) ) {
				update_post_meta( $id, 'ltef_flag_blocklist', 1 );
			}
		}
	}
	wp_safe_redirect( add_query_arg( 'ltef_done', 'added', admin_url( 'edit.php?post_type=ltef_place&page=ltef-to-try' ) ) );
	exit;
}

add_action( 'admin_post_ltef_totry_set', 'ltef_totry_handle_set' );
function ltef_totry_handle_set() {
	check_admin_referer( 'ltef_totry_set' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Not allowed.' );
	}
	$to = isset( $_POST['to'] ) ? sanitize_key( wp_unslash( $_POST['to'] ) ) : '';
	if ( isset( ltef_totry_views()[ $to ] ) ) {
		foreach ( array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) ) as $id ) {
			if ( $id && 'ltef_submission' === get_post_type( $id ) && 'suggest' === get_post_meta( $id, 'ltef_form', true ) ) {
				update_post_meta( $id, 'ltef_try', $to );
				if ( 'pending' === get_post_status( $id ) ) {
					wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
				}
			}
		}
	}
	$view = isset( $_POST['view'] ) ? sanitize_key( wp_unslash( $_POST['view'] ) ) : '';
	wp_safe_redirect( add_query_arg( array_filter( array( 'view' => $view, 'ltef_done' => 'moved' ) ), admin_url( 'edit.php?post_type=ltef_place&page=ltef-to-try' ) ) );
	exit;
}

/** Once: suggestions still waiting in the review queue move to this list (they never needed approving). */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'ltef_totry_v1' ) ) {
			return;
		}
		update_option( 'ltef_totry_v1', time(), false );
		foreach ( ltef_totry_items() as $p ) {
			if ( 'pending' === $p->post_status ) {
				wp_update_post( array( 'ID' => $p->ID, 'post_status' => 'publish' ) );
			}
		}
	}
);
