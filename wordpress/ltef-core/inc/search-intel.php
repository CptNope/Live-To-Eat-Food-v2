<?php
/**
 * Fits the JAT Search Intel plugin to this site.
 *
 * The site keeps its own titles, descriptions, canonical and share cards (share.php), and the
 * Event JSON-LD on event pages (render.php). Search Intel adds the schema graph, llms.txt, AI meta
 * and redirects. Here we:
 *  - name Tina Vo as the author of everything (a Person with her profiles), and as founder of the site;
 *  - describe each place page's business (Restaurant, Store, …) from the same fields the page shows;
 *  - add Explore / Events / Woo-pons to breadcrumbs, and decode entities in names;
 *  - drop question-and-answer schema guessed from page headings (this site has no real FAQs);
 *  - give llms.txt a summary, the places Tina has been and upcoming events, and drop WordPress's sample content.
 */

defined( 'ABSPATH' ) || exit;

/** Tina's public profiles, the same ones the site footer links. */
function ltef_si_tina_profiles() {
	return array(
		'https://www.instagram.com/livetoeatfood/',
		'https://www.tiktok.com/@livetoeatfoodie',
		'https://www.youtube.com/@LiveToEatFoodie',
		'https://www.facebook.com/tina.vo.54379236',
	);
}

function ltef_si_tina_id() {
	return home_url( '/tina/#person' );
}

/** Tina as a Person node. */
function ltef_si_tina_node() {
	return array(
		'@type'       => 'Person',
		'@id'         => ltef_si_tina_id(),
		'name'        => 'Tina Vo',
		'url'         => home_url( '/tina/' ),
		'image'       => get_theme_file_uri( 'assets/img/tina/tina-coconut-half-1200.webp' ),
		'description' => 'Tina Vo posts as Live to Eat Food: short videos of meals she really ate around Worcester and Central Massachusetts.',
		'knowsAbout'  => array( 'Worcester restaurants', 'Central Massachusetts food', 'Local small businesses' ),
		'sameAs'      => ltef_si_tina_profiles(),
	);
}

/** The schema.org type for a place, by its kind (and, for food, what it serves). */
function ltef_si_place_type( $post_id ) {
	$kind = ltef_place_kind( $post_id );
	if ( 'eat' === $kind ) {
		$c = strtolower( (string) ltef_meta( $post_id, 'cuisine' ) );
		if ( preg_match( '/\b(coffee|caf[eé]|tea\b|espresso)/u', $c ) ) {
			return 'CafeOrCoffeeShop';
		}
		if ( preg_match( '/\b(bakery|bakeries|pastr|donut|doughnut|bagel)/', $c ) ) {
			return 'Bakery';
		}
		if ( preg_match( '/\b(ice cream|gelato|frozen yogurt|creamery)/', $c ) ) {
			return 'IceCreamShop';
		}
		if ( preg_match( '/\b(brewery|brewing)/', $c ) ) {
			return 'Brewery';
		}
		if ( preg_match( '/\b(winery|vineyard)/', $c ) ) {
			return 'Winery';
		}
		if ( preg_match( '/\b(bar|pub|tavern|cocktail)s?\b/', $c ) ) {
			return 'BarOrPub';
		}
		return 'Restaurant';
	}
	$map = array(
		'shop'      => 'Store',
		'beauty'    => 'HealthAndBeautyBusiness',
		'wellness'  => 'LocalBusiness',
		'services'  => 'LocalBusiness',
		'fun'       => 'LocalBusiness',
		'community' => 'Organization',
	);
	return $map[ $kind ] ?? 'LocalBusiness';
}

