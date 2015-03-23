<?php
defined('ABSPATH') or die("No direct access");

add_action('widgets_init', create_function('', 'return register_widget("Winnower_Badge");') );
class Winnower_Badge extends WP_Widget {
  function __construct() {
    parent::__construct(
      'winnower_badge',
      'Winnower Publisher DOI/Review',
      array(
        'description' => 'Shows on pages with posts that you have published on The Winnower.'
      )
    );
  }

  function form($instance) {
    $winnower_settings = get_option('winnower_publisher_settings');
    $api_key_confirmation = $winnower_settings['api_key_confirmation'];

    if($api_key_confirmation != 'true') {
      echo '<p>Visit the <a href="'. admin_url('admin.php?page=winnower_settings') .'">Winnower Publisher Settings</a> to enter your api key.</p>';
    }
  }

  function widget($args, $instance) {
    extract($args);

    global $post;
    $winnower_settings = get_option('winnower_publisher_settings');
    $api_key_confirmation = $winnower_settings['api_key_confirmation'];
    $api_endpoint = $winnower_settings['api_endpoint'];
    $blog_id = $winnower_settings['user_blog_id'];
    $pub_status = get_post_meta($post->ID, 'winnower_cross_publish', true);

    if($api_key_confirmation == 'true' && $pub_status == 'true' && !is_home()) {
      $iframe_src = $api_endpoint . "blog_badge?blog_id=${blog_id}&blog_post_id={$post->ID}";
      echo "<iframe id=\"js-winnower-publisher-frame\" src=\"${iframe_src}\" width=\"1000\" height=\"440\"></iframe>";
    }
  }
}
