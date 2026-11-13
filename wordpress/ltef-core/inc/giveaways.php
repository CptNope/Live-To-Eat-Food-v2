<?php
/**
 * Giveaways: prizes local businesses donate, entered on Tina's socials.
 *
 * Tina adds each one under Woo-pons → Giveaways: the prize is the title, the photo is the featured image.
 * The Giveaways page shows what's running and coming up with a button to her post, and past ones with the
 * winner. While one runs, a banner shows on the home page. Each giveaway's screen has a short blurb to paste
 * into Monthly Crumbs.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'ltef_giveaway',
			array(
				'labels'       => array_merge( ltef_labels( 'Giveaway', 'Giveaways' ), array( 'all_items' => 'Giveaways' ) ),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'edit.php?post_type=ltef_woopon',
				'show_in_rest' => true,
				'supports'     => array( 'title', 'thumbnail', 'custom-fields', 'revisions' ),
				'description'  => 'Prizes local businesses donate. People enter on Tina’s socials; the Giveaways page shows each one and, after, who won.',
			)
		);
	}
);

/** The fields on a giveaway. */
function ltef_giveaway_fields() {
	return array(
		'donor'  => array( 'Donated by', 'text', 'The business giving the prize. Shown as “Prize from …”.' ),
		'place'  => array( 'Their place on the site', 'place', 'Optional. Links “Prize from …” to their page.' ),
		'start'  => array( 'Starts', 'date', 'The day her post goes up. Before this it shows as “Coming soon”.' ),
		'end'    => array( 'Entries close', 'date', 'The last day to enter. The home page banner comes down after this day.' ),
		'link'   => array( 'Her giveaway post', 'url', 'The Instagram, TikTok or Facebook post people enter on.' ),
		'how'    => array( 'How to enter', 'textarea', 'One step per line, as in her post. Like: Follow @livetoeatfood and @theirplace / Like the post / Tag a friend in the comments.' ),
		'value'  => array( 'Prize value', 'text', 'Optional. Like $50.' ),
		'rules'  => array( 'Fine print', 'textarea', 'Optional. Who can enter, pickup, when the winner is picked. Kept as written.' ),
		'winner' => array( 'Winner', 'text', 'Fill in once she picks: a first name or handle. Shown on the Giveaways page.' ),
	);
}

