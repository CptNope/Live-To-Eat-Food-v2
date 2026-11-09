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
	if ( 'eat' !== ltef_place_kind( $post_id ) ) {
		return 'lemon';
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
	$out = ltef_tina_ate( $post_id )
		? ltef_label( 'tina', trim( ltef_tina_label( $post_id ) . ' · ' . ltef_month_label( ltef_meta( $post_id, 'tina_date' ) ), ' ·' ), $small )
		: '<span class="ltef-lbl ltef-lbl--notyet' . ( $small ? ' ltef-lbl--sm' : '' ) . '">Tina hasn’t been yet</span>';
	if ( ltef_meta( $post_id, 'hosted' ) ) {
		$out .= ltef_label( 'hosted', 'Hosted', $small );
	}
	if ( ltef_meta( $post_id, 'enhanced' ) ) {
		$out .= ltef_label( 'enhanced', 'Enhanced listing', $small );
	}
	return $out;
}

/** Favorite (star) or Don't miss (target). Empty string when Tina hasn't given one. */
function ltef_verdict_html( $verdict ) {
	if ( 'favorite' === $verdict ) {
		return '<span class="ltef-verdict ltef-verdict--fav">' . ltef_icon( 'star' ) . 'Favorite</span>';
	}
	if ( 'dont-miss' === $verdict ) {
		return '<span class="ltef-verdict ltef-verdict--miss">' . ltef_icon( 'target' ) . 'Don’t miss</span>';
	}
	return '';
}

