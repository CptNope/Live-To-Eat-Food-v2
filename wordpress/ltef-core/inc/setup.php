<?php
/**
 * Tools → Live to Eat Food setup: create the pages, import Tina's places and guides, add labeled samples.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** The site's pages: slug => [ title, theme pattern that fills it ]. */
function ltef_pages() {
	return array(
		'home'             => array( 'Home', '' ),
		'guides'           => array( 'Guides', '' ),
		'roulette'         => array( 'Restaurant Roulette', 'live-to-eat-food/page-roulette' ),
		'food-help'        => array( 'Food help', 'live-to-eat-food/page-food-help' ),
		'submit'           => array( 'Send something in', 'live-to-eat-food/page-submit' ),
		'work-with-tina'   => array( 'Work with Tina', 'live-to-eat-food/page-work-with-tina' ),
		'for-restaurants'  => array( 'For restaurants', 'live-to-eat-food/page-for-restaurants' ),
		'how-it-works'     => array( 'How it works', 'live-to-eat-food/page-how-it-works' ),
		'editorial-policy' => array( 'Editorial policy', 'live-to-eat-food/page-editorial-policy' ),
		'crumbs'           => array( 'Monthly Crumbs', 'live-to-eat-food/page-crumbs' ),
		'tina'             => array( 'Tina', 'live-to-eat-food/page-tina' ),
		'daily-deals'      => array( 'Daily deals', 'live-to-eat-food/page-daily-deals' ),
		'your-bites'       => array( 'Your bites', 'live-to-eat-food/page-your-bites' ),
	);
}

add_action(
	'admin_menu',
	function () {
		add_management_page( 'Live to Eat Food setup', 'Live to Eat Food setup', 'manage_options', 'ltef-setup', 'ltef_setup_page' );
	}
);

