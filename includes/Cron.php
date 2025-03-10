<?php

namespace triboon\pubjet\includes;

use triboon\pubjet\includes\enums\EnumBacklinkStatus;
use triboon\pubjet\includes\enums\EnumPostMetakeys;
use triboon\pubjet\includes\enums\EnumPostStatus;
use triboon\pubjet\includes\enums\EnumPostTypes;
use triboon\pubjet\includes\helper\Cache;

defined( 'ABSPATH' ) || exit;

class Cron extends Singleton {

	/**
	 * @return void
	 */
	public function init() {
		add_filter( 'cron_schedules', [ $this, 'registerInterval' ], 15 );
		add_action( 'wp', [ $this, 'registerCron' ], 15 );
		add_action( 'init', [ $this, 'runMissedScheduleEvents' ] );
		add_action( 'pubjet_sync_reportage_url', [ $this, 'runSyncReportageUrl' ], 15 );
		add_action( 'pubjet_publish_missed_schedule_posts', [ $this, 'publishMissedSchedulePosts' ] );
		add_action( 'pubjet_publish_future_backlinks', [ $this, 'publishFutureBacklinks' ] );
	}

	public function publishFutureBacklinks(): void {
		$futuresBacklinks = pubjet_db()->backlinks->findFutures();
		pubjet_log( $futuresBacklinks );
		if ( $futuresBacklinks && is_array( $futuresBacklinks ) ) {
			foreach ( $futuresBacklinks as $item ) {
				// Notify Triboon
				pubjet_publish_backlink_request( $item->backlink_id );
				// Update Database
				pubjet_db()->backlinks->update( $item->id, [
					'status' => EnumBacklinkStatus::Publish,
				] );
			}
		}
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
	public function runSyncReportageUrl(): void {
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

	public function runMissedScheduleEvents(): void {
		$doingCron = Cache::get( 'doing_cron' );

		if ( $doingCron === false ) {
			$dryRun  = false;
			$crons   = _get_cron_array();
			$egoTime = strtotime( '-2 minutes' );
			foreach ( $crons as $timestamp => $list ) {
				if ( $egoTime < $timestamp ) {
					continue;
				}

				foreach ( $list as $action => $cron ) {
					if ( strpos( $action, 'pubjet' ) !== 0 ) {
						continue;
					}
					$dryRun = true;

					foreach ( $cron as $sig => $event ) {
						//do_action( $action, $info['args'] );
						$this->forceScheduleSingleEvent( $action, $event['args'] );
					}
				}
			}

			if ( $dryRun ) {
				delete_transient( 'doing_cron' );
				spawn_cron();
			}

			Cache::set( 'doing_cron', true, MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Forcibly schedules a single event for the purpose of manually running it.
	 *
	 * This is used instead of `wp_schedule_single_event()` to avoid the duplicate check that's otherwise performed.
	 *
	 * @param  string  $hook  Action hook to execute when the event is run.
	 * @param  mixed[]  $args  Optional. Array containing each separate argument to pass to the hook's callback function.
	 *
	 * @return true|\WP_Error True if event successfully scheduled. WP_Error on failure.
	 * @copyright This method copied from wp-crontrol plugin force_schedule_single_event function
	 *
	 */
	public function forceScheduleSingleEvent( $hook, $args = array() ) {
		$event = (object) array(
			'hook'      => $hook,
			'timestamp' => 1,
			'schedule'  => false,
			'args'      => $args,
		);
		$crons = _get_cron_array();
		$key   = md5( serialize( $event->args ) );

		$crons[ $event->timestamp ][ $event->hook ][ $key ] = array(
			'schedule' => $event->schedule,
			'args'     => $event->args,
		);
		ksort( $crons );

		$result = _set_cron_array( $crons );

		// Not using the WP_Error from `_set_cron_array()` here so we can provide a more specific error message.
		if ( false === $result ) {
			return new \WP_Error(
				'could_not_add',
				sprintf(
				/* translators: %s: The name of the cron event. */
					__( 'Failed to schedule the cron event %s.', 'wp-crontrol' ),
					$hook
				)
			);
		}

		return true;
	}

	/**
	 * @param $schedules
	 *
	 * @return array
	 */
	public function registerInterval( $schedules ): array {
		$title = __( 'Every %d Minutes' );
		for ( $i = 1; $i <= 60; $i ++ ) {
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
	public function registerCron(): void {
		if ( ! wp_next_scheduled( 'pubjet_sync_reportage_url' ) ) {
			wp_schedule_event( time(), 'every_1_minutes', 'pubjet_sync_reportage_url' );
		}

		if ( ! wp_next_scheduled( 'pubjet_publish_missed_schedule_posts' ) ) {
			wp_schedule_event( time(), 'every_10_minutes', 'pubjet_publish_missed_schedule_posts' );
		}

		if ( ! wp_next_scheduled( 'pubjet_publish_future_backlinks' ) ) {
			wp_schedule_event( time(), 'every_5_minutes', 'pubjet_publish_future_backlinks' );
		}
	}
}