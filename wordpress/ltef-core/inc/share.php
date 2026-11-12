<?php
/**
 * Link previews: what a shared link shows in iMessage, Facebook, Instagram DMs, X, Slack and Google.
 *
 * Every page gets a description, Open Graph and X card tags, and a 1200×630 share card drawn for it:
 * the wordmark, the page's name, one line about it and a label (Tina ate here · the month, the kind of event,
 * Woo-pon…), with the place's sticker or a photo of Tina. Cards are drawn once with GD from the fonts and
 * PNGs in assets/cards (built by tools/build-cards.py) and kept in uploads/ltef-cards as JPEGs; a new card is drawn
 * when what's on it changes. If the server can't draw, the ready-made default card is used.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** Bump to redraw every card after a design change. */
const LTEF_CARD_V = 4;

/* ------------------------------------------------------------------ What each page says */

/** Short text from HTML: tags gone, spaces tidied, cut at a word with an ellipsis. */
function ltef_share_text( $html, $max = 200 ) {
	$t = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $html ) ), ENT_QUOTES, 'UTF-8' ) ) );
	if ( mb_strlen( $t ) <= $max ) {
		return $t;
	}
	$cut = mb_substr( $t, 0, $max - 1 );
	$cut = preg_replace( '/\s+\S*$/u', '', $cut );
	return rtrim( $cut, " ,;:–—" ) . '…';
}

/** The first lede paragraph of a page's content, or its first paragraph. */
function ltef_share_lede( $content ) {
	if ( preg_match( '#<p[^>]*class="[^"]*is-style-lede[^"]*"[^>]*>(.*?)</p>#s', (string) $content, $m ) ) {
		return $m[1];
	}
	if ( preg_match( '#<p[^>]*>(.*?)</p>#s', (string) $content, $m ) ) {
		return $m[1];
	}
	return '';
}

/** Tina's photos for pages, by page, so each page keeps the same one. */
function ltef_share_photo( $seed ) {
	$photos = array( 'tina-coconut-half', 'tina-sushi', 'tina-cheers', 'tina-coconut' );
	return 'tina/' . $photos[ abs( crc32( (string) $seed ) ) % count( $photos ) ] . '-1200.webp';
}

/**
 * What the current request shares: [ title, description, type, card ], where card is
 * [ title, sub, kicker, kicker style (tina|own|kind-<key>|plain|''), art (sticker:<name> | photo:<theme img path> | att:<id>) ].
 */
