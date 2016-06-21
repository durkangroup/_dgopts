<?php

/*
Plugin Name: DG Options
Plugin URI: https://durkangroup.com
Description: Options for sites.
Version: 0.2.1
Author: Durkan Group
Author URI: https://durkangroup.com
License: GPL2
*/

if (!defined('WPINC'))
  die();

// github updater
add_action( 'init', 'dgopts_github_plugin_updater_init' );
function dgopts_github_plugin_updater_init() {

  include_once 'inc/updater.php';

  define( 'WP_GITHUB_FORCE_UPDATE', true );

  if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin

    $username = 'durkangroup';
    $repo = '_dgopts';

    $config = array(
      'slug' => plugin_basename( __FILE__ ),
      'proper_folder_name' => $repo,
      'api_url' => 'https://api.github.com/repos/'.$username.'/'.$repo.'',
      'raw_url' => 'https://raw.github.com/'.$username.'/'.$repo.'/master',
      'github_url' => 'https://github.com/'.$username.'/'.$repo.'',
      'zip_url' => 'https://github.com/'.$username.'/'.$repo.'/archive/master.zip',
      'sslverify' => true,
      'requires' => '4.0',
      'tested' => '4.5.2',
      'readme' => 'README.md',
      'access_token' => '',
    );

    new WP_GitHub_Updater( $config );

  }

}


/* Standard Cleanups */

if(get_option('standard_cleanups')) {

  // CLEAN UP OUTPUT OF STYLESHEET <LINK> TAGS
  function clean_style_tag($input) {
    preg_match_all("!<link rel='stylesheet'\s?(id='[^']+')?\s+href='(.*)' type='text/css' media='(.*)' />!", $input, $matches);
    // ONLY DISPLAY MEDIA IF IT IS MEANINGFUL
    $media = $matches[3][0] !== '' && $matches[3][0] !== 'all' ? ' media="' . $matches[3][0] . '"' : '';
    return '<link rel="stylesheet" href="' . $matches[2][0] . '"' . $media . '>' . "\n";
  }

  // CLEAN UP OUTPUT OF <SCRIPT> TAGS
  function clean_script_tag($input) {
    $input = str_replace("type='text/javascript' ", '', $input);
    return str_replace("'", '"', $input);
  }

  // REMOVE WP VERSION PARAM FROM ANY ENQUEUED SCRIPTS
  function vc_remove_wp_ver_css_js( $src ) {
    if ( strpos( $src, 'ver=' ) )
      $src = remove_query_arg( 'ver', $src );
    return $src;
  }

  // REMOVE 'TEXT/CSS' FROM OUR ENQUEUED STYLESHEET
  function _dg_style_remove($tag) {
    return preg_replace('~\s+type=["\'][^"\']++["\']~', '', $tag);
  }

  // REMOVE UNNECESSARY SELF-CLOSING TAGS
  function remove_self_closing_tags($input) {
    return str_replace(' />', '>', $input);
  }

  // DISABLE EMOJIS
  function disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
  }
  function disable_emojis_tinymce( $plugins ) {
    if ( is_array( $plugins ) ) {
      return array_diff( $plugins, array( 'wpemoji' ) );
    } else {
      return array();
    }
  }

  add_action( 'init', 'disable_emojis' );

  remove_action('wp_head', 'wp_oembed_add_discovery_links'); // DISCOVERY LINK TAGS IN HEAD
  remove_action('wp_head','wp_oembed_add_host_js'); // REMOVE OEMBED JS FILE FROM FOOTER
  remove_action('wp_head','rest_output_link_wp_head'); // REMOVE REST JSON URL FROM HEAD

  add_filter('style_loader_tag', 'clean_style_tag');
  add_filter('script_loader_tag', 'clean_script_tag');
  add_filter('style_loader_src', 'vc_remove_wp_ver_css_js', 9999 );
  add_filter('script_loader_src', 'vc_remove_wp_ver_css_js', 9999 );
  add_filter('style_loader_tag', '_dg_style_remove');
  add_filter('get_avatar', 'remove_self_closing_tags');
  add_filter('comment_id_fields', 'remove_self_closing_tags');
  add_filter('post_thumbnail_html', 'remove_self_closing_tags');
  add_filter('wp_calculate_image_srcset_meta','__return_null'); // DISABLE RESPONSIVE IMAGES
}




/* Options */

add_action('admin_menu', '_dgopts_menu');
function _dgopts_menu() {
  add_options_page('DG Options', 'DG Options', 'manage_options', '_dgopts', '_dgopts_options');
}

add_action('admin_init', 'register__dgopts_settings');
function register__dgopts_settings() {
  register_setting('_dgopts-settings-group', 'standard_cleanups');
}

function _dgopts_options() {
  if (!current_user_can('manage_options'))
    wp_die(__('You do not have sufficient permissions to access this page.'));

?>

<div class="wrap">
    <h2>DG Options</h2>
    <form method="post" action="options.php">

    <?php settings_fields('_dgopts-settings-group'); ?>
    <?php do_settings_sections('_dgopts-settings-group'); ?>

    <table class="form-table">
      <tr valign="top">
        <th scope="row">Standard WordPress Cleanups</th>
        <td>
          <fieldset>
            <label for="standard_cleanups">
              <input name="standard_cleanups" id="standard_cleanups" value="1" <?=((get_option('standard_cleanups'))) ? 'checked="checked"' : ''; ?> type="checkbox">
              Yes
            </label>
          </fieldset>
        </td>
      </tr>
    </table>

    <?php submit_button(); ?>

    </form>
</div>
<?php

}

