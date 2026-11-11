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
		'beyond-food'      => array( 'Beyond food', 'live-to-eat-food/page-beyond-food' ),
		'your-bites'       => array( 'Your bites', 'live-to-eat-food/page-your-bites' ),
		'accessibility'    => array( 'Accessibility', 'live-to-eat-food/page-accessibility' ),
		'privacy-policy'   => array( 'Privacy policy', 'live-to-eat-food/page-privacy-policy' ),
		'terms'            => array( 'Terms of use', 'live-to-eat-food/page-terms' ),
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
	echo '<p class="ltef-lede">Press Import again after each plugin update to bring in what’s new.</p><div class="ltef-actions">';
	$btn = function ( $do, $label, $help, $primary = true ) {
		printf( '<form class="ltef-action" method="post" action="%s"><input type="hidden" name="action" value="ltef_setup"><input type="hidden" name="do" value="%s">%s<p>%s</p><button class="button%s">%s</button></form>', esc_url( admin_url( 'admin-post.php' ) ), esc_attr( $do ), wp_nonce_field( 'ltef_setup', '_wpnonce', true, false ), esc_html( $help ), $primary ? ' button-primary' : '', esc_html( $label ) );
	};
	$btn( 'pages', 'Create the pages', 'Home, Guides, Roulette, Food help, Send something in, Work with Tina, Accessibility, Privacy policy, Terms of use and the rest, filled from the theme’s patterns. Pages that already exist are left alone.' );
	$data = json_decode( (string) file_get_contents( LTEF_DIR . 'data/places.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$btn( 'places', 'Import Tina’s places and videos', sprintf( '%d places, %d guides and %d round-ups from her public posts, plus her other videos from places already here. Anything already on the site is left as it is, so it’s safe to press again after an update.', count( $data['places'] ?? array() ), count( $data['guides'] ?? array() ), count( $data['roundups'] ?? array() ) ) . ( ! empty( $data['specials'] ) ? sprintf( ' Also %d weekly deals for Daily deals.', count( $data['specials'] ) ) : '' ) . ( ! empty( $data['partners'] ) ? sprintf( ' And Tina’s %d partner codes for Woo-pons.', count( $data['partners'] ) ) : '' ) );
	$btn( 'samples', 'Add labeled samples', 'A few sample Woo-pons, events and jobs so the pages aren’t empty while you build. Delete them before launch.', false );
	$btn( 'refresh', 'Refresh the built-in pages', 'Replaces Food help, Send something in, Work with Tina and the other built-in pages with the theme’s latest version (live prices included). Edits you made to those pages are replaced; the old versions stay under each page’s Revisions.', false );
	echo '</div></div>';
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
		$have = get_page_by_path( $slug );
		// WordPress starts every site with a draft "Privacy Policy" at /privacy-policy/: fill that one instead of skipping it.
		$wp_draft = $have && 'publish' !== $have->post_status && (int) $have->ID === (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $have && ! $wp_draft ) {
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
		$id = wp_insert_post( array( 'ID' => $wp_draft ? $have->ID : 0, 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $def[0], 'post_name' => $slug, 'post_content' => $content, 'meta_input' => $def[1] ? array( '_wp_page_template' => 'page-no-title' ) : array() ) );
		if ( 'privacy-policy' === $slug && $id && ! is_wp_error( $id ) ) {
			update_option( 'wp_page_for_privacy_policy', $id );
		}
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
		return implode( "\n", array_map( function ( $v ) { return $v['month'] . ' | ' . $v['url'] . ( empty( $v['youtube'] ) ? '' : ' ' . $v['youtube'] ) . ' | ' . $v['caption']; }, $p['more'] ?? array() ) );
	};
	$m = 0;
	$t = 0;
	$y = 0;
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
			// Her YouTube copy plays first (TikTok's player is often throttled). Only fills an empty field.
			if ( ! empty( $p['youtube'] ) && '' === (string) get_post_meta( $existing->ID, 'ltef_youtube', true ) ) {
				update_post_meta( $existing->ID, 'ltef_youtube', $p['youtube'] );
				++$y;
			}
			// Her other videos: add the ones the place doesn't have yet (matched by link), keep anything typed in by hand.
			$have = (string) get_post_meta( $existing->ID, 'ltef_more_videos', true );
			// Lines already there get their YouTube copy put after the TikTok link, so YouTube plays first.
			foreach ( $p['more'] ?? array() as $v ) {
				if ( ! empty( $v['youtube'] ) && false === strpos( $have, $v['youtube'] ) && preg_match( '/' . preg_quote( $v['url'], '/' ) . '(?=[\s|]|$)/', $have ) ) {
					$have = preg_replace( '/' . preg_quote( $v['url'], '/' ) . '(?=[\s|]|$)/', $v['url'] . ' ' . $v['youtube'], $have, 1 );
					update_post_meta( $existing->ID, 'ltef_more_videos', $have );
					++$y;
				}
			}
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
			$meta['youtube']      = $p['youtube'] ?? '';
			$y                   += empty( $p['youtube'] ) ? 0 : 1;
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
	// Weekly deals from her posts and from the places themselves. A deal the importer added is kept in step
	// with the data; once someone edits it by hand in the admin, it's left as they made it.
	$sp = 0;
	$su = 0;
	foreach ( $data['specials'] ?? array() as $d ) {
		$place = isset( $ids[ $d['place'] ] ) ? $ids[ $d['place'] ] : ( get_page_by_path( $d['place'], OBJECT, 'ltef_place' ) ? get_page_by_path( $d['place'], OBJECT, 'ltef_place' )->ID : 0 );
		if ( ! $place ) {
			continue;
		}
		$want = array(
			'deal'       => $d['deal'],
			'details'    => $d['details'] ?? '',
			'days'       => array_values( $d['days'] ),
			'source'     => $d['source'] ?? 'tina',
			'source_url' => $d['url'] ?? '',
			'checked'    => $d['checked'] ?? '',
		);
		$old = get_page_by_path( $d['id'], OBJECT, 'ltef_special' );
		if ( $old ) {
			$stored = get_post_meta( $old->ID, 'ltef_import_hash', true );
			$now    = md5( wp_json_encode( ltef_special_import_fields( $old->ID ) ) );
			if ( ( $stored && $stored !== $now ) || md5( wp_json_encode( $want ) ) === $now ) {
				continue; // edited by hand, or already up to date
			}
			$sid = $old->ID;
			wp_update_post( array( 'ID' => $sid, 'post_title' => get_the_title( $place ) . ': ' . $d['deal'] ) );
			++$su;
		} else {
			$sid = wp_insert_post( array( 'post_type' => 'ltef_special', 'post_status' => 'publish', 'post_title' => get_the_title( $place ) . ': ' . $d['deal'], 'post_name' => $d['id'] ) );
			if ( ! $sid || is_wp_error( $sid ) ) {
				continue;
			}
			++$sp;
		}
		update_post_meta( $sid, 'ltef_place', $place );
		update_post_meta( $sid, 'ltef_deal', $want['deal'] );
		update_post_meta( $sid, 'ltef_details', $want['details'] );
		foreach ( ltef_weekdays() as $day => $label ) {
			if ( in_array( $day, $want['days'], true ) ) {
				update_post_meta( $sid, 'ltef_day_' . $day, true );
			} else {
				delete_post_meta( $sid, 'ltef_day_' . $day );
			}
		}
		update_post_meta( $sid, 'ltef_source', $want['source'] );
		update_post_meta( $sid, 'ltef_source_url', $want['source_url'] );
		update_post_meta( $sid, 'ltef_checked', $want['checked'] );
		update_post_meta( $sid, 'ltef_import_hash', md5( wp_json_encode( ltef_special_import_fields( $sid ) ) ) );
	}
	// Tina's partner codes (Lensmart and the rest): added once, then left alone so edits and deletions stick.
	$pc = 0;
	foreach ( $data['partners'] ?? array() as $w ) {
		if ( get_page_by_path( $w['id'], OBJECT, 'ltef_woopon' ) ) {
			continue;
		}
		$wid = wp_insert_post( array( 'post_type' => 'ltef_woopon', 'post_status' => 'publish', 'post_title' => $w['off'], 'post_name' => $w['id'] ) );
		if ( is_wp_error( $wid ) || ! $wid ) {
			continue;
		}
		$meta = array( 'kind' => 'online', 'tier' => 'partner', 'start' => '', 'end' => '' ) + array_diff_key( $w, array_flip( array( 'id', 'off' ) ) );
		foreach ( $meta as $k => $v ) {
			update_post_meta( $wid, 'ltef_' . $k, $v );
		}
		++$pc;
	}
	// The Daily deals page gets the week list if it doesn't have it yet.
	$deals_page = get_page_by_path( 'daily-deals' );
	if ( $deals_page && false === strpos( $deals_page->post_content, 'wp:ltef/daily-deals' ) ) {
		$week    = '<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"vinyl","layout":{"type":"constrained","contentSize":"1240px"}} --><section class="wp-block-group is-style-sheet has-vinyl-background-color has-background"><!-- wp:ltef/daily-deals /--></section><!-- /wp:group -->';
		$content = $deals_page->post_content;
		$at      = strpos( $content, '<!-- /wp:group -->' );
		$content = false === $at ? $week . $content : substr( $content, 0, $at + 18 ) . "\n\n" . $week . substr( $content, $at + 18 );
		wp_update_post( array( 'ID' => $deals_page->ID, 'post_content' => wp_slash( $content ) ) );
	}
	return sprintf( '%d places, %d guides and %d round-ups imported, and %d more videos added.', $n, $g, $r, $m ) . ( $sp ? sprintf( ' %d weekly %s added.', $sp, 1 === $sp ? 'deal' : 'deals' ) : '' ) . ( $su ? sprintf( ' %d weekly %s updated.', $su, 1 === $su ? 'deal' : 'deals' ) : '' ) . ( $pc ? sprintf( ' %d partner %s added to Woo-pons.', $pc, 1 === $pc ? 'code' : 'codes' ) : '' ) . ( $t ? sprintf( ' %d places now show Tina’s visit.', $t ) : '' ) . ( $y ? sprintf( ' %d YouTube %s added, and %s first.', $y, 1 === $y ? 'copy' : 'copies', 1 === $y ? 'it plays' : 'they play' ) : '' ) . ( $skipped ? sprintf( ' %d skipped because they match your don’t-work-with list.', $skipped ) : '' );
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
	return 'Samples added: 3 Woo-pons, 3 events, 2 jobs. Each is marked [Sample]; delete them before launch.';
}

/** A weekly deal's imported fields as they stand, to tell an untouched deal from one edited by hand. */
function ltef_special_import_fields( $sid ) {
	$days = array();
	foreach ( array_keys( ltef_weekdays() ) as $day ) {
		if ( get_post_meta( $sid, 'ltef_day_' . $day, true ) ) {
			$days[] = $day;
		}
	}
	return array(
		'deal'       => (string) get_post_meta( $sid, 'ltef_deal', true ),
		'details'    => (string) get_post_meta( $sid, 'ltef_details', true ),
		'days'       => $days,
		'source'     => (string) get_post_meta( $sid, 'ltef_source', true ),
		'source_url' => (string) get_post_meta( $sid, 'ltef_source_url', true ),
		'checked'    => (string) get_post_meta( $sid, 'ltef_checked', true ),
	);
}
