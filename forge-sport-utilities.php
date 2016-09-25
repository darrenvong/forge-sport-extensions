<?php
/**
 * @package forge-sport-utilities
 * @version 3.3.1
 */
/*
Plugin Name: Forge Sport Utilities
Plugin URI: https://github.com/darrenvong/forge-sport-utilities
Description: A utility plugin that works with existing plugins to patch their shortcomings in order to 'make the Forge Sport website work'.
Author: Darren Vong
Version: 3.3.1
Author URI: https://github.com/darrenvong/
*/

if ( !defined('ABSPATH') ) wp_die();

add_action('do_meta_boxes', 'forge_remove_useless_metaboxes');
function forge_remove_useless_metaboxes() {
  $pages_to_exclude = array('banner', 'post', 'page');
  if ( !current_user_can('install_plugins') ) { //Checks whether user is an admin
    remove_meta_box('postcustom', $pages_to_exclude, 'normal'); //Custom Fields
    remove_meta_box('eg-meta-box', $pages_to_exclude, 'normal'); //Essential Grid
    remove_meta_box('mymetabox_revslider_0', $pages_to_exclude, 'normal'); //Rev Slider 
  }
}


function forge_enqueue_object_fit_polyfill() {
  if ( is_page('get-involved') )
    wp_enqueue_script('object-fit-polyfill', plugin_dir_url(__FILE__) . 'js/ofi.browser.js');
}
add_action('wp_enqueue_scripts', 'forge_enqueue_object_fit_polyfill');

/**
 * A custom query which fetches the latest five sport match results that Forge Sport
 * covers. Use the shortcode [forge_print_results] to display the contents.
 *
 * The implementation of the functionalities here were largely helped by the following
 * files in WP Club Manager:
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
        * CANNOT actually be used - it is there for clarity. Use array indexes to
        * access them instead!
        */
      $post = get_the_ID();

      // Returns array($home, $away) where $home and $away are the teams' string labels
      $sides = (function_exists("wpcm_get_match_clubs"))? wpcm_get_match_clubs($post) : array("Home Team", "Away Team");
      // Returns array($result, $home_goal, $away_goal, $delimiter) where $result is
      // the full result string, $home_goal and $away_goal is self explanatory,
      // $delimiter is the symbol separating the score
      $score = (function_exists("wpcm_get_match_result"))? wpcm_get_match_result($post) : array("0 - 0");
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
      
      if ($comp_sport) {
        $comp_sport = $comp_sport->name;
        // _print_new_section($comp_sport, $sports, $is_first_result);
      }
      else {
      /** Could have selected parent directly instead and not through the tedious
       * process of creating EVERY competitions... */
        $comp_sport = $comp[0]->name;
        // _print_new_section($comp_sport, $sports, $is_first_result);
      }
?>
      <div class="forge-single-result">
<?php
      // Include a clickable link if provided  
      if ($post_url = get_the_content()): ?>
          <a href="<?= esc_url( $post_url ); ?>">
<?php   endif; ?>
            <span class="forge-result-date"><?= $date; ?></span>
            <span class="forge-home-team"><?= $sides[0]; ?></span>
            <span class="forge-score-card"><?= $score[0]; ?></span>
            <span class="forge-away-team"><?= $sides[1]; ?></span>
<?php   if ( $post_url ): ?> </a> <?php endif; ?>
      </div>
<?php
    endwhile;
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

/**
 * Helper function for detecting when it is appropriate to begin a new sport
 * section for the sidebar.
 * @param $comp_sport - the name of the sport section
 * @param &$sports - reference to the sport sections array used to keep track of
 * whether the sport has already been seen. It's important that the $sports is a
 * reference so that the changes made to $sports is reflected in the original array
 * @param $is_first_result - boolean indicating whether this is the first sport result
 * or not
 */
function _print_new_section($comp_sport, &$sports, $is_first_result) {
  if ( !array_key_exists($comp_sport, $sports) ): // New sport encountered 
    if ($comp_sport):
      // sport/comp name is not the empty string, so add to sports array  
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
  endif;
}

/**
 * Filter function to run through byline (if it exists) to ensure authors in the
 * byline are displayed instead of the "author"/person who published the post.
 * @param string $author_name - the publisher's name to filter
 * @return the byline author names if exists, otherwise the publisher's name
 */
function forge_show_byline_author($author_name) {
  if ( function_exists("byline") ) {
    $name = byline($author_name);
    $author_name = ($name)? $name : $author_name;
    return $author_name;
  }
  else
    return $author_name;
}
add_filter("the_author", "forge_show_byline_author");

/**
 * Custom query which fetches the win/draw/loss data of UoS's performance before outputting it under
 * the right half of the header.
 */
function forge_custom_banner_query() {
  $banner_query = new WP_Query(
    array(
      'post_type' => 'banner',
      'numberposts' => 1
    )
  );
  $field_data = array();
  if ($banner_query->have_posts()):
    while ($banner_query->have_posts()):
      $banner_query->the_post();
      $field_data['W'] = esc_html( do_shortcode('[ct id="_ct_text_57c8cfe6da990" property="value"]') ); //Wins field
      $field_data['D'] = esc_html( do_shortcode('[ct id="_ct_text_57c8d074955aa" property="value"]') ); //Draws field
      $field_data['L'] = esc_html( do_shortcode('[ct id="_ct_text_57c8d0f4716d3" property="value"]') ); //Losses field
      $field_data['I'] = do_shortcode('[ct id="_ct_upload_57e29ab501d0e" property="value"]'); // Logo image upload field
      $banner_html = "<div class='uos-bucs'> UoS in BUCS: Win {$field_data['W']}, Draw {$field_data['D']}, Lost {$field_data['L']}</div>";
?>
    <div class="uos-bucs hidden">
      <span id="uni-crest">
        <?= $field_data["I"] ?>
        in BUCS: 
      </span>
      <span id="uni-stats">
        <div id="wins"><span class="stat-label">Wins</span><span class="score"><?= $field_data['W']; ?></span></div>
        <div id="draws"><span class="stat-label">Draws</span><span class="score"><?= $field_data['D']; ?></span></div>
        <div id="losses"><span class="stat-label">Losses</span><span class="score"><?= $field_data['L']; ?></span></div>        
      </span>
    </div>
    <script>
      // Annoying work around to force the banner inside the right header...
      (function($) {
        $(function() {
          var banner = $("div.uos-bucs");
          $(".header-right").append(banner);
          banner.removeClass("hidden");
        });
      })(window.jQuery || $);
    </script>
<?php
    endwhile;
  endif;
  wp_reset_postdata();
}

add_shortcode('forge_get_banner', 'forge_custom_banner_query');

/**
 * Useful function for debugging
 * @param $vars - the variables information to print out
 */
function _forge_debug($vars) {
  echo "---------------------------------------------- <br>";
  if (is_array($vars)) {
    foreach ($vars as $var) {
      var_dump($var);
      echo "<br>";
    }    
  }
  else {
    var_dump($vars);
    echo "<br>";
  }
  echo "---------------------------------------------- <br>";
}

?>
