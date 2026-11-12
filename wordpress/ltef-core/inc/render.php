<?php
/**
 * HTML for places, Woo-pons, events, jobs and ad spots, in the design system's classes
 * (ltef-card, ltef-lbl, ltef-coupon, ltef-slot…). The theme's stylesheet styles them.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** URL of a food sticker in the theme (falls back to the plugin copy). */
function ltef_sticker_url( $name ) {
	$rel = 'assets/img/stickers/' . $name . '.svg';
	if ( file_exists( get_theme_file_path( $rel ) ) ) {
		return get_theme_file_uri( $rel );
	}
	return LTEF_URL . 'assets/stickers/' . $name . '.svg';
}

/** The sticker for a place: what Tina ordered decides first, then the kind of place. */
function ltef_place_sticker( $post_id ) {
	$own = ltef_meta( $post_id, 'sticker' );
	if ( $own ) {
		return $own;
	}
	$by_place_kind = array( 'shop' => 'bag', 'wellness' => 'leaf', 'beauty' => 'flower', 'services' => 'camera', 'fun' => 'ticket', 'community' => 'heart' );
	if ( 'eat' !== ltef_place_kind( $post_id ) ) {
		return $by_place_kind[ ltef_place_kind( $post_id ) ] ?? 'lemon';
	}
	$dish = strtolower( (string) ltef_meta( $post_id, 'dish' ) );
	$cui  = strtolower( (string) ltef_meta( $post_id, 'cuisine' ) );
	$by_dish = array( 'burger' => 'burger', 'beer' => 'beer', 'pasta' => 'pasta', 'carrot' => 'carrot', 'curr' => 'curry', 'haddock' => 'fish', 'oyster' => 'oyster', 'lobster' => 'oyster', 'taco' => 'taco', 'soup dumpling' => 'dumpling', 'wings' => 'drumstick', 'matcha' => 'matcha', 'acai' => 'acai', 'crêpe' => 'croissant', 'croffle' => 'croissant', 'pastr' => 'croissant', 'bagel' => 'bagel', 'banh mi' => 'banhmi', 'hot dog' => 'hotdog', 'sushi' => 'sushi', 'nigiri' => 'sushi', 'salmon' => 'sushi', 'poké' => 'sushi', 'pizza' => 'pizza', 'flatbread' => 'pizza-pie', 'ramen' => 'ramen', 'pho' => 'noodles', 'udon' => 'noodles', 'noodle' => 'noodles', 'stir-fry' => 'noodles', 'grill' => 'skewer', 'meats' => 'skewer', 'buffet' => 'dumpling' );
	$by_kind = array( 'brewery' => 'beer', 'gastropub' => 'beer', 'pub' => 'burger', 'american' => 'burger', 'italian' => 'pasta', 'indian' => 'curry', 'steakhouse' => 'skewer', 'hibachi' => 'skewer', 'brazilian' => 'skewer', 'mongolian' => 'noodles', 'vietnamese' => 'noodles', 'izakaya' => 'ramen', 'sushi' => 'sushi', 'thai' => 'chili', 'korean' => 'chili', 'mexican' => 'taco', 'coffee' => 'coffee', 'bakery' => 'croissant', 'seafood' => 'oyster', 'diner' => 'egg', 'cuban' => 'avocado', 'tapas' => 'avocado', 'peruvian' => 'avocado', 'mediterranean' => 'avocado', 'fruit' => 'acai' );
	foreach ( array( array( $dish, $by_dish ), array( $cui, $by_kind ) ) as $pair ) {
		foreach ( $pair[1] as $k => $v ) {
			if ( '' !== $pair[0] && false !== strpos( $pair[0], $k ) ) {
				return $v;
			}
		}
	}
	return 'donut';
}

/** A dish as it reads mid-sentence: "Wood-fired pizza" -> "wood-fired pizza"; "Korean fried chicken" stays. */
function ltef_in_sentence( $t ) {
	$t     = trim( (string) $t );
	$first = strtok( $t, ' ' );
	$proper = array( 'Korean', 'Thai', 'Italian', 'Peruvian', 'Cuban', 'Spanish', 'Vietnamese', 'Brazilian', 'Mongolian', 'Neapolitan', 'Indian', 'Mexican', 'Japanese', 'Chinese', 'Brussels' );
	if ( '' === $t || in_array( rtrim( $first, ',' ), $proper, true ) || strtoupper( $first ) === $first || ( strlen( $first ) > 1 && strtolower( substr( $first, 1 ) ) !== substr( $first, 1 ) ) ) {
		return $t;
	}
	return strtolower( substr( $t, 0, 1 ) ) . substr( $t, 1 );
}

/** A pastel field for a place, stable per slug. */
function ltef_place_field( $post_id ) {
	$fields = array( 'pistachio', 'peach', 'lemon', 'butter' );
	$slug   = get_post_field( 'post_name', $post_id );
	return $fields[ array_sum( array_map( 'ord', str_split( (string) $slug ) ) ) % 4 ];
}

function ltef_tina_ate( $post_id ) {
	return '' !== (string) ltef_meta( $post_id, 'tina_video' ) || '' !== (string) ltef_meta( $post_id, 'tina_date' );
}

function ltef_place_area_name( $post_id ) {
	$t = get_the_terms( $post_id, 'ltef_area' );
	return ( $t && ! is_wp_error( $t ) ) ? $t[0]->name : '';
}

function ltef_place_meta_line( $post_id, $with_address = true ) {
	$bits = array_filter(
		array(
			ltef_meta( $post_id, 'cuisine' ),
			$with_address ? ltef_meta( $post_id, 'address' ) : '',
			ltef_meta( $post_id, 'town' ) ? ltef_meta( $post_id, 'town' ) : ltef_place_area_name( $post_id ), // a town outside Worcester says more than its region
			ltef_meta( $post_id, 'price' ),
		)
	);
	return implode( ' · ', $bits );
}

/** "Tina ate here · Aug 2026" or "Tina hasn't been yet", plus Hosted / Enhanced when true. */
function ltef_place_labels( $post_id, $small = false ) {
	$out = ltef_tina_ate( $post_id ) || ltef_tina_owns( $post_id )
		? ltef_place_trust_label( $post_id, $small )
		: '<span class="ltef-lbl ltef-lbl--notyet' . ( $small ? ' ltef-lbl--sm' : '' ) . '">Tina hasn’t been yet</span>';
	if ( ltef_meta( $post_id, 'hosted' ) ) {
		$out .= ltef_label( 'hosted', 'Hosted', $small );
	}
	if ( ltef_meta( $post_id, 'enhanced' ) ) {
		$out .= ltef_label( 'enhanced', 'Enhanced listing', $small );
	}
	return $out;
}

/** Favorite (star) or Must try (target). Empty string when Tina hasn't given one. */
function ltef_verdict_html( $verdict ) {
	if ( 'favorite' === $verdict ) {
		return '<span class="ltef-verdict ltef-verdict--fav">' . ltef_icon( 'star' ) . 'Favorite</span>';
	}
	if ( 'dont-miss' === $verdict ) {
		return '<span class="ltef-verdict ltef-verdict--miss">' . ltef_icon( 'target' ) . 'Must try</span>';
	}
	return '';
}

