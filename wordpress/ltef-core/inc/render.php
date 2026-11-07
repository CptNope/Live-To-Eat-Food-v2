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
			ltef_place_area_name( $post_id ),
			ltef_meta( $post_id, 'price' ),
		)
	);
	return implode( ' · ', $bits );
}

/** "Tina ate here · Aug 2026" or "Tina hasn't been yet", plus Hosted / Enhanced when true. */
function ltef_place_labels( $post_id, $small = false ) {
	$out = ltef_tina_ate( $post_id )
		? ltef_label( 'tina', trim( 'Tina ate here · ' . ltef_month_label( ltef_meta( $post_id, 'tina_date' ) ), ' ·' ), $small )
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
		ltef_tina_ate( $post_id ) ? ltef_label( 'tina', 'Tina ate here · ' . ltef_month_label( ltef_meta( $post_id, 'tina_date' ) ), false ) : '',
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
		'<article class="panel place-row" data-name="%1$s" data-area="%2$s" data-cravings="%3$s" data-with="%4$s" data-tina="%5$d"><div class="place-row__ph" style="background: var(--%6$s);"><img src="%7$s" alt="" width="148" height="148"></div><div class="place-row__txt"><a href="%8$s" class="h-s tap">%9$s</a><span class="meta">%10$s</span>%11$s<span class="place-row__labels">%12$s%13$s</span></div></article>',
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
		ltef_verdict_html( ltef_meta( $post_id, 'verdict' ) )
	);
}

/**
 * Tina's video as a paused still. Nothing loads from any platform until someone presses play.
 * It plays the best copy there is (see ltef_video_sources) and links to the others underneath.
 */