function ltef_setup_page() {
	$msg = isset( $_GET['ltef_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['ltef_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap"><h1>Live to Eat Food setup</h1>';
	if ( $msg ) {
		echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
	}
	$btn = function ( $do, $label, $help ) {
		printf( '<form method="post" action="%s" style="margin:18px 0"><input type="hidden" name="action" value="ltef_setup"><input type="hidden" name="do" value="%s">%s<button class="button button-primary">%s</button> <span class="description">%s</span></form>', esc_url( admin_url( 'admin-post.php' ) ), esc_attr( $do ), wp_nonce_field( 'ltef_setup', '_wpnonce', true, false ), esc_html( $label ), esc_html( $help ) );
	};
	$btn( 'pages', 'Create the pages', 'Home, Guides, Roulette, Food help, Send something in, Work with Tina and the rest, filled from the theme’s patterns. Pages that already exist are left alone.' );
	$data = json_decode( (string) file_get_contents( LTEF_DIR . 'data/places.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$btn( 'places', 'Import Tina’s places and videos', sprintf( '%d places, %d guides and %d round-ups from her public posts, plus her other videos from places already here. Anything already on the site is left as it is, so it’s safe to press again after an update.', count( $data['places'] ?? array() ), count( $data['guides'] ?? array() ), count( $data['roundups'] ?? array() ) ) );
	$btn( 'samples', 'Add labeled samples', 'A few sample Woo-pons, events and jobs so the pages aren’t empty while you build. Delete them before launch.' );
	$btn( 'refresh', 'Refresh the built-in pages', 'Replaces Food help, Send something in, Work with Tina and the other built-in pages with the theme’s latest version (live prices included). Edits you made to those pages are replaced; the old versions stay under each page’s Revisions.' );
	echo '</div>';
}

add_action( 'admin_post_ltef_setup', 'ltef_handle_setup' );
function ltef_handle_setup() {
	check_admin_referer( 'ltef_setup' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	$msg = '';
	if ( 'pages' === $do ) {
		$msg = ltef_create_pages();
	} elseif ( 'places' === $do ) {
		$msg = ltef_import_places();
	} elseif ( 'samples' === $do ) {
		$msg = ltef_add_samples();
	} elseif ( 'refresh' === $do ) {
		$msg = ltef_refresh_pages();
	}
	wp_safe_redirect( admin_url( 'tools.php?page=ltef-setup&ltef_msg=' . rawurlencode( $msg ) ) );
	exit;
}

function ltef_create_pages() {
	$made = 0;
	foreach ( ltef_pages() as $slug => $def ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		$content = $def[1] ? '<!-- wp:pattern {"slug":"' . $def[1] . '"} /-->' : '';
		// Expand the pattern into real blocks so the page is editable on its own.
		if ( $def[1] && class_exists( 'WP_Block_Patterns_Registry' ) ) {
			$pat = WP_Block_Patterns_Registry::get_instance()->get_registered( $def[1] );
			if ( $pat && ! empty( $pat['content'] ) ) {
				$content = $pat['content'];
			}
		}
		wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $def[0], 'post_name' => $slug, 'post_content' => $content, 'meta_input' => $def[1] ? array( '_wp_page_template' => 'page-no-title' ) : array() ) );
		++$made;
	}
	$home   = get_page_by_path( 'home' );
	$guides = get_page_by_path( 'guides' );
	if ( $home && $guides ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home->ID );
		update_option( 'page_for_posts', $guides->ID );
	}
	if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' ); // updates the option and the live rewrite object
		ltef_register_post_types(); // register again so places, Woo-pons, events and jobs get their pretty URLs in this request
	}
	flush_rewrite_rules();
	return $made . ' pages created. Home and Guides are set under Settings → Reading.';
}

/** Re-fills the built-in pages from the theme's patterns. WordPress keeps the old content as a revision. */
function ltef_refresh_pages() {
	$n = 0;
	foreach ( ltef_pages() as $slug => $def ) {
		$page = $def[1] ? get_page_by_path( $slug ) : null;
		$pat  = $page && class_exists( 'WP_Block_Patterns_Registry' ) ? WP_Block_Patterns_Registry::get_instance()->get_registered( $def[1] ) : null;
		if ( $pat && ! empty( $pat['content'] ) && $pat['content'] !== $page->post_content ) {
			wp_update_post( array( 'ID' => $page->ID, 'post_content' => wp_slash( $pat['content'] ) ) );
			++$n;
		}
	}
	return $n ? $n . ' pages refreshed from the theme.' : 'The pages already match the theme.';
}

function ltef_term( $tax, $slug, $name ) {
	$t = get_term_by( 'slug', $slug, $tax );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$r = wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

function ltef_import_places() {
	$data = json_decode( (string) file_get_contents( LTEF_DIR . 'data/places.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! $data ) {
		return 'The places file couldn’t be read.';
	}
	$crave = array_column( $data['cravings'], 'label', 'id' );
	$with  = array_column( $data['withs'], 'label', 'id' );
	$ids   = array();
	$n     = 0;
	$more = function ( $p ) {
		return implode( "\n", array_map( function ( $v ) { return $v['month'] . ' | ' . $v['url'] . ' | ' . $v['caption']; }, $p['more'] ?? array() ) );
	};
	$m = 0;
	$t = 0;
	$skipped = 0;
	// Never add a business that's on Tina's private don't-work-with list. The names stay on the server.
	$norm  = function ( $t ) { return preg_replace( '/[^a-z0-9]/', '', strtolower( remove_accents( (string) $t ) ) ); };
	$avoid = array_filter( array_map( $norm, preg_split( '/\r\n|\r|\n/', (string) ltef_setting( 'dont_work_with' ) ) ), function ( $x ) { return strlen( $x ) >= 4; } );
	foreach ( $data['places'] as $p ) {
		$existing = get_page_by_path( $p['id'], OBJECT, 'ltef_place' );
		if ( ! $existing ) {
			$name = $norm( $p['name'] );
			foreach ( $avoid as $a ) {
				if ( false !== strpos( $name, $a ) || false !== strpos( $a, $name ) ) {
					++$skipped;
					continue 2;
				}
			}
		}
		if ( $existing ) {
			$ids[ $p['id'] ] = $existing->ID;
			// A "not yet" place she has since been to: add her video and line, unless something was typed in by hand.
			if ( ! empty( $p['tina'] ) && '' === (string) get_post_meta( $existing->ID, 'ltef_tina_video', true ) && '' === (string) get_post_meta( $existing->ID, 'ltef_tina_quote', true ) ) {
				$sort = (string) ( $p['tina']['sort'] ?? '' );
				update_post_meta( $existing->ID, 'ltef_tina_quote', $p['tina']['quote'] ?? '' );
				update_post_meta( $existing->ID, 'ltef_tina_video', $p['tina']['url'] ?? '' );
				update_post_meta( $existing->ID, 'ltef_tina_date', strlen( $sort ) >= 6 ? substr( $sort, 0, 4 ) . '-' . substr( $sort, 4, 2 ) : '' );
				if ( '' === (string) get_post_meta( $existing->ID, 'ltef_dish', true ) && ! empty( $p['dish'] ) ) {
					update_post_meta( $existing->ID, 'ltef_dish', $p['dish'] );
				}
				++$t;
			}
			// Her other videos: add the ones the place doesn't have yet (matched by link), keep anything typed in by hand.
			$have = (string) get_post_meta( $existing->ID, 'ltef_more_videos', true );
			$add  = array_filter( $p['more'] ?? array(), function ( $v ) use ( $have ) { return false === strpos( $have, $v['url'] ); } );
			if ( $add ) {
				update_post_meta( $existing->ID, 'ltef_more_videos', trim( $have . "\n" . $more( array( 'more' => $add ) ) ) );
				$m += count( $add );
			}
			continue;
		}
		$id = wp_insert_post( array( 'post_type' => 'ltef_place', 'post_status' => 'publish', 'post_title' => $p['name'], 'post_name' => $p['id'] ) );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		$ids[ $p['id'] ] = $id;
		$area            = $data['areas'][ $p['area'] ] ?? array( 'name' => $p['area'], 'town' => 'Worcester' );
		$town            = $p['town'] ?? ( $area['town'] ?? 'Worcester' );
		$meta            = array(
			'kind'    => $p['kind'] ?? 'eat',
			'address' => trim( ( $p['num'] ?? '' ) . ' ' . ( $p['street'] ?? '' ) ),
			'town'    => 'Worcester' === $town ? '' : $town,
			'cuisine' => $p['cuisine'] ?? '',
			'price'   => $p['price'] ?? '',
			'phone'   => $p['phone'] ?? '',
			'website' => $p['website'] ?? '',
			'facts'   => implode( "\n", $p['facts'] ?? array() ),
		);
		if ( ! empty( $p['tina'] ) ) {
			$sort                 = (string) ( $p['tina']['sort'] ?? '' );
			$meta['dish']         = $p['dish'] ?? '';
			$meta['tina_quote']   = $p['tina']['quote'] ?? '';
			$meta['tina_video']   = $p['tina']['url'] ?? '';
			$meta['tina_date']    = strlen( $sort ) >= 6 ? substr( $sort, 0, 4 ) . '-' . substr( $sort, 4, 2 ) : '';
			$meta['more_videos']  = $more( $p );
			$m                   += count( $p['more'] ?? array() );
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, 'ltef_' . $k, $v );
		}
		wp_set_object_terms( $id, array( ltef_term( 'ltef_area', $p['area'], $area['name'] ) ), 'ltef_area' );
		$c = array();
		foreach ( $p['cravings'] ?? array() as $k ) {
			$c[] = ltef_term( 'ltef_craving', $k, ucfirst( $crave[ $k ] ?? $k ) );
		}
		wp_set_object_terms( $id, $c, 'ltef_craving' );
		$w = array();
		foreach ( $p['with'] ?? array() as $k ) {
			$w[] = ltef_term( 'ltef_with', $k, ucfirst( $with[ $k ] ?? $k ) );
		}
		wp_set_object_terms( $id, $w, 'ltef_with' );
		++$n;
	}
	// guides become posts (the Guides page lists them)
	$g   = 0;
	$cat = term_exists( 'guides', 'category' );
	$cat = $cat ? (int) $cat['term_id'] : (int) wp_insert_term( 'Guides', 'category', array( 'slug' => 'guides' ) )['term_id'];
	foreach ( $data['guides'] as $guide ) {
		if ( get_page_by_path( $guide['id'], OBJECT, 'post' ) ) {
			continue;
		}
		$blocks = '<!-- wp:paragraph {"className":"is-style-lede"} --><p class="is-style-lede">' . esc_html( $guide['lede'] ?? '' ) . '</p><!-- /wp:paragraph -->';
		$real   = array_values( array_filter( $guide['stops'], function ( $s ) { return empty( $s['sponsor'] ); } ) );
		foreach ( $real as $i => $stop ) {
			$pid = $ids[ $stop['place'] ] ?? 0;
			$name = $pid ? get_the_title( $pid ) : $stop['place'];
			$link = $pid ? get_permalink( $pid ) : '#';
			$blocks .= '<!-- wp:heading {"fontSize":"display-s"} --><h2 class="wp-block-heading has-display-s-font-size">' . ( $i + 1 ) . '. <a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></h2><!-- /wp:heading -->';
			$blocks .= '<!-- wp:paragraph --><p>' . esc_html( $stop['say'] ?? '' ) . ( ! empty( $stop['extra'] ) ? ' <em>' . esc_html( $stop['extra'] ) . '</em>' : '' ) . '</p><!-- /wp:paragraph -->';
			if ( 1 === $i && count( $real ) >= 4 ) {
				$blocks .= '<!-- wp:ltef/ad-spot {"spot":"guide"} /-->';
			}
		}
		wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $guide['title'], 'post_name' => $guide['id'], 'post_excerpt' => $guide['short'] ?? '', 'post_content' => $blocks, 'post_category' => array( $cat ) ) );
		++$g;
	}
	// Round-ups: her event, trip and market videos, as posts under Out & about.
	$r   = 0;
	$out = term_exists( 'out-and-about', 'category' );
	$out = $out ? (int) $out['term_id'] : (int) wp_insert_term( 'Out & about', 'category', array( 'slug' => 'out-and-about' ) )['term_id'];
	$video_block = function ( $v ) {
		return '<!-- wp:ltef/video ' . wp_json_encode( array( 'url' => $v['url'], 'month' => $v['month'], 'caption' => $v['caption'] ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . ' /-->';
	};
	$intro = function ( $ru ) {
		return empty( $ru['intro'] ) ? '' : '<!-- wp:paragraph {"className":"is-style-lede"} --><p class="is-style-lede">' . esc_html( $ru['intro'] ) . '</p><!-- /wp:paragraph -->';
	};
	foreach ( $data['roundups'] ?? array() as $ru ) {
		$post = get_page_by_path( $ru['id'], OBJECT, 'post' );
		if ( $post ) {
			// Add any of her videos the round-up doesn't have yet. If it's still only her videos, keep them in date order.
			$missing = array_filter( $ru['videos'], function ( $v ) use ( $post ) { return false === strpos( $post->post_content, $v['url'] ); } );
			if ( $missing ) {
				$only_videos = '' === trim( preg_replace( array( '#<!-- wp:ltef/video .*? /-->#s', '#<!-- wp:paragraph \{"className":"is-style-lede"\} -->.*?<!-- /wp:paragraph -->#s' ), '', $post->post_content ) );
				$content     = $only_videos ? $intro( $ru ) . implode( '', array_map( $video_block, $ru['videos'] ) ) : $post->post_content . implode( '', array_map( $video_block, $missing ) );
				wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $content ) ) );
				$m += count( $missing );
			}
			continue;
		}
		$blocks = $intro( $ru ) . implode( '', array_map( $video_block, $ru['videos'] ) );
		wp_insert_post(
			array(
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_title'    => $ru['title'],
				'post_name'     => $ru['id'],
				'post_date'     => $ru['date'] . ' 12:00:00',
				'post_excerpt'  => $ru['excerpt'] ?? '',
				'post_content'  => wp_slash( $blocks ),
				'post_category' => array( $out ),
			)
		);
		++$r;
	}
	return sprintf( '%d places, %d guides and %d round-ups imported, and %d more videos added.', $n, $g, $r, $m ) . ( $t ? sprintf( ' %d places now show Tina’s visit.', $t ) : '' ) . ( $skipped ? sprintf( ' %d skipped because they match your don’t-work-with list.', $skipped ) : '' );
}

