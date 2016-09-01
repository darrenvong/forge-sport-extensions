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

/**
 * A custom query which fetches the latest five sport match results that Forge Sport
 * covers. Use the shortcode [forge_print_results] to display the contents.
 *
 * EXTRA NOTE TO SELF: useful files to look at in WP Club Manager:
 * - includes/class-wpcm-widget-results.php
 * - templates/content-widget-results.php
 * - includes/wpcm-match-functions.php
 * - includes/wpcm-template-hooks.php ?
 */
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
    'posts_per_page' => $limit
  );

  $match_query = new WP_Query($args);
?>
  <div class="layout-fix">
    <div class="forge-results-title">Latest Results</div>
<?php

  if ( $match_query->have_posts() ):
    $sports = array();
    $is_first_result = true;

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
      // Returns array($result, $home_goal, $away_goal, $delimiter) where $result is
      // the full result string, $home_goal and $away_goal is self explanatory,
      // $delimiter is the symbol separating the score
      $score = (function_exists("wpcm_get_match_result"))? wpcm_get_match_result($post) : "empty_res";
      // May break the date above down further for finer grain control by doing:
      // $time = the_time('G:i')
      //Date displayed like this: 27/8/16
      $date = get_the_date('j/n/y', $post);

      // Largely based on `wpcm_get_match_comp` function in includes/wpcm-match-functions.php
      $comp = get_the_terms($post, 'wpcm_comp');
      if ( is_array($comp) ) {
        $comp_sport = $comp[0]->parent;
        if ( $comp_sport ) {
          $comp_sport = get_term($comp_sport, 'wpcm_comp');
        }     
      }

      if ($is_first_result)
          $is_first_result = false;
      
      if ($comp_sport):
        $comp_sport = $comp_sport->name;

        if ( !array_key_exists($comp_sport, $sports) ):
          $sports[$comp_sport] = true;
          if (!$is_first_result) { // Not the first result
            // So close the previous div before starting another for a different comp
            echo "</div>";
          }
?>
        <div class="forge-comp-results">
          <div class="forge-comp-name"><?= $comp_sport; ?></div>
<?php
        endif;
      else:
      // Could have selected parent directly instead and not through the tedious
      // process of creating EVERY competitions...
        $comp_sport = $comp[0]->name;
        if ( !array_key_exists($comp_sport, $sports) && $comp_sport !== "" ):
          $sports[$comp_sport] = true;
          if (!$is_first_result) { // Not the first result
            // So close the previous div before starting another for a different comp
            echo "</div>";
          }
?>
        <div class="forge-comp-results">
          <div class="forge-comp-name"><?= $comp_sport; ?></div> 
<?php
        else:
          $sports["Miscellaneous"] = true;
?>
        <div class="forge-comp-results">
          <div class="forge-comp-name">Miscellaneous</div> 
<?php
        endif;
        // _forge_debug(array($comp, $comp_name));
      endif;
?>
      <div class="forge-single-result">
        <span class="forge-result-date"><?= $date; ?></span>
        <span class="forge-home-team"><?= $sides[0]; ?></span>
        <span class="forge-score-card"><?= $score[0]; ?></span>
        <span class="forge-away-team"><?= $sides[1]; ?></span>
      </div>
<?php
    endwhile;
    // _forge_debug(array($sports));
  else: ?>
    <p>No matches played yet. Come back and check again later!</p>
<?php
  endif;

  wp_reset_postdata();
?>
    </div> <!-- Closes the last competition level div -->
<?php
}

add_shortcode('forge_print_results', 'forge_custom_match_query');

function _forge_debug($vars) {
  echo "---------------------------------------------- <br>";
  foreach ($vars as $var) {
    var_dump($var);
    echo "<br>";
  }
  echo "---------------------------------------------- <br>";
}

?>