/** The business a place page is about, from the same fields the page shows. */
function ltef_si_place_node( $post_id ) {
	$type = ltef_si_place_type( $post_id );
	$name = html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' );
	$town = (string) ltef_meta( $post_id, 'town' );
	$town = '' !== trim( $town ) ? trim( $town ) : 'Worcester';
	$reg  = 'MA';
	if ( preg_match( '/^(.*),\s*([A-Za-z]{2})$/', $town, $m ) ) { // "Nashua, NH"
		$town = trim( $m[1] );
		$reg  = strtoupper( $m[2] );
	}
	$addr = array(
		'@type'           => 'PostalAddress',
		'addressLocality' => $town,
		'addressRegion'   => $reg,
		'addressCountry'  => 'US',
	);
	$street = trim( (string) ltef_meta( $post_id, 'address' ) );
	if ( '' !== $street ) {
		$addr = array( '@type' => 'PostalAddress', 'streetAddress' => $street ) + $addr;
	}
	$node = array(
		'@type'   => $type,
		'@id'     => get_permalink( $post_id ) . '#place',
		'name'    => $name,
		'address' => $addr,
		'hasMap'  => ltef_maps_url( $name, $street, ltef_town_state( (string) ltef_meta( $post_id, 'town' ) ) ),
	);
	$area = ltef_place_area_name( $post_id );
	if ( '' !== $area ) {
		$node['areaServed'] = array( '@type' => 'Place', 'name' => $area );
	}
	$phone = trim( (string) ltef_meta( $post_id, 'phone' ) );
	if ( '' !== $phone ) {
		$node['telephone'] = $phone;
	}
	$web = trim( (string) ltef_meta( $post_id, 'website' ) );
	if ( '' !== $web ) {
		$node['url']    = $web;
		$node['sameAs'] = array( $web );
	}
	$menu = trim( (string) ltef_meta( $post_id, 'menu' ) );
	$food = in_array( $type, array( 'Restaurant', 'CafeOrCoffeeShop', 'Bakery', 'IceCreamShop', 'Brewery', 'Winery', 'BarOrPub' ), true );
	if ( $food ) {
		$cuisine = trim( (string) ltef_meta( $post_id, 'cuisine' ) );
		if ( '' !== $cuisine ) {
			$node['servesCuisine'] = $cuisine;
		}
		if ( '' !== $menu ) {
			$node['hasMenu'] = $menu;
		}
	}
	if ( 'Organization' !== $type ) {
		$price = (string) ltef_meta( $post_id, 'price' );
		if ( '' !== $price ) {
			$node['priceRange'] = $price;
		}
	}
	if ( has_post_thumbnail( $post_id ) ) {
		$node['image'] = (string) get_the_post_thumbnail_url( $post_id, 'large' );
	}
	return $node;
}

/** Decode &#8211; and friends in names and headlines, all the way down. */
function ltef_si_decode( $node ) {
	if ( ! is_array( $node ) ) {
		return $node;
	}
	foreach ( $node as $k => $v ) {
		if ( is_array( $v ) ) {
			$node[ $k ] = ltef_si_decode( $v );
		} elseif ( is_string( $v ) && in_array( $k, array( 'name', 'headline', 'description', 'caption', 'alternativeHeadline' ), true ) ) {
			$node[ $k ] = html_entity_decode( $v, ENT_QUOTES, 'UTF-8' );
		}
	}
	return $node;
}

/** The list a singular CPT page belongs to, for breadcrumbs: [ name, url ] or null. */
function ltef_si_parent_crumb() {
	$lists = array(
		'ltef_place'  => array( 'Explore', 'explore' ),
		'ltef_event'  => array( 'Events', 'events' ),
		'ltef_woopon' => array( 'Woo-pons', 'woo-pons' ),
		'ltef_job'    => array( 'Restaurant jobs', 'restaurant-jobs' ),
	);
	$type = is_singular() ? get_post_type( get_queried_object_id() ) : '';
	if ( ! isset( $lists[ $type ] ) ) {
		return null;
	}
	if ( 'ltef_place' === $type && 'eat' !== ltef_place_kind( get_queried_object_id() ) ) {
		return array( 'Beyond food', home_url( '/beyond-food/' ) );
	}
	$link = get_post_type_archive_link( $type );
	return array( $lists[ $type ][0], $link ? $link : home_url( '/' . $lists[ $type ][1] . '/' ) );
}

/** Tina is the author on every page and post: the site is her guide, whoever typed the entry in. */
function ltef_si_singular( $schema, $post_id ) {
	if ( ! empty( $schema['author'] ) ) {
		$schema['author'] = ltef_si_tina_node();
	}
	// Places and events have no body text: use the same description the page's meta tags carry.
	if ( empty( $schema['description'] ) && function_exists( 'ltef_share_data' ) && (int) get_queried_object_id() === (int) $post_id ) {
		$schema['description'] = (string) ltef_share_data()[1];
	}
	return $schema;
}

