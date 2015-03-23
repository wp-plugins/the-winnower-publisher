<?php
/*
Title: Publisher Settings
Setting: winnower_publisher_settings
Order: 2
*/

$debug = isset($_GET['debug']) ? true : false;

if($debug){
  echo '<pre>';
  var_dump('winnower_publisher_settings', get_option('winnower_publisher_settings'));
  var_dump('winnower_topics', get_option('winnower_topics'));
  var_dump('winnower_topics_last_updated', get_option('winnower_topics_last_updated'));
  echo '</pre>';
}


piklist('field', array(
  'type' => 'text',
  'field' => 'api_key',
  'label' => 'API Key',
  'description' => 'Your API Key can be found on <a target="_blank" href="https://thewinnower.com">your account page</a>.',
  'attributes' => array(
    'class' => 'widefat winnower_api_key'
  )
));

piklist('field', array(
  'type' => 'html',
  'field' => 'api_status',
  'value' => '<div class="js-api-status"></div>'
));

piklist('field', array(
  'type' => 'checkbox',
  'field' => 'display_cite_link',
  'label' => 'Display Citation Link',
  'description' => 'Check this box to append a link to The Winnower papers that you have cross-posted.',
  'choices' => array(
    'true' => ''
  )
));

piklist('field', array(
  'type' => 'hidden',
  'field' => 'api_key_confirmation',
  'value' => 'false'
));

piklist('field', array(
  'type' => 'hidden',
  'field' => 'user_blog_id',
  'value' => 0
));

piklist('field', array(
  'type' => $debug ? 'text' : 'hidden',
  'label' => $debug ? 'Api Endpoint' : false,
  'field' => 'api_endpoint',
));

?><script>
    (function($){
      $(document).ready(function(){
        var status = $(".js-api-status");
        var keyField = $(".winnower_api_key");
        function checkKey() {
          var endPoint = $("#winnower_publisher_settings_api_endpoint_0").val();

          if (!endPoint) {
            status.text("No api endpoint set, please deactivate and activate this plugin.");
            return;
          }

          var url = endPoint + "current_user?api_key=";
          var apiKey = keyField.val();
          var checkUrl = url + apiKey;

          if(!apiKey) {
            $(".winnower_publisher_settings_api_key_confirmation").val("false");
            status.text("Please enter an API Key.");
            return;
          }

          status.text("Checking your API Key...");

          $.ajax({
            url: checkUrl,
            statusCode: {
              503: function(){
                status.text("We're having trouble communicating with TheWinnower.com, please try again in a few minutes.");
              }
            },
            success: function(data){
              $(".winnower_publisher_settings_api_key_confirmation").val("true");
              $(".winnower_publisher_settings_user_blog_id").val(data.users.blog_id);
              status.text("Key registered to " + data.users.name);
            },
            error: function(){
              $(".winnower_publisher_settings_api_key_confirmation").val("false");
              $(".winnower_publisher_settings_user_blog_id").val(0);
              status.text("Your key is not valid.");
            }
          });
        }

        keyField.on("blur", checkKey);
        checkKey();
      });
    })(jQuery);
  </script>
<?php
