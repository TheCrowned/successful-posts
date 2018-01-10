<?php
/*
Plugin Name: Successful post (ALPHA)
Plugin URI: http://thecrowned.org
Description: Provides site successful posts list and tries to find success criteria.
Author: Stefano Ottolenghi
Version: 0.1
Author URI: http://www.thecrowned.org/
*/

/** Copyright Stefano Ottolenghi 2013
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

//If trying to open this file out of wordpress, warn and exit
if( ! function_exists( 'add_action' ) )
    die( 'This file is not meant to be called directly.' );

global $sp_global, $sp_metrics, $sp_data_dir_name;

//ENTER INPUT DATA DIRECTORY NAME
$sp_data_dir_name = 'Stefano Ottolenghis Wordpress';

$sp_global = array(
	'current_version' => get_option( 'sp_current_version' ),
	'newest_version' => '1.0',
	'folder_path' => plugins_url( '/', __FILE__ ),
	'dir_path' => plugin_dir_path( __FILE__ ),
	'options' => array(
		'ga_token' => 'sp_ga_token',
		'ga_last_request' => 'sp_ga_last_request'
	)
);

//Cluster class only supports integer indexes for coordinates, but we'd like to refer to them with string representations
//In case revenue is available, it will have index 1 and comments will be shifted to 2. This is because revenue is not always available.
$sp_metrics = array(
	'visit_count' => 0,
	'comment_count' => 1
);

require_once( 'classes/functions.php' );
require_once( 'classes/cluster-functions.php' );
require_once( 'classes/prepare-data-for-analysis.php' );
require_once( 'classes/data-analysis.php' );
require_once( 'classes/wp-list-table.php' );
//require_once( 'classes/google-analytics.php' );
//require_once( 'classes/ajax-functions.php' );
//require_once( 'classes/html-functions.php' );

class successful_posts {

    function __construct() {
		global $sp_global;
		
        //Add left menu entries for both stats and options pages
        add_action( 'admin_menu', array( $this, 'admin_menus' ) );

		//Plugin update routine
		//add_action( 'plugins_loaded', array( $this, 'maybe_update' ) );

        //On load plugin pages
        add_action( 'load-toplevel_page_successful-posts_stats', array( $this, 'on_load_stats_page' ) );
		//add_action( 'load-successful-posts_page_successful-posts_options', array( $this, 'on_load_options_page' ), 1 );
        //add_filter('set-screen-option', array( $this, 'handle_stats_pagination_values' ), 10, 3);

        //Localization
        //add_action( 'plugins_loaded', array( $this, 'load_localization' ) );

		//add_action( 'admin_init', array( 'SP_functions', 'execute_one_batch' ) );

        //Manage AJAX calls
		add_action( 'wp_ajax_sp_ga_auth_key', array( 'SP_ajax_functions', 'GA_register_first_token' ) );
		add_action( 'wp_ajax_sp_ga_show_user_profiles', array( 'SP_ajax_functions', 'GA_show_user_profiles' ) );
		add_action( 'wp_ajax_sp_ga_set_user_profile', array( 'SP_ajax_functions', 'GA_set_user_profile' ) );
		add_action( 'wp_ajax_sp_ga_revoke_auth', array( 'SP_ajax_functions', 'GA_revoke_auth' ) );
		add_action( 'wp_ajax_sp_ga_update_data_custom', array( 'SP_ajax_functions', 'GA_update_data_custom' ) );
		add_action( 'wp_ajax_sp_ga_delete_data_temp', array( 'SP_ajax_functions', 'GA_delete_data_temp' ) );		
    }

    /**
     * Adds menu.
     *
     * @access  public
     * @since   1.0
     */
    function admin_menus() {
		global $sp_global;
		
        add_menu_page( 'Successful Posts', 'Successful Posts', 'manage_options', 'successful-posts_stats', array( $this, 'show_stats' ) );
        add_submenu_page( 'successful-posts_stats', 'Successful Posts Stats', __( 'Stats', 'post-pay-counter' ), 'manage_options', 'successful-posts_stats', array( $this, 'show_stats' ) );
        //$sp_global['options_menu_slug'] = add_submenu_page( 'successful-posts_stats', 'Successful Posts Options', __( 'Options', 'post-pay-counter' ), 'manage_options', 'successful-posts_options', array( $this, 'show_options' ) );
    }

    /**
     * Reponsible of the plugin's js and css loading in the options page
     *
     * @access  public
     * @since   1.0
     */
    function on_load_options_page() {
        global $sp_global;
        wp_enqueue_script( 'post' );

        require_once( 'classes/meta-boxes-functions.php' );

        add_meta_box( 'sp_analytics', __( 'Analytics Settings', 'post-pay-counter' ), array( 'SP_meta_boxes', 'google_analytics' ), $sp_global['options_menu_slug'], 'normal', 'default' );
        
        wp_enqueue_style( 'ppc_header_style', $sp_global['folder_path'].'style/header.css', array( 'wp-admin' ), filemtime( $sp_global['dir_path'].'style/header.css' ) );
        wp_enqueue_style( 'ppc_options_style', $sp_global['folder_path'].'style/options.css', array( 'wp-admin' ), filemtime( $sp_global['dir_path'].'style/options.css' ) );

        wp_enqueue_script( 'sp_options_ajax_stuff', $sp_global['folder_path'].'js/options_ajax_stuff.js', array( 'jquery' ) );
        wp_localize_script( 'sp_options_ajax_stuff', 'sp_options_ajax_stuff_vars', array(
			'nonce_sp_ga_revoke_auth' => wp_create_nonce( 'sp_ga_revoke_auth' ),
            'nonce_sp_ga_auth_key' => wp_create_nonce( 'sp_ga_auth_key' ),
            'nonce_sp_ga_show_user_profiles' => wp_create_nonce( 'sp_ga_show_user_profiles' ),
            'nonce_sp_ga_set_user_profile' => wp_create_nonce( 'sp_ga_set_user_profile' ),
            'nonce_sp_ga_update_data_custom' => wp_create_nonce( 'sp_ga_update_data_custom' ),
            'nonce_sp_ga_delete_data_temp' => wp_create_nonce( 'sp_ga_delete_data_temp' ),
            'localized_sp_ga_revoked_auth' => __( 'The authorization was revoked and Google Analytics data deleted successfully. If you want to configure again the Google Analytics access, please reload this page.', 'ppcp'),
            'localized_sp_ga_setup_complete' => __( 'You have successfully set up Google Analytics. The page will be reloaded to make further settings available.', 'ppcp'),
            'localized_revoke_ga_auth_warning' => __( 'Beware that revoking Analytics authorization will delete all its data!', 'ppcp'),
            'options_url' => 'admin.php?page=successful-posts_options'
        ) );
	}

    /**
     * Starts elaborating data in stats page.
     *
     * @access  public
     * @since   1.0
     */
    function on_load_stats_page() {
        global $sp_global, $sp_metrics, $sp_data_dir_name;

		$option = 'per_page';
		$args = array(
			 'label' => 'Posts',
			 'default' => 500,
			 'option' => 'sp_posts_per_page'
		);
		add_screen_option( $option, $args );

		$data_path = $sp_global['dir_path'].'/data/'.$sp_data_dir_name.'/';
		$data = array();
		$files = scandir( $data_path );
		
		foreach( $files as $file ) {
			if( $file == '.' OR $file == '..' ) continue;

			$name_chunks = explode( '__', $file );
			$website = $name_chunks[0];
			$time = explode( '-', $name_chunks[1] );
			$file_chunk_n = $name_chunks[2];

			//if( ! isset( $data[$website][$time[0]] ) )
				//$data[$website][$time[0]] = array();

			$file_content = file_get_contents( $data_path.$file );
			if( ! $file_content ) die('Errore lettura');

			$file_content = maybe_unserialize( $file_content );

			foreach( $file_content as $post ) {
				if( $post->post_title == 'Home' ) continue;
				if( in_array( 'offerta', $post->post_tags ) !== false ) continue;
				/*if( ! isset( $data[$website][$time[0]][$post->ID] ) ) {
					$data[$website][$time[0]][$post->ID] = $post;
				} else {
					$data[$website][$time[0]][$post->ID]->sp_metrics[0] += $post->sp_metrics[0];
				}*/

				//If there is revenue data
				if( isset( $post->sp_metrics[1] ) ) {
					$sp_metrics['comment_count'] = 2;
					$sp_metrics['revenue'] = 1;
				}

				if( ! isset( $data[$website][$post->ID] ) ) {
					$data[$website][$post->ID] = $post;
					$data[$website][$post->ID]->sp_metrics[$sp_metrics['comment_count']] = $post->comment_count;

					if( isset( $sp_metrics['revenue'] ) )
						$data[$website][$post->ID]->sp_metrics[$sp_metrics['revenue']] = $post->sp_metrics[$sp_metrics['revenue']];
						
				} else {
					$data[$website][$post->ID]->sp_metrics[$sp_metrics['visit_count']] += $post->sp_metrics[$sp_metrics['visit_count']];
					$data[$website][$post->ID]->sp_metrics[$sp_metrics['comment_count']] += $post->comment_count;

					if( isset( $sp_metrics['revenue'] ) )
						$data[$website][$post->ID]->sp_metrics[$sp_metrics['revenue']] += $post->sp_metrics[$sp_metrics['revenue']];
				}
			}
		}
		
		$_clusters = new SP_cluster_functions();
		$this->clusters = $_clusters->execute_cluster( $data[$website] );

		//If best clusters contain less than 0.01% of posts (or of they contain less than 10 posts), cluster again worst cluster
		//Posts details from worst cluster are obtained through array_diff with best cluster for performance
		$posts_n = count( $data[$website] );
		while( SP_cluster_functions::count_clusters_points( $this->clusters['best_clusters'] ) / $posts_n < 0.01 OR SP_cluster_functions::count_clusters_points( $this->clusters['best_clusters'] ) < 10 ) {
			$not_to_be_clustered_again = array(); 
			foreach( $this->clusters['best_clusters'] as $best_cluster ) {
				foreach( $best_cluster->getIterator() as $point ) {
					$point_data = $point->getSpace()[$point];
					$not_to_be_clustered_again[$point_data['post_id']] = $data[$website][$point_data['post_id']];
				}
			}
			$to_be_clustered_again = array_diff_key( $data[$website], $not_to_be_clustered_again );

			$new_clusters = $_clusters->execute_cluster( $to_be_clustered_again );

			//Perform points migrations
			foreach( $new_clusters['best_clusters'] as $cluster ) {
				current( $this->clusters['best_clusters'] )->attachAll( $cluster->getIterator() );
				current( $this->clusters['worst_clusters'] )->detachAll( $cluster->getIterator() );
			}
			
			//Update all cluster's centroids
			foreach( array_merge( $this->clusters['best_clusters'], $this->clusters['worst_clusters'] ) as $cluster )
				$cluster->updateCentroid();
		}

		//From here onwards, clusters can be treated as arrays, but can't be manipulated anymore
		$_clusters->cast_to_array( $this->clusters );
		
		$cols = array(
			'post_id' => __( 'Post ID' ),
			'post_visits' => __( 'Visits Count' ),
			'post_revenue' => __( 'Revenue' ),
			'comment_count' => __( 'Comment Count' ),
			'post_title' => __( 'Post Title' ),
			'post_length' => __( 'Post Length' ),
			'html_h2_count' => __( 'H2 count' ),
			'html_strong_count' => __( 'strong count' ),
			'html_em_count' => __( 'em count' ),
			'html_img_count' => __( 'img count' ),
			'html_a_count' => __( 'a count' ),
			'post_videos' => __( 'Videos count' ),
			'post_tags' => __( 'Post Tags' ),
			'post_categories' => __( 'Post Categories' )
		);

		//HTML tags to be extracted/counted in post content
		$html_tags = array(
			'h1', 'h2', 'h3', 'h4', 'strong', 'em', 'img', 'a', 'p'
		);

		$_prepared_data_best_clusters = new SP_prepare_data_for_analysis( $data[$website], $this->clusters['best_clusters'] );
		$prepare_data_criteria = array(
			'html_tags' => array( 'callback' => array( $_prepared_data_best_clusters, 'extract_and_count_html_tags' ), 'args' => array( 'html_tags' => $html_tags ) )
		);
		$_prepared_data_best_clusters->execute_prepare_data( $prepare_data_criteria );
		$prepared_data_best = $_prepared_data_best_clusters->get_prepared_data();

		$_prepared_data_worst_clusters = new SP_prepare_data_for_analysis( $data[$website], $this->clusters['worst_clusters'] );
		$prepare_data_criteria = array(
			'html_tags' => array( 'callback' => array( $_prepared_data_worst_clusters, 'extract_and_count_html_tags' ), 'args' => array( 'html_tags' => $html_tags ) )
		);
		$_prepared_data_worst_clusters->execute_prepare_data( $prepare_data_criteria );
		$prepared_data_worst = $_prepared_data_worst_clusters->get_prepared_data();

		$this->prepared_data = array( 'best_points' => $prepared_data_best, 'worst_points' => $prepared_data_worst );
		$this->table = new Successful_Posts_List_Table( array( 'data' => $prepared_data_best, 'cols' => $cols ) );
    }

    /**
     * Shows the Stats page.
     *
     * @access  public
     * @since   1.0
     */
    function show_stats() {
        ?>

<div class="wrap">
	<h2>Successful Posts</h2>

		<?php

		$this->table->prepare_items();
		$this->table->display();

		$analysis_methods = array(
			'html_h1' => array( 'callback' => array( 'SP_analyze_data', 'html_tag' ), 'args' => array( 'tag' => 'h1' ) ),
			'html_h2' => array( 'callback' => array( 'SP_analyze_data', 'html_tag' ), 'args' => array( 'tag' => 'h2' ) ),
			'html_h3' => array( 'callback' => array( 'SP_analyze_data', 'html_tag' ), 'args' => array( 'tag' => 'h3' ) ),
			'html_h4' => array( 'callback' => array( 'SP_analyze_data', 'html_tag' ), 'args' => array( 'tag' => 'h4' ) ),
			'html_strong' => array( 'callback' => array( 'SP_analyze_data', 'html_tag' ), 'args' => array( 'tag' => 'strong' ) ),
			'html_em' => array( 'callback' => array( 'SP_analyze_data', 'html_tag' ), 'args' => array( 'tag' => 'em' ) ),
			'html_img' => array( 'callback' => array( 'SP_analyze_data', 'html_tag' ), 'args' => array( 'tag' => 'img' ) ),
			'html_a' => array( 'callback' => array( 'SP_analyze_data', 'html_tag' ), 'args' => array( 'tag' => 'a' ) ),
			'post_length' => array( 'callback' => array( 'SP_analyze_data', 'post_length' ), 'args' => array() ),
			'paragraph_density' => array( 'callback' => array( 'SP_analyze_data', 'paragraph_density' ), 'args' => array() ),
			'post_tags' => array( 'callback' => array( 'SP_analyze_data', 'post_tags' ), 'args' => array() ),
			'post_categories' => array( 'callback' => array( 'SP_analyze_data', 'post_categories' ), 'args' => array() ),
			'post_videos' => array( 'callback' => array( 'SP_analyze_data', 'post_videos' ), 'args' => array() )
		);
		$_analyze_data = new SP_analyze_data( $analysis_methods, $this->clusters, $this->prepared_data );

		echo '<h3>Found indicators of success are:</h3>';
		var_dump( $_analyze_data->get_success_metrics() );
        ?>

</div>

	<?php
	}

	/**
     * Shows the Stats page.
     *
     * @access  public
     * @since   1.0
     */
    function show_options() {
        global $sp_global;
        ?>

<div class="wrap">
	<div id="sp_header">
		<div id="sp_header_text">
			<div id="sp_header_links">
			<?php echo 'Installed version: '.$sp_global['newest_version']; ?>
			</div>
			<h2>Successful Posts - Options</h2>
		</div>
	</div>

		<?php
        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
        ?>

	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div id="post-body" class="has-sidebar">
			<div id="post-body-content" class="has-sidebar-content">

		<?php
        do_meta_boxes( $sp_global['options_menu_slug'], 'normal', null );
        ?>

			</div>
		</div>
		<div id="side-info-column" class="inner-sidebar">

		<?php
        do_meta_boxes( $sp_global['options_menu_slug'], 'side', null );
        ?>

		</div>
	</div>
</div>

		<?php
    }
}

function SP_performance_debug( $tstart, $where ) {
	return;
	$tend = microtime( true );
	echo $where.'<br />';
	var_dump($tend - $tstart);
	echo '<br /><br />';
}

function sp_start() {
	new successful_posts();
}
add_action( 'plugins_loaded', 'sp_start' );
