<?php
/**
 * Fields for each content type, the edit-screen box that holds them, and saving.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions: key => array( label, type, help, options, private ).
 * Private fields (contact details) never reach the REST API or the front end.
 */
function ltef_fields( $type ) {
	$f = array(
		'ltef_place'   => array(
			'address'     => array( 'Street address', 'text', 'Number and street, like 93 Shrewsbury St. The neighborhood is set under Neighborhoods.' ),
			'town'        => array( 'Town', 'text', 'Leave blank for Worcester.' ),
			'kind'        => array( 'What kind of place', 'select', 'Only food & drink places go on Restaurant Roulette and Tina’s latest picks.', array( 'eat' => 'Food & drink', 'shop' => 'Shop', 'wellness' => 'Wellness & beauty', 'services' => 'Services', 'fun' => 'Things to do', 'community' => 'Community' ) ),
			'cuisine'     => array( 'Cuisine (or what it is)', 'text', 'Short: Vietnamese, Neapolitan pizza, Coffee and pastries. For other places: Thrift shop, Hot yoga, Theater.' ),
			'price'       => array( 'Price', 'select', '', array( '' => '—', '$' => '$ (under $15)', '$$' => '$$ (under $30)', '$$$' => '$$$' ) ),
			'dish'        => array( 'What Tina ordered', 'text', 'From her post. Leave blank if she hasn’t been.' ),
			'tina_quote'  => array( 'Tina’s line', 'textarea', 'Her words from the post, exactly. Never written for her.' ),
			'tina_video'  => array( 'Tina’s video link', 'url', 'Where she first posted it: TikTok, Instagram, Facebook or YouTube. Filled in means Tina ate here.' ),
			'youtube'     => array( 'YouTube copy', 'url', 'Optional. A Shorts, youtu.be or watch link.' ),
			'instagram'   => array( 'Instagram copy', 'url', 'Optional. The reel or post link (instagram.com/reel/…).' ),
			'facebook'    => array( 'Facebook copy', 'url', 'Optional. Open the video on Facebook and copy the link from the address bar (facebook.com/reel/… or …/videos/…). The video must be Public. The site plays the first copy it has, in this order: YouTube, Instagram, Facebook, TikTok, and links to the others under the video.' ),
			'tina_date'   => array( 'Month she posted', 'month', 'Shown as “Tina ate here · Aug 2026”.' ),
			'more_videos' => array( 'More videos from here', 'textarea', 'Her other videos from this place, one per line: month | link | her caption. Example: 2026-08 | https://www.tiktok.com/@livetoeatfoodie/video/… | Another bread-cellent meal. Put a YouTube or Instagram copy in the same line after the first link, with a space between.' ),
			'verdict'     => array( 'Tina’s verdict', 'select', 'Only Tina picks this. Most places get neither.', array( '' => 'No verdict', 'favorite' => 'Favorite (star)', 'dont-miss' => 'Don’t miss (target)' ) ),
			'hosted'      => array( 'Hosted visit', 'checkbox', 'The place invited Tina and covered the meal. The page says so above her take.' ),
			'enhanced'    => array( 'Enhanced listing', 'checkbox', 'The owner pays for extra modules. Never changes Tina’s take or any ranking.' ),
			'phone'       => array( 'Phone', 'tel', '' ),
			'website'     => array( 'Website', 'url', '' ),
			'hours'       => array( 'Hours', 'text', 'Leave blank until someone checks them.' ),
			'hours_checked' => array( 'Hours checked on', 'date', '' ),
			'facts'       => array( 'Worth knowing', 'textarea', 'One fact per line.' ),
		),
		'ltef_woopon'  => array(
			'business'    => array( 'Business name', 'text', '' ),
			'place'       => array( 'Place on the site', 'place', 'Optional. Links the ticket to the place page and says whether Tina has eaten there.' ),
			'kind'        => array( 'Kind', 'select', 'Sets the ticket colour.', array( 'food' => 'Food & drink', 'service' => 'Service', 'online' => 'Online code' ) ),
			'tier'        => array( 'How it runs', 'select', '', array( 'free' => 'Try it free (2 weeks, once)', 'weekly' => 'Weekly', 'vip' => 'VIP Woo-pon (in Tina’s monthly video)', 'partner' => 'Partner code (Tina earns a commission)' ) ),
			'area_text'   => array( 'Neighborhood (shown on the ticket)', 'text', '' ),
			'code'        => array( 'Code', 'text', '' ),
			'start'       => array( 'Start date', 'date', '' ),
			'end'         => array( 'End date', 'date', 'The ticket comes down after this day.' ),
			'exclusions'  => array( 'Exclusions', 'textarea', '' ),
			'fine'        => array( 'Fine print', 'textarea', '' ),
			'shop_url'    => array( 'Shop link (online codes)', 'url', '' ),
			'flyer'       => array( 'Flyer for the video', 'attachment', 'Required for VIP Woo-pons.' ),
			'contact_phone' => array( 'Contact phone', 'tel', 'Private.', null, true ),
			'contact_email' => array( 'Contact email', 'email', 'Private.', null, true ),
		),
		'ltef_event'   => array(
			'date'        => array( 'Date', 'date', '' ),
			'time'        => array( 'Time', 'text', 'Like 6–10pm.' ),
			'venue'       => array( 'Where', 'text', 'The place and the neighborhood.' ),
			'price_text'  => array( 'Price', 'text', 'Free, $35, Pay your own way…' ),
			'link'        => array( 'Details or tickets link', 'url', '' ),
			'kind'        => array( 'Kind', 'select', '', array( 'food' => 'Food or drink', 'market' => 'Market or fair', 'music' => 'Music or a show', 'art' => 'Art or a class', 'family' => 'Family', 'other' => 'Something else' ) ),
			'premium'     => array( 'Premium listing (' . ltef_money( ltef_setting( 'event_premium' ) ) . ' a month)', 'checkbox', 'Pinned at the top of its week, labeled.' ),
			'tina'        => array( 'Tina’s own event', 'checkbox', 'Shows her avatar.' ),
			'contact_phone' => array( 'Contact phone', 'tel', 'Private.', null, true ),
			'contact_email' => array( 'Contact email', 'email', 'Private.', null, true ),
		),
		'ltef_job'     => array(
			'business'    => array( 'Business', 'text', '' ),
			'area_text'   => array( 'Neighborhood', 'text', '' ),
			'job_kind'    => array( 'Kind of job', 'select', '', array( 'front' => 'Front of house', 'kitchen' => 'Kitchen', 'manage' => 'Management' ) ),
			'hours_type'  => array( 'Hours', 'select', '', array( 'ft' => 'Full-time', 'pt' => 'Part-time', 'seasonal' => 'Seasonal' ) ),
			'pay'         => array( 'Pay', 'text', '' ),
			'how_to_apply' => array( 'How to apply', 'text', 'An email, a link, or “walk in Tuesday 2–4pm”.' ),
			'until'       => array( 'Listed until', 'date', 'Job posts are ' . ltef_money( ltef_setting( 'job_post' ) ) . ' a month.' ),
			'contact_phone' => array( 'Contact phone', 'tel', 'Private.', null, true ),
			'contact_email' => array( 'Contact email', 'email', 'Private.', null, true ),
		),
		'ltef_ad'      => array(
			'spot'        => array( 'Spot', 'select', '', array( 'home' => 'Home page, under Tina’s latest picks', 'rail' => 'Place pages, beside the facts', 'feed' => 'A card in Explore', 'guide' => 'Between stops on a guide' ) ),
			'line'        => array( 'One line, in the advertiser’s words', 'text', '' ),
			'url'         => array( 'Link', 'url', '' ),
			'cta'         => array( 'Button text', 'text', 'Visit [business], See the offer…' ),
			'start'       => array( 'Runs from', 'date', '' ),
			'end'         => array( 'Runs until', 'date', 'After this day the spot shows “Ad space open” again.' ),
		),
	);
	return isset( $f[ $type ] ) ? $f[ $type ] : array();
}