function ltef_add_samples() {
	$today = ltef_today();
	$plus  = function ( $days ) {
		return wp_date( 'Y-m-d', strtotime( "+$days days" ) );
	};
	$woopons = array(
		array( '$5 off', array( 'business' => '[Sample] Pizza place', 'kind' => 'food', 'tier' => 'vip', 'area_text' => 'Canal District', 'code' => 'WOO-1042', 'start' => $today, 'end' => $plus( 28 ), 'exclusions' => 'Delivery apps, other offers', 'fine' => 'Two large pies or more · Show the code before you pay' ) ),
		array( 'Free boba', array( 'business' => '[Sample] Tea shop', 'kind' => 'food', 'tier' => 'weekly', 'area_text' => 'Main South', 'code' => 'WOO-2290', 'start' => $today, 'end' => $plus( 42 ), 'exclusions' => 'Seasonal drinks', 'fine' => 'With any entrée · Weekdays' ) ),
		array( '$5 off a cut', array( 'business' => '[Sample] Barbershop', 'kind' => 'service', 'tier' => 'free', 'area_text' => 'Main South', 'code' => 'WOO-6104', 'start' => $today, 'end' => $plus( 14 ), 'exclusions' => 'Kids’ cuts, beard trims', 'fine' => 'First visit only' ) ),
		array( '15% off', array( 'business' => 'Lensmart', 'kind' => 'online', 'tier' => 'partner', 'code' => 'LTEF', 'start' => $today, 'end' => '', 'exclusions' => '', 'fine' => 'Glasses and sunglasses, at checkout on lensmartonline.com', 'shop_url' => 'https://www.lensmartonline.com/?lenid=fx_wh_LTEF' ) ),
	);
	foreach ( $woopons as $w ) {
		$id = wp_insert_post( array( 'post_type' => 'ltef_woopon', 'post_status' => 'publish', 'post_title' => $w[0] ) );
		foreach ( $w[1] as $k => $v ) {
			update_post_meta( $id, 'ltef_' . $k, $v );
		}
	}
	$events = array(
		array( '[Sample] Premium event', array( 'date' => $plus( 3 ), 'time' => '[Time]', 'venue' => '[Place], Canal District', 'price_text' => '[Price]', 'premium' => true ), 'A sample premium listing, pinned at the top of its week. Written by the organizer, not by Tina.' ),
		array( '[Sample] Night market', array( 'date' => $plus( 4 ), 'time' => '6–10pm', 'venue' => 'Green St, Canal District', 'price_text' => 'Free' ), 'Vendors, music and a soft-serve truck.' ),
		array( '[Sample] Art walk and live music', array( 'date' => $plus( 5 ), 'time' => '5–9pm', 'venue' => 'Canal District', 'price_text' => 'Free' ), 'Not a food event, and that’s fine.' ),
	);
	foreach ( $events as $e ) {
		$id = wp_insert_post( array( 'post_type' => 'ltef_event', 'post_status' => 'publish', 'post_title' => $e[0], 'post_excerpt' => $e[2] ) );
		foreach ( $e[1] as $k => $v ) {
			update_post_meta( $id, 'ltef_' . $k, $v );
		}
	}
	$jobs = array(
		array( 'Line cook', array( 'business' => '[Sample restaurant]', 'area_text' => 'Shrewsbury Street', 'job_kind' => 'kitchen', 'hours_type' => 'ft', 'pay' => '[Pay range]', 'how_to_apply' => '[How to apply]', 'until' => $plus( 30 ) ) ),
		array( 'Server', array( 'business' => '[Sample restaurant]', 'area_text' => 'Canal District', 'job_kind' => 'front', 'hours_type' => 'pt', 'pay' => '[Pay range]', 'how_to_apply' => '[How to apply]', 'until' => $plus( 30 ) ) ),
	);
	foreach ( $jobs as $j ) {
		$id = wp_insert_post( array( 'post_type' => 'ltef_job', 'post_status' => 'publish', 'post_title' => $j[0] ) );
		foreach ( $j[1] as $k => $v ) {
			update_post_meta( $id, 'ltef_' . $k, $v );
		}
	}
	return 'Samples added: 4 Woo-pons, 3 events, 2 jobs. Each is marked [Sample]; delete them before launch.';
}