/** A place as a card: photo or sticker, label, name, meta, Tina's line. */
function ltef_place_card( $post_id ) {
	$field = ltef_place_field( $post_id );
	$own   = function_exists( 'ltef_enhanced_card_photo' ) ? ltef_enhanced_card_photo( $post_id ) : '';
	$img   = has_post_thumbnail( $post_id )
		? get_the_post_thumbnail( $post_id, 'medium_large', array( 'alt' => '', 'class' => 'ltef-card__photo' ) )
		: ( $own ? $own : '<img src="' . esc_url( ltef_sticker_url( ltef_place_sticker( $post_id ) ) ) . '" alt="" width="148" height="148">' );
	$quote = ltef_meta( $post_id, 'tina_quote' );
	$v     = ltef_tina_owns( $post_id ) ? '' : ltef_verdict_html( ltef_meta( $post_id, 'verdict' ) );
	return sprintf(
		'<article class="ltef-card"><a href="%1$s" class="ltef-card__link"><div class="ltef-card__ph" style="background: var(--%2$s);">%3$s%4$s</div><div class="ltef-card__row"><h3 class="ltef-card__name">%5$s</h3></div><p class="ltef-card__meta">%6$s</p>%7$s%8$s</a></article>',
		esc_url( get_permalink( $post_id ) ),
		esc_attr( $field ),
		ltef_place_trust_label( $post_id, false ),
		$img,
		esc_html( get_the_title( $post_id ) ),
		esc_html( ltef_place_meta_line( $post_id, false ) ),
		$v ? '<div class="ltef-verdicts">' . $v . '</div>' : '',
		$quote ? '<p class="ltef-note">“' . esc_html( $quote ) . '”</p>' : ( function_exists( 'ltef_enhanced_card_line' ) ? ltef_enhanced_card_line( $post_id ) : '' )
	);
}

/** A place as a row in Explore, with the data the filters read. */
function ltef_place_row( $post_id ) {
	$terms = function ( $tax ) use ( $post_id ) {
		$t = get_the_terms( $post_id, $tax );
		return ( $t && ! is_wp_error( $t ) ) ? implode( ' ', wp_list_pluck( $t, 'slug' ) ) : '';
	};
	$dish = ltef_meta( $post_id, 'dish' );
	$name = get_the_title( $post_id );
	// An Enhanced listing fills the same row a little more: the owner's first photo, and the start of their story when Tina hasn't been.
	$enh   = function_exists( 'ltef_enhanced_on' ) && ltef_enhanced_on( $post_id );
	$gal   = $enh ? ltef_owner_gallery( $post_id ) : array();
	$photo = $gal ? wp_get_attachment_image_url( $gal[0], 'thumbnail' ) : '';
	$story = $enh && ! $dish ? (string) ltef_meta( $post_id, 'story' ) : '';
	return sprintf(
		'<article class="panel place-row" data-name="%1$s" data-area="%2$s" data-cravings="%3$s" data-with="%4$s" data-tina="%5$d" data-kind="%14$s"><div class="place-row__ph" style="background: var(--%6$s);"><img src="%7$s" alt="" width="148" height="148" loading="lazy" decoding="async"' . ( $photo ? ' class="place-row__photo"' : '' ) . '></div><div class="place-row__txt"><a href="%8$s" class="h-s tap">%9$s</a><span class="meta">%10$s</span>%11$s<span class="place-row__labels">%12$s%13$s</span></div></article>',
		esc_attr( strtolower( $name . ' ' . ltef_meta( $post_id, 'cuisine' ) . ' ' . $dish ) ),
		esc_attr( $terms( 'ltef_area' ) ),
		esc_attr( $terms( 'ltef_craving' ) ),
		esc_attr( $terms( 'ltef_with' ) ),
		ltef_tina_ate( $post_id ) ? 1 : 0,
		esc_attr( ltef_place_field( $post_id ) ),
		esc_url( $photo ? $photo : ltef_sticker_url( ltef_place_sticker( $post_id ) ) ),
		esc_url( get_permalink( $post_id ) ),
		esc_html( $name ),
		esc_html( ltef_place_meta_line( $post_id ) ),
		$dish ? '<span class="row-order"><span class="row-order__k">Tina ordered</span> ' . esc_html( ltef_in_sentence( $dish ) ) . '</span>' : ( '' !== $story ? '<span class="row-order"><span class="row-order__k">From the owner</span> ' . esc_html( wp_trim_words( $story, 14 ) ) . '</span>' : '' ),
		ltef_place_labels( $post_id, true ),
		ltef_verdict_html( ltef_meta( $post_id, 'verdict' ) ),
		esc_attr( ltef_place_kind( $post_id ) )
	);
}

/**
 * One of Tina's videos as a paused still. Nothing loads from any platform until someone presses play.
 * It plays the best copy there is (see ltef_sources_from_urls) and, for the big player, links to the others underneath.
 *
 * $o: title, month (YYYY-MM), caption, field (pastel), cover (img HTML or ''), sticker (URL), small (bool), id (attribute).
 */
function ltef_video_box( $sources, $o ) {
	if ( ! $sources ) {
		return '';
	}
	$o     = wp_parse_args( $o, array( 'title' => 'Tina’s video', 'month' => '', 'caption' => '', 'field' => 'pistachio', 'cover' => '', 'sticker' => '', 'small' => false, 'id' => '' ) );
	$main  = $sources[0];
	$still = $o['cover'] ? $o['cover'] : '<span class="tt-plate" aria-hidden="true"></span><img src="' . esc_url( $o['sticker'] ? $o['sticker'] : ltef_sticker_url( 'lemon' ) ) . '" alt="" width="148" height="148">';
	$cap   = mb_strlen( $o['caption'] ) > 72 ? rtrim( mb_substr( $o['caption'], 0, 70 ) ) . '…' : $o['caption'];
	$play  = $main['embed'] ? '<button class="tt-play" type="button" aria-label="' . esc_attr( 'Play ' . $o['title'] ) . '">' . ltef_icon( 'play' ) . '</button>' : '';
	$month = ltef_month_label( $o['month'] );
	// Instagram photo posts (/p/ links) show the post rather than a video.
	$photo  = 'instagram' === $main['platform'] && false !== strpos( $main['url'], '/p/' );
	$handle = 'instagram' === $main['platform'] ? '@livetoeatfood' : '@livetoeatfoodie';
	$box   = sprintf(
		'<div class="tt%1$s%2$s" data-embed="%3$s" data-title="%4$s" data-platform="%14$s" data-url="%7$s" data-label="%9$s" data-alt="%15$s" style="background: var(--%5$s);">%6$s<a class="tt-out" href="%7$s" target="_blank" rel="noopener">%8$s<span class="vh"> (opens %9$s)</span></a>%10$s<span class="tt-cap" aria-hidden="true"><b>%13$s%11$s</b><span>%12$s</span></span><span class="tt-bar" aria-hidden="true"></span></div>',
		$o['cover'] ? ' tt--cover' : '',
		$o['small'] ? ' tt--sm' : '',
		esc_url( $main['embed'] ),
		esc_attr( $o['title'] ),
		esc_attr( $o['field'] ),
		$still,
		esc_url( $main['url'] ),
		$o['small'] ? esc_html( $main['label'] ) : ( $photo ? 'See the post' : 'Watch the video' ),
		esc_html( $main['label'] ),
		$play,
		$month ? ' · ' . esc_html( $month ) : '',
		esc_html( $cap ),
		esc_html( $handle ),
		esc_attr( $main['platform'] ),
		esc_url( isset( $sources[1] ) && $sources[1]['embed'] ? $sources[1]['embed'] : '' )
	);
	if ( $o['small'] ) {
		return $box;
	}
	$note = '';
	if ( $main['embed'] ) {
		$players = array(
			'youtube'   => 'Plays in YouTube’s privacy-enhanced player.',
			'instagram' => 'Plays in Instagram’s player.',
			'facebook'  => 'Plays in Facebook’s player.',
			'tiktok'    => 'Plays in TikTok’s player.',
		);
		$alts = array();
		foreach ( $sources as $s ) {
			$alts[] = '<a href="' . esc_url( $s['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $s['label'] ) . '<span class="vh"> (opens a new tab)</span></a>';
		}
		$note = $photo
			? '<p class="meta tt-note">A photo post from her Instagram. It opens in Instagram’s viewer: no account needed, and nothing loads until you press play. Not showing? See it on ' . implode( ' · ', $alts ) . '.</p>'
			: '<p class="meta tt-note">' . $players[ $main['platform'] ] . ' No account needed, and nothing loads until you press play. Not playing? Watch it on ' . implode( ' · ', $alts ) . '.</p>';
	}
	return '<div class="take-video"' . ( $o['id'] ? ' id="' . esc_attr( $o['id'] ) . '"' : '' ) . '>' . $box . $note . '</div>';
}

/** The cover for a place's video: a saved TikTok still, then the featured image, else none. */
function ltef_place_cover( $post_id, $sources ) {
	foreach ( $sources as $s ) {
		if ( 'tiktok' === $s['platform'] ) {
			$rel = 'assets/photos/videos/' . ltef_tiktok_id( $s['url'] ) . '.webp';
			if ( file_exists( get_theme_file_path( $rel ) ) ) {
				return '<img class="tt-cover" src="' . esc_url( get_theme_file_uri( $rel ) ) . '" alt="" width="540" height="960" loading="lazy">';
			}
		}
	}
	return has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'tt-cover', 'alt' => '' ) ) : '';
}

