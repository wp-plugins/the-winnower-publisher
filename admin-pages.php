<?php
defined('ABSPATH') or die("No direct access");

add_filter('piklist_admin_pages', 'winnower_publisher_admin_pages');
function winnower_publisher_admin_pages($pages) {
   $pages[] = array(
    'page_title' => __('The Winnower Publisher'),
    'menu_title' => __('The Winnower', 'piklist'),
    'capability' => 'manage_options',
    'menu_slug' => 'winnower_settings',
    'setting' => 'winnower_publisher_settings',
    'single_line' => true,
    'default_tab' => 'Basic',
    'save_text' => 'Save The Winnower Publisher Settings'
  );
  return $pages;
}

add_filter("plugin_action_links_winnower-publisher/winnower-publisher.php", 'winnower_publisher_settings_link' );
function winnower_publisher_settings_link($links) {
  $url = get_admin_url(null, "options-general.php?page=winnower_settings");
  $settings_link = "<a href=\"$url\">" . __('Settings') . "</a>";
  array_unshift($links, $settings_link);
  return $links;
}

add_action('admin_notices', 'winnower_show_key_warning');
function winnower_show_key_warning() {
  global $pagenow, $post;

  if($pagenow != 'plugins.php' && $pagenow != 'post.php') {
    return;
  }

  $winnower_settings = get_option('winnower_publisher_settings');
  $api_key = $winnower_settings['api_key'];

  if(!$api_key) { ?>
    <div class="updated">
      <p>Winnower Publisher: Almost done! <a href="<?php echo get_admin_url(null, "options-general.php?page=winnower_settings") ?>">Activate the Winnower Publisher to set your api key</a> then start publishing!</p>
    </div>
  <?php }
}
