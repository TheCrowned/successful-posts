<?php

/**
 * Analysis functions.
 *
 * @package     SP
 * @copyright   2017
 * @author 		Stefano Ottolenghi
 */

define( 'SUCCESS_THREHSOLD', '0.67' ); //0.67 means that successful posts should have at least 1.5 = 2/3 more of a given metric in respect to non-successful ones

class SP_analyze_data {
	public $data_to_analyze = array();
	public $clusters = array();
	public $success_metrics = array();

	function __construct( $analysis_methods, $clusters, $data_to_analyze ) {
		$tstart = microtime(true);

		$this->data_to_analyze = $data_to_analyze;
		$this->clusters = $clusters;

		foreach( $analysis_methods as $method ) {
			call_user_func( $method['callback'], $method['args'] );
		}

		SP_performance_debug( $tstart, 'analysis_functions::construct' );
	}

	static function html_tag( $args ) {
		$count_best = $count_worst = 0;
		$tag = $args['tag'];

		if( count( $this->data_to_analyze['best_points'] ) == 0 ) return;

		foreach( $this->data_to_analyze['best_points'] as $post ) {
			if( isset( $post['html_'.$tag.'_count'] ) AND $post['html_'.$tag.'_count'] != 0 )
				$count_best += $post['html_'.$tag.'_count'];
		}

		foreach( $this->data_to_analyze['worst_points'] as $post ) {
			if( isset( $post['html_'.$tag.'_count'] ) AND $post['html_'.$tag.'_count'] != 0 )
				$count_worst += $post['html_'.$tag.'_count'];
		}

		$average_best = $count_best / count( $this->data_to_analyze['best_points'] );
		$average_worst = $count_worst / count( $this->data_to_analyze['worst_points'] );

		if( $average_best != 0 )
			$ratio = $average_worst / $average_best;
		else
			$ratio = 1000;
		
		if( count( $this->data_to_analyze['worst_points'] ) == 0 ) {
			$successful_metric = 1;
		} else {
			if( ( $ratio ) <= SUCCESS_THREHSOLD )
				$successful_metric = 1;
			else
				$successful_metric = 0;
		}		

		if( $successful_metric )
			$this->success_metrics[] = 'html_'.$tag.'_count';

		self::print_analysis_result( 'HTML tag '.$tag, $average_best, $average_worst, $ratio );
	}

	static function post_length( $args ) {
		$count_best = $count_worst = 0;

		if( count( $this->data_to_analyze['best_points'] ) == 0 ) return;

		foreach( $this->data_to_analyze['best_points'] as $post ) {
			if( isset( $post['post_length'] ) )
				$count_best += $post['post_length'];
		}

		foreach( $this->data_to_analyze['worst_points'] as $post ) {
			if( isset( $post['post_length'] ) )
				$count_worst += $post['post_length'];
		}

		$average_best = $count_best / count( $this->data_to_analyze['best_points'] );
		$average_worst = $count_worst / count( $this->data_to_analyze['worst_points'] );

		if( $average_best != 0 )
			$ratio = $average_worst / $average_best;
		else
			$ratio = 1000;
		
		if( count( $this->data_to_analyze['worst_points'] ) == 0 ) {
			$successful_metric = 1;
		} else {
			if( ( $ratio ) <= ( SUCCESS_THREHSOLD ) )
				$successful_metric = 1;
			else
				$successful_metric = 0;
		}		

		if( $successful_metric )
			$this->success_metrics[] = 'post_length';

		self::print_analysis_result( 'Post length (with HTML tags stripped)', $average_best, $average_worst, $ratio );
	}

	static function paragraph_density( $args ) {
		$count_best = $count_worst = 0;

		if( count( $this->data_to_analyze['best_points'] ) == 0 ) return;

		foreach( $this->data_to_analyze['best_points'] as $post ) {
			if( isset( $post['post_length'] ) AND isset( $post['html_p_count'] ) ) {
				if( $post['html_p_count'] == 0 ) $post['html_p_count'] = 1;
				$count_best += $post['post_length'] / $post['html_p_count'];
			}
		}

		foreach( $this->data_to_analyze['worst_points'] as $post ) {
			if( isset( $post['post_length'] ) AND isset( $post['html_p_count'] ) ) {
				if( $post['html_p_count'] == 0 ) $post['html_p_count'] = 1;
				$count_worst += $post['post_length'] / $post['html_p_count'];
			}
		}

		$average_best = $count_best / count( $this->data_to_analyze['best_points'] );
		$average_worst = $count_worst / count( $this->data_to_analyze['worst_points'] );

		if( $average_best != 0 )
			$ratio = $average_worst / $average_best;
		else
			$ratio = 1000;
		
		if( count( $this->data_to_analyze['worst_points'] ) == 0 ) {
			$successful_metric = 1;
		} else {
			if( ( $ratio ) <= ( SUCCESS_THREHSOLD ) )
				$successful_metric = 1;
			else
				$successful_metric = 0;
		}		

		if( $successful_metric )
			$this->success_metrics[] = 'paragraph_density';

		self::print_analysis_result( 'Paragraph density', $average_best, $average_worst, $ratio );
	}

