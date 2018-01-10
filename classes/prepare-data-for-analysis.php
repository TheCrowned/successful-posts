<?php

/**
 * Prepare for analysis functions.
 *
 * Extracts features from data to be latered analyzed.
 *
 * @package     SP
 * @copyright   2017
 * @author 		Stefano Ottolenghi
 */

class SP_prepare_data_for_analysis {
	public $raw_data = array();
	public $analyzed_data = array();
	public $clusters = array();

	function __construct( $raw_data, $clusters ) {
		$this->raw_data = $raw_data;
		$this->clusters = $clusters;
	}

	function execute_prepare_data( $criteria ) {
		$tstart = microtime( true );
		if( empty( $this->clusters ) ) return;

		foreach( $this->clusters as $cluster ) {
			if( ! isset( $cluster['points'] ) OR count( $cluster['points'] ) == 0 ) continue; //no points

			foreach( $cluster['points'] as $point ) {
				$post_id = $point['data']['post_id'];
				$post = $this->raw_data[$post_id];

				$post_tags = $this->raw_data[$post_id]->post_tags;
				if( empty( $post_tags ) ) $post_tags = '';
				
				$post_categories = $this->raw_data[$post_id]->post_categories;
				if( empty( $post_categories ) ) $post_categories = '';

				$post_videos = (int) preg_match_all( '/youtube.com|youtu.be/', $post->post_content, $array );
			
				if( isset( $post->sp_metrics[2] ) )
					$post_revenue = round( $post->sp_metrics[1] );
				else
					$post_revenue = 0;
				
				//Store basic info
				$this->store_analyzed_data( $post->ID, 'post_id', $post->ID );
				$this->store_analyzed_data( $post->ID, 'post_visits', round( $post->sp_metrics[0] ) );
				$this->store_analyzed_data( $post->ID, 'post_revenue', $post_revenue );
				$this->store_analyzed_data( $post->ID, 'post_length', strlen( strip_tags( $post->post_content ) ) );
				$this->store_analyzed_data( $post->ID, 'post_title', $post->post_title );
				$this->store_analyzed_data( $post->ID, 'post_content', $post->post_content );
				$this->store_analyzed_data( $post->ID, 'post_tags', $post_tags );
				$this->store_analyzed_data( $post->ID, 'post_categories', $post_categories );
				$this->store_analyzed_data( $post->ID, 'comment_count', (int) $post->comment_count );
				$this->store_analyzed_data( $post->ID, 'post_videos', $post_videos );

				//Custom criteria, call related callbacks
				foreach( $criteria as $single ) {
					call_user_func( $single['callback'], $post, $single['args'] );
				}

			}
		}

		SP_performance_debug( $tstart, 'prepare_data_for_analysis::execute_prepare_data' );
	}

	function get_prepared_data() {
		return $this->analyzed_data;
	}

	function store_analyzed_data( $post_id, $label, $data ) {
		if( ! isset( $this->analyzed_data[$post_id] ) )
			$this->analyzed_data[$post_id] = array();

		$this->analyzed_data[$post_id][$label] = $data;
	}

	function extract_and_count_html_tags( $post, $args ) {
		$html_tags = $args['html_tags'];
		
		if( empty( $post->post_content ) ) return;

		if( ! class_exists( 'DOMDocument' ) ) { echo 'NO DOMDOCUMENT CLASS'; return; }

		$doc = new DOMDocument();
		@$doc->loadHTML( $post->post_content ); //avoid warning because of badly formatted html

		foreach( $html_tags as $tag ) {
			$tag_count = $doc->getElementsByTagName( $tag ); //returns the count of found objects. If foreached, it's made up of DOMElement with details
			$this->store_analyzed_data( $post->ID, 'html_'.$tag.'_count', $tag_count->length );
		}
	}
}