/** Tina's main video for a place. */
function ltef_video_html( $post_id ) {
	if ( ! ltef_meta( $post_id, 'tina_video' ) ) {
		return '';
	}
	$sources = ltef_video_sources( $post_id );
	return ltef_video_box(
		$sources,
		array(
			'title'   => 'Tina’s video at ' . get_the_title( $post_id ),
			'month'   => ltef_meta( $post_id, 'tina_date' ),
			'caption' => (string) ltef_meta( $post_id, 'tina_quote' ),
			'field'   => ltef_place_field( $post_id ),
			'cover'   => ltef_place_cover( $post_id, $sources ),
			'sticker' => ltef_sticker_url( ltef_place_sticker( $post_id ) ),
			'id'      => 'video',
		)
	);
}

/** "More from Tina": her other videos from the same place, small, newest first. */
function ltef_more_videos_html( $post_id ) {
	$items = '';
	foreach ( ltef_more_videos( $post_id ) as $i => $v ) {
		$sources = ltef_sources_from_urls( $v['urls'] );
		if ( ! $sources ) {
			continue;
		}
		$month  = ltef_month_label( $v['month'] );
		$title  = 'Tina’s video at ' . get_the_title( $post_id ) . ( $month ? ', ' . $month : '' );
		$items .= '<li class="more-vid">' . ltef_video_box( $sources, array( 'title' => $title, 'month' => $v['month'], 'caption' => $v['caption'], 'field' => array( 'peach', 'lemon', 'butter', 'pistachio' )[ $i % 4 ], 'sticker' => ltef_sticker_url( ltef_place_sticker( $post_id ) ), 'small' => true ) )
			. ( $v['caption'] ? '<p class="more-vid__cap">“' . esc_html( $v['caption'] ) . '”</p>' : '' )
			. ( $month ? '<p class="meta more-vid__when">' . esc_html( $month ) . '</p>' : '' ) . '</li>';
	}
	if ( ! $items ) {
		return '';
	}
	return '<section class="more-vids" aria-labelledby="more-vids-h"><h2 class="h-m" id="more-vids-h">More from Tina here</h2><p class="meta">Her other videos from ' . esc_html( get_the_title( $post_id ) ) . '. No account needed, and nothing loads until you press play.</p><ul class="more-vids__list">' . $items . '</ul></section>';
}

/** Before you go: facts from the listing, then what's worth knowing. */
function ltef_place_facts( $post_id ) {
	$rows = array();
	$addr = ltef_meta( $post_id, 'address' );
	$town = ltef_town_state( ltef_meta( $post_id, 'town' ) );
	if ( $addr ) {
		$rows[] = array( 'Address', $addr . ', ' . $town );
	} elseif ( ltef_meta( $post_id, 'town' ) ) {
		$rows[] = array( 'Town', $town );
	}
	$hours = ltef_meta( $post_id, 'hours' );
	$rows[] = array( 'Hours', $hours ? $hours . ( ltef_meta( $post_id, 'hours_checked' ) ? ' (checked ' . date_i18n( 'M j', strtotime( ltef_meta( $post_id, 'hours_checked' ) ) ) . ')' : '' ) : 'Not checked yet. Call ahead.' );
	if ( ltef_meta( $post_id, 'phone' ) ) {
		$rows[] = array( 'Phone', ltef_meta( $post_id, 'phone' ) );
	}
	if ( ltef_meta( $post_id, 'price' ) ) {
		$rows[] = array( 'Price', ltef_meta( $post_id, 'price' ) );
	}
	$rows = array_merge( $rows, ltef_place_specials_rows( $post_id ) );
	$dl = '';
	foreach ( $rows as $r ) {
		$dl .= '<div><dt>' . esc_html( $r[0] ) . '</dt><dd>' . esc_html( $r[1] ) . '</dd></div>';
	}
	$facts = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $post_id, 'facts' ) ) ) );
	$worth = $facts ? '<div class="facts-worth"><h3 class="h-s">Worth knowing</h3><ul class="facts-list">' . implode( '', array_map( function ( $f ) { return '<li>' . esc_html( $f ) . '</li>'; }, $facts ) ) . '</ul></div>' : '';
	$web   = ltef_meta( $post_id, 'website' ) ? '<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( ltef_meta( $post_id, 'website' ) ) . '" target="_blank" rel="noopener">Website<span class="vh"> (opens a new tab)</span></a>' : '';
	$menu  = ltef_meta( $post_id, 'menu' ) ? '<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( ltef_meta( $post_id, 'menu' ) ) . '" target="_blank" rel="noopener">Menu<span class="vh"> (opens a new tab)</span></a>' : '';
	$owned = function_exists( 'ltef_owner_emails' ) && ltef_owner_emails( $post_id );
	$note  = $owned ? (string) ltef_meta( $post_id, 'owner_note' ) : '';
	$from  = '' !== $note ? '<figure class="facts-owner"><figcaption class="facts-owner__by">' . ltef_icon( 'store' ) . 'From the owner</figcaption><blockquote><p>' . esc_html( $note ) . '</p></blockquote></figure>' : '';
	$claim = $owned
		? '<p class="meta facts-claim">The owner keeps the hours and links up to date. Something still wrong? <a href="' . esc_url( home_url( '/for-restaurants/' ) ) . '#listing">Tell us</a>.</p>'
		: ( ltef_tina_owns( $post_id ) ? '' : '<p class="meta facts-claim">Is this your business? <a href="' . esc_url( add_query_arg( 'claim', $post_id, home_url( '/for-restaurants/' ) ) ) . '#claim">Claim this listing</a> to keep its hours, links and menu up to date. Free.</p>' );
	return '<div class="panel facts-panel"><div style="display: grid; gap: 4px;"><h2 class="h-m" style="font-size: 30px;">Before you go</h2><p class="meta" style="margin: 0;">' . ( $owned ? 'From the owner, public listings and Tina’s posts.' : 'From public listings and Tina’s posts.' ) . '</p></div>'
		. '<div class="facts-cols"><dl class="facts-dl">' . $dl . '</dl>' . $worth . '</div>' . $from
		. '<div class="ltef-actions"><a class="ltef-btn ltef-btn--sm" href="' . esc_url( ltef_maps_url( get_the_title( $post_id ), $addr, $town ) ) . '" target="_blank" rel="noopener">' . ltef_icon( 'pin' ) . 'Get directions<span class="vh"> (opens Google Maps)</span></a>' . $web . $menu . '</div>' . $claim . '</div>';
}