/** The giveaway fields join the site's field list, so they get the usual box, saving and REST. */
add_action(
	'init',
	function () {
		foreach ( ltef_giveaway_fields() as $key => $def ) {
			register_post_meta(
				'ltef_giveaway',
				'ltef_' . $key,
				array(
					'type'              => 'place' === $def[1] ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'ltef_sanitize_' . ( in_array( $def[1], array( 'place', 'url', 'textarea' ), true ) ? $def[1] : 'text' ),
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	},
	21
);
add_filter(
	'ltef_fields',
	function ( $fields, $type ) {
		return 'ltef_giveaway' === $type ? ltef_giveaway_fields() : $fields;
	},
	10,
	2
);
add_action(
	'add_meta_boxes_ltef_giveaway',
	function ( $post ) {
		add_meta_box( 'ltef-fields', 'The giveaway', 'ltef_render_meta_box', 'ltef_giveaway', 'normal', 'high' );
		add_meta_box( 'ltef-giveaway-crumbs', 'For Monthly Crumbs', 'ltef_giveaway_crumbs_box', 'ltef_giveaway', 'side', 'default' );
	}
);

/** Where a giveaway stands today: soon, running or ended. */
function ltef_giveaway_state( $id ) {
	$today = ltef_today();
	$start = (string) ltef_meta( $id, 'start' );
	$end   = (string) ltef_meta( $id, 'end' );
	if ( $start && $start > $today ) {
		return 'soon';
	}
	return $end && $end < $today ? 'ended' : 'running';
}

/** Published giveaways in a state, soonest-ending first (ended: newest first). */
function ltef_giveaways( $states, $limit = -1 ) {
	$all = get_posts( array( 'post_type' => 'ltef_giveaway', 'post_status' => 'publish', 'numberposts' => -1 ) );
	$all = array_values( array_filter( $all, function ( $p ) use ( $states ) { return in_array( ltef_giveaway_state( $p->ID ), (array) $states, true ); } ) );
	usort(
		$all,
		function ( $a, $b ) use ( $states ) {
			$ea = (string) ltef_meta( $a->ID, 'end' );
			$eb = (string) ltef_meta( $b->ID, 'end' );
			return array( 'ended' ) === (array) $states ? strcmp( $eb, $ea ) : strcmp( $ea, $eb );
		}
	);
	return $limit > 0 ? array_slice( $all, 0, $limit ) : $all;
}

/** "Nov 20" */
function ltef_giveaway_day( $ymd ) {
	return $ymd ? wp_date( 'M j', strtotime( $ymd . ' 12:00' ) ) : '';
}

/** "Instagram", "TikTok" or "Facebook" for her post, or ''. */
function ltef_giveaway_platform( $id ) {
	$pf = ltef_video_platform( (string) ltef_meta( $id, 'link' ) );
	return ltef_video_labels()[ $pf ] ?? '';
}

/** "Prize from Gold Pizza", linked to their page when it's on the site. */
function ltef_giveaway_donor( $id ) {
	$donor = (string) ltef_meta( $id, 'donor' );
	$place = (int) ltef_meta( $id, 'place' );
	if ( '' === $donor && $place ) {
		$donor = html_entity_decode( get_the_title( $place ), ENT_QUOTES, 'UTF-8' );
	}
	if ( '' === $donor ) {
		return '';
	}
	$name = $place && 'publish' === get_post_status( $place ) ? '<a href="' . esc_url( get_permalink( $place ) ) . '">' . esc_html( $donor ) . '</a>' : esc_html( $donor );
	return 'Prize from ' . $name;
}

/** One giveaway as a card for the Giveaways page. */
function ltef_giveaway_card( $id ) {
	$state = ltef_giveaway_state( $id );
	$pf    = ltef_giveaway_platform( $id );
	$link  = (string) ltef_meta( $id, 'link' );
	$value = (string) ltef_meta( $id, 'value' );
	$img   = get_the_post_thumbnail( $id, 'medium_large', array( 'class' => 'gw-card__img', 'alt' => '', 'loading' => 'lazy' ) );
	$when  = array(
		'soon'    => 'Starts ' . ltef_giveaway_day( ltef_meta( $id, 'start' ) ),
		'running' => ltef_meta( $id, 'end' ) ? 'Enter by ' . ltef_giveaway_day( ltef_meta( $id, 'end' ) ) : 'Open now',
		'ended'   => 'Ended ' . ltef_giveaway_day( ltef_meta( $id, 'end' ) ),
	)[ $state ];
	$steps = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $id, 'how' ) ) ) );
	$how   = $steps && 'ended' !== $state ? '<ol class="gw-card__how">' . implode( '', array_map( function ( $s ) { return '<li>' . esc_html( $s ) . '</li>'; }, $steps ) ) . '</ol>' : '';
	$cta   = '';
	if ( 'running' === $state && $link ) {
		$cta = '<a class="ltef-btn ltef-btn--sm" href="' . esc_url( $link ) . '" target="_blank" rel="noopener">Enter on ' . esc_html( $pf ? $pf : 'her post' ) . '<span class="vh"> (opens a new tab)</span></a>';
	}
	$winner = (string) ltef_meta( $id, 'winner' );
	$result = 'ended' === $state ? '<p class="gw-card__winner">' . ( $winner ? 'Winner: <strong>' . esc_html( $winner ) . '</strong>. Congratulations!' : 'Winner coming soon.' ) . '</p>' : '';
	$rules  = (string) ltef_meta( $id, 'rules' );
	$fine   = array();
	if ( '' !== $rules ) {
		$fine[] = esc_html( $rules );
	}
	if ( $pf ) {
		$fine[] = esc_html( 'Not sponsored, endorsed or run by ' . $pf . '.' );
	}
	$donor = ltef_giveaway_donor( $id );
	return '<article class="gw-card gw-card--' . esc_attr( $state ) . '" id="giveaway-' . (int) $id . '">'
		. ( $img ? '<div class="gw-card__ph">' . $img . '</div>' : '' )
		. '<div class="gw-card__body">'
		. '<p class="gw-card__when">' . esc_html( $when ) . '</p>'
		. '<h3 class="gw-card__t">' . esc_html( get_the_title( $id ) ) . ( $value ? ' <span class="gw-card__value">(' . esc_html( $value ) . ')</span>' : '' ) . '</h3>'
		. ( $donor ? '<p class="gw-card__donor">' . $donor . '</p>' : '' )
		. $how . $cta . $result
		. ( $fine ? '<p class="gw-card__fine">' . implode( ' ', $fine ) . '</p>' : '' )
		. '</div></article>';
}