function ltef_share_data() {
	$site  = 'Live to Eat Food';
	$home  = 'Ten years of Worcester and Central Mass meals Tina really ate. Tell us what you’re craving and we’ll show you where she’d go.';
	$title = wp_get_document_title();
	$desc  = $home;
	$type  = 'website';
	$card  = array( 'Tina Vo’s food guide to Worcester and Central Mass', 'Meals Tina really ate, Woo-pons, daily deals and events.', '', '', 'photo:tina/tina-coconut-half-1200.webp' );

	if ( is_front_page() ) {
		$title = $site . ': Tina Vo’s food guide to Worcester and Central Mass';
		return array( $title, $desc, $type, $card );
	}
	if ( is_singular() ) {
		$id   = get_queried_object_id();
		$post = get_post( $id );
		$name = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
		$title = $name;
		switch ( $post->post_type ) {
			case 'ltef_place':
				$area  = function_exists( 'ltef_place_area_name' ) ? ltef_place_area_name( $id ) : '';
				$town  = (string) ltef_meta( $id, 'town' );
				$where = $area ? $area : ( $town ? $town : 'Worcester' );
				$sub   = implode( ' · ', array_filter( array( (string) ltef_meta( $id, 'cuisine' ), $where ) ) );
				$kick  = '';
				$style = '';
				if ( ltef_tina_owns( $id ) ) {
					$kick  = 'Tina’s own business';
					$style = 'own';
				} elseif ( function_exists( 'ltef_tina_ate' ) && ltef_tina_ate( $id ) ) {
					$kick  = trim( ltef_tina_label( $id ) . ' · ' . ltef_month_label( ltef_meta( $id, 'tina_date' ) ), ' ·' );
					$style = 'tina';
				}
				$dish  = (string) ltef_meta( $id, 'dish' );
				$quote = (string) ltef_meta( $id, 'tina_quote' );
				$bits  = array();
				if ( $dish ) {
					$bits[] = 'What Tina ordered: ' . $dish . '.';
				}
				if ( $quote ) {
					$bits[] = '“' . ltef_share_text( $quote, 120 ) . '”';
				}
				$bits[] = $sub ? $sub . '.' : '';
				$bits[] = 'Hours, address and directions on Live to Eat Food.';
				$desc   = ltef_share_text( implode( ' ', array_filter( $bits ) ), 240 );
				$gal    = function_exists( 'ltef_owner_gallery' ) && ltef_meta( $id, 'enhanced' ) ? ltef_owner_gallery( $id ) : array();
				$art    = $gal ? 'att:' . (int) $gal[0] : 'sticker:' . ltef_place_sticker( $id );
				$card   = array( $name, $sub, $kick, $style, $art );
				break;
			case 'ltef_event':
				$date  = (string) ltef_meta( $id, 'date' );
				$ts    = $date ? strtotime( $date ) : 0;
				$when  = trim( ( $ts ? date_i18n( 'D, M j', $ts ) : '' ) . ( ltef_meta( $id, 'time' ) ? ' · ' . ltef_meta( $id, 'time' ) : '' ), ' ·' );
				$sub   = implode( ' · ', array_filter( array( $when, (string) ltef_meta( $id, 'venue' ) ) ) );
				$kind  = function_exists( 'ltef_event_kind' ) ? ltef_event_kind( $id ) : 'other';
				$about = has_excerpt( $id ) ? get_the_excerpt( $id ) : $post->post_content;
				$price = function_exists( 'ltef_event_price' ) ? ltef_event_price( $id ) : '';
				$desc  = ltef_share_text( implode( ' · ', array_filter( array( $ts ? date_i18n( 'l, F j', $ts ) : '', (string) ltef_meta( $id, 'time' ), (string) ltef_meta( $id, 'venue' ), $price ) ) ) . '. ' . ltef_share_text( $about, 160 ), 240 );
				$stick = array( 'fitness' => 'leaf', 'market' => 'bag', 'retail' => 'bag', 'family' => 'icecream', 'nonprofit' => 'heart', 'food' => 'donut' )[ $kind ] ?? 'ticket';
				$card  = array( $name, $sub, ltef_field_label( 'ltef_event', 'kind', $kind ), 'kind-' . $kind, 'sticker:' . $stick );
				break;
			case 'ltef_woopon':
				$biz  = (string) ltef_meta( $id, 'business' );
				$end  = (string) ltef_meta( $id, 'end' );
				$sub  = implode( ' · ', array_filter( array( $biz, $end ? 'Through ' . date_i18n( 'M j', strtotime( $end ) ) : '' ) ) );
				$desc = ltef_share_text( $name . ( $biz ? ' at ' . $biz : '' ) . '. A Woo-pon on Live to Eat Food' . ( $end ? ', good through ' . date_i18n( 'F j', strtotime( $end ) ) : '' ) . '. Show the ticket when you pay.', 240 );
				$card = array( $name, $sub, 'Woo-pon', 'plain', 'sticker:woopon' );
				break;
			case 'ltef_job':
				$biz  = (string) ltef_meta( $id, 'business' );
				$sub  = implode( ' · ', array_filter( array( $biz, (string) ltef_meta( $id, 'area_text' ), (string) ltef_meta( $id, 'pay' ) ) ) );
				$desc = ltef_share_text( $name . ( $biz ? ' at ' . $biz : '' ) . '. ' . ( ltef_meta( $id, 'how_to_apply' ) ? 'How to apply: ' . ltef_meta( $id, 'how_to_apply' ) : 'A restaurant job around Worcester.' ), 240 );
				$card = array( $name, $sub, 'Restaurant job', 'plain', 'sticker:lemon' );
				break;
			default:
				$lede = has_excerpt( $id ) ? get_the_excerpt( $id ) : ltef_share_lede( $post->post_content );
				$desc = ltef_share_text( $lede ? $lede : $post->post_content, 220 );
				$desc = $desc ? $desc : $home;
				$type = 'post' === $post->post_type ? 'article' : 'website';
				$art  = has_post_thumbnail( $id ) ? 'att:' . (int) get_post_thumbnail_id( $id ) : 'photo:' . ltef_share_photo( $id );
				$card = array( $name, ltef_share_text( $desc, 120 ), '', '', $art );
		}
		return array( $title, $desc, $type, $card );
	}
	$archives = array(
		'ltef_place'  => array( 'Explore', 'Every place to eat on the site: the ones Tina ate at, newest first, then the ones she hasn’t been to yet. Search what she ordered, pick a craving or a neighborhood.', 'sticker:pizza' ),
		'ltef_event'  => array( 'Events', 'Food nights, markets, music, art walks, classes and Live to Eat meetups in Worcester and beyond.', 'sticker:ticket' ),
		'ltef_woopon' => array( 'Woo-pons', 'Local deals around Worcester, sent in by the businesses themselves. Some are at places Tina loves, some at places she hasn’t tried yet, and every ticket says which.', 'sticker:woopon' ),
		'ltef_job'    => array( 'Restaurant jobs', 'Hosts, servers, cooks and managers wanted around Worcester and Central Mass. Posted by the businesses themselves.', 'sticker:lemon' ),
	);
	foreach ( $archives as $pt => $a ) {
		if ( is_post_type_archive( $pt ) ) {
			return array( $a[0] . ' – ' . $site, $a[1], $type, array( $a[0], ltef_share_text( $a[1], 120 ), '', '', $a[2] ) );
		}
	}
	if ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		$name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
		$what = array( 'ltef_area' => 'Places to eat in ' . $name . ', from Tina’s videos and the rest of Worcester.', 'ltef_craving' => 'Where to get ' . strtolower( $name ) . ' around Worcester, from the places Tina ate at and more.' )[ $term->taxonomy ] ?? '';
		$desc = $term->description ? ltef_share_text( $term->description, 220 ) : ( $what ? $what : $desc );
		return array( $title, $desc, $type, array( $name, ltef_share_text( $desc, 120 ), '', '', 'sticker:lemon' ) );
	}
	return array( $title, $desc, $type, $card );
}

