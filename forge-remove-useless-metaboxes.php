<?php
/**
 * @package forge-remove-useless-metaboxes
 * @version 1.0
 */
/*
Plugin Name: Remove Useless Metaboxes
Plugin URI: http://www.forgetoday.com/tv/
Description: Removes useless metaboxes that non-admin users don't need to see.
Author: Darren Vong
Version: 1.0
Author URI: http://www.github.com/darrenvong/
*/

add_action('do_meta_boxes', 'forge_remove_useless_metaboxes');
function forge_remove_useless_metaboxes() {
  $pages_to_exclude = array('score', 'post', 'page');
  if ( !current_user_can('install_plugins') ) {
    remove_meta_box('postcustom', $pages_to_exclude, 'normal'); //Custom Fields
    remove_meta_box('eg-meta-box', $pages_to_exclude, 'normal'); //Essential Grid
    remove_meta_box('mymetabox_revslider_0', $pages_to_exclude, 'normal'); //Rev Slider 
  }
}