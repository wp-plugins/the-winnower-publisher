<?php
defined('ABSPATH') or die("No direct access");

add_action('activated_plugin', 'winnower_set_default_settings');
function winnower_set_default_settings(){
  delete_option('winnower_topics');
  delete_option('winnower_topics_set');
  delete_option('winnower_topics_last_updated');
  delete_option('winnower_topics_json');

  $default_options = array(
    'api_endpoint' => 'https://thewinnower.com/api/',
    'display_cite_link' => false,
    'api_key' => ""
  );

  $new = add_option('winnower_publisher_settings', $default_options);

  if (!$new) {
    $options = get_option('winnower_publisher_settings');
    if(!array_key_exists('api_endpoint', $options) || !$options['api_endpoint']){
      $options['api_endpoint'] = $default_options['api_endpoint'];
    }

    update_option('winnower_publisher_settings', $options);
  }
}