function ltef_video_html( $post_id ) {
	$url = ltef_meta( $post_id, 'tina_video' );
	if ( ! $url ) {
		return '';
	}
	$title   = 'Tina’s video at ' . get_the_title( $post_id );
	$sources = ltef_video_sources( $post_id );
	$main    = $sources ? $sources[0] : array( 'platform' => '', 'label' => '', 'url' => $url, 'embed' => '' );
	$cover   = '';
	$tt_cover = '';
	foreach ( $sources as $s ) {
		if ( 'tiktok' === $s['platform'] ) {
			$tt_cover = ltef_tiktok_id( $s['url'] );
		}
	}
	$cover_rel = 'assets/photos/videos/' . $tt_cover . '.webp';
	if ( $tt_cover && file_exists( get_theme_file_path( $cover_rel ) ) ) {
		$cover = '<img class="tt-cover" src="' . esc_url( get_theme_file_uri( $cover_rel ) ) . '" alt="" width="540" height="960" loading="lazy">';
	} elseif ( has_post_thumbnail( $post_id ) ) {
		$cover = get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'tt-cover', 'alt' => '' ) );
	}
	$still = $cover ? $cover : '<span class="tt-plate" aria-hidden="true"></span><img src="' . esc_url( ltef_sticker_url( ltef_place_sticker( $post_id ) ) ) . '" alt="" width="148" height="148">';
	$quote = (string) ltef_meta( $post_id, 'tina_quote' );
	$cap   = mb_strlen( $quote ) > 72 ? rtrim( mb_substr( $quote, 0, 70 ) ) . '…' : $quote;
	$play  = $main['embed'] ? '<button class="tt-play" type="button" aria-label="' . esc_attr( 'Play ' . $title ) . '">' . ltef_icon( 'play' ) . '</button>' : '';

	$players = array(
		'youtube'   => 'Plays in YouTube’s privacy-enhanced player.',
		'instagram' => 'Plays in Instagram’s player.',
		'facebook'  => 'Plays in Facebook’s player.',
		'tiktok'    => 'Plays in TikTok’s player.',
	);
	$note = '';
	if ( $main['embed'] ) {
		$note = $players[ $main['platform'] ] . ' No account needed, and nothing loads until you press play.';
		$alts = array();
		foreach ( $sources as $s ) {
			$alts[] = '<a href="' . esc_url( $s['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $s['label'] ) . '<span class="vh"> (opens a new tab)</span></a>';
		}
		$note .= ' Not playing? Watch it on ' . implode( ' · ', $alts ) . '.';
		$note  = '<p class="meta tt-note">' . $note . '</p>';
	}
	return sprintf(
		'<div class="take-video" id="video"><div class="tt%1$s" data-embed="%2$s" data-title="%3$s" style="background: var(--%4$s);">%5$s<a class="tt-out" href="%6$s" target="_blank" rel="noopener">Watch the video</a>%7$s<span class="tt-cap" aria-hidden="true"><b>@livetoeatfoodie · %8$s</b><span>%9$s</span></span><span class="tt-bar" aria-hidden="true"></span></div>%10$s</div>',
		$cover ? ' tt--cover' : '',
		esc_url( $main['embed'] ),
		esc_attr( $title ),
		esc_attr( ltef_place_field( $post_id ) ),
		$still,
		esc_url( $main['url'] ),
		$play,
		esc_html( ltef_month_label( ltef_meta( $post_id, 'tina_date' ) ) ),
		esc_html( $cap ),
		$note
	);
}

/** Before you go: facts from the listing, then what's worth knowing. */
function ltef_place_facts( $post_id ) {
	$rows = array();
	$addr = ltef_meta( $post_id, 'address' );
	$town = ltef_meta( $post_id, 'town' ) ? ltef_meta( $post_id, 'town' ) : 'Worcester';
	if ( $addr ) {
		$rows[] = array( 'Address', $addr . ', ' . $town );
	}
	$hours = ltef_meta( $post_id, 'hours' );
	$rows[] = array( 'Hours', $hours ? $hours . ( ltef_meta( $post_id, 'hours_checked' ) ? ' (checked ' . date_i18n( 'M j', strtotime( ltef_meta( $post_id, 'hours_checked' ) ) ) . ')' : '' ) : 'Not checked yet. Call ahead.' );
	if ( ltef_meta( $post_id, 'phone' ) ) {
		$rows[] = array( 'Phone', ltef_meta( $post_id, 'phone' ) );
	}
	if ( ltef_meta( $post_id, 'price' ) ) {
		$rows[] = array( 'Price', ltef_meta( $post_id, 'price' ) );
	}
	$dl = '';
	foreach ( $rows as $r ) {
		$dl .= '<div><dt>' . esc_html( $r[0] ) . '</dt><dd>' . esc_html( $r[1] ) . '</dd></div>';
	}
	$facts = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $post_id, 'facts' ) ) ) );
	$worth = $facts ? '<div class="facts-worth"><h3 class="h-s">Worth knowing</h3><ul class="facts-list">' . implode( '', array_map( function ( $f ) { return '<li>' . esc_html( $f ) . '</li>'; }, $facts ) ) . '</ul></div>' : '';
	$web   = ltef_meta( $post_id, 'website' ) ? '<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( ltef_meta( $post_id, 'website' ) ) . '" target="_blank" rel="noopener">Website<span class="vh"> (opens a new tab)</span></a>' : '';
	return '<div class="panel facts-panel"><div style="display: grid; gap: 4px;"><h2 class="h-m" style="font-size: 30px;">Before you go</h2><p class="meta" style="margin: 0;">From public listings and Tina’s posts.</p></div>'
		. '<div class="facts-cols"><dl class="facts-dl">' . $dl . '</dl>' . $worth . '</div>'
		. '<div class="ltef-actions"><a class="ltef-btn ltef-btn--sm" href="' . esc_url( ltef_maps_url( get_the_title( $post_id ), $addr, $town . ', MA' ) ) . '" target="_blank" rel="noopener">' . ltef_icon( 'pin' ) . 'Get directions<span class="vh"> (opens Google Maps)</span></a>' . $web . '</div></div>';
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
