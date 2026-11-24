<?php
/**
 * Video thumbnails: the real first frame of each of Tina's videos, shown on the paused player instead of a sticker.
 *
 * The site saves a copy of each thumbnail in uploads/ltef-thumbs/ and serves it itself, so a page still loads
 * nothing from YouTube or TikTok until someone presses play. YouTube Shorts give a tall thumbnail at a fixed
 * address; TikTok gives one through its public oEmbed lookup. Instagram and Facebook need an app token for this,
 * so a video that is only there keeps its sticker.
 *
 * @package LTEF_Core
 */

defined( 'ABSPATH' ) || exit;

const LTEF_THUMBS_DIR = 'ltef-thumbs';

/** The file name stem for one copy of a video: "youtube-AbC…" or "tiktok-7709…". '' when the platform has no thumbnail for us. */
function ltef_thumb_key( $url ) {
	$pf = ltef_video_platform( $url );
	if ( 'youtube' === $pf && ltef_youtube_id( $url ) ) {
		return 'youtube-' . ltef_youtube_id( $url );
	}
	if ( 'tiktok' === $pf && ltef_tiktok_id( $url ) ) {
		return 'tiktok-' . ltef_tiktok_id( $url );
	}
	return '';
}

/** Where thumbnails live: array( dir, url ). */
function ltef_thumb_base() {
	$up = wp_upload_dir( null, false );
	return array( trailingslashit( $up['basedir'] ) . LTEF_THUMBS_DIR, trailingslashit( $up['baseurl'] ) . LTEF_THUMBS_DIR );
}

/** The saved thumbnail for a video key: array( path, url ) or null. */
function ltef_thumb_file( $key ) {
	if ( '' === $key ) {
		return null;
	}
	list( $dir, $url ) = ltef_thumb_base();
	foreach ( array( 'webp', 'jpg' ) as $ext ) {
		if ( file_exists( "$dir/$key.$ext" ) ) {
			return array( "$dir/$key.$ext", "$url/$key.$ext?v=" . filemtime( "$dir/$key.$ext" ) );
		}
	}
	return null;
}

/** The saved thumbnail URL for a video, from whichever of its copies has one. '' when none has been saved yet. */
function ltef_video_thumb_url( $sources ) {
	foreach ( (array) $sources as $s ) {
		$f = ltef_thumb_file( ltef_thumb_key( is_array( $s ) ? $s['url'] : $s ) );
		if ( $f ) {
			return $f[1];
		}
	}
	return '';
}

/** The cover image HTML for a paused player, or '' to keep the sticker. */
function ltef_video_thumb_img( $sources ) {
	$src = ltef_video_thumb_url( $sources );
	return $src ? '<img class="tt-cover" src="' . esc_url( $src ) . '" alt="" width="540" height="960" loading="lazy" decoding="async">' : '';
}

/** Where to download a copy's thumbnail from. Each entry is a URL to try in order. */
function ltef_thumb_remote( $url ) {
	$pf = ltef_video_platform( $url );
	if ( 'youtube' === $pf && ltef_youtube_id( $url ) ) {
		$id = ltef_youtube_id( $url );
		// oar2 is the tall Shorts frame. A regular video's wide frame would be mostly cropped away in the tall player.
		return false !== strpos( $url, '/shorts/' )
			? array( "https://i.ytimg.com/vi/$id/oar2.jpg" )
			: array( "https://i.ytimg.com/vi/$id/oar2.jpg", "https://i.ytimg.com/vi/$id/hqdefault.jpg" );
	}
	if ( 'tiktok' === $pf && ltef_tiktok_id( $url ) ) {
		$res = wp_remote_get( 'https://www.tiktok.com/oembed?url=' . rawurlencode( $url ), array( 'timeout' => 8 ) );
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		return ! empty( $data['thumbnail_url'] ) ? array( (string) $data['thumbnail_url'] ) : array();
	}
	return array();
}

/** Download and save one copy's thumbnail, sized for the player. Returns true when there is one saved now. */
function ltef_thumb_fetch( $url ) {
	$key = ltef_thumb_key( $url );
	if ( '' === $key ) {
		return false;
	}
	if ( ltef_thumb_file( $key ) ) {
		return true;
	}
	list( $dir ) = ltef_thumb_base();
	if ( ! wp_mkdir_p( $dir ) ) {
		return false;
	}
	foreach ( ltef_thumb_remote( $url ) as $remote ) {
		$res = wp_remote_get( $remote, array( 'timeout' => 10, 'limit_response_size' => 4 * MB_IN_BYTES ) );
		$img = is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_body( $res );
		// YouTube answers a missing frame with a 120×90 grey placeholder; anything that small isn't a real frame.
		if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) || strlen( $img ) < 3000 ) {
			continue;
		}
		$tmp = wp_tempnam( $key );
		file_put_contents( $tmp, $img ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$size = @getimagesize( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $size || $size[0] < 200 ) {
			wp_delete_file( $tmp );
			continue;
		}
		$ed = wp_get_image_editor( $tmp );
		if ( is_wp_error( $ed ) ) {
			wp_delete_file( $tmp );
			continue;
		}
		$ed->resize( 540, 960, false );
		$ed->set_quality( 78 );
		$webp = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
		$out  = $ed->save( "$dir/$key." . ( $webp ? 'webp' : 'jpg' ), $webp ? 'image/webp' : 'image/jpeg' );
		wp_delete_file( $tmp );
		if ( ! is_wp_error( $out ) ) {
			return true;
		}
	}
	return false;
}