/** A place as a card: photo or sticker, label, name, meta, Tina's line. */
function ltef_place_card( $post_id ) {
	$field = ltef_place_field( $post_id );
	$img   = has_post_thumbnail( $post_id )
		? get_the_post_thumbnail( $post_id, 'medium_large', array( 'alt' => '', 'class' => 'ltef-card__photo' ) )
		: '<img src="' . esc_url( ltef_sticker_url( ltef_place_sticker( $post_id ) ) ) . '" alt="" width="148" height="148">';
	$quote = ltef_meta( $post_id, 'tina_quote' );
	$v     = ltef_verdict_html( ltef_meta( $post_id, 'verdict' ) );
	return sprintf(
		'<article class="ltef-card"><a href="%1$s" class="ltef-card__link"><div class="ltef-card__ph" style="background: var(--%2$s);">%3$s%4$s</div><div class="ltef-card__row"><h3 class="ltef-card__name">%5$s</h3></div><p class="ltef-card__meta">%6$s</p>%7$s%8$s</a></article>',
		esc_url( get_permalink( $post_id ) ),
		esc_attr( $field ),
		ltef_tina_ate( $post_id ) ? ltef_label( 'tina', trim( ltef_tina_label( $post_id ) . ' · ' . ltef_month_label( ltef_meta( $post_id, 'tina_date' ) ), ' ·' ), false ) : '',
		$img,
		esc_html( get_the_title( $post_id ) ),
		esc_html( ltef_place_meta_line( $post_id, false ) ),
		$v ? '<div class="ltef-verdicts">' . $v . '</div>' : '',
		$quote ? '<p class="ltef-note">“' . esc_html( $quote ) . '”</p>' : ''
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
	return sprintf(
		'<article class="panel place-row" data-name="%1$s" data-area="%2$s" data-cravings="%3$s" data-with="%4$s" data-tina="%5$d" data-kind="%14$s"><div class="place-row__ph" style="background: var(--%6$s);"><img src="%7$s" alt="" width="148" height="148" loading="lazy" decoding="async"></div><div class="place-row__txt"><a href="%8$s" class="h-s tap">%9$s</a><span class="meta">%10$s</span>%11$s<span class="place-row__labels">%12$s%13$s</span></div></article>',
		esc_attr( strtolower( $name . ' ' . ltef_meta( $post_id, 'cuisine' ) . ' ' . $dish ) ),
		esc_attr( $terms( 'ltef_area' ) ),
		esc_attr( $terms( 'ltef_craving' ) ),
		esc_attr( $terms( 'ltef_with' ) ),
		ltef_tina_ate( $post_id ) ? 1 : 0,
		esc_attr( ltef_place_field( $post_id ) ),
		esc_url( ltef_sticker_url( ltef_place_sticker( $post_id ) ) ),
		esc_url( get_permalink( $post_id ) ),
		esc_html( $name ),
		esc_html( ltef_place_meta_line( $post_id ) ),
		$dish ? '<span class="row-order"><span class="row-order__k">Tina ordered</span> ' . esc_html( ltef_in_sentence( $dish ) ) . '</span>' : '',
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
		'<div class="tt%1$s%2$s" data-embed="%3$s" data-title="%4$s" style="background: var(--%5$s);">%6$s<a class="tt-out" href="%7$s" target="_blank" rel="noopener">%8$s<span class="vh"> (opens %9$s)</span></a>%10$s<span class="tt-cap" aria-hidden="true"><b>%13$s%11$s</b><span>%12$s</span></span><span class="tt-bar" aria-hidden="true"></span></div>',
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
		esc_html( $handle )
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
	return '<div class="panel facts-panel"><div style="display: grid; gap: 4px;"><h2 class="h-m" style="font-size: 30px;">Before you go</h2><p class="meta" style="margin: 0;">From public listings and Tina’s posts.</p></div>'
		. '<div class="facts-cols"><dl class="facts-dl">' . $dl . '</dl>' . $worth . '</div>'
		. '<div class="ltef-actions"><a class="ltef-btn ltef-btn--sm" href="' . esc_url( ltef_maps_url( get_the_title( $post_id ), $addr, $town ) ) . '" target="_blank" rel="noopener">' . ltef_icon( 'pin' ) . 'Get directions<span class="vh"> (opens Google Maps)</span></a>' . $web . '</div></div>';
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
		if ( ltef_meta( $place, 'tina_video' ) || ltef_meta( $place, 'tina_quote' ) ) {
			$month = ltef_month_label( ltef_meta( $place, 'tina_date' ) );
			$label = ltef_label( 'tina', ltef_tina_label( $place ) . ( $month ? ' · ' . $month : '' ) );
		}
		$head = '<a href="' . esc_url( get_permalink( $place ) ) . '" class="h-s tap special-card__name">' . esc_html( $name ) . '</a>';
		$maps = '<div><a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( ltef_maps_url( $name, ltef_meta( $place, 'address' ), ltef_town_state( ltef_meta( $place, 'town' ) ) ) ) . '" target="_blank" rel="noopener">Get directions<span class="vh"> to ' . esc_html( $name ) . ' (opens Google Maps)</span></a></div>';
	}
	$video = ltef_meta( $post_id, 'source_url' ) ? ' · <a href="' . esc_url( ltef_meta( $post_id, 'source_url' ) ) . '" target="_blank" rel="noopener">her video<span class="vh"> (opens a new tab)</span></a>' : '';
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
		$jump .= '<a class="ltef-chip" href="#' . esc_attr( $d ) . '">' . esc_html( substr( $day, 0, 3 ) ) . '</a>';
		$cards = $by_day[ $d ]
			? implode( '', array_map( 'ltef_special_card', $by_day[ $d ] ) )
			: '<p class="panel special-empty">Nothing on ' . esc_html( $day ) . 's yet. <a href="#tell">Know one?</a></p>';
		$cols .= '<section class="day-col" data-day="' . (int) $num[ $d ] . '" id="' . esc_attr( $d ) . '" aria-labelledby="d-' . esc_attr( $d ) . '"><h2 id="d-' . esc_attr( $d ) . '" class="h-m day-col__h">' . esc_html( $day ) . '</h2>' . $cards . '</section>';
	}
	return '<nav class="ltef-chips day-jump" aria-label="Jump to a day">' . $jump . '</nav><div class="day-grid" data-ltef-days>' . $cols . '</div>';
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
		$tina = ltef_label( 'partner', 'Partner: Tina earns a commission' );
	} elseif ( $place && ltef_tina_ate( $place ) ) {
		$tina = ltef_label( 'tina', 'Tina ate here · ' . ltef_month_label( ltef_meta( $place, 'tina_date' ) ) );
	} else {
		$tina = '<span class="ltef-lbl ltef-lbl--notyet ltef-lbl--sm">Tina hasn’t been yet</span>';
	}
	$vip    = 'vip' === $tier ? str_replace( 'class="ltef-lbl ', 'class="ltef-lbl ltef-coupon__vip ', ltef_label( 'vip', 'VIP Woo-pon' ) ) : '';
	$end    = ltef_meta( $post_id, 'end' );
	$runs   = $end ? 'Through ' . date_i18n( 'M j', strtotime( $end ) ) : 'Ongoing';
	$terms  = '<dt>Runs</dt><dd>' . esc_html( $runs ) . '</dd>';
	if ( ltef_meta( $post_id, 'exclusions' ) ) {
		$terms .= '<dt>Excludes</dt><dd>' . esc_html( ltef_meta( $post_id, 'exclusions' ) ) . '</dd>';
	}
	$code   = ltef_meta( $post_id, 'code' );
	$fine   = ltef_meta( $post_id, 'fine' );
	$action = 'online' === $kind && ltef_meta( $post_id, 'shop_url' )
		? '<a class="ltef-btn ltef-btn--sm" href="' . esc_url( ltef_meta( $post_id, 'shop_url' ) ) . '" target="_blank" rel="sponsored noopener">Shop ' . esc_html( $biz ) . '<span class="vh"> (opens a new tab)</span></a>'
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
		'<div class="ltef-coupon ltef-coupon--%1$s" data-tags="%2$s"><span class="ltef-coupon__kind ltef-coupon__kind--%1$s">%3$s</span>%4$s<span class="ltef-coupon__off">%5$s</span><span class="ltef-coupon__where">%6$s</span>%7$s%8$s<dl class="ltef-coupon__terms">%9$s</dl>%10$s%11$s</div>',
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
		$action ? '<div class="ltef-coupon__act">' . $action . '</div>' : ''
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
	$meta = implode( ' · ', array_filter( array( ltef_meta( $post_id, 'time' ), ltef_meta( $post_id, 'venue' ), ltef_meta( $post_id, 'price_text' ) ) ) );
	$link = ltef_meta( $post_id, 'link' );
	return sprintf(
		'<article class="panel ev-row%1$s"><span class="ev-date"><span>%2$s</span><b>%3$s</b></span><div class="ev-txt">%4$s<h3 class="h-s">%5$s</h3><p class="meta">%6$s</p>%7$s<div class="ltef-actions">%8$s<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="%9$s" target="_blank" rel="noopener">Get directions<span class="vh"> (opens Google Maps)</span></a></div></div></article>',
		$prem ? ' ev-premium' : '',
		$ts ? esc_html( date_i18n( 'D', $ts ) ) : '',
		$ts ? esc_html( date_i18n( 'j', $ts ) ) : '',
		$prem ? ltef_label( 'sponsored', 'Premium listing' ) : '',
		esc_html( get_the_title( $post_id ) ),
		esc_html( $meta ),
		has_excerpt( $post_id ) ? '<p>' . esc_html( get_the_excerpt( $post_id ) ) . '</p>' : '',
		$link ? '<a class="ltef-btn ltef-btn--sm" href="' . esc_url( $link ) . '" target="_blank" rel="noopener">Details<span class="vh"> (opens a new tab)</span></a>' : '',
		esc_url( ltef_maps_url( '', (string) ltef_meta( $post_id, 'venue' ) ) )
	);
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
