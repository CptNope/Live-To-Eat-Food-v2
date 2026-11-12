<?php
/**
 * Dynamic blocks. Each renders on the server from the content types; the editor shows a live preview.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block definitions: name => [ title, description, icon, attributes, render callback, uses post context ].
 */
function ltef_block_defs() {
	return array(
		'place-take'     => array( 'Place: Tina’s take', 'Labels, verdict, what Tina ordered and her line, with the hosted-visit note when it applies.', 'format-quote', array(), 'ltef_block_place_take', true ),
		'place-video'    => array( 'Place: Tina’s video', 'Her post as a paused still; it loads only when pressed.', 'video-alt3', array(), 'ltef_block_place_video', true ),
		'place-more'     => array( 'Place: More from Tina', 'Her other videos from this place, small, newest first. Shows nothing when there are none.', 'playlist-video', array(), 'ltef_block_place_more', true ),
		'video'          => array( 'Tina’s video (any link)', 'One of her videos from a link: TikTok, Instagram, Facebook or YouTube. Add more links, separated by spaces, and the site plays the best one.', 'video-alt3', array( 'url' => array( 'type' => 'string', 'default' => '' ), 'month' => array( 'type' => 'string', 'default' => '' ), 'caption' => array( 'type' => 'string', 'default' => '' ) ), 'ltef_block_video', false ),
		'place-enhanced' => array( 'Place: From the owner (Enhanced)', 'The owner’s photos, story, menu highlights and links, when the place has an Enhanced listing. Shows nothing otherwise.', 'store', array(), 'ltef_block_place_enhanced', true ),
		'place-announcement' => array( 'Place: Owner announcement', 'The owner’s dated news, near the top of the page. Shows nothing when there isn’t one.', 'megaphone', array(), 'ltef_block_place_announcement', true ),
		'place-facts'    => array( 'Place: Before you go', 'Address, hours, phone and what’s worth knowing, with the place-page ad spot beside it.', 'info-outline', array( 'showAd' => array( 'type' => 'boolean', 'default' => true ) ), 'ltef_block_place_facts', true ),
		'place-card'     => array( 'Place card', 'One place as a card. Use inside a Query Loop of places.', 'id', array(), 'ltef_block_place_card', true ),
		'latest-places'  => array( 'Tina’s latest picks', 'Her newest places as cards.', 'location', array( 'count' => array( 'type' => 'number', 'default' => 4 ) ), 'ltef_block_latest_places', false ),
		'explore'        => array( 'Explore every place', 'Every place to eat with search and filters, and the Explore ad spot after the fourth.', 'search', array(), 'ltef_block_explore', false ),
		'beyond-food'    => array( 'Beyond food', 'Tina’s shops, salons, classes and days out, grouped by kind.', 'store', array(), 'ltef_block_beyond_food', false ),
		'woopons'        => array( 'Woo-pons', 'The live Woo-pons with filter chips, or only the online partner codes.', 'tickets-alt', array( 'show' => array( 'type' => 'string', 'default' => 'local' ) ), 'ltef_block_woopons', false ),
		'woopon-ticket'  => array( 'Woo-pon ticket', 'The current Woo-pon as a ticket.', 'tickets-alt', array(), 'ltef_block_woopon_ticket', true ),
		'ad-spot'        => array( 'Ad spot', 'The sold ad for this spot, or “Ad space open” with a way to advertise.', 'megaphone', array( 'spot' => array( 'type' => 'string', 'default' => 'home' ) ), 'ltef_block_ad_spot', false ),
		'owner-area'     => array( 'Owners: manage a listing', 'Approved owners ask for a sign-in link here, then update their phone, website, menu link, hours, note and announcement.', 'admin-users', array(), 'ltef_block_owner_area', false ),
		'submit-form'    => array( 'Submission form', 'A form that sends to Tina’s review queue.', 'feedback', array( 'form' => array( 'type' => 'string', 'default' => 'woopon' ) ), 'ltef_block_submit_form', false ),
		'event-facts'    => array( 'Event: the details', 'When, where, price and kind, with the tickets and directions buttons.', 'calendar-alt', array(), 'ltef_block_event_facts', true ),
		'job-facts'      => array( 'Job post: the details', 'Business, hours, pay and how to apply.', 'businessperson', array(), 'ltef_block_job_facts', true ),
		'events'         => array( 'Upcoming events', 'Events from today on, premium listings first in their week. With Calendar on, a List / Calendar switch and a month view.', 'calendar-alt', array( 'count' => array( 'type' => 'number', 'default' => 12 ), 'calendar' => array( 'type' => 'boolean', 'default' => false ) ), 'ltef_block_events', false ),
		'jobs'           => array( 'Restaurant jobs', 'Open job posts with filter chips.', 'businessperson', array(), 'ltef_block_jobs', false ),
		'roulette'       => array( 'Restaurant Roulette', 'The wheel: Tina’s places or every restaurant.', 'image-rotate', array(), 'ltef_block_roulette', false ),
		'site-nav'       => array( 'Site navigation', 'The header menu: top links and a grouped More panel on wide screens, a grouped Menu on phones, and the Monthly Crumbs button.', 'menu', array(), 'ltef_block_site_nav', false ),
		'daily-deals'    => array( 'Daily deals by day', 'Every weekly special, Monday to Sunday, with today marked. Add deals under Places → Daily deals.', 'calendar', array(), 'ltef_block_daily_deals', false ),
		'verdicts'       => array( 'Tina’s two verdicts', 'Favorite and Don’t miss, explained.', 'star-filled', array(), 'ltef_block_verdicts', false ),
	);
}

