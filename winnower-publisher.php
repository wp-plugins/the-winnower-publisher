<?php
/*
Plugin Name: The Winnower Publisher
Plugin URI: https://thewinnower.com
Description: Publish, peer review and get cited with your own DOI by cross posting to thewinnower.com.
Author: The Winnower
Version: 1.3
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

  include_once('class-piklist-checker.php');

  if (!piklist_checker::check(__FILE__)) {
   return;
  }
}

do_action('activated_plugin', 'winnower_set_default_settings');
function winnower_set_default_settings(){
  add_option('winnower_publisher_settings', array(
    'api_endpoint' => 'https://thewinnower.com/api/',
    'display_cite_link' => false
  ));
  delete_option('winnower_topics');
  delete_option('winnower_topics_set');
  delete_option('winnower_topics_json');
}

add_filter('piklist_admin_pages', 'winnower_publisher_admin_pages');
function winnower_publisher_admin_pages($pages) {
   $pages[] = array(
    'page_title' => __('Winnower Publisher Settings'),
    'menu_title' => __('The Winnower', 'piklist'),
    'capability' => 'manage_options',
    'menu_slug' => 'winnower_settings',
    'setting' => 'winnower_publisher_settings',
    //,'menu_icon' => plugins_url('piklist/parts/img/piklist-icon.png')
    //,'page_icon' => plugins_url('piklist/parts/img/piklist-page-icon-32.png')
    'single_line' => true,
    'default_tab' => 'Basic',
    'save_text' => 'Save The Winnower Publisher Settings'
  );
  return $pages;
}

add_action('save_post', 'winnower_save_meta', 10, 2);
function winnower_save_meta($ID, $post) {
  if(isset($_POST['win_toggle'])) {
    $pub_win = $_POST['win_toggle'];
    $topic = $_POST['topic'];
    $sub_1 = $_POST['topic_child_1'];
    $sub_2 = $_POST['topic_child_2'];
    $sub_3 = $_POST['topic_child_3'];
    $updatable = $_POST['updatable'];

    update_post_meta($ID, 'win_toggle', $pub_win);
    update_post_meta($ID, 'topic', $topic);
    update_post_meta($ID, 'topic_child_1', $sub_1);
    update_post_meta($ID, 'topic_child_2', $sub_2);
    update_post_meta($ID, 'topic_child_3', $sub_3);
    update_post_meta($ID, 'updatable', $updatable);
  }
}

add_action( 'save_post', 'winnower_post_published', 11, 2 );
function winnower_post_published($ID, $post) {
  global $winnower_skip_cite_link;
  $winnower_settings = get_option('winnower_publisher_settings');
  $api_key = $winnower_settings['api_key'];
  $api_endpoint = $winnower_settings['api_endpoint'];

  if(!$api_key) {
    update_post_meta($ID, 'pub', 'Please set your API key <a href="'. admin_url('admin.php?page=winnower_settings') .'">here</a> to enable publishing.');
    return;
  }

  $pub_win = get_post_meta($ID, 'win_toggle', true);
  $updatable = get_post_meta($ID, 'updatable', true);
  if($pub_win == 'false' || $post->post_status != 'publish') {
    update_post_meta($ID, 'pub', 'Not Yet Published.');
    return;
  } else if($updatable == 'false') {
    update_post_meta($ID, 'pub', 'This paper can no longer be updated.');
    return;
  }
  $title = $post->post_title;

  // This is an ugly hack to keep our citation link from showing on the winnower.com
  $winnower_skip_cite_link = true;
  $html = apply_filters('the_content', $post->post_content);
  $winnower_skip_cite_link = false;

  $topic = get_post_meta($ID, 'topic');
  $sub_1 = get_post_meta($ID, 'topic_child_1');
  $sub_2 = get_post_meta($ID, 'topic_child_2');
  $sub_3 = get_post_meta($ID, 'topic_child_3');

  $topics = array_merge($topic, $sub_1, $sub_2, $sub_3);

  $body = array(
    'posts' => array(
      'title' => $title,
      'content' => $html,
      'blog_post_id' => $ID,
      'topics' => $topics
    )
  );
  $body = json_encode($body);

  $winnower_settings = get_option('winnower_publisher_settings');
  $api_key = $winnower_settings['api_key'];
  $api_endpoint = $winnower_settings['api_endpoint'];
  $url = $api_endpoint . "papers/?api_key=" . $api_key;

  $req_array = array(
    'method' => 'POST',
    'timeout' => 30,
    'headers' => array(
      'Content-Type' => 'application/json; charset=utf-8'
    ),
    'body' => $body
  );

  $response = wp_remote_post($url, $req_array);

  if(is_wp_error($response)) {
    $error_message = 'Wordpress Error: ' . $response->get_error_message();
    update_post_meta($ID, 'pub', $error_message);
  } else {
    $human_response = winnower_parse_response($response);
    update_post_meta($ID, 'pub', $human_response );
  }
}

function winnower_parse_response($response_array) {
  global $post;
  $response = $response_array['response'];
  $code = $response['code'];
  $message = $response['message'];

  if($code >= 500) {
    return $message;
  }

  $body = $response_array['body'];

  switch ($code) {
    case 200:
    case 201:
      $paper = json_decode($body, true);
      $paper = $paper['papers'];

      $id = $paper['id'];
      $title = htmlentities($paper['title']);
      $slug = $paper['slug'];
      $DOI = $paper['DOI'];
      $url = $paper['links']['view'];
      $edit = $paper['links']['edit'];

      update_post_meta($post->ID, 'cite-link', $url);
      update_post_meta($post->ID, 'doi', $DOI);
      update_post_meta($post->ID, 'paper-id', $id);
      update_post_meta($post->ID, 'edit-link', $edit);

      return "Post has been published for review. View it here: <a target=\"_blank\" href=\"$url\">$title</a></p>";

      break;
    case 422:
    case 401:
      $errors = json_decode($body, true);
      $errors = $errors['errors'];
      $fields = $errors['fields'];
      if($errors) {
        $err_message = '<span title="'.$errors['status'].'">There was a problem submitting your post to The Winnower.</span><br><ul>';
        foreach($fields as $error) {
          $err_message .= '<li>'.$error.'</li>';
        }
        $err_message .= '</ul>';
        return $err_message;
      } else {
        return 'There has been an unknown error!';
      }
      break;
  }
}


add_filter('the_content', 'winnower_cite_link', 11);
function winnower_cite_link($content) {
  global $post;
  global $winnower_skip_cite_link;
  if ($winnower_skip_cite_link) {
    return $content;
  }
  $winnower_settings = get_option('winnower_publisher_settings');
  $display_cite_link = $winnower_settings['display_cite_link'];

  if($display_cite_link) {
    $cite_url = get_post_meta($post->ID, 'cite-link', true);
    $DOI = get_post_meta($post->ID, 'doi-status', true);

    if($DOI) {
      $content .= '<p class="winnower-cite-link">DOI: <a target="_blank" href="https://dx.doi.org/'.$DOI.'">'.$DOI.'</a> provided by <a href="https://thewinnower.com">The Winnower</a>, an open platform for review.</p>';
    } else if($cite_url) {
      $content .= '<p class="winnower-cite-link"><a target="_blank" href="'.$cite_url.'">This paper is currently available for review at The Winnower.</a></p>';
    }
  }

  return $content;
}

add_action('admin_notices', 'winnower_admin_notice');
function winnower_admin_notice() {
  global $pagenow;

  if($pagenow != 'post.php') {
    return;
  }

  global $post;
  $ID = $post->ID;
  $pub_win = get_post_meta($ID, 'win_toggle', true);
  $paper_id = get_post_meta($ID, 'paper-id', true);
  $message_seen = get_post_meta($ID, 'message-success', true);

  if($message_seen) {
    return;
  }

  if($paper_id) {
    update_post_meta($ID, 'message-success', 'true');
    echo '<div class="updated">
      <p>This paper has been posted to the Winnower!</p>
    </div>';
  } else if($pub_win == 'true') {
    echo '
    <div class="error">
      <p>There was an error while trying to submit your paper. Check the Publisher Status in The Winnower Post Settings box for more information.</p>
    </div>
    ';
  }
}

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
    $ID = $post->ID;
    $pub_status = get_post_meta($ID, 'win_toggle', true);

    if($api_key_confirmation == 'true' && $pub_status == 'true' && !is_home()) {
      $iframe_src = $api_endpoint . "blog_badge?";

      echo $before_widget;
      echo "<iframe src=\"${iframe_src}blog_id=${blog_id}&blog_post_id=${ID}\" width=\"1000\" height=\"440\"></iframe>";
      echo $after_widget;
    }
  }
}
