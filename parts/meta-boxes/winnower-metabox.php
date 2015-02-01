<?php
/*
Title: The Winnower Post Settings
Post Type: post
*/

piklist('field', array(
  'type' => 'radio',
  'field' => 'win_toggle',
  'label' => 'Cross-Post to The Winnower',
  'value' => 'false',
  'description' => 'Check this box to submit post to The Winnower',
  'choices' => array(
    'true' => 'Yes',
    'false' => 'No'
  )
));


function winnower_pubstatus() {
  global $post;
  $ID = $post->ID;

  $status = get_post_meta($ID, 'pub', true);

  if(empty($status)) {
    $status = 'Not Yet Published';
  }

  return $status;
}

piklist('field', array(
  'type' => 'html',
  'field' => 'status',
  'label' => 'Publisher Status',
  'value' => '<span class="pub-status" title="Paper ID: '. winnower_get_paper_id() .'">'. winnower_pubstatus() .'</span>',
  'conditions' => array(
    array(
      'field' => 'win_toggle',
      'value' => 'false',
      'compare' => '!='
    )
  )
));

function winnower_get_topic_list($format) {
  if( get_option('winnower_topics_set') == false || get_option('winnower_topics_set') != date('j') ) {
    global $post;
    $winnower_settings = get_option('winnower_publisher_settings');
    $end_point = $winnower_settings['api_endpoint'];
    $url = $end_point .'topics/';
    $error_message = "We're having trouble communicating with TheWinnower.com, please try again in a few minutes.";

    $response = wp_remote_get($url);

    if(is_wp_error($response)) {
      update_post_meta($post->ID, 'pub', $error_message . " " . $response->get_error_message());
      return;
    } else {
      $response = $response['body'];

      $decode = json_decode($response, true);
      if(!is_array($decode['topics']) || empty($decode['topics'])){
        update_post_meta($post->ID, 'pub', $error_message);
        return;
      }

      update_option('winnower_topics', $decode);
      update_option('winnower_topics_json', $response);
      update_option('winnower_topics_set', date('j'));
    }
  }

  if($format == 'array') {
    $topics = get_option('winnower_topics');
  } else {
    $topics = get_option('winnower_topics_json');
  }

  return $topics;

}

function winnower_create_parent_topic_list($data_array) {
  if(is_array($data_array)) {
    $parent_list[0] = '';
    $topics = $data_array['topics'];
    foreach($topics as $topic) {
      $ID = (string)$topic['id'];
      $name = $topic['name'];
      $parent = $topic['parent_id'];

      if(is_null($parent)) {
        $parent_list[$ID] = $name;
      }
    }
    return $parent_list;
  } else {
    echo $data_array;
  }
}

piklist('field', array(
  'type' => 'select',
  'field' => 'topic',
  'label' => 'Topic of Study',
  'description' => 'Required',
  'attributes' => array(
    'class' => 'widefat',
    'required' => 'required'
  ),
  'choices' => winnower_create_parent_topic_list(winnower_get_topic_list('array')),
  'conditions' => array(
    array(
      'field' => 'win_toggle',
      'value' => 'false',
      'compare' => '!=',
      'reset' => 'false'
    )
  )
));

function winnower_create_child_topic_list($data_array) {
  if(is_array($data_array)) {
    global $post;
    $post_ID = $post->ID;

    $par_id = get_post_meta($post_ID, 'topic', true);

    $child_list[0] = '';
    $topics = $data_array['topics'];
    foreach($topics as $topic) {
      $ID = $topic['id'];
      $name = $topic['name'];
      $parent = $topic['parent_id'];

      if(!is_null($parent) && $parent == $par_id) {
        $child_list[$ID] = $name;
      }
    }
    return $child_list;
  } else {
    echo $data_array;
  }
}

for($i = 1; $i < 4; $i++) {
  piklist('field', array(
    'type' => 'select',
    'field' => 'topic_child_'.$i,
    'label' => 'Sub Topic',
    'description' => 'Optional',
    'attributes' => array(
      'class' => 'widefat'
    ),
    'choices' => winnower_create_child_topic_list(winnower_get_topic_list('array')),
    'conditions' => array(
      array(
        'field' => 'win_toggle',
        'value' => 'false',
        'compare' => '!=',
        'reset' => 'false'
      )
    )
  ));
}