add_action( 'init', 'ltef_register_meta', 20 );
function ltef_register_meta() {
	foreach ( array( 'ltef_place', 'ltef_woopon', 'ltef_event', 'ltef_job', 'ltef_ad' ) as $type ) {
		foreach ( ltef_fields( $type ) as $key => $def ) {
			$private = ! empty( $def[4] );
			$kind    = $def[1];
			register_post_meta(
				$type,
				'ltef_' . $key,
				array(
					'type'              => in_array( $kind, array( 'checkbox' ), true ) ? 'boolean' : ( in_array( $kind, array( 'place', 'attachment' ), true ) ? 'integer' : 'string' ),
					'single'            => true,
					'show_in_rest'      => ! $private,
					'sanitize_callback' => 'ltef_sanitize_' . ( in_array( $kind, array( 'checkbox', 'place', 'attachment', 'url', 'email', 'textarea' ), true ) ? $kind : 'text' ),
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}

function ltef_sanitize_text( $v ) { return sanitize_text_field( (string) $v ); }
function ltef_sanitize_textarea( $v ) { return sanitize_textarea_field( (string) $v ); }
function ltef_sanitize_url( $v ) { return esc_url_raw( (string) $v ); }
function ltef_sanitize_email( $v ) { return sanitize_email( (string) $v ); }
function ltef_sanitize_checkbox( $v ) { return (bool) $v; }
function ltef_sanitize_place( $v ) { return absint( $v ); }
function ltef_sanitize_attachment( $v ) { return absint( $v ); }

add_action( 'add_meta_boxes', 'ltef_meta_boxes' );
function ltef_meta_boxes() {
	$titles = array( 'ltef_place' => 'Place details', 'ltef_woopon' => 'The deal', 'ltef_event' => 'Event details', 'ltef_job' => 'Job details', 'ltef_ad' => 'The ad' );
	foreach ( $titles as $type => $title ) {
		add_meta_box( 'ltef-fields', $title, 'ltef_render_meta_box', $type, 'normal', 'high' );
	}
}

function ltef_render_meta_box( $post ) {
	wp_nonce_field( 'ltef_save_fields', 'ltef_fields_nonce' );
	echo '<div class="ltef-fields" style="display:grid;gap:14px;max-width:760px">';
	foreach ( ltef_fields( $post->post_type ) as $key => $def ) {
		list( $label, $kind ) = $def;
		$help  = isset( $def[2] ) ? $def[2] : '';
		$opts  = isset( $def[3] ) ? $def[3] : null;
		$id    = 'ltef_' . $key;
		$value = get_post_meta( $post->ID, $id, true );
		echo '<p style="margin:0">';
		if ( 'checkbox' === $kind ) {
			printf( '<label><input type="checkbox" name="%1$s" value="1" %2$s> <strong>%3$s</strong></label>', esc_attr( $id ), checked( (bool) $value, true, false ), esc_html( $label ) );
		} else {
			printf( '<label for="%1$s"><strong>%2$s</strong></label><br>', esc_attr( $id ), esc_html( $label ) );
			if ( 'select' === $kind ) {
				printf( '<select id="%1$s" name="%1$s">', esc_attr( $id ) );
				foreach ( $opts as $v => $t ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $v ), selected( (string) $value, (string) $v, false ), esc_html( $t ) );
				}
				echo '</select>';
			} elseif ( 'textarea' === $kind ) {
				printf( '<textarea id="%1$s" name="%1$s" rows="3" class="large-text">%2$s</textarea>', esc_attr( $id ), esc_textarea( $value ) );
			} elseif ( 'place' === $kind ) {
				$places = get_posts( array( 'post_type' => 'ltef_place', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) );
				printf( '<select id="%1$s" name="%1$s"><option value="0">—</option>', esc_attr( $id ) );
				foreach ( $places as $p ) {
					printf( '<option value="%d" %s>%s</option>', $p->ID, selected( (int) $value, $p->ID, false ), esc_html( $p->post_title ) );
				}
				echo '</select>';
			} elseif ( 'attachment' === $kind ) {
				if ( $value ) {
					$url = wp_get_attachment_url( (int) $value );
					printf( '<a href="%s" target="_blank" rel="noopener">%s</a> ', esc_url( $url ), esc_html( basename( (string) $url ) ) );
				}
				printf( '<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text"> <span class="description">Media library ID</span>', esc_attr( $id ), esc_attr( $value ) );
			} else {
				$type = 'month' === $kind ? 'month' : ( in_array( $kind, array( 'date', 'url', 'email', 'tel' ), true ) ? $kind : 'text' );
				printf( '<input type="%3$s" id="%1$s" name="%1$s" value="%2$s" class="regular-text">', esc_attr( $id ), esc_attr( $value ), esc_attr( $type ) );
			}
		}
		if ( $help ) {
			printf( '<br><span class="description">%s</span>', esc_html( $help ) );
		}
		echo '</p>';
	}
	echo '</div>';
}

add_action( 'save_post', 'ltef_save_meta', 10, 2 );
function ltef_save_meta( $post_id, $post ) {
	if ( ! isset( $_POST['ltef_fields_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ltef_fields_nonce'] ) ), 'ltef_save_fields' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( ltef_fields( $post->post_type ) as $key => $def ) {
		$id = 'ltef_' . $key;
		if ( 'checkbox' === $def[1] ) {
			update_post_meta( $post_id, $id, ! empty( $_POST[ $id ] ) );
			continue;
		}
		if ( isset( $_POST[ $id ] ) ) {
			$raw = wp_unslash( $_POST[ $id ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by the registered callback.
			update_post_meta( $post_id, $id, $raw );
		}
	}
}