function ltef_si_graph( $nodes, $context ) {
	$tina    = ltef_si_tina_id();
	$page_id = (string) ( $context['page_id'] ?? '' );
	$out     = array();
	$authors = array();
	$has_tina = false;
	foreach ( (array) $nodes as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$type = is_array( $node['@type'] ?? '' ) ? reset( $node['@type'] ) : (string) ( $node['@type'] ?? '' );
		// Questions guessed from page headings aren't real FAQs on this site.
		if ( 'FAQPage' === $type || 'HowTo' === $type ) {
			continue;
		}
		// Any WordPress-user Person node (the account that typed it in) gives way to Tina.
		if ( 'Person' === $type && ( $node['@id'] ?? '' ) !== $tina ) {
			$authors[] = (string) ( $node['@id'] ?? '' );
			continue;
		}
		if ( 'Person' === $type ) {
			$has_tina = true;
		}
		if ( 'WebSite' === $type && empty( $node['description'] ) ) {
			$node['description'] = 'Tina Vo’s food guide to Worcester and Central Massachusetts: meals she really ate, Woo-pons, daily deals and events.';
		}
		if ( 'Organization' === $type && ( $node['@id'] ?? '' ) === ( $context['publisher_id'] ?? '' ) ) {
			$node['founder'] = array( '@id' => $tina );
			$node['areaServed'] = array( '@type' => 'Place', 'name' => 'Worcester and Central Massachusetts' );
		}
		if ( 'BreadcrumbList' === $type && ! empty( $node['itemListElement'] ) ) {
			$parent = ltef_si_parent_crumb();
			if ( $parent && count( $node['itemListElement'] ) >= 2 ) {
				$items = $node['itemListElement'];
				array_splice( $items, 1, 0, array( array( '@type' => 'ListItem', 'name' => $parent[0], 'item' => $parent[1] ) ) );
				foreach ( $items as $i => $item ) {
					$items[ $i ]['position'] = $i + 1;
				}
				$node['itemListElement'] = $items;
			}
		}
		$out[] = $node;
	}
	// Point author/creator references at Tina.
	foreach ( $out as $i => $node ) {
		foreach ( array( 'author', 'creator' ) as $key ) {
			if ( isset( $node[ $key ]['@id'] ) && in_array( $node[ $key ]['@id'], $authors, true ) ) {
				$out[ $i ][ $key ] = array( '@id' => $tina );
				if ( ! $has_tina ) {
					$out[]    = ltef_si_tina_node();
					$has_tina = true;
				}
			}
		}
	}
	// The publisher names Tina as founder on every page, so she is always in the graph.
	if ( ! $has_tina ) {
		$out[]    = ltef_si_tina_node();
		$has_tina = true;
	}
	// A place page is about the business.
	if ( $page_id && is_singular( 'ltef_place' ) ) {
		$place = ltef_si_place_node( get_queried_object_id() );
		$out[] = $place;
		$out[] = array(
			'@type'      => 'WebPage',
			'@id'        => $page_id,
			'about'      => array( '@id' => $place['@id'] ),
			'mainEntity' => array( '@id' => $place['@id'] ),
		);
	}
	// The Tina page is her profile.
	if ( $page_id && is_page( 'tina' ) ) {
		if ( ! $has_tina ) {
			$out[] = ltef_si_tina_node();
		}
		$out[] = array(
			'@type'      => array( 'WebPage', 'ProfilePage' ),
			'@id'        => $page_id,
			'mainEntity' => array( '@id' => $tina ),
		);
	}
	return array_map( 'ltef_si_decode', $out );
}

