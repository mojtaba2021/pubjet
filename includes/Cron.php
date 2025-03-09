<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\enums\EnumPostStatus;
use triboon\pubjet\includes\enums\EnumPostTypes;

defined( 'ABSPATH' ) || exit;

class Cron extends Singleton {

	/**
	 * @return void
	 */
	public function init() {
		add_filter( 'cron_schedules', [ $this, 'registerInterval' ], 15 );
		add_action( 'wp', [ $this, 'registerCron' ], 15 );
		add_action( 'pubjet_sync_reportage_url', [ $this, 'runSyncReportageUrl' ], 15 );
		add_action( 'pubjet_publish_missed_schedule_posts', [ $this, 'publishMissedSchedulePosts' ] );
	}

	public function publishMissedSchedulePosts(): void {
		global $wpdb;

		$now    = date( 'Y-m-d H:i:s', current_time( 'U' ) );
		$sql    = $wpdb->prepare( "SELECT `ID` FROM $wpdb->posts WHERE `post_type` = %s AND post_status='future' AND post_date_gmt < %s",
			PUBJET_POST_TYPE, $now );
		$result = $wpdb->get_results( $sql );

		if ( $result ) {
			foreach ( $result as $post ) {
				if ( ! pubjet_is_reportage( $post->ID ) ) {
					continue; // Just publish reportage post
				}
				$postStatus = pubjet_should_publish_reportage_manually() ? EnumPostStatus::Pending : EnumPostStatus::Publish;
				pubjet_log( [ 'missed_reportage' => $post->ID, 'new_post_status' => $postStatus ] );
				wp_update_post( [
					'ID'          => $post->ID,
					'post_status' => $postStatus
				] );
			}
		}
	}

	/**
	 * @return void
	 */
	public function registerInterval( $schedules ) {
		for ( $i = 1; $i <= 60; $i ++ ) {
			$title                                   = str_replace( "%", "%d",
				__( 'Every % Minutes' ) );
			$schedules[ 'every_' . $i . '_minutes' ] = array(
				'interval' => $i * 60,
				'display'  => sprintf( $title, $i )
			);
		}

		return $schedules;
	}

	/**
	 * @return void
	 */
	public function runSyncReportageUrl() {
		$args = [
			'post_type'      => EnumPostTypes::Post,
			'meta_key'       => EnumPostMetakeys::FailedSyncUrl,
			'posts_per_page' => - 1,
		];

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$reportage_id = pubjet_find_reportage_id( $post->ID );
			if ( ! $reportage_id ) {
				continue;
			}
			$result = pubjet_publish_reportage( $post->ID, $reportage_id );
			if ( isset( $result['code'] ) && ( $result['code'] == 200 || $result['code'] == 429 ) ) {
				delete_post_meta( $post->ID, EnumPostMetakeys::FailedSyncUrl );
			}
		}
	}

	/**
	 * @return void
	 */
	public function registerCron() {
		if ( ! wp_next_scheduled( 'pubjet_sync_reportage_url' ) ) {
			wp_schedule_event( time(), 'every_1_minutes', 'pubjet_sync_reportage_url' );
		}

		if ( ! wp_next_scheduled( 'pubjet_publish_missed_schedule_posts' ) ) {
			wp_schedule_event( time(), 'every_10_minutes', 'pubjet_publish_missed_schedule_posts' );
		}
	}

}