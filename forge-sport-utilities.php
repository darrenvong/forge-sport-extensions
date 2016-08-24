<?php
/**
 * @package forge-sport-utilities
 * @version 2.0
 */
/*
Plugin Name: Forge Sport Utilities
Plugin URI: http://www.forgetoday.com/tv/
Description: A utility plugin that works with existing plugins to patch their shortcomings in order to 'make the Forge Sport website work'.
Author: Darren Vong
Version: 2.0
Author URI: https://github.com/darrenvong/FT-2016/forge-sport-utilities
*/

if ( !defined('ABSPATH') ) wp_die();

add_action('do_meta_boxes', 'forge_remove_useless_metaboxes');
function forge_remove_useless_metaboxes() {
  $pages_to_exclude = array('score', 'post', 'page');
  if ( !current_user_can('install_plugins') ) {
    remove_meta_box('postcustom', $pages_to_exclude, 'normal'); //Custom Fields
    remove_meta_box('eg-meta-box', $pages_to_exclude, 'normal'); //Essential Grid
    remove_meta_box('mymetabox_revslider_0', $pages_to_exclude, 'normal'); //Rev Slider 
  }
}

function forge_custom_match_query() {
  $limit = 5;
  $args = array(
    'numberposts' => $limit,
    'orderby' => 'post_date',
    'post_type' => 'wpcm_match',
    'meta_query' => array(
      array(
        'key' => 'wpcm_played',
        'value' => true
      )
    ),
    'posts_per_page' => $limit,
    'tax_query' => array(
      array(
        'taxonomy' => 'wpcm_season',
        'field' => 'slug',
        'terms' => '2016-17'
      )
    )
  );

  $match_query = new WP_Query($args);

  if ( $match_query->have_posts() ):
    while ( $match_query->have_posts() ):
      $match_query->the_post();

      /**
        * N.B.: whilst some results returned below are labelled with var names, they
        * CANNOT actually be used - it is there for clarity. Use array indexes to access
        * them instead!
        */
      $post = get_the_ID();
      // Returns array($home, $away) where $home and $away are the teams' string labels
      $sides = (function_exists("wpcm_get_match_clubs"))? wpcm_get_match_clubs($post) : "empty_res";
      // Returns array where [0] => full comp name, [1] => (colloquial?) label name
      $comp = (function_exists("wpcm_get_match_comp"))? wpcm_get_match_comp($post) : "empty_res";
      // Returns array($result, $home_goal, $away_goal, $delimiter) where $result is the
      // full result string, $home_goal and $away_goal is self explanatory, $delimiter
      // is the symbol separating the score
      $score = (function_exists("wpcm_get_match_result"))? wpcm_get_match_result($post) : "empty_res";
      //Date displayed like this: August 27, 2016 15:00
      $date = get_the_date('F j, Y G:i', $post);
      // May break the date above down further for finer grain control by doing:
      // $time = the_time('G:i')
    endwhile;
  endif;

  wp_reset_postdata();
}

add_shortcode('forge_print_results', 'forge_custom_match_query');