/** The owner's announcement on a place page: dated, labeled as theirs and kept apart from Tina's take. */
function ltef_place_announcement( $post_id ) {
	$a = function_exists( 'ltef_owner_announcement' ) ? ltef_owner_announcement( $post_id ) : null;
	if ( ! $a ) {
		return '';
	}
	list( $text, $link, $posted, $until ) = $a;
	$when = array_filter(
		array(
			$posted ? 'Posted ' . date_i18n( 'M j', strtotime( $posted ) ) : '',
			$until ? 'Ends ' . date_i18n( 'M j', strtotime( $until ) ) : '',
			'From the owner, not Tina',
		)
	);
	$hid = 'place-news-' . (int) $post_id;
	return '<aside class="panel place-news" aria-labelledby="' . esc_attr( $hid ) . '">'
		. '<h2 class="place-news__by" id="' . esc_attr( $hid ) . '">' . ltef_icon( 'mega' ) . 'News from ' . esc_html( get_the_title( $post_id ) ) . '</h2>'
		. '<p class="place-news__t">' . esc_html( $text ) . '</p>'
		. ( $link ? '<p class="place-news__go"><a class="ltef-btn ltef-btn--sm" href="' . esc_url( $link ) . '" target="_blank" rel="noopener">Learn more<span class="vh"> about this from ' . esc_html( get_the_title( $post_id ) ) . ' (opens a new tab)</span></a></p>' : '' )
		. '<p class="meta place-news__meta">' . esc_html( implode( ' · ', $when ) ) . '</p>'
		. '</aside>';
}

/* ------------------------------------------------------------------ Daily deals */

