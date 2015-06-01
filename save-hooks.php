<?php
defined('ABSPATH') or die("No direct access");

add_action('save_post', 'winnower_save_meta', 10, 2);
function winnower_save_meta($ID, $post) {
  if(isset($_POST['winnower_cross_publish'])) {
    $pub_win = $_POST['winnower_cross_publish'];
    $topic = $_POST['topic'];
    $sub_1 = $_POST['topic_child_1'];
    $sub_2 = $_POST['topic_child_2'];
    $sub_3 = $_POST['topic_child_3'];
    $updatable = $_POST['updatable'];

    update_post_meta($ID, 'winnower_cross_publish', $pub_win);
    update_post_meta($ID, 'topic', $topic);
    update_post_meta($ID, 'topic_child_1', $sub_1);
    update_post_meta($ID, 'topic_child_2', $sub_2);
    update_post_meta($ID, 'topic_child_3', $sub_3);
    update_post_meta($ID, 'updatable', $updatable);
  }
}

add_action('save_post', 'winnower_post_published', 11, 2 );
function winnower_post_published($ID, $post) {
  global $winnower_skip_cite_link;
  update_post_meta($post->ID, 'winnower-message-seen', false);
  $winnower_settings = get_option('winnower_publisher_settings');
  $api_key = $winnower_settings['api_key'];
  $api_endpoint = $winnower_settings['api_endpoint'];

  if(!$api_key) {
    update_post_meta($ID, 'winnower_pub_status', 'Please set your API key <a href="'. admin_url('admin.php?page=winnower_settings') .'">here</a> to enable publishing.');
    return;
  }

  $pub_win = get_post_meta($ID, 'winnower_cross_publish', true);
  $updatable = get_post_meta($ID, 'updatable', true);
  if($pub_win == 'false' || $post->post_status != 'publish') {
    update_post_meta($ID, 'winnower_pub_status', 'Not Yet Published.');
    return;
  } else if($updatable == 'false') {
    update_post_meta($ID, 'winnower_pub_status', 'This paper can no longer be updated.');
    return;
  }
  $title = strip_tags($post->post_title);

  // This is an ugly hack to keep our citation link from showing on the winnower.com
  $winnower_skip_cite_link = true;
  $html = apply_filters('the_content', $post->post_content);
  $winnower_skip_cite_link = false;

  $topic = get_post_meta($ID, 'topic', true);
  $sub_1 = get_post_meta($ID, 'topic_child_1', true);
  $sub_2 = get_post_meta($ID, 'topic_child_2', true);
  $sub_3 = get_post_meta($ID, 'topic_child_3', true);

  $author = get_userdata($post->post_author);

  if (!$author->user_firstname || !$author->user_lastname) {
    update_post_meta($ID, 'winnower_pub_status', 'Please fill out both your first and last name set in your <a target="_blank" href="' . admin_url('profile.php#user_login'). '">wordpress profile</a>.');
    return;
  }

  $body = array(
    'posts' => array(
      'title' => $title,
      'content' => $html,
      'raw_content' => $post->post_content,
      'blog_post_id' => $post->ID,
      'topics' => array($topic, $sub_1, $sub_2, $sub_3),
      'links' => array(
        'self' => get_permalink($post->ID)
      ),
      'authors' => array(
        array(
          "first_name" => $author->user_firstname,
          "last_name" => $author->user_lastname,
          "email" => $author->user_email,
          "is_corresponding" => true
        )
      )
    )
  );

  $url = $api_endpoint . "papers/?api_key=" . $api_key;

  $req_array = array(
    'sslverify' => false,
    'method' => 'POST',
    'timeout' => 30,
    'headers' => array(
      'Content-Type' => 'application/json; charset=utf-8'
    ),
    'body' => json_encode($body)
  );

  $response = wp_remote_post($url, $req_array);

  if(is_wp_error($response)) {
    $error_message = 'Wordpress Error: ' . $response->get_error_message();
    update_post_meta($ID, 'winnower_pub_status', $error_message);
  } else {
    $human_response = winnower_parse_response($response);
    update_post_meta($ID, 'winnower_pub_status', $human_response );
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
      $title = strip_tags($paper['title']);
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
        if (is_array($fields)) {
          foreach($fields as $error) {
            $err_message .= '<li>'.$error.'</li>';
          }
        }
        $err_message .= '</ul>';
        return $err_message;
      } else {
        return 'There has been an unknown error!';
      }
      break;
  }
}

add_action('admin_notices', 'winnower_admin_notice');
function winnower_admin_notice() {
  global $pagenow, $post;

  if($pagenow != 'post.php') {
    return;
  }

  $message_seen = get_post_meta($post->ID, 'winnower-message-seen', true);
  if($message_seen) {
    return;
  }

  $pub_win = get_post_meta($post->ID, 'winnower_cross_publish', true);
  $paper_id = get_post_meta($post->ID, 'paper-id', true);

  if($paper_id) {
    update_post_meta($post->ID, 'winnower-message-seen', true);
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