/** Explore and the jobs list are named for what they are, not "Places" and "Jobs". */
add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( is_post_type_archive( 'ltef_place' ) ) {
			$parts['title'] = 'Explore';
		} elseif ( is_post_type_archive( 'ltef_job' ) ) {
			$parts['title'] = 'Restaurant jobs';
		}
		return $parts;
	}
);

/* ------------------------------------------------------------------ The tags */

add_action( 'wp_head', 'ltef_share_tags', 4 );
function ltef_share_tags() {
	if ( is_404() || is_search() || is_feed() ) {
		return;
	}
	list( $title, $desc, $type, $card ) = ltef_share_data();
	if ( false === strpos( $title, 'Live to Eat Food' ) ) {
		$title .= ' – Live to Eat Food';
	}
	$url = is_singular() ? get_permalink( get_queried_object_id() ) : ( is_front_page() ? home_url( '/' ) : home_url( add_query_arg( array(), wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$url = strtok( $url, '?' ); // shared links point at the page, not at filters or tracking bits
	$img = ltef_card_url( $card, is_singular() ? get_post_type() . '-' . get_queried_object_id() : 'p-' . md5( $url ) );
	$alt = $card[0] . ( $card[1] ? ' · ' . $card[1] : '' ) . ' (Live to Eat Food)';
	$tags = array(
		array( 'name', 'description', $desc ),
		array( 'property', 'og:site_name', 'Live to Eat Food' ),
		array( 'property', 'og:locale', 'en_US' ),
		array( 'property', 'og:type', $type ),
		array( 'property', 'og:title', $title ),
		array( 'property', 'og:description', $desc ),
		array( 'property', 'og:url', $url ),
		array( 'property', 'og:image', $img ),
		array( 'property', 'og:image:secure_url', $img ),
		array( 'property', 'og:image:type', 'image/jpeg' ),
		array( 'property', 'og:image:width', '1200' ),
		array( 'property', 'og:image:height', '630' ),
		array( 'property', 'og:image:alt', $alt ),
		array( 'name', 'twitter:card', 'summary_large_image' ),
		array( 'name', 'twitter:title', $title ),
		array( 'name', 'twitter:description', $desc ),
		array( 'name', 'twitter:image', $img ),
		array( 'name', 'twitter:image:alt', $alt ),
	);
	if ( 'article' === $type ) {
		$tags[] = array( 'property', 'article:published_time', get_the_date( 'c' ) );
		$tags[] = array( 'property', 'article:modified_time', get_the_modified_date( 'c' ) );
	}
	echo "\n";
	foreach ( $tags as $t ) {
		printf( '<meta %s="%s" content="%s">' . "\n", $t[0], esc_attr( $t[1] ), esc_attr( $t[2] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attribute names are fixed
	}
	if ( ! is_singular() && ! is_front_page() ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; // WordPress only adds one on single pages
	}
}

/* ------------------------------------------------------------------ The card */

/** The card's address, drawn now if it isn't on disk yet. Falls back to the default card. */
function ltef_card_url( $card, $slot ) {
	$fallback = LTEF_URL . 'assets/cards/default.jpg';
	$up       = wp_upload_dir();
	if ( ! empty( $up['error'] ) ) {
		return $fallback;
	}
	$dir  = trailingslashit( $up['basedir'] ) . 'ltef-cards';
	$slot = sanitize_file_name( $slot );
	$file = $slot . '-' . substr( md5( wp_json_encode( $card ) . LTEF_CARD_V ), 0, 12 ) . '.jpg';
	if ( ! file_exists( $dir . '/' . $file ) ) {
		if ( ! wp_mkdir_p( $dir ) || ! ltef_card_draw( $card, $dir . '/' . $file ) ) {
			return $fallback;
		}
		foreach ( (array) glob( $dir . '/' . $slot . '-*.jpg' ) as $old ) { // the old card for this page
			if ( $old && basename( $old ) !== $file ) {
				wp_delete_file( $old );
			}
		}
	}
	return set_url_scheme( trailingslashit( $up['baseurl'] ) . 'ltef-cards/' . $file, 'https' );
}

/** Colors from the brand, as GD wants them. */
function ltef_card_rgb( $im, $hex, $alpha = 0 ) {
	$hex = ltrim( $hex, '#' );
	return imagecolorallocatealpha( $im, hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ), $alpha );
}

/** A filled rounded rectangle. */
function ltef_card_round( $im, $x1, $y1, $x2, $y2, $r, $col ) {
	imagefilledrectangle( $im, $x1 + $r, $y1, $x2 - $r, $y2, $col );
	imagefilledrectangle( $im, $x1, $y1 + $r, $x2, $y2 - $r, $col );
	foreach ( array( array( $x1 + $r, $y1 + $r ), array( $x2 - $r, $y1 + $r ), array( $x1 + $r, $y2 - $r ), array( $x2 - $r, $y2 - $r ) ) as $c ) {
		imagefilledellipse( $im, $c[0], $c[1], $r * 2, $r * 2, $col );
	}
}

/** Words wrapped to a width: the lines, at the largest size from $sizes that fits in $max lines. */
function ltef_card_wrap( $text, $font, $sizes, $width, $max ) {
	foreach ( $sizes as $size ) {
		$lines = array();
		$line  = '';
		foreach ( preg_split( '/\s+/u', trim( $text ) ) as $w ) {
			$try = '' === $line ? $w : $line . ' ' . $w;
			$box = imagettfbbox( $size, 0, $font, $try );
			if ( $box[2] - $box[0] > $width && '' !== $line ) {
				$lines[] = $line;
				$line    = $w;
			} else {
				$line = $try;
			}
		}
		$lines[] = $line;
		if ( count( $lines ) <= $max ) {
			return array( $size, $lines );
		}
	}
	$lines            = array_slice( $lines, 0, $max );
	$lines[ $max - 1 ] = rtrim( $lines[ $max - 1 ], ' ,.;:' ) . '…';
	return array( $size, $lines );
}

/** Draws a card to a JPEG file. Twice the size first, then halved, so edges and type come out smooth. */
function ltef_card_draw( $card, $path ) {
	if ( ! function_exists( 'imagettftext' ) || ! function_exists( 'imagecreatetruecolor' ) ) {
		return false;
	}
	list( $title, $sub, $kick, $style, $art ) = array_pad( (array) $card, 5, '' );
	$a  = LTEF_DIR . 'assets/cards/';
	$fb = $a . 'fonts/bricolage-800.ttf';
	$fr = $a . 'fonts/figtree-500.ttf';
	$fx = $a . 'fonts/figtree-700.ttf';
	$fh = $a . 'fonts/shantell-700.ttf';
	$W  = 2400;
	$H  = 1260;
	$im = imagecreatetruecolor( $W, $H );
	imagesavealpha( $im, true );
	imagealphablending( $im, true );
	$basil  = ltef_card_rgb( $im, '1e3b2a' );
	$olive  = ltef_card_rgb( $im, '46583e' );
	$tomato = ltef_card_rgb( $im, 'b3311a' );
	$white  = ltef_card_rgb( $im, 'ffffff' );
	imagefilledrectangle( $im, 0, 0, $W, $H, ltef_card_rgb( $im, 'ffe586' ) );
	ltef_card_round( $im, 44, 44, $W - 44, $H - 44, 76, ltef_card_rgb( $im, 'fff6cf' ) );

	// the art on the right: a photo in a rounded frame, or a sticker on a white disc
	$right = 1540;
	if ( 0 === strpos( $art, 'photo:' ) || 0 === strpos( $art, 'att:' ) ) {
		$src = 0 === strpos( $art, 'att:' ) ? get_attached_file( (int) substr( $art, 4 ) ) : get_theme_file_path( 'assets/img/' . substr( $art, 6 ) );
		$ph  = $src && file_exists( $src ) ? ltef_card_load( $src ) : null;
		if ( $ph ) {
			$fw = 700;
			$fh2 = 1000;
			$fx2 = $W - 120 - $fw;
			$fy  = ( $H - $fh2 ) / 2;
			$frame = imagecreatetruecolor( $fw, $fh2 );
			$sw = imagesx( $ph );
			$sh = imagesy( $ph );
			$scale = max( $fw / $sw, $fh2 / $sh );
			$cw = (int) ( $fw / $scale );
			$ch = (int) ( $fh2 / $scale );
			imagecopyresampled( $frame, $ph, 0, 0, (int) ( ( $sw - $cw ) / 2 ), (int) ( ( $sh - $ch ) / 3 ), $fw, $fh2, $cw, $ch );
			ltef_card_round( $im, $fx2 - 14, $fy - 14, $fx2 + $fw + 14, $fy + $fh2 + 14, 64, $white );
			ltef_card_round_copy( $im, $frame, $fx2, $fy, 52 );
			$right = $fx2 - 90;
		}
	} elseif ( 0 === strpos( $art, 'sticker:' ) ) {
		$file = $a . 'stickers/' . sanitize_file_name( substr( $art, 8 ) ) . '.png';
		$file = file_exists( $file ) ? $file : $a . 'stickers/lemon.png';
		$st   = imagecreatefrompng( $file );
		if ( $st ) {
			$cx = $W - 470;
			$cy = $H / 2;
			imagefilledellipse( $im, $cx, $cy, 760, 760, $white );
			$s  = 560;
			$sw = imagesx( $st );
			$sh = imagesy( $st );
			$k  = min( $s / $sw, $s / $sh );
			imagecopyresampled( $im, $st, (int) ( $cx - $sw * $k / 2 ), (int) ( $cy - $sh * $k / 2 ), 0, 0, (int) ( $sw * $k ), (int) ( $sh * $k ), $sw, $sh );
			$right = $cx - 380 - 90;
		}
	}
	$x     = 140;
	$width = $right - $x;

	// the wordmark
	$wm = imagecreatefrompng( $a . 'wordmark.png' );
	if ( $wm ) {
		$k = 640 / imagesx( $wm );
		imagecopyresampled( $im, $wm, $x, 130, 0, 0, 640, (int) ( imagesy( $wm ) * $k ), imagesx( $wm ), imagesy( $wm ) );
	}

	// the title, the line under it and the label, set from the bottom of the space up
	$sub_lines = $sub ? ltef_card_wrap( $sub, $fr, array( 52, 46 ), $width, 2 ) : array( 0, array() );
	$rest      = ( $sub_lines[1] ? 40 + count( $sub_lines[1] ) * (int) round( $sub_lines[0] * 1.35 ) : 0 ) + ( $kick ? 140 : 0 );
	foreach ( array( 150, 136, 122, 108, 96, 86 ) as $try ) { // the biggest title that leaves room above the address line
		list( $ts, $tl ) = ltef_card_wrap( $title, $fb, array( $try, 86 ), $width, 3 );
		$lh              = (int) round( $ts * 1.15 );
		$block           = count( $tl ) * $lh + $rest;
		if ( $block <= 640 ) {
			break;
		}
	}
	$y = max( 320, (int) ( 320 + ( 980 - 320 - $block ) / 2 ) );

	if ( $kick ) {
		$kf  = 'tina' === $style ? $fh : $fx;
		$ks  = 44;
		$box = imagettfbbox( $ks, 0, $kf, $kick );
		$kw  = $box[2] - $box[0];
		$dot = 0 === strpos( $style, 'kind-' );
		$pw  = $kw + 76 + ( $dot ? 40 : 0 );
		$kinds = array( 'food' => array( 'ffd8c2', 'd9693a' ), 'market' => array( 'ffe586', 'c99a00' ), 'music' => array( 'e4d8f7', '7656b3' ), 'art' => array( 'f9d3e3', 'bf4a7e' ), 'crafts' => array( 'e9e4b4', '857d14' ), 'fitness' => array( 'cdeab4', '3d8a37' ), 'retail' => array( 'cfe3f6', '3a74b0' ), 'family' => array( 'd3efec', '2a8a80' ), 'nonprofit' => array( 'f6cfcb', 'b8322b' ), 'other' => array( 'efeadb', '8a8170' ) );
		if ( 'tina' === $style ) {
			ltef_card_round( $im, $x, $y, $x + $pw, $y + 88, 44, $tomato );
			ltef_card_round( $im, $x + 6, $y + 6, $x + $pw - 6, $y + 82, 38, $white );
			$kc = $tomato;
		} elseif ( $dot ) {
			$c = $kinds[ substr( $style, 5 ) ] ?? $kinds['other'];
			ltef_card_round( $im, $x, $y, $x + $pw, $y + 88, 44, ltef_card_rgb( $im, $c[0] ) );
			imagefilledellipse( $im, $x + 50, $y + 44, 26, 26, ltef_card_rgb( $im, $c[1] ) );
			$kc = $basil;
		} elseif ( 'own' === $style ) {
			ltef_card_round( $im, $x, $y, $x + $pw, $y + 88, 44, ltef_card_rgb( $im, 'efeadb' ) );
			$kc = $basil;
		} else {
			ltef_card_round( $im, $x, $y, $x + $pw, $y + 88, 44, $basil );
			$kc = ltef_card_rgb( $im, 'fff6cf' );
		}
		imagettftext( $im, $ks, 0, $x + 38 + ( $dot ? 40 : 0 ) - $box[0], $y + 44 + (int) ( ( -$box[5] ) / 2 ) - 4, $kc, $kf, $kick );
		$y += 140;
	}
	foreach ( $tl as $line ) {
		$y += $lh;
		imagettftext( $im, $ts, 0, $x, $y - (int) ( $lh * 0.2 ), $basil, $fb, $line );
	}
	if ( $sub_lines[1] ) {
		$y  += 40;
		$slh = (int) round( $sub_lines[0] * 1.35 );
		foreach ( $sub_lines[1] as $line ) {
			$y += $slh;
			imagettftext( $im, $sub_lines[0], 0, $x, $y - (int) ( $slh * 0.25 ), $olive, $fr, $line );
		}
	}
	imagettftext( $im, 40, 0, $x, $H - 120, $basil, $fx, 'livetoeatfoodie.com' );

	$out = imagecreatetruecolor( 1200, 630 );
	imagecopyresampled( $out, $im, 0, 0, 0, 0, 1200, 630, $W, $H );
	$ok = imagejpeg( $out, $path, 86 ); // JPEG keeps cards small enough for WhatsApp's 300 KB limit
	imagedestroy( $im );
	imagedestroy( $out );
	return $ok;
}

/** An image file GD can read: JPEG, PNG or WebP. */
function ltef_card_load( $file ) {
	$t = wp_check_filetype( $file );
	switch ( $t['type'] ) {
		case 'image/jpeg':
			return imagecreatefromjpeg( $file );
		case 'image/png':
			return imagecreatefrompng( $file );
		case 'image/webp':
			return function_exists( 'imagecreatefromwebp' ) ? imagecreatefromwebp( $file ) : null;
	}
	return null;
}

/** Copies an image onto the card with rounded corners: the middle in three strips, the corners pixel by pixel. */
function ltef_card_round_copy( $dst, $src, $dx, $dy, $r ) {
	$w  = imagesx( $src );
	$h  = imagesy( $src );
	$dx = (int) $dx;
	$dy = (int) $dy;
	imagecopy( $dst, $src, $dx + $r, $dy, $r, 0, $w - 2 * $r, $h );
	imagecopy( $dst, $src, $dx, $dy + $r, 0, $r, $r, $h - 2 * $r );
	imagecopy( $dst, $src, $dx + $w - $r, $dy + $r, $w - $r, $r, $r, $h - 2 * $r );
	foreach ( array( array( 0, 0, $r, $r ), array( $w - $r, 0, $w - $r - 1, $r ), array( 0, $h - $r, $r, $h - $r - 1 ), array( $w - $r, $h - $r, $w - $r - 1, $h - $r - 1 ) ) as $c ) {
		for ( $y = $c[1]; $y < $c[1] + $r; $y++ ) {
			for ( $x = $c[0]; $x < $c[0] + $r; $x++ ) {
				if ( ( $x - $c[2] ) ** 2 + ( $y - $c[3] ) ** 2 <= $r * $r ) {
					imagesetpixel( $dst, $dx + $x, $dy + $y, imagecolorat( $src, $x, $y ) );
				}
			}
		}
	}
}