add_action( 'init', 'ltef_register_blocks' );
function ltef_register_blocks() {
	wp_register_script( 'ltef-editor', LTEF_URL . 'assets/js/editor.js', array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components', 'wp-i18n' ), LTEF_VERSION . '.' . filemtime( LTEF_DIR . 'assets/js/editor.js' ), true );
	$defs = array();
	foreach ( ltef_block_defs() as $slug => $d ) {
		list( $title, $desc, $icon, $attrs, $cb, $uses_post ) = $d;
		register_block_type(
			'ltef/' . $slug,
			array(
				'api_version'     => 3,
				'title'           => $title,
				'description'     => $desc,
				'category'        => 'ltef',
				'icon'            => $icon,
				'attributes'      => $attrs,
				'uses_context'    => $uses_post ? array( 'postId', 'postType' ) : array(),
				'render_callback' => $cb,
				'editor_script'   => 'ltef-editor',
				'supports'        => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
			)
		);
		$defs[ 'ltef/' . $slug ] = array( 'title' => $title, 'description' => $desc, 'icon' => $icon, 'attributes' => $attrs, 'usesContext' => $uses_post ? array( 'postId', 'postType' ) : array() );
	}
	wp_add_inline_script( 'ltef-editor', 'window.LTEF_BLOCKS = ' . wp_json_encode( $defs ) . ';', 'before' );
}

add_filter(
	'block_categories_all',
	function ( $cats ) {
		array_unshift( $cats, array( 'slug' => 'ltef', 'title' => 'Live to Eat Food' ) );
		return $cats;
	}
);

/** Front-end script: video play, filters, Roulette, the VIP flyer field. */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script( 'ltef', LTEF_URL . 'assets/js/ltef.js', array(), LTEF_VERSION . '.' . filemtime( LTEF_DIR . 'assets/js/ltef.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
);

function ltef_ctx_post( $block ) {
	if ( isset( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}
	return (int) get_the_ID();
}

function ltef_block_site_nav() {
	return ltef_wrap( ltef_site_nav_html(), 'ltef-site-nav' );
}

function ltef_block_daily_deals() {
	return ltef_wrap( ltef_daily_deals_html(), 'daily-deals' );
}

function ltef_wrap( $html, $class = '' ) {
	return '<div ' . get_block_wrapper_attributes( array( 'class' => $class ) ) . '>' . $html . '</div>';
}

function ltef_block_place_take( $attrs, $content, $block ) {
	$id = ltef_ctx_post( $block );
	if ( ! $id || 'ltef_place' !== get_post_type( $id ) ) {
		return '';
	}
	$out  = '<div class="place-labels">' . ltef_place_labels( $id ) . '</div>';
	$out .= '<p class="meta place-meta">' . esc_html( ltef_place_meta_line( $id ) ) . '</p>';
	$own  = ltef_tina_owns( $id );
	$v    = $own ? '' : ltef_verdict_html( ltef_meta( $id, 'verdict' ) );
	if ( $own ) {
		$out .= '<p class="ltef-disclose ltef-disclose--partner">' . ltef_icon( 'store' ) . '<span>Tina owns this business. It’s on the site because it’s hers, not as an independent pick, so it never gets a verdict. <a href="' . esc_url( home_url( '/editorial-policy/#own' ) ) . '">How we handle this</a></span></p>';
	}
	if ( ltef_meta( $id, 'hosted' ) ) {
		$out .= '<p class="ltef-disclose">' . ltef_icon( 'mail' ) . '<span>Hosted visit: ' . esc_html( get_the_title( $id ) ) . ' invited Tina and covered this meal. Nobody paid for this page or saw it before it went up.</span></p>';
	}
	if ( ltef_tina_ate( $id ) ) {
		$dish = ltef_meta( $id, 'dish' );
		if ( $dish ) {
			$out .= '<div class="ordered"><span class="ordered__ph" style="background: var(--' . esc_attr( ltef_place_field( $id ) ) . ');"><img src="' . esc_url( ltef_sticker_url( ltef_place_sticker( $id ) ) ) . '" alt="" width="56" height="56"></span><span class="ordered__txt"><span class="ordered__k">What Tina ordered</span><span class="ordered__dish">' . esc_html( $dish ) . '</span></span></div>';
		}
		if ( ltef_meta( $id, 'tina_quote' ) ) {
			$out .= '<p class="ltef-note place-quote">“' . esc_html( ltef_meta( $id, 'tina_quote' ) ) . '”</p>';
		}
		$out .= $own ? '' : ( $v ? '<div class="ltef-verdicts">' . $v . '</div>' : '<p class="verdict-line"><span class="verdict-line__k">No verdict yet</span> Tina marks a place Favorite or Don’t miss when it really gets her. <a href="' . esc_url( home_url( '/how-it-works/#verdicts' ) ) . '">How verdicts work</a></p>' );
	} else {
		$out .= '<p class="verdict-line"><span class="verdict-line__k">Not yet</span> Tina hasn’t posted from here. <a href="' . esc_url( home_url( '/how-it-works/#ltef-suggest' ) ) . '">Think she should go?</a></p>';
	}
	return ltef_wrap( $out, 'place-take' );
}

function ltef_block_place_video( $attrs, $content, $block ) {
	$id = ltef_ctx_post( $block );
	return $id ? ltef_wrap( ltef_video_html( $id ), 'place-video' ) : '';
}

function ltef_block_place_more( $attrs, $content, $block ) {
	$id   = ltef_ctx_post( $block );
	$html = $id && 'ltef_place' === get_post_type( $id ) ? ltef_more_videos_html( $id ) : '';
	return $html ? ltef_wrap( $html, 'place-more' ) : '';
}

/** A standalone video in a post: the caption is Tina's own, shown under it. */
function ltef_block_video( $attrs ) {
	$sources = ltef_sources_from_urls( preg_split( '/\s+/', trim( (string) ( $attrs['url'] ?? '' ) ) ) );
	if ( ! $sources ) {
		return '';
	}
	$cap   = (string) ( $attrs['caption'] ?? '' );
	$month = (string) ( $attrs['month'] ?? '' );
	$html  = ltef_video_box( $sources, array( 'title' => 'Tina’s video' . ( $month ? ', ' . ltef_month_label( $month ) : '' ), 'month' => $month, 'caption' => $cap, 'field' => 'lemon' ) );
	return ltef_wrap( $html . ( $cap ? '<p class="ltef-note video-cap">“' . esc_html( $cap ) . '”</p>' : '' ), 'ltef-video' );
}

function ltef_block_place_announcement( $attrs, $content, $block ) {
	$id   = ltef_ctx_post( $block );
	$html = $id && 'ltef_place' === get_post_type( $id ) ? ltef_place_announcement( $id ) : '';
	return $html ? ltef_wrap( $html, 'place-announcement' ) : '';
}

function ltef_block_place_facts( $attrs, $content, $block ) {
	$id = ltef_ctx_post( $block );
	if ( ! $id || 'ltef_place' !== get_post_type( $id ) ) {
		return '';
	}
	$rail = '';
	if ( ! empty( $attrs['showAd'] ) && ! ltef_meta( $id, 'enhanced' ) && 0 === $id % 2 ) {
		$area = ltef_place_area_name( $id );
		$rail = '<div class="go-rail"><h2 class="go-rail__h">From a local business</h2>' . ltef_ad_spot( 'rail', $area ? 'Reach people looking at ' . $area . ' places. One local business per page.' : '' ) . '</div>';
	}
	return ltef_wrap( '<div class="go-grid' . ( $rail ? ' go-grid--rail' : '' ) . '">' . ltef_place_facts( $id ) . $rail . '</div>', 'place-facts' );
}

function ltef_block_place_card( $attrs, $content, $block ) {
	$id = ltef_ctx_post( $block );
	return $id ? ltef_wrap( ltef_place_card( $id ) ) : '';
}

/** Places Tina ate at, newest post first. $food_only leaves out shops, wellness and the rest. */
function ltef_tina_places( $count = -1, $food_only = false ) {
	$q = array(
		'post_type'   => 'ltef_place',
		'post_status' => 'publish',
		'numberposts' => $count,
		'meta_key'    => 'ltef_tina_date', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'     => array( 'meta_value' => 'DESC', 'date' => 'DESC' ),
		'meta_query'  => array( array( 'key' => 'ltef_tina_date', 'value' => '', 'compare' => '!=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	if ( $food_only ) {
		$q['meta_query'][] = array( 'relation' => 'OR', array( 'key' => 'ltef_kind', 'compare' => 'NOT EXISTS' ), array( 'key' => 'ltef_kind', 'value' => array( '', 'eat' ), 'compare' => 'IN' ) );
	}
	return get_posts( $q );
}

function ltef_block_latest_places( $attrs ) {
	$posts = ltef_tina_places( max( 1, (int) ( $attrs['count'] ?? 4 ) ), true );
	if ( ! $posts ) {
		return ltef_wrap( '<p class="meta">Tina’s places show up here once they’re added under Places.</p>' );
	}
	return ltef_wrap( '<div class="cards">' . implode( '', array_map( function ( $p ) { return ltef_place_card( $p->ID ); }, $posts ) ) . '</div>' );
}

/** Explore is for eating: shops, salons, outings and the rest live on Beyond food. */
function ltef_block_explore() {
	$tina  = ltef_tina_places( -1, true );
	$rest  = array_values(
		array_filter(
			get_posts(
				array(
					'post_type'   => 'ltef_place',
					'post_status' => 'publish',
					'numberposts' => -1,
					'orderby'     => 'title',
					'order'       => 'ASC',
					'meta_query'  => array( 'relation' => 'OR', array( 'key' => 'ltef_tina_date', 'compare' => 'NOT EXISTS' ), array( 'key' => 'ltef_tina_date', 'value' => '' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			),
			function ( $p ) { return 'eat' === ltef_place_kind( $p->ID ); }
		)
	);
	$all   = array_merge( $tina, $rest );
	$rows  = array_map( function ( $p ) { return ltef_place_row( $p->ID ); }, $all );
	if ( count( $rows ) >= 4 ) {
		array_splice( $rows, 4, 0, array( ltef_ad_spot( 'feed' ) ) );
	}
	$opts = function ( $tax, $any ) {
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) );
		$o     = '<option value="">' . esc_html( $any ) . '</option>';
		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			$o .= '<option value="' . esc_attr( $t->slug ) . '">' . esc_html( $t->name ) . '</option>';
		}
		return $o;
	};
	$form = '<form class="explore-find panel" role="search" data-explore-form>'
		. '<div class="ltef-field"><label class="ltef-field__label" for="ex-q">Looking for a dish or a place?</label><input class="ltef-input" id="ex-q" type="search" placeholder="A dish or a place, like crudo or fufu" data-explore-q></div>'
		. '<div class="ltef-field"><label class="ltef-field__label" for="ex-craving">Craving</label><select class="ltef-input" id="ex-craving" data-explore-craving>' . $opts( 'ltef_craving', 'Anything' ) . '</select></div>'
		. '<div class="ltef-field"><label class="ltef-field__label" for="ex-area">Neighborhood</label><select class="ltef-input" id="ex-area" data-explore-area>' . $opts( 'ltef_area', 'Everywhere' ) . '</select></div>'
		. '<button class="ltef-chip" type="button" aria-pressed="false" data-explore-tina>Only places Tina’s been</button></form>';
	$beyond = ltef_beyond_food_places();
	$aside  = $beyond ? '<p class="meta explore-beyond">Looking for Tina’s shops, salons and days out? They’re on <a href="' . esc_url( home_url( '/beyond-food/' ) ) . '">Beyond food</a>.</p>' : '';
	$out  = $form . $aside . '<p class="meta" aria-live="polite" data-explore-count>' . count( $all ) . ' places</p><div class="explore-list">' . implode( '', $rows ) . '</div>'
		. '<p class="explore-more-wrap"><button class="ltef-btn ltef-btn--alt" type="button" data-explore-more hidden>Show more</button></p>'
		. '<p class="meta explore-empty" data-explore-empty hidden>Nothing matches. Try another craving or neighborhood, or <a href="' . esc_url( home_url( '/how-it-works/#ltef-suggest' ) ) . '">tell Tina where to go</a>.</p>';
	return ltef_wrap( $out, 'ltef-explore' );
}

/** Places Tina went that aren't for eating, grouped by kind, her newest visit first in each group. */
function ltef_beyond_food_places() {
	static $by_kind = null;
	if ( null !== $by_kind ) {
		return $by_kind;
	}
	$by_kind = array();
	$posts   = get_posts(
		array(
			'post_type'   => 'ltef_place',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
			'meta_query'  => array( array( 'key' => 'ltef_kind', 'value' => array_diff( array_keys( ltef_place_kinds() ), array( 'eat' ) ), 'compare' => 'IN' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	usort(
		$posts,
		function ( $a, $b ) {
			return strcmp( (string) ltef_meta( $b->ID, 'tina_date' ), (string) ltef_meta( $a->ID, 'tina_date' ) );
		}
	);
	foreach ( ltef_place_kinds() as $k => $label ) {
		$in = array_values( array_filter( $posts, function ( $p ) use ( $k ) { return ltef_place_kind( $p->ID ) === $k; } ) );
		if ( 'eat' !== $k && $in ) {
			$by_kind[ $k ] = $in;
		}
	}
	return $by_kind;
}

function ltef_block_beyond_food() {
	$by_kind = ltef_beyond_food_places();
	if ( ! $by_kind ) {
		return ltef_wrap( '<p class="meta">Shops, salons and days out show up here once a place’s Kind is set to something other than Food &amp; drink.</p>' );
	}
	$about = array(
		'shop'      => 'Thrift shops, antiques, markets and more.',
		'wellness'  => 'Nails, hair, lashes, skin, yoga, fitness and recovery.',
		'services'  => 'Photographers and other local services.',
		'fun'       => 'Shows, classes, games, museums and days out.',
		'community' => 'Rescues and nonprofits.',
	);
	$kinds = ltef_place_kinds();
	$jump  = '';
	$out   = '';
	foreach ( $by_kind as $k => $posts ) {
		$jump .= '<a class="ltef-chip" href="#' . esc_attr( $k ) . '">' . esc_html( $kinds[ $k ] ) . ' <span class="beyond-n">' . count( $posts ) . '</span></a>';
		$out  .= '<section class="beyond-group" id="' . esc_attr( $k ) . '" aria-labelledby="bf-' . esc_attr( $k ) . '"><div class="beyond-group__h"><h2 id="bf-' . esc_attr( $k ) . '" class="h-m">' . esc_html( $kinds[ $k ] ) . '</h2><p class="meta">' . esc_html( $about[ $k ] ?? '' ) . ' ' . sprintf( esc_html( _n( '%d place', '%d places', count( $posts ) ) ), count( $posts ) ) . '</p></div>'
			. '<div class="explore-list beyond-list">' . implode( '', array_map( function ( $p ) { return ltef_place_row( $p->ID ); }, $posts ) ) . '</div></section>';
	}
	return ltef_wrap( '<nav class="ltef-chips day-jump" aria-label="Jump to a kind of place">' . $jump . '</nav>' . $out, 'ltef-beyond' );
}

function ltef_live_woopons( $online ) {
	$posts = get_posts( array( 'post_type' => 'ltef_woopon', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
	$live  = array_filter(
		$posts,
		function ( $p ) use ( $online ) {
			$is_online = 'online' === ltef_meta( $p->ID, 'kind' );
			return ltef_woopon_live( $p->ID ) && $is_online === $online;
		}
	);
	usort(
		$live,
		function ( $a, $b ) {
			return ( 'vip' === ltef_meta( $b->ID, 'tier' ) ) <=> ( 'vip' === ltef_meta( $a->ID, 'tier' ) );
		}
	);
	return $live;
}

function ltef_block_woopons( $attrs ) {
	$online = 'online' === ( $attrs['show'] ?? 'local' );
	$live   = ltef_live_woopons( $online );
	if ( ! $live ) {
		return ltef_wrap( '<p class="meta">No Woo-pons live right now. <a href="' . esc_url( home_url( '/woo-pons/' ) ) . '#submit">Run one</a>: the first two weeks are free.</p>' );
	}
	$tickets = implode( '', array_map( function ( $p ) { return ltef_woopon_ticket( $p->ID ); }, $live ) );
	$chips   = '';
	if ( ! $online ) {
		$chips = '<div class="ltef-chips" role="group" aria-label="Filter deals"><button class="ltef-chip" type="button" data-deal-filter="all" aria-pressed="true">All deals</button><button class="ltef-chip" type="button" data-deal-filter="food" aria-pressed="false">Food &amp; drink</button><button class="ltef-chip" type="button" data-deal-filter="service" aria-pressed="false">Services</button><button class="ltef-chip" type="button" data-deal-filter="soon" aria-pressed="false">Ending soon</button></div>'
			. '<p class="meta" aria-live="polite"><span data-deal-count>' . count( $live ) . ' deals</span> · Businesses pay to list Woo-pons; that never buys a pick.</p>';
	}
	return ltef_wrap( $chips . '<div class="woopon-grid">' . $tickets . '</div>', 'ltef-woopons' );
}

function ltef_block_woopon_ticket( $attrs, $content, $block ) {
	$id = ltef_ctx_post( $block );
	return $id && 'ltef_woopon' === get_post_type( $id ) ? ltef_wrap( ltef_woopon_ticket( $id ) ) : '';
}

function ltef_block_event_facts( $attrs, $content, $block ) {
	$id = ltef_ctx_post( $block );
	if ( ! $id || 'ltef_event' !== get_post_type( $id ) ) {
		return '';
	}
	return ltef_wrap( ltef_event_facts( $id ) . ltef_excerpt_as_body( $id ), 'event-facts' );
}

function ltef_block_job_facts( $attrs, $content, $block ) {
	$id = ltef_ctx_post( $block );
	if ( ! $id || 'ltef_job' !== get_post_type( $id ) ) {
		return '';
	}
	return ltef_wrap( ltef_job_facts( $id ) . ltef_excerpt_as_body( $id ), 'job-facts' );
}

function ltef_block_ad_spot( $attrs ) {
	return ltef_wrap( ltef_ad_spot( sanitize_key( $attrs['spot'] ?? 'home' ) ) );
}

function ltef_block_events( $attrs ) {
	$posts = get_posts(
		array(
			'post_type'   => 'ltef_event',
			'post_status' => 'publish',
			'numberposts' => max( 1, (int) ( $attrs['count'] ?? 12 ) ),
			'meta_key'    => 'ltef_date', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'     => 'meta_value',
			'order'       => 'ASC',
			'meta_query'  => array( array( 'key' => 'ltef_date', 'value' => ltef_today(), 'compare' => '>=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$cal = ! empty( $attrs['calendar'] );
	if ( ! $posts ) {
		$empty = '<p class="meta">Nothing listed yet. <a href="' . esc_url( home_url( '/events/' ) ) . '#submit">Add your event</a>: listing is free.</p>';
		return ltef_wrap( $cal ? ltef_events_views( $empty ) : $empty );
	}
	// premium listings first within their week
	usort(
		$posts,
		function ( $a, $b ) {
			$wa = gmdate( 'oW', strtotime( ltef_meta( $a->ID, 'date' ) ) );
			$wb = gmdate( 'oW', strtotime( ltef_meta( $b->ID, 'date' ) ) );
			if ( $wa !== $wb ) {
				return strcmp( $wa, $wb );
			}
			$pa = (int) ltef_meta( $a->ID, 'premium' );
			$pb = (int) ltef_meta( $b->ID, 'premium' );
			return $pb <=> $pa ?: strcmp( ltef_meta( $a->ID, 'date' ), ltef_meta( $b->ID, 'date' ) );
		}
	);
	$list = '<div class="ev-list">' . implode( '', array_map( function ( $p ) { return ltef_event_row( $p->ID ); }, $posts ) ) . '</div>';
	return ltef_wrap( $cal ? ltef_events_views( $list ) : $list );
}

/** The List / Calendar switch around the list. The switch and calendar stay hidden until the script runs, so without it the list is all there is. */
function ltef_events_views( $list ) {
	return '<div class="ev-views" data-ltef-events><div class="ev-switch" role="group" aria-label="Show events as" hidden><button type="button" class="ltef-chip" data-ev-view="list" aria-pressed="true">List</button><button type="button" class="ltef-chip" data-ev-view="calendar" aria-pressed="false">Calendar</button></div>'
		. '<div class="ev-listview">' . $list . '</div>' . ltef_events_calendar() . '</div>';
}

/**
 * Month view: this month through the month of the last listed event (a year at most). A table of weeks on wide
 * screens, the same month as a list by date on phones. Today and past days are marked by the script, in the
 * visitor's own day, since the page may come from the cache.
 */
function ltef_events_calendar() {
	$first  = new DateTimeImmutable( wp_date( 'Y-m-01' ), wp_timezone() );
	$events = get_posts(
		array(
			'post_type'   => 'ltef_event',
			'post_status' => 'publish',
			'numberposts' => 300,
			'meta_key'    => 'ltef_date', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'     => 'meta_value',
			'order'       => 'ASC',
			'meta_query'  => array( array( 'key' => 'ltef_date', 'value' => $first->format( 'Y-m-d' ), 'compare' => '>=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$by   = array();
	$last = $first;
	foreach ( $events as $e ) {
		$d = (string) ltef_meta( $e->ID, 'date' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) {
			continue;
		}
		$by[ $d ][] = $e->ID;
		$m          = new DateTimeImmutable( substr( $d, 0, 7 ) . '-01', wp_timezone() );
		$last       = $m > $last ? $m : $last;
	}
	foreach ( $by as $d => $ids ) { // premium first within a day
		usort( $ids, function ( $a, $b ) { return (int) ltef_meta( $b, 'premium' ) <=> (int) ltef_meta( $a, 'premium' ); } );
		$by[ $d ] = $ids;
	}
	$count = min( 12, ( (int) $last->format( 'Y' ) - (int) $first->format( 'Y' ) ) * 12 + (int) $last->format( 'n' ) - (int) $first->format( 'n' ) + 1 );
	$heads = '';
	foreach ( array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ) as $wd ) {
		$heads .= '<th scope="col" abbr="' . esc_attr( $wd ) . '">' . esc_html( substr( $wd, 0, 3 ) ) . '</th>';
	}
	$link = function ( $id, $long ) {
		$prem = (int) ltef_meta( $id, 'premium' );
		$time = (string) ltef_meta( $id, 'time' );
		$more = $long ? implode( ' · ', array_filter( array( (string) ltef_meta( $id, 'venue' ), ltef_event_price( $id ) ) ) ) : '';
		return '<li class="cal-ev' . ( $prem ? ' is-prem' : '' ) . '"><a href="' . esc_url( get_permalink( $id ) ) . '">' . ( '' !== $time ? '<span class="cal-ev__t">' . esc_html( $time ) . '</span> ' : '' ) . '<span class="cal-ev__n">' . esc_html( get_the_title( $id ) ) . '</span>' . ( $prem ? '<span class="vh"> (premium listing)</span>' : '' ) . ( '' !== $more ? '<span class="cal-ev__m">' . esc_html( $more ) . '</span>' : '' ) . '</a></li>';
	};
	$months = '';
	for ( $i = 0; $i < $count; $i++ ) {
		$m     = $first->modify( '+' . $i . ' month' );
		$label = wp_date( 'F Y', $m->getTimestamp() );
		$days  = (int) $m->format( 't' );
		$lead  = (int) $m->format( 'w' );
		$cells = str_repeat( '<td class="cal-pad"></td>', $lead );
		$rows  = '';
		$agenda = '';
		for ( $day = 1; $day <= $days; $day++ ) {
			$iso  = $m->format( 'Y-m-' ) . str_pad( (string) $day, 2, '0', STR_PAD_LEFT );
			$ids  = $by[ $iso ] ?? array();
			$ts   = strtotime( $iso . ' 12:00' );
			$num  = '<span class="cal-num"><span class="vh">' . esc_html( wp_date( 'l, F', $ts ) ) . ' </span>' . $day . '</span>';
			$cells .= '<td class="cal-day' . ( $ids ? ' has-ev' : '' ) . '" data-date="' . esc_attr( $iso ) . '">' . $num . ( $ids ? '<ul class="cal-evs">' . implode( '', array_map( function ( $id ) use ( $link ) { return $link( $id, false ); }, $ids ) ) . '</ul>' : '' ) . '</td>';
			if ( $ids ) {
				$agenda .= '<li class="cal-agenda__day" data-date="' . esc_attr( $iso ) . '"><h4 class="cal-agenda__d">' . esc_html( wp_date( 'D, M j', $ts ) ) . '</h4><ul class="cal-evs">' . implode( '', array_map( function ( $id ) use ( $link ) { return $link( $id, true ); }, $ids ) ) . '</ul></li>';
			}
			if ( 0 === ( $lead + $day ) % 7 ) {
				$rows .= '<tr>' . $cells . '</tr>';
				$cells = '';
			}
		}
		if ( '' !== $cells ) {
			$rows .= '<tr>' . $cells . str_repeat( '<td class="cal-pad"></td>', 7 - ( $lead + $days ) % 7 ) . '</tr>';
		}
		$months .= '<section class="cal-month" data-month="' . esc_attr( $m->format( 'Y-m' ) ) . '" data-label="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '"' . ( $i ? ' hidden' : '' ) . '>'
			. '<table class="cal-grid"><caption class="vh">Events in ' . esc_html( $label ) . '</caption><thead><tr>' . $heads . '</tr></thead><tbody>' . $rows . '</tbody></table>'
			. ( '' !== $agenda ? '<ol class="cal-agenda">' . $agenda . '</ol>' : '<p class="meta cal-none">Nothing listed in ' . esc_html( wp_date( 'F', $m->getTimestamp() ) ) . ' yet. <a href="#submit">Add your event</a>: listing is free.</p>' )
			. '</section>';
	}
	$arrow = function ( $d ) {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . ( 'prev' === $d ? 'M15 5l-7 7 7 7' : 'M9 5l7 7-7 7' ) . '" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	};
	return '<div class="cal" data-ltef-cal hidden><div class="cal-bar"><button type="button" class="cal-step" data-cal-step="-1" aria-label="Previous month">' . $arrow( 'prev' ) . '</button>'
		. '<h3 class="h-m cal-title" aria-live="polite">' . esc_html( wp_date( 'F Y', $first->getTimestamp() ) ) . '</h3>'
		. '<button type="button" class="cal-step" data-cal-step="1" aria-label="Next month">' . $arrow( 'next' ) . '</button></div>' . $months . '</div>';
}

function ltef_block_jobs() {
	$posts = get_posts( array( 'post_type' => 'ltef_job', 'post_status' => 'publish', 'numberposts' => -1 ) );
	$posts = array_filter( $posts, function ( $p ) { $u = ltef_meta( $p->ID, 'until' ); return ! $u || $u >= ltef_today(); } );
	if ( ! $posts ) {
		return ltef_wrap( '<p class="meta">No jobs posted right now. <a href="#post-job">Post one</a>.</p>' );
	}
	$chips = '<div class="ltef-chips" role="group" aria-label="Kind of job"><button class="ltef-chip" type="button" data-job-filter="all" aria-pressed="true">All jobs</button><button class="ltef-chip" type="button" data-job-filter="front" aria-pressed="false">Front of house</button><button class="ltef-chip" type="button" data-job-filter="kitchen" aria-pressed="false">Kitchen</button><button class="ltef-chip" type="button" data-job-filter="manage" aria-pressed="false">Management</button><button class="ltef-chip" type="button" data-job-filter="part" aria-pressed="false">Part-time</button></div>';
	return ltef_wrap( $chips . '<div class="job-list">' . implode( '', array_map( function ( $p ) { return ltef_job_row( $p->ID ); }, $posts ) ) . '</div><p class="meta">Posts come from the businesses, and each is a paid post. A job post isn’t a pick: it never changes what Tina says about a place.</p>' );
}

function ltef_block_verdicts() {
	return ltef_wrap( '<div class="ltef-verdicts">' . ltef_verdict_html( 'favorite' ) . ltef_verdict_html( 'dont-miss' ) . '</div><p>No stars and no scores. When a place or a dish really gets her, Tina marks it <strong>Favorite</strong> (the star: she keeps going back) or <strong>Don’t miss</strong> (the target: order this one). Most places get neither, and that’s fine: the post and her words say the rest. Only Tina gives them, only for meals she ate, and nothing pays for one.</p>', 'verdicts-row' );
}

function ltef_block_roulette() {
	$places = get_posts( array( 'post_type' => 'ltef_place', 'post_status' => 'publish', 'numberposts' => -1 ) );
	$data   = array();
	foreach ( $places as $p ) {
		if ( 'eat' !== ltef_place_kind( $p->ID ) ) {
			continue; // the wheel is for where to eat
		}
		$cr     = get_the_terms( $p->ID, 'ltef_craving' );
		$wi     = get_the_terms( $p->ID, 'ltef_with' );
		$vs     = ltef_video_sources( $p->ID );
		$data[] = array(
			'id'       => $p->ID,
			'name'     => $p->post_title,
			'meta'     => ltef_place_meta_line( $p->ID ),
			'tina'     => ltef_tina_ate( $p->ID ),
			'month'    => ltef_month_label( ltef_meta( $p->ID, 'tina_date' ) ),
			'quote'    => (string) ltef_meta( $p->ID, 'tina_quote' ),
			'dish'     => (string) ltef_meta( $p->ID, 'dish' ),
			'url'      => $vs ? $vs[0]['url'] : (string) ltef_meta( $p->ID, 'tina_video' ),
			'href'     => get_permalink( $p->ID ),
			'maps'     => ltef_maps_url( $p->post_title, (string) ltef_meta( $p->ID, 'address' ), ltef_town_state( ltef_meta( $p->ID, 'town' ) ) ),
			'town'     => ltef_meta( $p->ID, 'town' ) ? (string) ltef_meta( $p->ID, 'town' ) : 'Worcester',
			'img'      => ltef_sticker_url( ltef_place_sticker( $p->ID ) ),
			'bg'       => 'var(--' . ltef_place_field( $p->ID ) . ')',
			'cravings' => ( $cr && ! is_wp_error( $cr ) ) ? wp_list_pluck( $cr, 'slug' ) : array(),
			'with'     => ( $wi && ! is_wp_error( $wi ) ) ? wp_list_pluck( $wi, 'slug' ) : array(),
		);
	}
	$chips = function ( $attr, $items ) {
		$h = '';
		$i = 0;
		foreach ( $items as $k => $v ) {
			$h .= '<button class="ltef-chip" type="button" data-' . $attr . '="' . esc_attr( $k ) . '" aria-pressed="' . ( 0 === $i++ ? 'true' : 'false' ) . '">' . esc_html( $v ) . '</button>';
		}
		return $h;
	};
	$cravings = array( '' => 'Anything' );
	foreach ( get_terms( array( 'taxonomy' => 'ltef_craving', 'hide_empty' => true ) ) as $t ) {
		if ( ! is_wp_error( $t ) && is_object( $t ) ) {
			$cravings[ $t->slug ] = $t->name;
		}
	}
	$withs = array( '' => 'Anyone' );
	foreach ( get_terms( array( 'taxonomy' => 'ltef_with', 'hide_empty' => true ) ) as $t ) {
		if ( is_object( $t ) && 'anyone' !== $t->slug ) {
			$withs[ $t->slug ] = $t->name;
		}
	}
	// Towns for the "Where" picker: Worcester first, then the rest A to Z.
	$towns = array_count_values( wp_list_pluck( $data, 'town' ) );
	uksort( $towns, function ( $a, $b ) { return ( 'Worcester' === $b ) <=> ( 'Worcester' === $a ) ?: strcasecmp( $a, $b ); } );
	$town_opts = '<option value="">Anywhere</option>';
	foreach ( $towns as $t => $n ) {
		$town_opts .= '<option value="' . esc_attr( $t ) . '">' . esc_html( $t ) . '</option>';
	}
	$wheel = function_exists( 'get_theme_file_uri' ) ? get_theme_file_uri( 'assets/img/stickers/roulette-wheel.svg' ) : '';
	$ptr   = function_exists( 'get_theme_file_uri' ) ? get_theme_file_uri( 'assets/img/stickers/roulette-pointer.svg' ) : '';
	$html  = '<div class="roul" data-roulette>'
		. '<div class="roul-left"><div class="roul-result" aria-live="polite">'
		. '<div class="roul-card roul-card--wait" data-state="spinning" hidden><p class="h-m">Spinning…</p><p>Picking from <span data-f="count"></span> places.</p></div>'
		. '<div class="roul-card roul-card--none" data-state="nomatch" hidden><p class="h-m">Nothing on the wheel for that yet.</p><p>Try another town or craving, say anyone’s coming, or spin from all restaurants.</p></div>'
		. '<article class="roul-card roul-card--pick" data-state="pick" hidden><div class="roul-card__labels"><span class="ltef-lbl ltef-lbl--tina" data-if="tina">' . ltef_icon( 'pen' ) . 'Tina ate here · <span data-f="month"></span></span><span class="ltef-lbl ltef-lbl--notyet" data-if="notyet">Tina hasn’t been yet</span></div>'
		. '<h2 class="roul-card__name" tabindex="-1" data-f="name"></h2><p class="meta" data-f="meta"></p>'
		. '<div data-if="tina"><div class="ordered"><span class="ordered__ph" data-f="bg"><img src="" alt="" data-f="img" width="56" height="56"></span><span class="ordered__txt"><span class="ordered__k">What Tina ordered</span><span class="ordered__dish" data-f="dish"></span></span></div><p class="ltef-note">“<span data-f="quote"></span>”</p></div>'
		. '<p data-if="notyet">Tina hasn’t eaten here yet, so there’s no video or take. It’s on the wheel because you picked all restaurants.</p>'
		. '<div class="roul-card__acts"><a class="ltef-btn" href="#" data-f="maps" target="_blank" rel="noopener">' . ltef_icon( 'pin' ) . 'Get directions</a><a class="ltef-btn ltef-btn--alt" href="#" data-f="video" data-if="tina" target="_blank" rel="noopener">' . ltef_icon( 'play' ) . 'Watch Tina’s video</a><a class="ltef-btn ltef-btn--alt" href="#" data-f="href">See the place</a></div></article></div>'
		. '<div class="roul-adjust"><div class="roul-group"><span class="roul-group__h">Spin from</span><div class="ltef-chips" role="group" aria-label="Which places">' . $chips( 'where', array( 'tina' => 'Where Tina ate', 'all' => 'All restaurants' ) ) . '</div></div>'
		. '<div class="roul-group"><label class="roul-group__h" for="roul-town">In or near</label><select class="ltef-input roul-town" id="roul-town" data-roul-town>' . $town_opts . '</select></div>'
		. '<div class="roul-group"><span class="roul-group__h">I’m in the mood for</span><div class="ltef-chips" role="group" aria-label="Craving">' . $chips( 'mood', $cravings ) . '</div></div>'
		. '<div class="roul-group"><span class="roul-group__h">Who’s coming</span><div class="ltef-chips" role="group" aria-label="Who’s coming">' . $chips( 'with', $withs ) . '</div></div></div></div>'
		. '<div class="roul-wheel"><div class="roul-wheel__disc"><img src="' . esc_url( $ptr ) . '" alt="" class="roul-pointer"><img src="' . esc_url( $wheel ) . '" alt="Restaurant Roulette wheel" data-wheel></div>'
		. '<button class="ltef-btn ltef-btn--alt roul-spin" type="button" data-spin>' . ltef_icon( 'spin' ) . '<span data-spin-label>Spin for it</span></button></div>'
		. '<script type="application/json" data-roulette-places>' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script></div>';
	return ltef_wrap( $html, 'ltef-roulette' );
}
