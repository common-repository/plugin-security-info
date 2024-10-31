<?php

/**
 * The main plugin file
 *
 * @package WordPress_Plugins
 * @subpackage PluginSecurityInfo
 */

/*
Plugin Name: Plugin Security Info
Version: 6.6.1
Description: Adds last update date and other age or updatable info to the plugins page to inform the admin at a glance how old a plugin is and if it can be updated automatically.
Author: Daniel Unterberger
Author URI: https://unterberger.media/
Plugin URI: https://unterberger.media/
Donate Link: https://unterberger.media/contact/
Text Domain: plugin-security-info
Domain Path: /languages


Copyright 2016-2024 Daniel Unterberger (email: info@unterberger.media)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
* The PluginSecurityInfo class
*
* @package WordPress_Plugins
* @subpackage PluginSecurityInfo
* @since 1.0
* @author daniel@unterberger.media
*/
class Pmd_Plugin_Security_Info {

    private $options = [];

	// set up initial actions
    public function __construct() {
        add_filter('manage_plugins_columns',        [ $this, 'pmd_plugins_columns' ]);
        add_action('admin_head-plugins.php',        [ $this, 'pmd_column_css_styles' ]);
        add_action('manage_plugins_custom_column',  [ $this, 'pmd_activated_columns' ], 10, 3);
    }

	// create column
    public function pmd_plugins_columns($columns) {

        $columns['security_info']      = __('Security Info', 'SecurityInfo');
        return $columns;
    }
// Main Processing of plugin files based on recursive modification time
    public function pmd_activated_columns($column_name, $plugin_file, $plugin_data) {



      $first = '';
      $plugin_base_name = plugin_basename($plugin_file);

    //Plugins folder path, we know this plugins dir, other plugins are in same parent-folder
      $plugins_url = plugin_dir_path( __DIR__  );

		// extract plugin folder name
        if (isset($plugin_base_name)) {
            $arr = explode("/", $plugin_base_name, 2);
            $first = $arr[0];
        }

        if ($first != '') {
          if (is_dir($plugins_url . "/" . $first)) {
              $plugin_time = $this->pmd_folder_modification_time($plugins_url . "/" . $first);
          } else {
              $plugin_time = filemtime($plugins_url . "/" . $first);
          }


          $color_ok                = "green";
          $color_warning           = "orange";
          $color_error             = "red";

          $text_warning_no_updates = "no automatic updates!";
          $text_label_age          = "Age :";
          $text_date_time          = date("M, d Y - H:i:s.", $plugin_time);



            // output diff time "age"
            $date_now    = new DateTime("now");
            $date_plugin = new DateTime( date("Y-m-d H:i:s", $plugin_time ) ) ;

            $interval = $date_now->diff( $date_plugin );

            $days = ( $interval->y * 365 + $interval->m * 30 + $interval->d );

            $text_age_color = $color_ok;
            if ( $interval->y == 0  and $interval->m > 6 ) {
              $text_age_color= $color_warning;
            }
            if ( $interval->y > 0  ) {
              $text_age_color= $color_error;
            }

            $text_date_age =
                 " "
               . $text_label_age
               . " "
               . $interval->y . "y "
               . $interval->m . "m "
               . $interval->d . "d "
               . " (". $days ."d)";

            $css_age_width = min( (int)($days / 4 ), 200 );

          // output template:
          ?>
            <div class="plugin-security-info">

              <div class="psi-last-update-date">
                <?= $text_date_time ?>
              </div>

            <?php if ( !isset( $plugin_data['url'] ) ) : ?>
              <div class="psi-automatic-info">
                <div class="icon-box" style="background-color:<?= $color_error ?>">
                  &nbsp;
                </div>
                <?= $text_warning_no_updates ?>
              </div>
            <?php endif ?>

            <div class="psi-age">
              <div class="icon-box" style="background-color:<?= $text_age_color ?>">
                &nbsp;
              </div>
              <?= $text_date_age ?>
              <div class="icon-bar" style="width:<?= $css_age_width  ?>px;background-color:<?= $text_age_color ?>">
                &nbsp;
              </div>
            </div>

          </div>
          <?php
        }
    }
	// column style
    public function pmd_column_css_styles() {
        ?>
        <style>
          #security_info {
            width: 18%;
          }
          #wpbody-content .plugins .plugin-title,
          #wpbody-content .plugins .theme-title {
                width:25%;
				white-space: normal;
			 }
          .plugin-security-info .icon-box {
            display:inline-block;
            margin:0.25em;
            width:1em;
            height:1em;
          }
          .plugin-security-info .icon-bar {
            display:block;
            margin-top:0.25em;
            height:4px;
          }
        </style>
        <?php

    }
// Recursive folder file modification time
    public function pmd_folder_modification_time($dir) {
        $foldermtime = 0;

        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, $flags));

        while ($it->valid()) {
            if (($filemtime = $it->current()->getMTime()) > $foldermtime) {
                $foldermtime = $filemtime;
            }
            $it->next();
        }

        return $foldermtime ? : false;
    }

}
// Initiate the plugin.
$GLOBALS['pmd_plugin_security_info'] = new Pmd_Plugin_Security_Info;
