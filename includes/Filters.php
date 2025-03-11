<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\helper\Cache;
use triboon\pubjet\includes\traits\Utils;

defined( 'ABSPATH' ) || exit;

class Filters extends Singleton {
	use Utils;

	/**
	 * @return void
	 */
	public function init() {
		add_filter( "views_edit-post", [ $this, "addReportageAdminPostsFilter" ], 15 );
		add_filter( "display_post_states", [ $this, "displayPostStates" ], 15, 2 );
		add_filter( 'parse_query', [ $this, "adminFilterPosts" ], 15 );
		// add_filter("the_content", [$this, "filterTheContent"], 0, 2);
		add_filter( "the_content", [ $this, "deleteFirstImage" ], 15, 2 );
		add_filter( "the_content", [ $this, "addReportageSource" ], 15, 2 );
		add_filter( 'post_row_actions', [ $this, 'regenerateThumbnail' ], 15, 2 );
		add_filter( 'post_class', [ $this, 'addPubjetClass' ], 15, 3 );
		add_filter( 'plugin_row_meta', [ $this, 'pluginRowMeta' ], 15, 2 );
		add_filter( 'plugin_action_links_' . PUBJET_PLUGIN_BASE, [ $this, 'pluginActionLinks' ], 15 );
		add_filter( 'https_ssl_verify', [ $this, 'noSslVerify' ], 15, 2 );
		add_filter( 'query_vars', [ $this, 'allowActionQueryVar' ], 15 );
	}

	public function addReportageAdminPostsFilter( $views ) {
		global $wpdb;
		$count = Cache::get( 'reportage_posts_count' );

		if ( $count === false ) {
			$posts = "SELECT COUNT(*) FROM {$wpdb->posts} as posts JOIN {$wpdb->postmeta} as meta ON meta.post_id = posts.ID where posts.post_type = 'post' AND posts.post_status IN ('publish', 'future', 'draft', 'pending') AND meta.meta_key = 'pubjet_reportage_id' ";
			$count = (int) $wpdb->get_var( $posts );
			Cache::set( 'reportage_posts_count', $count, DAY_IN_SECONDS );
		}

		$views['reportage'] = sprintf(
			'<a href="%s" class="%s">' . pubjet__( 'reportage' ) . ' <span class="count">(%d)</span></a>',
			admin_url( 'edit.php?post_type=' . get_query_var( 'post_type' ) . '&reportage=true' ),
			$this->get( 'reportage' ) === 'true' ? 'current' : '',
			$count
		);

		return $views;
	}

	/**
	 * @return void
	 */
	public function allowActionQueryVar( $query_vars ) {
		$query_vars[] = 'action';

		return $query_vars;
	}

	/**
	 * @param $verify
	 * @param $url
	 *
	 * @return false
	 */
	public function noSslVerify( $verify, $url ) {
		if ( strpos( $url, 'triboon' ) !== false ) {
			return false;
		}

		return $verify;
	}

	/**
	 * @return void
	 * @since  1.0
	 * @author Triboon
	 */
	public function pluginActionLinks( $links ) {
		array_unshift( $links,
			sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=pubjet_settings' ), 'تنظیمات' ),
		);

		return $links;
	}

