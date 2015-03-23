<?php
/*
 * Piklist Checker
 * Version: 0.5.1
 *
 * Verifies that Piklist is installed and activated.
 * If not, plugin will be deactivated and user will be notifed.
 *
 * Developers:
 ** Instructions on how to use this file in your plugin can be found here:
 ** http://piklist.com/user-guide/docs/piklist-checker/
 *
 * Most recent version of this file can be found here:
 * http://s-plugins.wordpress.org/piklist/assets/class-piklist-checker.php
 */

defined('ABSPATH') or die("No direct access");

if (!class_exists('Piklist_Checker')) {
  class Piklist_Checker {
    private static $plugins = array();

    public static function admin_notices() {
      add_action('network_admin_notices', array('piklist_checker', 'show_message'));
      add_action('admin_notices', array('piklist_checker', 'show_message'));
    }

    public static function check($this_plugin) {
      global $pagenow;

      if ($pagenow == 'update.php' || $pagenow == 'update-core.php') {
        return true;
      }

      require_once(ABSPATH . '/wp-admin/includes/plugin.php');

      if (is_multisite()) {
        if (is_plugin_active_for_network(plugin_basename($this_plugin))) {
          if (class_exists('Piklist')) {
            return true;
          }
          piklist_checker::deactivate_plugins($this_plugin, 'network');
        } else {
          if (class_exists('Piklist')) {
            return true;
          }
          piklist_checker::deactivate_plugins($this_plugin, 'single-network');
        }
      } else {
        if (class_exists('Piklist')) {
          return true;
        }
        piklist_checker::deactivate_plugins($this_plugin, 'single');
      }
    }

    public static function deactivate_plugins($this_plugin, $type) {
      if ($type == "single" || $type == "single-network") {
        $plugins = get_option('active_plugins', array());
      } else {
        $plugins = array_flip(get_site_option('active_sitewide_plugins', array()));
      }

      define('TYPE', $type);

      foreach ($plugins as $plugin) {
        if (strstr($this_plugin, $plugin)) {
          array_push(piklist_checker::$plugins, $this_plugin);
          deactivate_plugins($plugin);
          return false;
        }
      }
    }

    public static function message() {
      $piklist_file = 'piklist/piklist.php';
      $piklist_installed = false;

      if (array_key_exists($piklist_file, get_plugins())) {
        $piklist_installed = true;
      }

      $url_proper_dashboard = (TYPE == 'network' ? network_admin_url() : admin_url()) . 'plugins.php'; ?>

      <?php ob_start(); ?>
        <h3><?php _e('The following plugin(s) require Piklist, and have been deactivated:', 'piklist'); ?></h3>
        <ul>
          <?php foreach(piklist_checker::$plugins as $plugin): $data = get_plugin_data($plugin); ?>
            <li>
              <strong><?php echo $data['Title']; ?></strong>
              <br />
              <?php echo $data['Description']; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <h3><?php _e('You can:', 'piklist'); ?></h3>
        <ol>
          <?php

            if ($piklist_installed) {
              global $s;
              $context = 'all';

              if (TYPE == 'single' || TYPE == 'single-network') {
                $activate = '<a href="' . wp_nonce_url(admin_url() . 'plugins.php?action=activate&amp;plugin=' . $piklist_file . '&amp;plugin_status=' . $context . '&amp;s=' . $s, 'activate-plugin_' . $piklist_file) . '" title="' . esc_attr__('Activate Piklist for this site', 'piklist') . '" class="edit">' . __('Activate Piklist for this site', 'piklist') . '</a>';
                echo '<li>' . $activate . '</li>';
              }

              if ((TYPE == 'network' || TYPE == 'single-network') && is_multisite() && is_super_admin()) {
                $activate = '<a href="' . wp_nonce_url(network_admin_url() . 'plugins.php?action=activate&amp;plugin=' . $piklist_file . '&amp;plugin_status=' . $context . '&amp;s=' . $s, 'activate-plugin_' . $piklist_file) . '" title="' . esc_attr__('Network Activate Piklist for all sites.', 'piklist') . '" class="edit">' . __('Network Activate Piklist for all sites.', 'piklist') . '</a>';
                echo '<li>' . $activate . '</li>';
              }
            } else {
              $install = '<a href="' . wp_nonce_url(network_admin_url() . 'update.php?action=install-plugin&amp;plugin=piklist', 'install-plugin_' . 'piklist') . '"title="' . esc_attr__('Install Piklist', 'piklist') . '" class="edit">' . __('Install Piklist', 'piklist') . '</a>';
              echo '<li>' . $install . '</li>';

            }
            printf(__('%1$s %2$sDismiss this message.', 'piklist'),'<li>', '<a href="' . $url_proper_dashboard . '">','</a>', '</li>');

          ?>

        </ol>


        <?php
          $message = ob_get_contents();
          ob_end_clean();
          return $message;
    }

    public static function show_message($message, $errormsg = true) {
      if (!empty(piklist_checker::$plugins)) { ?>
        <div class="error">
            <p>
              <?php echo piklist_checker::message(); ?>
            </p>
        </div>
      <?php }
    }
  }

  piklist_checker::admin_notices();
}
