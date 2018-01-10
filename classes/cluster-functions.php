<?php

/**
 * Cluster functions.
 *
 * @package     SP
 * @copyright   2017
 * @author 		Stefano Ottolenghi
 */

//Cluster library https://github.com/bdelespierre/php-kmeans
require_once "php-kmeans-master/src/KMeans/Space.php";
require_once "php-kmeans-master/src/KMeans/Point.php";
require_once "php-kmeans-master/src/KMeans/Cluster.php";

class SP_cluster_functions {

	function execute_cluster( $data ) {
		$data = $this->normalize_data( $data );
		$result = $this->cluster_posts( $data );

		//$this->print_clusters_content( $result );
		
		$best_clusters = $this->pick_best_clusters( $result );
		$worst_clusters = array_diff_key( $result, $best_clusters );

		return array( 'best_clusters' => $best_clusters, 'worst_clusters' => $worst_clusters );
	}

	// display the cluster centers and attached points
	function print_clusters_content( $clusters ) {
		foreach( $clusters as $i => $cluster ) {
			printf( "Cluster %s [%d,%d,%d, %d]: %d points <br />
", $i, $cluster[0], $cluster[1], 1, 1, count( $cluster ) );
			continue;
			
			foreach( $cluster as $point ) {
				
				$sp = $point->toArray();
				print_r( ($sp['data']['post_id']) );
				print_r( $point->getCoordinates() );
			}
		}
	}

	function normalize_data( $posts ) {
		$tstart = microtime( true );

		//Get max for each metric 
		$max = array();
		foreach( $posts as $post ) {
			if( empty( $max ) )
				$max = $post->sp_metrics;

			foreach( $post->sp_metrics as $metric_key => $metric_val ) {
				if( $metric_val > $max[$metric_key] )
					$max[$metric_key] = $metric_val;
			}
		}
		
		//and normalize each post in respect to that
		foreach( $posts as &$post ) {
			$post->sp_metrics[0] /= ($max[0]/100);
			$post->sp_metrics[1] /= ($max[1]/20);
			//$post->sp_metrics[2] /= ($max[2]/20);
		}

		SP_performance_debug( $tstart, 'cluster_functions::normalize_data' );

		return $posts;
	}

	function cluster_posts( $posts, $cluster_n = 3 ) {
		($tstart = microtime(true));

		$single_post = current( $posts );
		$metrics_n = count( $single_post->sp_metrics );

		$space = new KMeans\Space( $metrics_n );

		// add points to space
		foreach( $posts as $single ) {
			$space->addPoint( $single->sp_metrics, array( 'post_id' => $single->ID ) );
		}

		// cluster points in 3 clusters
		$clusters = $space->solve( $cluster_n, KMeans\Space::SEED_DASV );

		SP_performance_debug( $tstart, 'cluster_functions::cluster_posts' );

		return $clusters;
	}

	function pick_best_clusters( &$clusters, $n = 2 ) {
		($tstart = microtime(true));

		$centroid_norms = array();
		foreach( $clusters as $i => $cluster ) {
			//compute cluster centroid norm
			foreach( $cluster->getCoordinates() as $coordinate ) {

				if( ! isset( $centroid_norms[$i] ) )
					$centroid_norms[$i] = $coordinate*$coordinate;
				else
					$centroid_norms[$i] += $coordinate*$coordinate;

			}
		}

		//sort high to low
		arsort( $centroid_norms );

		$result = array_intersect_key( $clusters, array_slice( $centroid_norms, 0, $n, true ) );

		SP_performance_debug( $tstart, 'cluster_functions::pick_best_clusters' );

		return $result;
	}

	static function count_clusters_points( $clusters ) {
		$n = 0;
		foreach( $clusters as &$cluster )
			$n += $cluster->count();

		return $n;
	}

	static function cast_to_array( &$clusters ) {
		foreach( $clusters['best_clusters'] as &$cluster )
			$cluster = $cluster->toArray();
		foreach( $clusters['worst_clusters'] as &$cluster )
			$cluster = $cluster->toArray();

		return $clusters;
	}
}
