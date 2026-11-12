<?php
/**
 * Small shared helpers: settings, icons, labels, dates.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin settings with defaults (prices decided with Tina, Nov 2, 2026).
 */
function ltef_settings() {
	$defaults = array(
		'notify_email'    => get_option( 'admin_email' ),
		'woopon_weekly'   => 10,
		'woopon_free'     => 2,
		'woopon_vip'      => 100,
		'vip_limit'       => 10,
		'event_premium'   => 50,
		'job_post'        => 100,
		'dish_price'      => 5,
		'enhanced_low'    => 29,
		'enhanced_high'   => 59,
		'enhanced_price'  => 29,
		'dont_work_with'  => '',
		'reply_days'      => 2,
		'turnstile_site'   => '',
		'turnstile_secret' => '',
		'turnstile_login'  => 0,
		'turnstile_ok'     => 0,
	);
	$saved = get_option( 'ltef_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/** A comma-separated list of email addresses, cleaned: "a@b.c, d@e.f". Invalid ones are dropped. */
function ltef_email_list( $raw ) {
	$out = array();
	foreach ( preg_split( '/[\s,;]+/', (string) $raw ) as $e ) {
		$e = sanitize_email( $e );
		if ( is_email( $e ) && ! in_array( strtolower( $e ), array_map( 'strtolower', $out ), true ) ) {
			$out[] = $e;
		}
	}
	return implode( ', ', $out );
}

function ltef_setting( $key ) {
	$s = ltef_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/**
 * Inline SVG icons used by labels and buttons. All decorative (aria-hidden).
 */
function ltef_icon( $name ) {
	$p = array(
		'pen'     => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10z"/><path d="M14 7l3 3"/></g>',
		'mail'    => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linejoin="round" stroke-linecap="round"><rect x="3.5" y="6" width="17" height="12.5" rx="2"/><path d="M4.5 7.5l7.5 6 7.5-6"/></g>',
		'dollar'  => '<path d="M12 3.5v17M16 7.8c-.6-1.4-2.2-2.4-4-2.4-2.2 0-4 1.2-4 3s1.8 2.5 4 2.9 4 1.4 4 3.2-1.8 2.8-4 2.8c-1.9 0-3.5-1-4.1-2.5" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>',
		'plus'    => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8.5v7M8.5 12h7"/></g>',
		'pin'     => '<g fill="none" stroke="currentColor" stroke-width="2.1"><path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.3"/></g>',
		'ticket'  => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linejoin="round"><path d="M3.5 7h17v3a2 2 0 0 0 0 4v3h-17v-3a2 2 0 0 0 0-4z"/><path d="M14.5 7v10" stroke-dasharray="2 2.4"/></g>',
		'play'    => '<path d="M8 5.5v13l11-6.5z" fill="currentColor"/>',
		'mega'    => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linejoin="round" stroke-linecap="round"><path d="M4 10v4h3l7 4.5v-13L7 10z"/><path d="M17.5 9a4 4 0 0 1 0 6"/></g>',
		'star'    => '<path d="M12 3.6l2.5 5.3 5.8.7-4.3 4 1.1 5.8L12 16.6l-5.1 2.8 1.1-5.8-4.3-4 5.8-.7z" fill="currentColor" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
		'target'  => '<g fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="8.6"/><circle cx="12" cy="12" r="4.8"/></g><circle cx="12" cy="12" r="1.9" fill="currentColor"/>',
		'spin'    => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 3.5v8.5l6 6M3.5 12H12"/></g>',
		'cal'     => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><rect x="4" y="5.5" width="16" height="14.5" rx="2.5"/><path d="M4 10.5h16M8.5 3.5v4M15.5 3.5v4"/></g>',
		'store'   => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 9.5 6 4.5h12l1.5 5"/><path d="M4.5 9.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5c0 1.4 1.1 2.5 2.5 2.5s2.5-1.1 2.5-2.5"/><path d="M6 12.5v7h12v-7M10 19.5v-4.5h4v4.5"/></g>',
		'arrow'   => '<path d="M5 12h13M13 6.5l5.5 5.5-5.5 5.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
	);
	$class = in_array( $name, array( 'star', 'target' ), true ) ? ' class="ltef-verdict__i"' : '';
	return isset( $p[ $name ] ) ? '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"' . $class . '>' . $p[ $name ] . '</svg>' : '';
}

/**
 * A trust or paid label. Paid kinds wear the oat hatch (ltef-lbl--sponsored).
 */
function ltef_label( $kind, $text, $small = true ) {
	$icons = array( 'tina' => 'pen', 'hosted' => 'mail', 'sponsored' => 'dollar', 'partner' => 'dollar', 'enhanced' => 'plus', 'vip' => 'star', 'open' => 'mega', 'own' => 'store' );
	$class = 'vip' === $kind ? 'sponsored' : $kind;
	$icon  = isset( $icons[ $kind ] ) ? ltef_icon( $icons[ $kind ] ) : '';
	return sprintf( '<span class="ltef-lbl ltef-lbl--%s%s">%s%s</span>', esc_attr( $class ), $small ? ' ltef-lbl--sm' : '', $icon, esc_html( $text ) );
}

/** "2026-08" -> "Aug 2026". */
function ltef_month_label( $ym ) {
	if ( ! $ym || ! preg_match( '/^(\d{4})-(\d{2})/', $ym, $m ) ) {
		return '';
	}
	return date_i18n( 'M Y', mktime( 0, 0, 0, (int) $m[2], 1, (int) $m[1] ) );
}

/** Google Maps search link for an address. */
function ltef_maps_url( $name, $address, $town = 'Worcester, MA' ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( trim( $name . ' ' . $address . ' ' . $town ) );
}

/** The numeric id from a TikTok video URL. */
function ltef_tiktok_id( $url ) {
	return preg_match( '#/video/(\d+)#', (string) $url, $m ) ? $m[1] : '';
}

/** The 11-character id from a YouTube link (watch, youtu.be, Shorts or embed). */
function ltef_youtube_id( $url ) {
	return preg_match( '#(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/))([A-Za-z0-9_-]{11})#', (string) $url, $m ) ? $m[1] : '';
}

/** The code from an Instagram post or reel link, with its kind: array( 'reel'|'p', code ). */
function ltef_instagram_ref( $url ) {
	if ( ! preg_match( '#instagram\.com/(?:[A-Za-z0-9_.]+/)?(p|reels?|tv)/([A-Za-z0-9_-]+)#', (string) $url, $m ) ) {
		return array();
	}
	return array( 'p' === $m[1] || 'tv' === $m[1] ? 'p' : 'reel', $m[2] );
}

/** Whether a Facebook link points at one video (reel, /videos/, watch or video.php), the kind its embed player accepts. */
function ltef_facebook_video( $url ) {
	return (bool) preg_match( '#^https?://(?:www\.|m\.|web\.)?facebook\.com/(?:reel/\d+|[^?\#]+/videos/(?:[^/?\#]+/)?\d+|watch/?\?(?:.*&)?v=\d+|video\.php\?(?:.*&)?v=\d+)#', (string) $url );
}

/** Which platform a video link is on: youtube, instagram, facebook, tiktok or ''. */
function ltef_video_platform( $url ) {
	$host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
	$host = preg_replace( '/^(www|m|web|vm)\./', '', $host );
	$map  = array( 'youtube.com' => 'youtube', 'youtu.be' => 'youtube', 'youtube-nocookie.com' => 'youtube', 'instagram.com' => 'instagram', 'facebook.com' => 'facebook', 'fb.watch' => 'facebook', 'tiktok.com' => 'tiktok' );
	return $map[ $host ] ?? '';
}

/** Labels for the video platforms, in the order the site prefers to play them. */
function ltef_video_labels() {
	return array( 'youtube' => 'YouTube', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok' );
}

/**
 * Copies of one video, best first. YouTube's privacy-enhanced player comes first: it is the one schools,
 * offices and privacy tools block least. TikTok comes last: it is the one most often blocked.
 * None of the four needs the visitor to have an account for a public video.
 * Each item: array( 'platform', 'label', 'url', 'embed' ) where embed is '' when the link can't be played on the site.
 */
function ltef_sources_from_urls( $urls ) {
	$labels = ltef_video_labels();
	$order  = apply_filters( 'ltef_video_priority', array_keys( $labels ) );
	$found  = array();
	foreach ( (array) $urls as $url ) {
		$url = trim( (string) $url );
		$pf  = ltef_video_platform( $url );
		if ( ! $url || ! $pf || isset( $found[ $pf ] ) ) {
			continue;
		}
		$embed = '';
		if ( 'youtube' === $pf && ltef_youtube_id( $url ) ) {
			$embed = 'https://www.youtube-nocookie.com/embed/' . ltef_youtube_id( $url ) . '?autoplay=1&rel=0&playsinline=1&cc_load_policy=1';
		} elseif ( 'instagram' === $pf && ltef_instagram_ref( $url ) ) {
			list( $kind, $code ) = ltef_instagram_ref( $url );
			$embed = 'https://www.instagram.com/' . $kind . '/' . $code . '/embed/';
		} elseif ( 'facebook' === $pf && ltef_facebook_video( $url ) ) {
			$embed = 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode( $url ) . '&show_text=false&autoplay=true';
		} elseif ( 'tiktok' === $pf && ltef_tiktok_id( $url ) ) {
			$embed = 'https://www.tiktok.com/player/v1/' . ltef_tiktok_id( $url ) . '?music_info=0&description=0&rel=0&native_context_menu=0&closed_caption=1&autoplay=1';
		}
		$found[ $pf ] = array( 'platform' => $pf, 'label' => $labels[ $pf ], 'url' => $url, 'embed' => $embed );
	}
	$play = array();
	$link = array(); // link-only copies go after every playable one
	foreach ( $order as $pf ) {
		if ( isset( $found[ $pf ] ) ) {
			if ( $found[ $pf ]['embed'] ) {
				$play[] = $found[ $pf ];
			} else {
				$link[] = $found[ $pf ];
			}
		}
	}
	return array_merge( $play, $link );
}

/** Every copy of Tina's main video for a place, best first. */
function ltef_video_sources( $post_id ) {
	$urls = array();
	foreach ( array( 'youtube', 'instagram', 'facebook', 'tina_video' ) as $key ) {
		$urls[] = ltef_meta( $post_id, $key );
	}
	return ltef_sources_from_urls( $urls );
}

/**
 * Tina's other videos from a place, newest first. One per line in the "More videos" field:
 * "2026-08 | link | her caption". Several links for the same video go in one line, separated by spaces.
 */
function ltef_more_videos( $post_id ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $post_id, 'more_videos' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 3 ) );
		if ( count( $parts ) < 2 ) {
			$parts = array( '', $parts[0] ?? '' );
		}
		$urls = preg_split( '/\s+/', $parts[1] );
		if ( ! array_filter( $urls ) ) {
			continue;
		}
		$out[] = array(
			'month'   => preg_match( '/^\d{4}-\d{2}/', $parts[0] ) ? substr( $parts[0], 0, 7 ) : '',
			'urls'    => $urls,
			'caption' => $parts[2] ?? '',
		);
	}
	usort( $out, function ( $a, $b ) { return strcmp( $b['month'], $a['month'] ); } );
	return $out;
}

/** What kind of place: eat (the default), shop, wellness, services, fun or community. */
function ltef_place_kinds() {
	return array( 'eat' => 'Food & drink', 'shop' => 'Shops', 'wellness' => 'Wellness & beauty', 'services' => 'Services', 'fun' => 'Things to do', 'community' => 'Community' );
}

function ltef_place_kind( $post_id ) {
	$k = (string) ltef_meta( $post_id, 'kind' );
	return isset( ltef_place_kinds()[ $k ] ) ? $k : 'eat';
}

/** A business Tina owns: the page says so, and it never gets a verdict or a place in her picks. */
function ltef_tina_owns( $post_id ) {
	return (bool) ltef_meta( $post_id, 'own' );
}

/** The trust label for a place: "Tina's own business", "Tina ate here · Aug 2026", or '' when she hasn't been. */
function ltef_place_trust_label( $post_id, $small = true ) {
	if ( ltef_tina_owns( $post_id ) ) {
		return ltef_label( 'own', 'Tina’s own business', $small );
	}
	if ( ! ltef_tina_ate( $post_id ) ) {
		return '';
	}
	return ltef_label( 'tina', trim( ltef_tina_label( $post_id ) . ' · ' . ltef_month_label( ltef_meta( $post_id, 'tina_date' ) ), ' ·' ), $small );
}

/** "Tina ate here" for food, "Tina went here" for everything else. */
function ltef_tina_label( $post_id ) {
	return 'eat' === ltef_place_kind( $post_id ) ? 'Tina ate here' : 'Tina went here';
}

/** A town for maps and addresses: "Worcester, MA", or the town as given when it already names its state. */
function ltef_town_state( $town ) {
	$town = $town ? $town : 'Worcester';
	return false !== strpos( $town, ',' ) ? $town : $town . ', MA';
}

/** Price shown as money, no cents when whole. */
function ltef_money( $n ) {
	$n = (float) $n;
	return '$' . ( floor( $n ) == $n ? number_format_i18n( $n ) : number_format_i18n( $n, 2 ) );
}

/**
 * [ltef_price key="woopon_weekly"] prints a live price from the settings, so a price
 * changed under Money → Prices & split changes everywhere on the site at once.
 * Counts (free weeks, VIP limit) print as plain numbers.
 */
add_shortcode(
	'ltef_price',
	function ( $atts ) {
		$atts   = shortcode_atts( array( 'key' => '' ), $atts, 'ltef_price' );
		$counts = array( 'woopon_free', 'vip_limit', 'reply_days' );
		$v      = ltef_setting( sanitize_key( $atts['key'] ) );
		if ( null === $v ) {
			return '';
		}
		return esc_html( in_array( $atts['key'], $counts, true ) ? (string) (int) $v : ltef_money( $v ) );
	}
);

/** Templates and template parts don't run shortcodes, so live prices are filled in as each block renders. */
add_filter(
	'render_block',
	function ( $html ) {
		return ( is_string( $html ) && false !== strpos( $html, '[ltef_price' ) ) ? do_shortcode( $html ) : $html;
	}
);

/** Today in the site's timezone, Y-m-d. */
function ltef_today() {
	return wp_date( 'Y-m-d' );
}

/** A post meta value, trimmed. */
function ltef_meta( $post_id, $key ) {
	$v = get_post_meta( $post_id, 'ltef_' . $key, true );
	return is_string( $v ) ? trim( $v ) : $v;
}

/**
 * Names on the private don't-work-with list (Settings). Never printed on the front end.
 */
function ltef_blocklist() {
	$lines = preg_split( '/\r\n|\r|\n/', (string) ltef_setting( 'dont_work_with' ) );
	return array_values( array_filter( array_map( 'trim', $lines ) ) );
}

/** Whether a business name matches the private list (loose, case-insensitive). */
function ltef_on_blocklist( $name ) {
	$name = strtolower( remove_accents( (string) $name ) );
	if ( '' === trim( $name ) ) {
		return false;
	}
	foreach ( ltef_blocklist() as $entry ) {
		$e = strtolower( remove_accents( preg_replace( '/\s*\(.*\)\s*/', '', $entry ) ) );
		$e = trim( preg_replace( "/['’]s\b/", '', $e ) );
		if ( $e && false !== strpos( preg_replace( "/['’]s\b/", '', $name ), $e ) ) {
			return true;
		}
	}
	return false;
}