	/**
	 * @return void
	 * @since  1.0
	 * @author Triboon
	 */
	public function pluginRowMeta( $plugin_meta, $plugin_file ) {
		if ( PUBJET_PLUGIN_BASE === $plugin_file ) {
			$row_meta    = [
				'website' => '<a href="https://www.triboon.net/%D8%AE%D8%B1%DB%8C%D8%AF-%D8%B1%D9%BE%D9%88%D8%B1%D8%AA%D8%A7%DA%98-%D8%A2%DA%AF%D9%87%DB%8C/" aria-label="خرید رپورتاژ اگهی" target="_blank">خرید رپورتاژ اگهی</a>',
			];
			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}

	/**
	 * @return string
	 */
	public function addPubjetClass( $classes, $class, $post_id ) {
		if ( ! pubjet_is_reportage( $post_id ) ) {
			return $classes;
		}
		$classes[] = "pubjet-post pubjet-reportage triboon-reportage";

		return $classes;
	}

	/**
	 * @return void
	 */
	public function regenerateThumbnail( $actions, $post ) {
		if ( ! pubjet_is_reportage( $post->ID ) ) {
			return $actions;
		}
		$actions['rethumb'] = sprintf( '<button type="button" class="button-link pubjet-regthumb" data-post-id="%s">تولید مجدد تصویر شاخص</button>',
			$post->ID );

		return $actions;
	}

	/**
	 * @param $post_states
	 * @param $post
	 *
	 * @return mixed
	 */
	public function displayPostStates( $post_states, $post ) {
		$reportage_id = pubjet_find_reportage_id( $post->ID );
		if ( ! empty( $reportage_id ) ) {
			$post_states[] = "رپورتاژ - " . intval( $reportage_id );
		}

		return $post_states;
	}

	/**
	 * @return string
	 */
	public function deleteFirstImage( $content ) {
		global $pubjet_settings;
		if ( ! pubjet_is_reportage( get_the_ID() ) ) {
			return $content;
		}
		$status = pubjet_isset_value( $pubjet_settings['deleteFirstImage'] );
		if ( ! $status ) {
			return $content;
		}
		// الگوی جستجو برای اولین عکس در محتوا
		$pattern = '/<p>\s*(<strong>\s*)?<img[^>]+>(\s*<\/strong>)?\s*<\/p>|<img[^>]+>/i';

		// جایگزینی اولین مطابقت با رشته خالی
		return preg_replace( $pattern, '', $content, 1 );
	}


	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function addReportageSource( $content ) {
		global $pubjet_settings;
		if ( ! pubjet_is_reportage( get_the_ID() ) ) {
			return $content;
		}
		$status = pubjet_isset_value( $pubjet_settings['addReportageSource'] );
		if ( ! $status ) {
			return $content;
		}

		libxml_use_internal_errors( true );
		$dom = new \DOMDocument();
		libxml_clear_errors();
		if ( ! $dom->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
			return $content;
		}

		$links        = $dom->getElementsByTagName( 'a' );
		$follow_links = [];
		foreach ( $links as $link ) {
			$rel  = $link->getAttribute( 'rel' );
			$href = $link->getAttribute( 'href' );
			if ( ! empty( $href ) && filter_var( $href, FILTER_VALIDATE_URL ) && ( empty( $rel ) || strpos( $rel,
						'nofollow' ) === false ) ) {
				$follow_links[] = $href;
			}
		}

		if ( empty( $follow_links ) ) {
			return $content;
		}

		$link_counts = array_count_values( $follow_links );
		arsort( $link_counts );
		$most_repeated_link = array_key_first( $link_counts );

		if ( ! $most_repeated_link ) {
			return $content;
		}

		$parsed_url  = parse_url( $most_repeated_link );
		$base_domain = preg_replace( '#^(www\.)#', '', $parsed_url['host'] );
		$content     .= '<p> منبع خبر : ' . esc_html( $base_domain ) . '</p>';

		return $content;
	}


	/**
	 * @param $content
	 *
	 * @return mixed|string
	 */
	public function filterTheContent( $content ) {

		if ( ! pubjet_show_copyright() ) {
			return $content;
		}

		if ( ! pubjet_is_reportage( get_the_ID() ) ) {
			return $content;
		}

		// Hide Triboon Tag
		$hide_triboon_tag = get_post_meta( get_the_ID(), EnumPostMetakeys::WithoutTriboonTag, true );
		if ( $hide_triboon_tag ) {
			return $content;
		}

		$content .= '<div class="pubjet-copyright"><p>انتشار از طریق پابجت <img src="' . PUBJET_DIR_URL . 'assets/img/logo.png' . '"></img></p></div>';

		return $content;
	}

	/**
	 * @param $query
	 *
	 * @return void
	 */
	public function adminFilterPosts( $query ) {
		global $pagenow;
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';

		if ( is_admin() &&
		     'post' == $post_type &&
		     'edit.php' == $pagenow &&
		     isset( $_GET['reportage'] ) &&
		     $_GET['reportage'] == 'true' ) {
			$query->query_vars['meta_key']     = 'pubjet_reportage_id';
			$query->query_vars['meta_value']   = '';
			$query->query_vars['meta_compare'] = '!=';
		}
	}

}