echo '<script>
  (function($){
    $(document).ready(function(){
      var topicSelect = $("._post_meta_topic");
      var subTopicsSelect = $("select[class*=topic_child]");
      var subTopicOptions = "";
      var topicsArr = ' . winnower_get_topic_list("json") . '.topics;
      var subTopicsArr = [];

      $.each(topicsArr, function(i, topic){
        var parentID = topic.parent_id;

        if(parentID !== null) {
          subTopicsArr.push(topic);
        }
      });

      function winnowerParseSubTopics() {
        var ID = $(this).val();

        subTopicOptions = "<option value=\"\"></option>";

        $.each(subTopicsArr, function(i, topic){
          var topicID = topic.id;
          var topicName = topic.name;
          var topicParent = topic.parent_id;

          if(topicParent == ID) {
            subTopicOptions += "<option value="+topicID+">"+topicName+"</option>";
          }
        });

        $("select[class*=topic_child]").each(function(i, childSelecter){
          $(childSelecter).remove("option");
          $(childSelecter).html(subTopicOptions);
        });
      }

      setTimeout(function(){
        topicSelect.on("change", winnowerParseSubTopics);
      }, 0);

    });
  })(jQuery);
  </script>';

piklist('field', array(
  'type' => 'hidden',
  'field' => 'doi-assigned',
  'value' => ''
));

function winnower_get_assigned_status() {
  global $post;

  $assigned_status = get_post_meta($post->ID, 'doi-assigned', true);

  if(strlen($assigned_status) > 1) {
    return "This DOI was assigned " . $assigned_status;
  } else {
    return;
  }
}

function winnower_get_edit_link() {
  global $post;

  $edit_link = get_post_meta($post->ID, 'edit-link', true);

  return $edit_link;
}

function winnower_get_paper_id() {
  global $post;

  $paper_id = get_post_meta($post->ID, 'paper-id', true);

  return $paper_id;
}

piklist('field', array(
  'type' => 'hidden',
  'field' => 'paper_id',
  'value' => winnower_get_paper_id()
));

if(winnower_get_paper_id()) {

  piklist('field', array(
    'type' => 'text',
    'field' => 'doi-status',
    'label' => 'DOI',
    'description' => '',
    'attributes' => array(
      'class' => 'widefat doi-field',
      'readonly' => 'readonly'
    ),
    'conditions' => array(
      array(
        'field' => 'win_toggle',
        'value' => 'false',
        'compare' => '!='
      )
    )
  ));

  piklist('field', array(
    'type' => 'html',
    'field' => 'assigned-status',
    'value' => '
      <p class="doi-status-message" style="margin:0">'.winnower_get_assigned_status().'</p>
      <p class="request-message" style="margin:0"></p>
      <p class="doi-message">
        <button name="check-doi" class="check-doi button button-primary button-large">Retrieve DOI</button>
        &nbsp; You can request a DOI <a target="_blank" href="'.winnower_get_edit_link().'">here</a>.
      </p>',
    'conditions' => array(
      array(
        'field' => 'win_toggle',
        'value' => 'false',
        'compare' => '!='
      )
    )
  ));
} else {
  piklist('field', array(
    'type' => 'html',
    'field' => 'doi_message',
    'label' => 'DOI',
    'value' => "DOI's can be requested for published papers.",
    'conditions' => array(
      array(
        'field' => 'win_toggle',
        'value' => 'false',
        'compare' => '!='
      )
    )
  ));
}

$winnower_settings = get_option('winnower_publisher_settings');
$end_point = $winnower_settings['api_endpoint'];

echo '<script>
      (function($){
          var checkButton = $(".check-doi"),
              doiField = $(".doi-field"),
              papersURL = "' . $end_point . 'papers/'.winnower_get_paper_id().'",
              statusMessage = $(".doi-status-message"),
              requestMessage = $(".request-message");

          checkButton.on("click", winnowerUpdateField);

          function winnowerUpdateField(e) {
            e.preventDefault();
            requestMessage.text("Retrieving DOI...");

            $.getJSON(papersURL, function(data){
              var paper = data.papers,
                  doi = paper.doi,
                  assignedAt = new Date(paper.doi_assigned_at).toLocaleString("en-US", {
                    weekday: "long",
                    year: "numeric",
                    month: "numeric",
                    day: "numeric",
                    hour: undefined,
                    minute: undefined,
                    second: undefined
                  }),
                  updatable = paper.updatable;

              if(paper.doi) {
                requestMessage.text("");
                doiField.val(paper.doi);
                statusMessage.text("You must click update on this post to save the DOI. It was assigned "+assignedAt);
                $("._post_meta_doi-assigned").val(assignedAt);
                $("._post_meta_updatable").val(""+updatable);
                $(".pub-status").text("This is paper has a DOI and will not update on The Winnower.");
              } else {
                doiField.val("");
                statusMessage.text("");
                $("._post_meta_doi-assigned").val("");
                requestMessage.text("There no DOI currently assigned to this paper.");
              }
            })
            .fail(function(jqhxr, statusText, err){
              requestMessage.text("There was an error retrieving your DOI.");
              requestMessage.attr("title",statusText + " " + err);
              console.log(err);
            });
          }
      })(jQuery);
    </script>
  ';

piklist('field', array(
  'type' => 'hidden',
  'field' => 'pub'
));

piklist('field', array(
  'type' => 'hidden',
  'field' => 'updatable',
  'value' => 'true'
));