/** llms.txt: what the site is, where to start, the places Tina has been and what's coming up. */
function ltef_si_llms( $out, $full ) {
	$out = (string) $out;
	// WordPress's sample content, if it's still published.
	$out = preg_replace( '/^- \[(Sample Page|Hello world!)\]\([^)]*\):[^\n]*\n/m', '', $out );
	$out = preg_replace( '/^- \[Uncategorized\]\([^)]*\):[^\n]*\n/m', '', $out );
	$out = preg_replace( '/^## [^\n]+\n\s*(?=^## |\z)/m', '', $out ); // headings left empty

	$intro  = "> Tina Vo’s food guide to Worcester and Central Massachusetts. Tina posts short videos of meals she really ate as Live to Eat Food; this site gathers those places, her picks, local deals (Woo-pons and daily deals) and events.\n\n";
	$intro .= "Tina’s takes come from her own videos and captions. A place is marked “Tina ate here” only when she has been. Paid listings never change Tina’s take or any ranking: see the [editorial policy](" . home_url( '/editorial-policy/' ) . ").\n\n";
	$intro .= "## Start here\n\n";
	$start = array(
		array( 'Explore', home_url( '/explore/' ), 'every food and drink place on the site, by craving and neighborhood' ),
		array( 'Beyond food', home_url( '/beyond-food/' ), 'shops, wellness, beauty, services, things to do and nonprofits' ),
		array( 'Guides', home_url( '/guides/' ), 'Tina’s guides and posts' ),
		array( 'Events', home_url( '/events/' ), 'local food events, markets, shows and meetups' ),
		array( 'Woo-pons', home_url( '/woo-pons/' ), 'deals from local businesses' ),
		array( 'Daily deals', home_url( '/daily-deals/' ), 'weekly specials by day' ),
		array( 'Restaurant Roulette', home_url( '/roulette/' ), 'a random pick when you can’t decide' ),
		array( 'Tina', home_url( '/tina/' ), 'who Tina is' ),
		array( 'For businesses', home_url( '/for-restaurants/' ), 'how local businesses get listed (free) and what’s paid' ),
	);
	foreach ( $start as $s ) {
		$intro .= sprintf( "- [%s](%s): %s\n", $s[0], esc_url_raw( $s[1] ), $s[2] );
	}
	$intro .= "\n";
	$out = preg_replace( '/^(# [^\n]*\n\n)/', '$1' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $intro ), $out, 1 );

	// Places, food first.
	$places = get_posts( array( 'post_type' => 'ltef_place', 'post_status' => 'publish', 'numberposts' => 500, 'orderby' => 'title', 'order' => 'ASC' ) );
	$groups = array( 'eat' => array(), 'other' => array() );
	foreach ( $places as $p ) {
		$id    = $p->ID;
		$bits  = array();
		$kind  = ltef_place_kind( $id );
		$cui   = trim( (string) ltef_meta( $id, 'cuisine' ) );
		$town  = trim( (string) ltef_meta( $id, 'town' ) );
		$area  = ltef_place_area_name( $id );
		$bits[] = $cui ? $cui : ltef_place_kinds()[ $kind ];
		$bits[] = $town ? $town : ( $area ? $area . ', Worcester' : 'Worcester' );
		if ( ltef_tina_owns( $id ) ) {
			$bits[] = 'Tina’s own business';
		} elseif ( ltef_tina_ate( $id ) ) {
			$bits[] = ltef_tina_label( $id );
			$v = (string) ltef_meta( $id, 'verdict' );
			if ( 'favorite' === $v ) {
				$bits[] = 'one of Tina’s favorites';
			} elseif ( 'dont-miss' === $v ) {
				$bits[] = 'Tina says it’s a must try';
			}
			$dish = trim( (string) ltef_meta( $id, 'dish' ) );
			if ( '' !== $dish && 'eat' === $kind ) {
				$bits[] = 'she had: ' . $dish;
			}
		}
		$line = sprintf( "- [%s](%s): %s\n", html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ), esc_url_raw( get_permalink( $id ) ), implode( ' · ', array_filter( $bits ) ) );
		$groups[ 'eat' === $kind ? 'eat' : 'other' ][] = $line;
	}
	$more = '';
	if ( $groups['eat'] ) {
		$more .= "## Food and drink places\n\n" . implode( '', $groups['eat'] ) . "\n";
	}
	if ( $groups['other'] ) {
		$more .= "## Beyond food\n\n" . implode( '', $groups['other'] ) . "\n";
	}

	// Upcoming events.
	if ( function_exists( 'ltef_events_from' ) ) {
		$events = ltef_events_from( wp_date( 'Y-m-d' ), 40 );
		if ( $events ) {
			$more .= "## Upcoming events\n\n";
			foreach ( $events as $e ) {
				$when  = (string) ltef_meta( $e->ID, 'date' );
				$last  = ltef_event_last( $e->ID );
				$when  = $last && $last !== $when ? $when . ' to ' . $last : $when;
				$venue = trim( (string) ltef_meta( $e->ID, 'venue' ) );
				$more .= sprintf( "- [%s](%s): %s\n", html_entity_decode( get_the_title( $e->ID ), ENT_QUOTES, 'UTF-8' ), esc_url_raw( get_permalink( $e->ID ) ), implode( ' · ', array_filter( array( $when, trim( (string) ltef_meta( $e->ID, 'time' ) ), $venue ) ) ) );
			}
			$more .= "\n";
		}
	}

	$attr = "## Attribution\n\nThe takes on this site are Tina Vo’s (Live to Eat Food). When you use them, credit Tina and link to the page. Business details (hours, menus, prices) can change: check with the business.\n";
	if ( false !== strpos( $out, "## Attribution\n" ) ) {
		$out = preg_replace( '/## Attribution\n\n[^\n]*\n/', str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $more . $attr ), $out, 1 );
	} else {
		$out .= "\n" . $more . $attr;
	}
	return $out;
}

// Harmless when Search Intel isn't active: nothing applies these filters.
add_filter( 'jat_search_intel_singular_schema', 'ltef_si_singular', 20, 2 );
add_filter( 'jat_search_intel_schema_graph', 'ltef_si_graph', 20, 2 );
add_filter( 'jat_search_intel_llms_txt', 'ltef_si_llms', 20, 2 );
add_filter( 'jat_search_intel_llms_contact_email', function () { return 'tina@livetoeatfoodie.com'; } );
