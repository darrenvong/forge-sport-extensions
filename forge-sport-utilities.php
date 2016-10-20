<?php
/**
 * @package forge-sport-utilities
 * @version 3.4.1
 */
/*
Plugin Name: Forge Sport Utilities
Plugin URI: https://github.com/darrenvong/forge-sport-utilities
Description: A utility plugin that works with existing plugins to patch their shortcomings in order to 'make the Forge Sport website work'.
Author: Darren Vong
Version: 3.4.1
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
 * Special patch to enable editors to post embed scripts (e.g. from live blog feeds)
 * in their posts.
 */
function forge_blog_embed_patch( $string ) {
  global $allowedposttags;
  $allowedposttags["script"] = array(
    "type" => array(),
    "src" => array()
  );
  return $string;
}
add_filter('pre_kses', 'forge_blog_embed_patch');

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
  // Process the results in the loop first
  if ( $match_query->have_posts() ):
    $sports = array();
    $counter = 0;

    while ( $match_query->have_posts() ):
      $match_query->the_post();

      /**
        * N.B.: whilst some results returned below are labelled with var names, they
        * CANNOT actually be used - it is there for clarity. Use array indexes to
        * access them instead!
        */
      $post = get_the_ID();

      // Returns array($home, $away) where $home and $away are the teams' string labels
      $sides = ( function_exists("wpcm_get_match_clubs") )? wpcm_get_match_clubs($post) : array("Home Team", "Away Team");
      // Returns array($result, $home_goal, $away_goal, $delimiter) where $result is
      // the full result string, $home_goal and $away_goal is self explanatory,
      // $delimiter is the symbol separating the score
      $score = ( function_exists("wpcm_get_match_result") )? wpcm_get_match_result($post) : array("0 - 0");
      // May break the date above down further for finer grain control by doing:
      // $time = the_time('G:i')
      //Date displayed like this: 27/8/16
      $date = get_the_date('j/n/y', $post);

      // Largely based on `wpcm_get_match_comp` function in includes/wpcm-match-functions.php
      $comp = get_the_terms($post, 'wpcm_comp');

      $comp_sport = $comp[0]->name;

      if ( $comp_sport ) {
        /* Name is not the empty string, so use it as a key for looking up results
         * in the sport */
        $sports[$comp_sport][$counter] = array(
          "home_team" => $sides[0],
          "away_team" => $sides[1],
          "score" => $score[0],
          "date" => $date
        );
        if ( $post_url = get_the_content() ) {
          $sports[$comp_sport][$counter]["link"] = esc_url($post_url);
        }
      }
      else {
        /* Empty string sport, so rather than displaying a blank box, defaults
         * to "Miscellaneous" instead */
        $sports["Miscellaneous"][$counter] = array(
          "home_team" => $sides[0],
          "away_team" => $sides[1],
          "score" => $score[0],
          "date" => $date
        );
        if ( $post_url = get_the_content() ) {
          $sports["Miscellaneous"][$counter]["link"] = esc_url($post_url);
        }
      }
      $counter++;
    endwhile;
  endif;

  wp_reset_postdata();

  //Now output everything
  if ( $sports ):
    foreach ($sports as $sport => $results):
?>
      <div class="forge-comp-results">
        <div class="forge-comp-name"><?= $sport; ?></div>
<?php
      foreach ($results as $result):
?>
        <div class="forge-single-result">
<?php     if ( array_key_exists("link", $result) ): ?>
            <a href="<?= $result["link"] ?>">
<?php     endif; ?>
          <span class="forge-result-date"><?= $result["date"] ?></span>
          <span class="forge-home-team"><?= $result["home_team"] ?></span>
          <span class="forge-score-card"><?= $result["score"] ?></span>
          <span class="forge-away-team"><?= $result["away_team"] ?></span>
<?php     if ( array_key_exists("link", $result) ) { echo "</a>"; } ?>
        </div>
<?php
      endforeach;
?>
      </div> <!-- end of comp section -->
<?php
    endforeach;
  else: ?>
    <p>No matches played yet. Come back and check again later!</p>
<?php
  endif;
}

add_shortcode('forge_print_results', 'forge_custom_match_query');

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
  if ( $banner_query->have_posts() ):
    while ( $banner_query->have_posts() ):
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
  if ( is_array($vars) ) {
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