/** Weekday keys, Monday first, with their names. */
function ltef_weekdays() {
	return array( 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday' );
}

/** The days a weekly deal runs, as weekday keys. */
function ltef_special_days( $post_id ) {
	return array_values( array_filter( array_keys( ltef_weekdays() ), function ( $d ) use ( $post_id ) { return (bool) ltef_meta( $post_id, 'day_' . $d ); } ) );
}

/** "Mondays and Tuesdays", "Weekdays", "Every day". */
function ltef_days_label( $days ) {
	$all = array_keys( ltef_weekdays() );
	if ( count( $days ) === 7 ) {
		return 'Every day';
	}
	if ( array_slice( $all, 0, 5 ) === $days ) {
		return 'Weekdays';
	}
	$idx = array_map( function ( $d ) use ( $all ) { return array_search( $d, $all, true ); }, $days );
	if ( count( $days ) >= 3 && end( $idx ) - reset( $idx ) === count( $days ) - 1 ) {
		return ltef_weekdays()[ reset( $days ) ] . ' to ' . ltef_weekdays()[ end( $days ) ];
	}
	$names = array_map( function ( $d ) { return ltef_weekdays()[ $d ] . 's'; }, $days );
	return count( $names ) > 1 ? implode( ', ', array_slice( $names, 0, -1 ) ) . ' and ' . end( $names ) : (string) reset( $names );
}

/** Published weekly deals, optionally for one place. */
function ltef_specials( $place_id = 0 ) {
	$q = array( 'post_type' => 'ltef_special', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' );
	if ( $place_id ) {
		$q['meta_query'] = array( array( 'key' => 'ltef_place', 'value' => (int) $place_id ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	return get_posts( $q );
}

/** Where a deal comes from: "per Tina, Aug 2026". */
function ltef_special_source( $post_id ) {
	$who   = array( 'tina' => 'per Tina', 'owner' => 'per the place', 'reader' => 'from a reader, checked with the place' );
	$src   = ltef_meta( $post_id, 'source' ) ? ltef_meta( $post_id, 'source' ) : 'tina';
	$month = ltef_month_label( ltef_meta( $post_id, 'checked' ) );
	return ( $who[ $src ] ?? 'per Tina' ) . ( $month ? ', ' . $month : '' );
}

/** One deal as a card on the Daily deals page. */
function ltef_special_card( $post_id ) {
	$place = (int) ltef_meta( $post_id, 'place' );
	$name  = $place ? get_the_title( $place ) : get_the_title( $post_id );
	$label = '';
	$maps  = '';
	$head  = '<span class="h-s">' . esc_html( $name ) . '</span>';
	if ( $place ) {
		if ( ltef_tina_owns( $place ) ) {
			$label = ltef_place_trust_label( $place );
		} elseif ( ltef_meta( $place, 'tina_video' ) || ltef_meta( $place, 'tina_quote' ) ) {
			$month = ltef_month_label( ltef_meta( $place, 'tina_date' ) );
			$label = ltef_label( 'tina', ltef_tina_label( $place ) . ( $month ? ' · ' . $month : '' ) );
		}
		$head = '<a href="' . esc_url( get_permalink( $place ) ) . '" class="h-s tap special-card__name">' . esc_html( $name ) . '</a>';
		$maps = '<div><a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( ltef_maps_url( $name, ltef_meta( $place, 'address' ), ltef_town_state( ltef_meta( $place, 'town' ) ) ) ) . '" target="_blank" rel="noopener">Get directions<span class="vh"> to ' . esc_html( $name ) . ' (opens Google Maps)</span></a></div>';
	}
	$link  = 'tina' === ( ltef_meta( $post_id, 'source' ) ? ltef_meta( $post_id, 'source' ) : 'tina' ) ? 'her video' : ( preg_match( '#facebook\.com|instagram\.com|tiktok\.com#', (string) ltef_meta( $post_id, 'source_url' ) ) ? 'their post' : 'their website' );
	$video = ltef_meta( $post_id, 'source_url' ) ? ' · <a href="' . esc_url( ltef_meta( $post_id, 'source_url' ) ) . '" target="_blank" rel="noopener">' . $link . '<span class="vh"> (opens a new tab)</span></a>' : '';
	$details = ltef_meta( $post_id, 'details' ) ? '<p class="special-card__details">' . esc_html( ltef_meta( $post_id, 'details' ) ) . '</p>' : '';
	return '<article class="panel special-card">' . $label . $head
		. '<p class="special-card__deal">' . esc_html( ltef_meta( $post_id, 'deal' ) ? ltef_meta( $post_id, 'deal' ) : get_the_title( $post_id ) ) . '</p>' . $details
		. '<p class="meta special-card__when">' . esc_html( ltef_days_label( ltef_special_days( $post_id ) ) ) . ' · ' . esc_html( ltef_special_source( $post_id ) ) . $video . '</p>'
		. $maps . '</article>';
}

/** The week: one column per day, Monday first. Today's column is marked by the script. */
function ltef_daily_deals_html() {
	$by_day = array_fill_keys( array_keys( ltef_weekdays() ), array() );
	foreach ( ltef_specials() as $p ) {
		foreach ( ltef_special_days( $p->ID ) as $d ) {
			$by_day[ $d ][] = $p->ID;
		}
	}
	$num  = array( 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 0 );
	$jump = '';
	$cols = '';
	foreach ( ltef_weekdays() as $d => $day ) {
		$n     = count( $by_day[ $d ] );
		// Each chip is a link to its day; the script turns them into tabs that show one day at a time.
		$jump .= '<a class="ltef-chip" href="#' . esc_attr( $d ) . '" id="tab-' . esc_attr( $d ) . '" data-day-tab="' . esc_attr( $d ) . '" data-day="' . (int) $num[ $d ] . '">' . esc_html( substr( $day, 0, 3 ) )
			. ( $n ? '<span class="day-jump__n"><span class="vh">, </span>' . $n . '<span class="vh"> ' . ( 1 === $n ? 'deal' : 'deals' ) . '</span></span>' : '' ) . '</a>';
		$cards = $by_day[ $d ]
			? implode( '', array_map( 'ltef_special_card', $by_day[ $d ] ) )
			: '<p class="panel special-empty">Nothing on ' . esc_html( $day ) . 's yet. <a href="#tell">Know one?</a></p>';
		$cols .= '<section class="day-col" data-day="' . (int) $num[ $d ] . '" id="' . esc_attr( $d ) . '" aria-labelledby="d-' . esc_attr( $d ) . '"><h2 id="d-' . esc_attr( $d ) . '" class="h-m day-col__h">' . esc_html( $day ) . '</h2><div class="day-col__cards">' . $cards . '</div></section>';
	}
	return '<nav class="ltef-chips day-jump" aria-label="Jump to a day" data-ltef-daytabs>' . $jump . '</nav><div class="day-grid" data-ltef-days>' . $cols . '</div>';
}

/** Weekly deals for one place, for the place page's facts. */
function ltef_place_specials_rows( $place_id ) {
	return array_map( function ( $p ) { return array( 'Weekly deal', ltef_days_label( ltef_special_days( $p->ID ) ) . ': ' . ( ltef_meta( $p->ID, 'deal' ) ? ltef_meta( $p->ID, 'deal' ) : get_the_title( $p->ID ) ) . ( ltef_meta( $p->ID, 'details' ) ? ' (' . ltef_meta( $p->ID, 'details' ) . ')' : '' ) . ' · ' . ltef_special_source( $p->ID ) ); }, ltef_specials( $place_id ) );
}

/* ------------------------------------------------------------------ Woo-pons */

/** Whether a Woo-pon is live today. */
function ltef_woopon_live( $post_id ) {
	$today = ltef_today();
	$start = ltef_meta( $post_id, 'start' );
	$end   = ltef_meta( $post_id, 'end' );
	return ( ! $start || $start <= $today ) && ( ! $end || $end >= $today );
}

function ltef_woopon_ticket( $post_id ) {
	$kind  = ltef_meta( $post_id, 'kind' ) ? ltef_meta( $post_id, 'kind' ) : 'food';
	$names = array( 'food' => 'Food & drink', 'service' => 'Service', 'online' => 'Online code' );
	$place = (int) ltef_meta( $post_id, 'place' );
	$biz   = ltef_meta( $post_id, 'business' );
	if ( ! $biz && $place ) {
		$biz = get_the_title( $place );
	}
	$where = trim( $biz . ( ltef_meta( $post_id, 'area_text' ) ? ' · ' . ltef_meta( $post_id, 'area_text' ) : '' ) );
	$tier  = ltef_meta( $post_id, 'tier' );
	$tina  = '';
	if ( 'partner' === $tier ) {
		$tina = ltef_label( 'partner', 'referral' === ltef_meta( $post_id, 'earns' ) ? 'Partner: Tina earns a referral fee' : 'Partner: Tina earns a commission' );
	} elseif ( $place && ltef_tina_owns( $place ) ) {
		$tina = ltef_place_trust_label( $place );
	} elseif ( $place && ltef_tina_ate( $place ) ) {
		$tina = ltef_label( 'tina', 'Tina ate here · ' . ltef_month_label( ltef_meta( $place, 'tina_date' ) ) );
	} else {
		$tina = '<span class="ltef-lbl ltef-lbl--notyet ltef-lbl--sm">Tina hasn’t been yet</span>';
	}
	$vip    = 'vip' === $tier ? str_replace( 'class="ltef-lbl ', 'class="ltef-lbl ltef-coupon__vip ', ltef_label( 'vip', 'VIP Woo-pon' ) ) : '';
	$end    = ltef_meta( $post_id, 'end' );
	$runs   = $end ? 'Through ' . date_i18n( 'M j', strtotime( $end ) ) : 'Ongoing';
	$terms  = '<dt>Runs</dt><dd>' . esc_html( $runs ) . '</dd>';
	if ( ltef_meta( $post_id, 'where' ) ) {
		$terms .= '<dt>Where</dt><dd>' . esc_html( ltef_meta( $post_id, 'where' ) ) . '</dd>';
	}
	if ( ltef_meta( $post_id, 'exclusions' ) ) {
		$terms .= '<dt>Excludes</dt><dd>' . esc_html( ltef_meta( $post_id, 'exclusions' ) ) . '</dd>';
	}
	$code   = ltef_meta( $post_id, 'code' );
	$fine   = ltef_meta( $post_id, 'fine' );
	$action = 'online' === $kind && ltef_meta( $post_id, 'shop_url' )
		? '<a class="ltef-btn ltef-btn--sm" href="' . esc_url( ltef_meta( $post_id, 'shop_url' ) ) . '" target="_blank" rel="sponsored noopener">' . esc_html( ltef_meta( $post_id, 'shop_label' ) ? ltef_meta( $post_id, 'shop_label' ) : 'Shop ' . $biz ) . '<span class="vh"> (opens ' . esc_html( $biz ) . ' in a new tab, a partner link)</span></a>'
		: '';
	$tags = array( $kind );
	if ( $end && strtotime( $end ) < strtotime( '+30 days' ) ) {
		$tags[] = 'soon';
	}
	$areas = get_the_terms( $post_id, 'ltef_area' );
	if ( $areas && ! is_wp_error( $areas ) ) {
		foreach ( $areas as $a ) {
			$tags[] = $a->slug;
		}
	}
	return sprintf(
		'<div class="ltef-coupon ltef-coupon--%1$s" data-tags="%2$s"><span class="ltef-coupon__kind ltef-coupon__kind--%1$s">%3$s</span>%4$s<span class="ltef-coupon__off">%5$s</span><span class="ltef-coupon__where">%6$s</span>%7$s%12$s%8$s<dl class="ltef-coupon__terms">%9$s</dl>%10$s%11$s</div>',
		esc_attr( $kind ),
		esc_attr( implode( ' ', $tags ) ),
		esc_html( $names[ $kind ] ?? $names['food'] ),
		$vip,
		esc_html( get_the_title( $post_id ) ),
		esc_html( $where ),
		str_replace( 'class="ltef-lbl ', 'class="ltef-lbl ltef-coupon__tina ', $tina ),
		$code ? '<span class="ltef-coupon__code"><span>Code</span><b>' . esc_html( $code ) . '</b></span>' : '',
		$terms,
		$fine ? '<span class="ltef-coupon__fine">' . esc_html( $fine ) . '</span>' : '',
		$action ? '<div class="ltef-coupon__act">' . $action . '</div>' : '',
		ltef_meta( $post_id, 'connection' ) ? '<span class="ltef-coupon__connect">' . esc_html( ltef_meta( $post_id, 'connection' ) ) . '</span>' : ''
	);
}

/* ------------------------------------------------------------------ Ad spots */

/** The running ad for a spot, if one is sold and in its dates. */
function ltef_running_ad( $spot ) {
	$today = ltef_today();
	$ads   = get_posts(
		array(
			'post_type'   => 'ltef_ad',
			'post_status' => 'publish',
			'numberposts' => 10,
			'meta_key'    => 'ltef_spot', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $spot, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $ads as $ad ) {
		$s = ltef_meta( $ad->ID, 'start' );
		$e = ltef_meta( $ad->ID, 'end' );
		if ( ( ! $s || $s <= $today ) && ( ! $e || $e >= $today ) ) {
			return $ad;
		}
	}
	return null;
}

/**
 * An ad spot: the sold ad (hatched, "Paid ad"), or, until one is sold, an invitation to advertise.
 */
function ltef_ad_spot( $spot, $context_line = '' ) {
	$kind   = in_array( $spot, array( 'rail', 'feed' ), true ) ? ' ltef-slot--' . $spot : '';
	$policy = home_url( '/editorial-policy/#paid-ads' );
	$ad     = ltef_running_ad( $spot );
	if ( $ad ) {
		$logo = has_post_thumbnail( $ad->ID ) ? get_the_post_thumbnail( $ad->ID, 'medium', array( 'alt' => get_the_title( $ad->ID ) . ' logo' ) ) : esc_html( get_the_title( $ad->ID ) );
		$cta  = ltef_meta( $ad->ID, 'cta' ) ? ltef_meta( $ad->ID, 'cta' ) : 'Visit ' . get_the_title( $ad->ID );
		return sprintf(
			'<aside class="ltef-slot%1$s" aria-label="Paid ad: %2$s"><div class="ltef-slot__head">%3$s<span class="ltef-slot__why">Paid placement · <a href="%4$s">How paid ads work</a></span></div><div class="ltef-slot__body"><span class="ltef-slot__mark">%5$s</span><p class="ltef-slot__name">%2$s</p><p class="ltef-slot__line">%6$s</p><a class="ltef-btn ltef-btn--sm ltef-btn--alt ltef-slot__cta" href="%7$s" rel="sponsored noopener" target="_blank">%8$s</a></div></aside>',
			$kind,
			esc_html( get_the_title( $ad->ID ) ),
			ltef_label( 'sponsored', 'Paid ad' ),
			esc_url( $policy ),
			$logo,
			esc_html( ltef_meta( $ad->ID, 'line' ) ),
			esc_url( ltef_meta( $ad->ID, 'url' ) ),
			esc_html( $cta )
		);
	}
	$lines = array(
		'home'  => 'Put your business under Tina’s latest picks, where people decide where to eat this week.',
		'rail'  => 'Reach people looking at places nearby. One local business per page.',
		'feed'  => 'A card in this list for your business. Never ranked, never numbered.',
		'guide' => 'A spot between the stops on this guide. Never one of Tina’s stops.',
	);
	$line = $context_line ? $context_line : ( $lines[ $spot ] ?? $lines['home'] );
	return sprintf(
		'<aside class="ltef-slot ltef-slot--open%1$s" aria-label="Ad space available"><div class="ltef-slot__head">%2$s<span class="ltef-slot__why">One local business per spot · <a href="%3$s">How paid ads work</a></span></div><div class="ltef-slot__body"><span class="ltef-slot__mark">Your logo here</span><p class="ltef-slot__name">Your business here?</p><p class="ltef-slot__line">%4$s Labeled Paid ad, and it never changes what Tina says.</p><a class="ltef-btn ltef-btn--sm ltef-slot__cta" href="%5$s">Advertise with us</a></div></aside>',
		$kind,
		ltef_label( 'open', 'Ad space open' ),
		esc_url( $policy ),
		esc_html( $line ),
		esc_url( add_query_arg( 'about', $spot, home_url( '/work-with-tina/' ) ) . '#contact' )
	);
}

/* ------------------------------------------------------------------ Events and jobs */

function ltef_event_row( $post_id ) {
	$date = ltef_meta( $post_id, 'date' );
	$ts   = $date ? strtotime( $date ) : 0;
	$prem = ltef_meta( $post_id, 'premium' );
	$meta = implode( ' · ', array_filter( array( ltef_meta( $post_id, 'time' ), ltef_meta( $post_id, 'venue' ), ltef_event_price( $post_id ) ) ) );
	$link = ltef_meta( $post_id, 'link' );
	return sprintf(
		'<article class="panel ev-row ev-k--%11$s%1$s"><span class="ev-date"><span>%2$s</span><b>%3$s</b></span><div class="ev-txt"><div class="ev-tags">%12$s%4$s</div><h3 class="h-s"><a class="ev-row__link" href="%10$s">%5$s</a></h3><p class="meta">%6$s</p>%7$s<div class="ltef-actions">%8$s<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="%9$s" target="_blank" rel="noopener">Get directions<span class="vh"> (opens Google Maps)</span></a></div></div></article>',
		$prem ? ' ev-premium' : '',
		$ts ? esc_html( date_i18n( 'D', $ts ) ) : '',
		$ts ? esc_html( date_i18n( 'j', $ts ) ) : '',
		$prem ? ltef_label( 'sponsored', 'Premium listing' ) : '',
		esc_html( get_the_title( $post_id ) ),
		esc_html( $meta ),
		has_excerpt( $post_id ) ? '<p>' . esc_html( get_the_excerpt( $post_id ) ) . '</p>' : '',
		$link ? '<a class="ltef-btn ltef-btn--sm" href="' . esc_url( $link ) . '" target="_blank" rel="noopener">Details<span class="vh"> (opens a new tab)</span></a>' : '',
		esc_url( ltef_maps_url( '', (string) ltef_meta( $post_id, 'venue' ) ) ),
		esc_url( get_permalink( $post_id ) ),
		esc_attr( ltef_event_kind( $post_id ) ),
		ltef_event_kind_chip( $post_id )
	);
}

/** An event's kind key, "other" when it has none. */
function ltef_event_kind( $post_id ) {
	$k = (string) ltef_meta( $post_id, 'kind' );
	return '' !== ltef_field_label( 'ltef_event', 'kind', $k ) ? $k : 'other';
}

/** An event's kind as a colored chip with its name, so the color is never the only cue. */
function ltef_event_kind_chip( $post_id ) {
	$k = ltef_event_kind( $post_id );
	return '<span class="ev-kind ev-k--' . esc_attr( $k ) . '">' . esc_html( ltef_field_label( 'ltef_event', 'kind', $k ) ) . '</span>';
}

/** The kinds' colors, explained: one chip per kind among these events. */
function ltef_event_legend( $ids ) {
	$kinds = array_unique( array_map( 'ltef_event_kind', $ids ) );
	if ( count( $kinds ) < 2 ) {
		return '';
	}
	$order = array_keys( ltef_fields( 'ltef_event' )['kind'][3] );
	usort( $kinds, function ( $a, $b ) use ( $order ) { return array_search( $a, $order, true ) <=> array_search( $b, $order, true ); } );
	return '<ul class="ev-legend" aria-label="Kinds of events">' . implode( '', array_map( function ( $k ) { return '<li><span class="ev-kind ev-k--' . esc_attr( $k ) . '">' . esc_html( ltef_field_label( 'ltef_event', 'kind', $k ) ) . '</span></li>'; }, $kinds ) ) . '</ul>';
}

/** An event's price as listed, with a dollar sign added when it's only a number ("45" → "$45"). */
function ltef_event_price( $post_id ) {
	$p = (string) ltef_meta( $post_id, 'price_text' );
	return preg_match( '/^\d+(\.\d{1,2})?$/', $p ) ? '$' . $p : $p;
}

/** A select field's label, like "Market or fair" for kind=market. */
function ltef_field_label( $type, $key, $value ) {
	$f = ltef_fields( $type );
	return (string) ( $f[ $key ][3][ $value ] ?? '' );
}

/** The panel of facts on a single event: when, where, price, kind, and the ticket and directions buttons. */
function ltef_event_facts( $post_id ) {
	$date  = ltef_meta( $post_id, 'date' );
	$ts    = $date ? strtotime( $date ) : 0;
	$time  = (string) ltef_meta( $post_id, 'time' );
	$venue = (string) ltef_meta( $post_id, 'venue' );
	$price = ltef_event_price( $post_id );
	$link  = (string) ltef_meta( $post_id, 'link' );
	$kind  = '';
	$rows  = array();
	if ( $ts ) {
		$rows[] = array( 'When', date_i18n( 'l, F j, Y', $ts ) . ( '' !== $time ? ' · ' . $time : '' ) );
	} elseif ( '' !== $time ) {
		$rows[] = array( 'Time', $time );
	}
	if ( '' !== $venue ) {
		$rows[] = array( 'Where', $venue );
	}
	if ( '' !== $price ) {
		$rows[] = array( 'Price', $price );
	}
	if ( '' !== $kind ) {
		$rows[] = array( 'Kind', $kind );
	}
	$by  = (string) ltef_meta( $post_id, 'submitted_by' );
	$aff = array( 'organizer' => 'the organizer', 'venue' => 'from the venue', 'vendor' => 'a vendor or performer', 'sharing' => 'not involved, sharing it' )[ (string) ltef_meta( $post_id, 'affiliation' ) ] ?? '';
	if ( '' !== $by && ! ltef_meta( $post_id, 'tina' ) ) {
		$rows[] = array( 'Listed by', $by . ( $aff ? ', ' . $aff : '' ) );
	}
	$dl = '';
	foreach ( $rows as $r ) {
		$dl .= '<div><dt>' . esc_html( $r[0] ) . '</dt><dd>' . esc_html( $r[1] ) . '</dd></div>';
	}
	$labels = trim( ltef_event_kind_chip( $post_id ) . ( ltef_meta( $post_id, 'tina' ) ? ltef_label( 'tina', 'Tina’s event' ) : '' ) . ( ltef_meta( $post_id, 'premium' ) ? ltef_label( 'sponsored', 'Premium listing' ) : '' ) );
	$past   = $ts && wp_date( 'Y-m-d', $ts ) < ltef_today();
	$btns   = ( $link ? '<a class="ltef-btn ltef-btn--sm" href="' . esc_url( $link ) . '" target="_blank" rel="noopener">Details and tickets<span class="vh"> (opens a new tab)</span></a>' : '' )
		. ( '' !== $venue ? '<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( ltef_maps_url( '', $venue ) ) . '" target="_blank" rel="noopener">' . ltef_icon( 'pin' ) . 'Get directions<span class="vh"> (opens Google Maps)</span></a>' : '' );
	return '<div class="panel facts-panel ev-facts">'
		. ( $labels ? '<div class="ev-facts__labels">' . $labels . '</div>' : '' )
		. ( $past ? '<p class="ev-facts__past">This event has passed. <a href="' . esc_url( home_url( '/events/' ) ) . '">See what’s coming up</a>.</p>' : '' )
		. ( $dl ? '<dl class="facts-dl">' . $dl . '</dl>' : '' )
		. ( $btns ? '<div class="ltef-actions">' . $btns . '</div>' : '' )
		. '<p class="meta facts-claim">' . ( ltef_meta( $post_id, 'tina' ) ? '' : 'Sent in to the site, not written by Tina. Times and prices can change, so check before you go. ' ) . 'Have an event? <a href="' . esc_url( home_url( '/events/' ) ) . '#submit">Add it here</a>: listing is free.</p>'
		. '</div>';
}

/** The panel of facts on a single job post. */
function ltef_job_facts( $post_id ) {
	$rows = array_filter(
		array(
			array( 'Business', (string) ltef_meta( $post_id, 'business' ) ),
			array( 'Neighborhood', (string) ltef_meta( $post_id, 'area_text' ) ),
			array( 'Kind of job', ltef_field_label( 'ltef_job', 'job_kind', (string) ltef_meta( $post_id, 'job_kind' ) ) ),
			array( 'Hours', ltef_field_label( 'ltef_job', 'hours_type', (string) ltef_meta( $post_id, 'hours_type' ) ) ),
			array( 'Pay', (string) ltef_meta( $post_id, 'pay' ) ),
		),
		function ( $r ) { return '' !== $r[1]; }
	);
	$dl = '';
	foreach ( $rows as $r ) {
		$dl .= '<div><dt>' . esc_html( $r[0] ) . '</dt><dd>' . esc_html( $r[1] ) . '</dd></div>';
	}
	$how = (string) ltef_meta( $post_id, 'how_to_apply' );
	if ( '' !== $how ) {
		$dl .= '<div><dt>How to apply</dt><dd>' . make_clickable( esc_html( $how ) ) . '</dd></div>';
	}
	$until  = (string) ltef_meta( $post_id, 'until' );
	$closed = $until && $until < ltef_today();
	return '<div class="panel facts-panel ev-facts">'
		. '<div class="ev-facts__labels">' . ltef_label( 'sponsored', 'Job post' ) . '</div>'
		. ( $closed ? '<p class="ev-facts__past">This job post has closed. <a href="' . esc_url( home_url( '/restaurant-jobs/' ) ) . '">See open jobs</a>.</p>' : '' )
		. ( $dl ? '<dl class="facts-dl">' . $dl . '</dl>' : '' )
		. '<p class="meta facts-claim">Posted by the business. Live to Eat Food doesn’t hire for it. <a href="' . esc_url( home_url( '/restaurant-jobs/' ) ) . '#post-job">Post a job</a>.</p>'
		. '</div>';
}

/** Event details for search engines (schema.org Event), from the same fields the page shows. */
add_action(
	'wp_head',
	function () {
		if ( ! is_singular( 'ltef_event' ) ) {
			return;
		}
		$id   = get_queried_object_id();
		$date = (string) ltef_meta( $id, 'date' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return;
		}
		$venue = (string) ltef_meta( $id, 'venue' );
		$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 40 );
		$data  = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'Event',
			'name'                => get_the_title( $id ),
			'startDate'           => $date,
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'url'                 => get_permalink( $id ),
		);
		if ( '' !== $venue ) {
			$data['location'] = array( '@type' => 'Place', 'name' => $venue, 'address' => $venue );
		}
		if ( '' !== trim( $desc ) ) {
			$data['description'] = $desc;
		}
		if ( preg_match( '/^\s*free\b/i', (string) ltef_meta( $id, 'price_text' ) ) ) {
			$data['isAccessibleForFree'] = true;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n";
	}
);

/** The excerpt, when a post has one and no body: older events were listed with a short description only. */
function ltef_excerpt_as_body( $post_id ) {
	$p = get_post( $post_id );
	if ( ! $p || '' !== trim( wp_strip_all_tags( $p->post_content ) ) || '' === trim( $p->post_excerpt ) ) {
		return '';
	}
	return '<div class="ltef-prose">' . wpautop( esc_html( $p->post_excerpt ) ) . '</div>';
}

function ltef_job_row( $post_id ) {
	$kinds = array( 'front' => 'Front of house', 'kitchen' => 'Kitchen', 'manage' => 'Management' );
	$hours = array( 'ft' => 'Full-time', 'pt' => 'Part-time', 'seasonal' => 'Seasonal' );
	$k     = ltef_meta( $post_id, 'job_kind' );
	$h     = ltef_meta( $post_id, 'hours_type' );
	$how   = ltef_meta( $post_id, 'how_to_apply' );
	return sprintf(
		'<article class="panel job-row" data-job="%1$s"><div style="display: grid; gap: 4px;"><span class="ltef-lbl ltef-lbl--sm job-kind">%2$s</span><h3 class="h-m" style="font-size: 28px;">%3$s</h3><p class="job-biz">%4$s</p><p class="meta" style="margin: 0;">%5$s</p>%6$s</div>%7$s</article>',
		esc_attr( trim( $k . ' ' . ( 'pt' === $h ? 'part' : '' ) ) ),
		esc_html( $kinds[ $k ] ?? 'Job' ),
		esc_html( get_the_title( $post_id ) ),
		esc_html( implode( ' · ', array_filter( array( ltef_meta( $post_id, 'business' ), ltef_meta( $post_id, 'area_text' ) ) ) ) ),
		esc_html( implode( ' · ', array_filter( array( $hours[ $h ] ?? '', ltef_meta( $post_id, 'pay' ) ) ) ) ),
		$how ? '<p style="margin: 0;"><strong>How to apply:</strong> ' . make_clickable( esc_html( $how ) ) . '</p>' : '',
		ltef_label( 'sponsored', 'Job post' )
	);
}

/* ------------------------------------------------------------------ Site navigation */

/**
 * Every page in the header, grouped. Each item: label, path, key used to mark the current page.
 * The first group's first five items are the top bar on wide screens.
 */
function ltef_nav_groups() {
	return array(
		'Find food'        => array(
			array( 'Explore', '/explore/', 'explore', 'Explore every place' ),
			array( 'Guides', '/guides/', 'guides' ),
			array( 'Woo-pons', '/woo-pons/', 'woo-pons' ),
			array( 'Daily deals', '/daily-deals/', 'daily-deals' ),
			array( 'Roulette', '/roulette/', 'roulette', 'Restaurant Roulette' ),
		),
		'Around town'      => array(
			array( 'Events', '/events/', 'events' ),
			array( 'Beyond food', '/beyond-food/', 'beyond-food', 'Beyond food: shops, salons, days out' ),
			array( 'Out & about', '/category/out-and-about/', 'out-and-about' ),
			array( 'Your bites', '/your-bites/', 'your-bites' ),
			array( 'Food help', '/food-help/', 'food-help' ),
		),
		'For restaurants'  => array(
			array( 'For restaurants', '/for-restaurants/', 'for-restaurants' ),
			array( 'Restaurant jobs', '/restaurant-jobs/', 'restaurant-jobs' ),
			array( 'Work with Tina', '/work-with-tina/', 'work-with-tina' ),
			array( 'Send something in', '/submit/', 'submit' ),
		),
		'Live to Eat Food' => array(
			array( 'About Tina', '/tina/', 'tina' ),
			array( 'How it works', '/how-it-works/', 'how-it-works' ),
			array( 'Editorial policy', '/editorial-policy/', 'editorial-policy' ),
		),
	);
}

/** Which nav item the current request belongs to. */
function ltef_nav_current() {
	if ( is_singular( 'ltef_place' ) && 'eat' !== ltef_place_kind( get_queried_object_id() ) ) {
		return 'beyond-food';
	}
	if ( is_singular( 'ltef_place' ) || is_post_type_archive( 'ltef_place' ) || is_tax( array( 'ltef_area', 'ltef_craving', 'ltef_with' ) ) ) {
		return 'explore';
	}
	if ( is_singular( 'ltef_woopon' ) || is_post_type_archive( 'ltef_woopon' ) ) {
		return 'woo-pons';
	}
	if ( is_singular( 'ltef_event' ) || is_post_type_archive( 'ltef_event' ) ) {
		return 'events';
	}
	if ( is_singular( 'ltef_job' ) || is_post_type_archive( 'ltef_job' ) ) {
		return 'restaurant-jobs';
	}
	if ( is_category( 'out-and-about' ) || ( is_singular( 'post' ) && in_category( 'out-and-about' ) ) ) {
		return 'out-and-about';
	}
	if ( is_home() || is_singular( 'post' ) || is_category() ) {
		return 'guides';
	}
	if ( is_page() ) {
		return get_post_field( 'post_name', get_queried_object_id() );
	}
	return '';
}

/** One nav link, marked when it's the current page. */
function ltef_nav_link( $item, $current, $long = false, $class = '' ) {
	$label = $long && ! empty( $item[3] ) ? $item[3] : $item[0];
	return '<li' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '><a href="' . esc_url( home_url( $item[1] ) ) . '"' . ( $current === $item[2] ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a></li>';
}

/** The header navigation: the top bar and a grouped More panel on wide screens, a grouped Menu on phones. */
function ltef_site_nav_html() {
	$groups  = ltef_nav_groups();
	$current = ltef_nav_current();
	$top     = array_merge( array_slice( $groups['Find food'], 0, 5 ), array( $groups['Around town'][0] ) );
	$top_keys = wp_list_pluck( $top, 2 );
	$main    = implode( '', array_map( function ( $i ) use ( $current ) { return ltef_nav_link( $i, $current ); }, $top ) );

	// More: everything not in the top bar, in its groups. The last two top links (Roulette, Events) move in here as the top bar narrows.
	$more        = '';
	$more_active = false;
	foreach ( $groups as $name => $items ) {
		$links = '';
		if ( 'Find food' === $name ) {
			$name = 'More food';
		}
		foreach ( $items as $i ) {
			if ( in_array( $i[2], $top_keys, true ) ) {
				$pos = array_search( $i[2], $top_keys, true ) + 1;
				if ( $pos >= 5 ) {
					$links .= ltef_nav_link( $i, $current, false, 'nav-overflow nav-overflow-' . $pos );
				}
				continue;
			}
			$more_active = $more_active || $current === $i[2];
			$links      .= ltef_nav_link( $i, $current );
		}
		if ( 'Around town' === $name ) {
			$more_links_food = $links;
			continue;
		}
		if ( 'More food' === $name ) {
			$food = $links;
			continue;
		}
		$more .= '<div class="site-more__group"><p class="drawer-h">' . esc_html( $name ) . '</p><ul>' . $links . '</ul></div>';
	}
	$more = '<div class="site-more__group"><p class="drawer-h">Food and around town</p><ul>' . $food . $more_links_food . '</ul></div>' . $more;
	$chev = '<svg viewBox="0 0 24 24" aria-hidden="true" class="chev"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	$cur_pos        = array_search( $current, $top_keys, true );
	$events_current = false !== $cur_pos && $cur_pos >= 4 ? ' is-current-n' . ( $cur_pos + 1 ) : '';

	// Menu on phones: every page, in its groups, with the newsletter last.
	$drawer = '';
	foreach ( $groups as $name => $items ) {
		$drawer .= '<div class="drawer-group"><p class="drawer-h">' . esc_html( $name ) . '</p><ul>' . implode( '', array_map( function ( $i ) use ( $current ) { return ltef_nav_link( $i, $current, true ); }, $items ) ) . '</ul></div>';
	}
	$crumbs = esc_url( home_url( '/crumbs/' ) );

	return '<nav class="site-nav" aria-label="Main"><ul class="site-nav__main">' . $main . '</ul>'
		. '<details class="site-more' . ( $more_active ? ' is-current' : '' ) . $events_current . '" data-ltef-menu><summary>More' . $chev . '</summary><div class="site-more__panel">' . $more . '</div></details></nav>'
		. '<a class="ltef-btn ltef-btn--sm site-cta" href="' . $crumbs . '">Get Monthly Crumbs</a>'
		. '<details class="site-drawer" data-ltef-menu><summary><span class="burger" aria-hidden="true"><i></i><i></i><i></i></span>Menu</summary>'
		. '<nav class="drawer-panel" aria-label="All pages">' . $drawer . '<a class="ltef-btn site-drawer-cta" href="' . $crumbs . '">Get Monthly Crumbs</a></nav></details>';
}
