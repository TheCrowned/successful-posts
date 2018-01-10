<?php

/**
 * Functions
 *
 * @package     SP
 * @copyright   2017
 * @author 		Stefano Ottolenghi
 */

class SP_functions {

	public static $response;

	static function get_all_posts() {
		$tstart = microtime(true);
		$args = array(
            'post_type' => 'any',
            'post_status' => array( 'publish' ),
            'posts_per_page' => 500,
            'ignore_sticky_posts' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false
        );

        $result = new WP_Query( $args );

		SP_performance_debug( $tstart, 'functions::get_all_posts' );

        return $result->posts;
	}

	static function get_posts_by_ids( $posts_ids ) {
		$tstart = microtime(true);
		$args = array(
            'post_type' => 'any',
            'posts_per_page' => -1,
            'ignore_sticky_posts' => 1,
            'post__in' => $posts_ids,
            'suppress_filters' => false
        );

        $result = new WP_Query( $args );

		SP_performance_debug( $tstart, 'functions::get_posts_by_ids' );

        return $result->posts;
	}

	function implement_relevant_metrics( $posts, $metrics ) {
		$tstart = microtime(true);

		foreach( $posts as &$post ) {
			if( ! is_a( $post, 'WP_Post' ) )
				$post = get_post( $post );

			$ID = $post->ID;

			//Visits
			$post->sp_metrics = array();
			$post->sp_metrics[0] = (int) $metrics['visits'][$ID];

			//Tags
			$raw_tags = get_the_terms( $post, 'post_tag' );
			$post_tags = array();
			if( $raw_tags ) {
				foreach( $raw_tags as $single )
					$post_tags[] = $single->name;
			}
			$post->post_tags = $post_tags;

			//Categories
			$raw_categories = get_the_terms( $post, 'category' );
			$post_categories = array();
			if( $raw_categories ) {
				foreach( $raw_categories as $category )
					$post_categories[] = $single->name;
			}
			$post->post_categories =$post_categories ;

			$post = apply_filters( 'sp_implement_relevant_metrics', $post );
		}

		SP_performance_debug( $tstart, 'functions::implement_relevant_metrics' );

		return $posts;
	}

	static function execute_one_batch() {
		//Stop at January 2016
		if( SP_google_analytics::get_last_request_time() < 1451606410 ) return;
		
		if( ! SP_google_analytics::get_pending_rows_count() ) {
			$request = SP_google_analytics::make_request_helper();
			
			if( $request->totalResults == 0 ) return;
		}
		
		$visits_data = SP_google_analytics::process_data_helper();
		
		if( empty( $visits_data ) ) return;
		
		$posts = SP_functions::get_posts_by_ids( array_keys( $visits_data ) );
		$posts_w_data = SP_functions::implement_relevant_metrics( $posts, array( 'visits' => $visits_data ) );

		$response = self::send_data_remote( $posts_w_data, SP_google_analytics::get_last_request_time() );

		self::$response = $response;
	}

	static function send_data_remote( $data, $last_request_time ) {
		$url = 'https://postpaycounter.com/sp/process.php';
		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'timeout'     => 10,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => array(
				'data' => serialize( $data ),
				'tend' => $last_request_time,
				'tstart' => $last_request_time + 30*86400,
				'site_url' => site_url(),
				'site_name' => get_bloginfo( 'name' )
			),
			'cookies'     => array()
			)
		);

		return $response;
	}
}