	static function post_videos( $args ) {
		$count_best = $count_worst = 0;

		if( count( $this->data_to_analyze['best_points'] ) == 0 ) return;

		foreach( $this->data_to_analyze['best_points'] as $post ) {
			if( isset( $post['post_videos'] ) )
				$count_best += $post['post_videos'];
		}

		foreach( $this->data_to_analyze['worst_points'] as $post ) {
			if( isset( $post['post_videos'] ) )
				$count_worst += $post['post_videos'];
		}

		$average_best = $count_best / count( $this->data_to_analyze['best_points'] );
		$average_worst = $count_worst / count( $this->data_to_analyze['worst_points'] );

		if( $average_best != 0 )
			$ratio = $average_worst / $average_best;
		else
			$ratio = 1000;
			
		if( count( $this->data_to_analyze['worst_points'] ) == 0 ) {
			$successful_metric = 1;
		} else {
			if( ( $ratio ) <= ( SUCCESS_THREHSOLD ) )
				$successful_metric = 1;
			else
				$successful_metric = 0;
		}		

		if( $successful_metric )
			$this->success_metrics[] = 'post_videos';

		self::print_analysis_result( 'Post videos', $average_best, $average_worst, $ratio );
	}

	static function post_tags( $args ) {
		$count_best = $count_worst = array();

		if( count( $this->data_to_analyze['best_points'] ) == 0 ) return;

		foreach( $this->data_to_analyze['best_points'] as $post ) {
			if( isset( $post['post_tags'] ) AND is_array( $post['post_tags'] ) ) {
				foreach( $post['post_tags'] as $tag ) {
					if( isset( $count_best[$tag] ) )
						$count_best[$tag]++;
					else
						$count_best[$tag] = 1;
				}
			}
		}

		foreach( $this->data_to_analyze['worst_points'] as $post ) {
			if( isset( $post['post_tags'] ) AND is_array( $post['post_tags'] ) ) {
				foreach( $post['post_tags'] as $tag ) {
					if( isset( $count_worst[$tag] ) )
						$count_worst[$tag]++;
					else
						$count_worst[$tag] = 1;
				}
			}
		}

		//Replace counts with means
		array_walk( $count_best, function( &$value, $key, $n ) {
			$value /= $n;
		}, count( $this->data_to_analyze['best_points'] )  );
		array_walk( $count_worst, function( &$value, $key, $n ) {
			$value /= $n;
		}, count( $this->data_to_analyze['worst_points'] )  );

		arsort( $count_best );
		arsort( $count_worst );

		//Calculates ratios (which are actually differences for each tag
		$ratio = $count_best;
		array_walk( $ratio, function( &$value, $key, $args ) {
			if( ! isset( $args['count_worst'][$key] ) ) $args['count_worst'][$key] = 0;
			$value = $value - $args['count_worst'][$key];
		}, array( 'count_worst' => $count_worst ) );

		arsort( $ratio );

		foreach( $ratio as $key => $value ) {
			if( $value > 0.10 ) {
				$this->success_metrics[] = 'post_tag_'.$key;

				if( ! isset( $count_worst[$key] ) ) $count_worst[$key] = 0;
				echo '<p>Post <strong>tag '.$key.'</strong> is present in '.( round( $count_best[$key], 2 ) * 100 ).'% of successful posts and in '.( round( $count_worst[$key], 2 ) * 100 ).'% of non-successful ones.</p>';
			}
		}
	}

	static function post_categories( $args ) {
		$count_best = $count_worst = array();

		if( count( $this->data_to_analyze['best_points'] ) == 0 ) return;

		foreach( $this->data_to_analyze['best_points'] as $post ) {
			if( isset( $post['post_categories'] ) AND is_array( $post['post_categories'] ) ) {
				foreach( $post['post_categories'] as $category ) {
					if( isset( $count_best[$category] ) )
						$count_best[$category]++;
					else
						$count_best[$category] = 1;
				}
			}
		}

		foreach( $this->data_to_analyze['worst_points'] as $post ) {
			if( isset( $post['post_categories'] ) AND is_array( $post['post_categories'] ) ) {
				foreach( $post['post_categories'] as $category ) {
					if( isset( $count_worst[$category] ) )
						$count_worst[$category]++;
					else
						$count_worst[$category] = 1;
				}
			}
		}

		//Replace counts with means
		array_walk( $count_best, function( &$value, $key, $n ) {
			$value /= $n;
		}, count( $this->data_to_analyze['best_points'] )  );
		array_walk( $count_worst, function( &$value, $key, $n ) {
			$value /= $n;
		}, count( $this->data_to_analyze['worst_points'] )  );

		arsort( $count_best );
		arsort( $count_worst );

		$ratio = $count_best;
		array_walk( $ratio, function( &$value, $key, $args ) {
			if( ! isset( $args['count_worst'][$key] ) ) $args['count_worst'][$key] = 0;
			$value = $value - $args['count_worst'][$key];
		}, array( 'count_worst' => $count_worst ) );

		arsort( $ratio );

		foreach( $ratio as $key => $value ) {
			if( $value > 0.10 ) {
				$this->success_metrics[] = 'post_category_'.$key;

				if( ! isset( $count_worst[$key] ) ) $count_worst[$key] = 0;
				echo '<p>Post <strong>category '.$key.'</strong> is present in '.( round( $count_best[$key], 2 ) * 100 ).'% of successful posts and in '.( round( $count_worst[$key], 2 ) * 100 ).'% of non-successful ones.</p>';
			}
		}
	}

	function get_success_metrics() {
		return $this->success_metrics;
	}

	static function print_analysis_result( $metric_label, $best, $worst, $ratio ) {
		echo '<p><strong>'.$metric_label.'</strong> scored '.round( $best, 2 ).' among successful posts and '.round( $worst, 2 ).' among non-successful ones, with a ratio of <strong>'.round( $ratio, 2 ).'</strong></p>';
	}
}