/** Each of a place's videos as a list of its copies: her main video first, then her other videos. */
function ltef_place_video_groups( $post_id ) {
	$groups = array();
	$main   = array();
	foreach ( array( 'youtube', 'tina_video', 'instagram', 'facebook' ) as $k ) {
		$main[] = (string) ltef_meta( $post_id, $k );
	}
	$groups[] = array_values( array_filter( $main ) );
	foreach ( ltef_more_videos( $post_id ) as $v ) {
		$groups[] = array_values( array_filter( (array) $v['urls'] ) );
	}
	return array_values( array_filter( $groups ) );
}

/**
 * Save a thumbnail for each of a place's videos that has none yet, trying its copies YouTube first.
 * Returns how many of its videos still have no thumbnail.
 */
function ltef_place_fetch_thumbs( $post_id ) {
	$missing = 0;
	foreach ( ltef_place_video_groups( $post_id ) as $urls ) {
		usort(
			$urls,
			function ( $a, $b ) {
				return ( 'youtube' === ltef_video_platform( $b ) ) <=> ( 'youtube' === ltef_video_platform( $a ) );
			}
		);
		if ( ltef_video_thumb_url( $urls ) ) {
			continue;
		}
		$got = false;
		foreach ( $urls as $u ) {
			if ( ltef_thumb_fetch( $u ) ) {
				$got = true;
				break;
			}
		}
		$missing += $got ? 0 : 1;
	}
	update_post_meta( $post_id, 'ltef_thumbs_for', md5( wp_json_encode( ltef_place_video_groups( $post_id ) ) ) );
	update_post_meta( $post_id, 'ltef_thumbs_missing', $missing );
	return $missing;
}

/** Bookkeeping fields stay out of the place's Custom Fields box. */
add_filter(
	'is_protected_meta',
	function ( $protected, $key ) {
		return in_array( $key, array( 'ltef_thumbs_for', 'ltef_thumbs_missing' ), true ) ? true : $protected;
	},
	10,
	2
);

/** When a place is saved, fetch thumbnails for any new videos shortly after (not during the save). */
add_action(
	'save_post_ltef_place',
	function ( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return;
		}
		if ( ! wp_next_scheduled( 'ltef_thumbs_one', array( (int) $post_id ) ) ) {
			wp_schedule_single_event( time() + 45, 'ltef_thumbs_one', array( (int) $post_id ) );
		}
	}
);
add_action(
	'ltef_thumbs_one',
	function ( $post_id ) {
		$before = get_post_meta( $post_id, 'ltef_thumbs_missing', true );
		ltef_place_fetch_thumbs( (int) $post_id );
		if ( function_exists( 'ltef_money_clear_page_cache' ) && (string) $before !== (string) get_post_meta( $post_id, 'ltef_thumbs_missing', true ) ) {
			ltef_money_clear_page_cache();
		}
	}
);

/** Places not looked at since their videos last changed, a few at a time. Returns array( done, left ). */
function ltef_thumbs_batch( $limit = 25, $seconds = 20 ) {
	$start = time();
	$ids   = get_posts(
		array(
			'post_type'   => 'ltef_place',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'DESC',
		)
	);
	$todo = array();
	foreach ( $ids as $id ) {
		$groups = ltef_place_video_groups( $id );
		if ( $groups && get_post_meta( $id, 'ltef_thumbs_for', true ) !== md5( wp_json_encode( $groups ) ) ) {
			$todo[] = $id;
		}
	}
	$done = 0;
	foreach ( $todo as $id ) {
		if ( $done >= $limit || time() - $start > $seconds ) {
			break;
		}
		ltef_place_fetch_thumbs( $id );
		++$done;
	}
	if ( $done && function_exists( 'ltef_money_clear_page_cache' ) ) {
		ltef_money_clear_page_cache();
	}
	return array( $done, count( $todo ) - $done );
}

/** Every hour, catch up on a few places, until every video that can have a thumbnail has one. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ltef_thumbs_batch' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'ltef_thumbs_batch' );
		}
	}
);
add_action(
	'ltef_thumbs_batch',
	function () {
		ltef_thumbs_batch();
	}
);

/** For an admin to run the catch-up now, a batch per call: POST /wp-json/ltef/v1/thumbs. */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ltef/v1',
			'/thumbs',
			array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => function ( $req ) {
					$id = (int) $req->get_param( 'place' );
					if ( $id ) {
						delete_post_meta( $id, 'ltef_thumbs_for' );
						return array( 'missing' => ltef_place_fetch_thumbs( $id ) );
					}
					list( $done, $left ) = ltef_thumbs_batch( 25, 20 );
					return array( 'done' => $done, 'left' => $left );
				},
			)
		);
	}
);
