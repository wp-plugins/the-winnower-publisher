<?php
/*
Plugin Name: The Winnower Publisher
Plugin URI: https://thewinnower.com
Description: Publish, peer review and get cited with your own DOI by cross posting to thewinnower.com.
Author: The Winnower
Version: 1.7
Author URI: https://thewinnower.com
Plugin Type: Piklist
License: GPL2
*/

defined('ABSPATH') or die("No direct access");

add_action('init', 'winnower_piklist_check');
function winnower_piklist_check() {
  if (!is_admin()) {
    return;
  }
  require_once('class-piklist-checker.php');
  if (!piklist_checker::check(__FILE__)) {
   return;
  }
}

require_once('activation-hooks.php');
require_once('save-hooks.php');
require_once('admin-pages.php');
require_once('cite-link.php');
require_once('widget.php');
