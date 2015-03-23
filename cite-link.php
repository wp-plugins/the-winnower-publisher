<?php
defined('ABSPATH') or die("No direct access");

add_filter('the_content', 'winnower_cite_link', 11);
function winnower_cite_link($content) {
  global $post;
  global $winnower_skip_cite_link;
  if ($winnower_skip_cite_link) {
    return $content;
  }

  $winnower_settings = get_option('winnower_publisher_settings');
  if (!$winnower_settings['display_cite_link']) {
    return $content;
  }

  $cite_url = get_post_meta($post->ID, 'cite-link', true);
  $DOI = get_post_meta($post->ID, 'doi-status', true);

  if($DOI) {
    $content .= '<p class="winnower-cite-link">DOI: <a target="_blank" href="https://dx.doi.org/'.$DOI.'">'.$DOI.'</a> provided by <a href="https://thewinnower.com">The Winnower</a>, a DIY scholarly publishing platform</p>';
  } else if($cite_url) {
    $content .= '<p class="winnower-cite-link"><a target="_blank" href="'.$cite_url.'">This post is open to read and review on The Winnower.</a></p>';
  }

  return $content;
}