/** The Giveaways page: running and coming up first, then the last few with their winners. */
function ltef_block_giveaways() {
	$now  = ltef_giveaways( array( 'running', 'soon' ) );
	$past = ltef_giveaways( array( 'ended' ), 6 );
	$out  = $now
		? '<h2 class="h-m gw-now-h" id="open-giveaways">Open now</h2><div class="gw-list">' . implode( '', array_map( function ( $p ) { return ltef_giveaway_card( $p->ID ); }, $now ) ) . '</div>'
		: '<p class="panel gw-none">No giveaway running right now. Follow Tina on <a href="https://www.instagram.com/livetoeatfood/" target="_blank" rel="noopener">Instagram</a> or <a href="https://www.tiktok.com/@livetoeatfoodie" target="_blank" rel="noopener">TikTok</a>, or get <a href="' . esc_url( home_url( '/crumbs/' ) ) . '">Monthly Crumbs</a>, to catch the next one.</p>';
	if ( $past ) {
		$out .= '<h2 class="h-m gw-past-h" id="past-giveaways">Past giveaways</h2><div class="gw-list gw-list--past">' . implode( '', array_map( function ( $p ) { return ltef_giveaway_card( $p->ID ); }, $past ) ) . '</div>';
	}
	return ltef_wrap( $out, 'giveaways' );
}

/** The home page banner while a giveaway runs; nothing otherwise. */
function ltef_block_giveaway_banner() {
	$now = ltef_giveaways( 'running', 1 );
	if ( ! $now ) {
		return '';
	}
	$id    = $now[0]->ID;
	$end   = ltef_meta( $id, 'end' ) ? ' Enter by ' . ltef_giveaway_day( ltef_meta( $id, 'end' ) ) . '.' : '';
	$donor = wp_strip_all_tags( ltef_giveaway_donor( $id ) );
	return ltef_wrap(
		'<aside class="gw-banner" aria-label="Giveaway">'
		. '<span class="gw-banner__k">Giveaway</span>'
		. '<p class="gw-banner__t"><strong>' . esc_html( get_the_title( $id ) ) . '</strong>' . ( $donor ? ' · ' . esc_html( $donor ) : '' ) . '.' . esc_html( $end ) . '</p>'
		. '<a class="ltef-btn ltef-btn--sm" href="' . esc_url( home_url( '/giveaways/' ) ) . '#giveaway-' . (int) $id . '">How to enter</a>'
		. '</aside>',
		'giveaway-banner'
	);
}

/** The blurb for Monthly Crumbs, ready to copy. */
function ltef_giveaway_crumbs_text( $id ) {
	$donor = wp_strip_all_tags( ltef_giveaway_donor( $id ) );
	$pf    = ltef_giveaway_platform( $id );
	$end   = ltef_meta( $id, 'end' ) ? ' by ' . ltef_giveaway_day( ltef_meta( $id, 'end' ) ) : '';
	$link  = (string) ltef_meta( $id, 'link' );
	return 'Giveaway: ' . html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) . ( $donor ? ' (' . lcfirst( $donor ) . ')' : '' ) . '. '
		. 'Enter on ' . ( $pf ? $pf : 'her post' ) . $end . ( $link ? ': ' . $link : '.' ) . "\n"
		. 'How to enter and the fine print: ' . home_url( '/giveaways/' );
}

function ltef_giveaway_crumbs_box( $post ) {
	if ( 'publish' !== $post->post_status ) {
		echo '<p class="description">Publish it first, then copy the blurb from here.</p>';
		return;
	}
	echo '<p class="description" style="margin-top:0">Paste this into Monthly Crumbs.</p><textarea readonly rows="6" class="widefat" onclick="this.select()">' . esc_textarea( ltef_giveaway_crumbs_text( $post->ID ) ) . '</textarea>';
}

/** A giveaway added, changed or removed: clear cached pages so the banner and page are right. */
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'ltef_giveaway' === $post->post_type && ( 'publish' === $new || 'publish' === $old ) && function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	},
	10,
	3
);
add_action(
	'save_post_ltef_giveaway',
	function ( $post_id ) {
		if ( 'publish' === get_post_status( $post_id ) && function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	},
	20
);

/** Just after midnight, when a giveaway starts or ended yesterday, clear the cache so the banner comes up or down. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ltef_giveaways_daily' ) ) {
			$midnight = ( new DateTime( 'tomorrow', wp_timezone() ) )->getTimestamp();
			wp_schedule_event( $midnight + 4 * MINUTE_IN_SECONDS, 'daily', 'ltef_giveaways_daily' );
		}
	}
);
add_action(
	'ltef_giveaways_daily',
	function () {
		$today     = ltef_today();
		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day' ) );
		foreach ( get_posts( array( 'post_type' => 'ltef_giveaway', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
			if ( ltef_meta( $id, 'start' ) === $today || ltef_meta( $id, 'end' ) === $yesterday ) {
				if ( function_exists( 'ltef_money_clear_page_cache' ) ) {
					ltef_money_clear_page_cache();
				}
				return;
			}
		}
	}
);
