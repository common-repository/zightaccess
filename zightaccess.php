<?php
/*
Plugin Name: ZigHtaccess
Plugin URI: https://www.zigpress.com/plugins/zightaccess/
Description: Edit your .htaccess file from the WordPress admin console.
Version: 1.1
Author: ZigPress
Author URI: https://www.zigpress.com/
License: GPLv2 or later
Requires PHP: 5.6
*/


/*
Copyright (c) 2014-2020 ZigPress

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation Inc, 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
*/


require_once dirname(__FILE__) . '/admincallbacks.php';


if (!class_exists('zightaccess')) {


	final class zightaccess {


		public $protocol;
		public $server;
		public $callback_url;
		public $plugin_folder;
		public $plugin_directory;
		public $plugin_path;
		public $admin_page_name;
		public $path_htaccess;
		public $path_htaccess_backup;
		public $folder_htaccess_backup;
		public $default_content;


		private static $_instance = null;


		public static function getinstance() {
			if (is_null(self::$_instance)) {
				self::$_instance = new self;
			}
			return self::$_instance;
		}


		private function __clone() {}


		private function __wakeup() {}


		private function __construct() {

			$this->protocol = 'http://';
			if ((@$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || ($_SERVER['SERVER_PORT'] == '443')) $this->protocol = 'https://';
			$this->server = $_SERVER['SERVER_NAME'];
			$this->callback_url = $this->protocol . $this->server . preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
			$this->plugin_folder = get_bloginfo('url') . '/' . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)); # no final slash
			$this->plugin_directory = WP_PLUGIN_DIR . '/zightaccess/';
			$this->plugin_path = str_replace('plugin.php', 'zightaccess.php', __FILE__);
			$this->admin_page_name = 'zightaccess-editor';
			$this->path_htaccess = ABSPATH . '.htaccess';
			$this->path_htaccess_backup = ABSPATH . 'wp-content/zightaccess/.htaccess';
			$this->folder_htaccess_backup = ABSPATH . 'wp-content/zightaccess/';
			$this->default_content = "# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . /index.php [L]\n</IfModule>\n\n# END WordPress\n";
			$this->get_params();
			add_action('plugins_loaded', array($this, 'action_plugins_loaded'));
			add_action('admin_init', array($this, 'action_admin_init'));
			add_action('admin_enqueue_scripts', array($this, 'action_admin_enqueue_scripts'));
			add_action('admin_menu', array($this, 'action_admin_menu'));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'filter_plugin_action_links'));
			add_filter('plugin_row_meta', array($this, 'filter_plugin_row_meta'), 10, 2 );
		}


		# ACTIVATION AND DEACTIVATION HOOKS


		public function autodeactivate($requirement) {
			if (!function_exists( 'get_plugins')) require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			$plugin = plugin_basename($this->plugin_path);
			$plugindata = get_plugin_data($this->plugin_path, false);
			if (is_plugin_active($plugin)) {
				deactivate_plugins($plugin);
				wp_die($plugindata['Name'] . ' requires ' . $requirement . ' and has been deactivated. <a href="' . admin_url('plugins.php') . '">Click here to go back.</a>');
			}
		}


		# ACTIONS


		public function action_plugins_loaded() {
			global $wp_version;
			if (version_compare(phpversion(), '5.6.0', '<')) $this->autodeactivate('PHP 5.6.0');
			if (version_compare($wp_version, '4.8', '<')) $this->autodeactivate('WordPress 4.8');
		}


		public function action_admin_init() {
			if (@$this->params['zigaction'] != '') new zightaccess_admincallbacks($this->params['zigaction']);
		}


		public function action_admin_enqueue_scripts() {
			wp_enqueue_style('zightaccess-admin', $this->plugin_folder . '/css/admin.css', false, rand());
			wp_enqueue_script('zightaccess-admin', $this->plugin_folder .'/js/admin.js', array('jquery'), rand(), true);
		}


		public function action_admin_menu() {
			add_management_page('ZigHtaccess', 'ZigHtaccess', 'manage_options', $this->admin_page_name, array($this, 'admin_page'));
		}


		# FILTERS


		public function filter_plugin_action_links($links) {
			$newlinks = array(
				'<a href="' . get_admin_url() . 'tools.php?page=' . $this->admin_page_name . '">Editor Page</a>',
			);
			return array_merge( $links, $newlinks );
		}


		public function filter_plugin_row_meta($links, $file) {
			$plugin = plugin_basename(__FILE__);
			$newlinks = array(
				'<a target="_blank" href="https://www.zigpress.com/donations/">Donate</a>',
				'<a href="' . get_admin_url() . 'tools.php?page=' . $this->admin_page_name . '">Editor Page</a>',
			);
			if ($file == $plugin) return array_merge($links, $newlinks);
			return $links;
		}


		# ADMIN CONTENT


		public function admin_page() {
			if (!current_user_can('manage_options')) { wp_die('You are not allowed to do this.'); }
			if (!defined('ZIGPRESS_GLOBAL_NOTICE_HANDLER_ADMIN') && ($this->result_type != '')) echo $this->show_result($this->result_type, $this->result_message);
			$content = $this->get_htaccess_content();
			?>
			<div class="wrap zightaccess-admin">
				<h2>ZigHtaccess</h2>
				<p>WARNING: Changing .htaccess content can lock you out of your site, including the admin pages! Use with care!</p>
				<h3>Current .htaccess content</h3>
				<form onsubmit="return sure('Are you sure you want to make changes to your .htaccess file?')" id="form_zightaccess" action="<?php echo $this->callback_url ?>?page=<?php echo $this->admin_page_name ?>" method="post" role="search">
					<input type="hidden" name="zigaction" value="update_htaccess" />
					<input type="hidden" name="default_content" id="default_content" value="<?php echo $this->default_content ?>" />
					<?php wp_nonce_field('zigpress_nonce'); ?>
					<textarea class="large-text codefield" rows="25" name="content" id="content"><?php echo $content ?></textarea>
					<input class="button button-primary" type="submit" value="Save Changes" />
					&nbsp;
					<a class="button" id="reset_to_default" href="#">Reset to WordPress default</a>
				</form>
				<br />
				<h3>Current .htaccess backup</h3>
				<?php
				if (file_exists($this->path_htaccess_backup)) {
					?>
					<p>
						Backup file exists at <?php echo $this->path_htaccess_backup ?> 
						&nbsp;
						<a onclick="return sure('Are you sure you want to restore from the backup?')" class="button" href="<?php echo $this->callback_url ?>?page=<?php echo $this->admin_page_name ?>&zigaction=restore_backup&_wpnonce=<?php echo wp_create_nonce('zigpress_nonce')?>">Restore</a>
						&nbsp;
						<a onclick="return sure('Are you sure you want to delete the backup?')" class="button" href="<?php echo $this->callback_url ?>?page=<?php echo $this->admin_page_name ?>&zigaction=delete_backup&_wpnonce=<?php echo wp_create_nonce('zigpress_nonce')?>">Delete</a>
					</p>
					<?php
				} else {
					?>
					<p>Backup file does NOT currently exist and will be created when .htaccess file is saved.</p>
					<?php
				}
				?>
				<h3>Important</h3>
				<p>
					If you make changes and end up being locked out of your site's admin pages, 
					you will need to FTP in to your site and delete the .htaccess file at root level 
					(the one in the same folder as your wp-config.php file). 
					You can then copy the backup .htaccess file from the wp-content/zightaccess folder to replace it.
					Your FTP client must be set to show hidden files. We recommend <a href="https://filezilla-project.org/" target="_blank">FileZilla</a>.
				</p>
				<p>
					This plugin is offered without any kind of warranty, promise or guarantee, 
					and the plugin author bears no responsibility for any problems or loss of code or data incurred as a result of using this plugin. 
					By using this plugin you are agreeing to this condition.
				</p>
			</div>
			<?php
		}


		# SUPPORTING FUNCTIONS


		public function htaccess_exists() {
			clearstatcache();
			return file_exists($this->path_htaccess);
		}


		public function htaccess_backup_exists() {
			clearstatcache();
			return file_exists($this->path_htaccess_backup);
		}


		public function get_htaccess_content() {
			clearstatcache();
			if (!file_exists($this->path_htaccess)) return '';
			$content = @file_get_contents($this->path_htaccess);
			return $content;			
		}


		public function get_htaccess_backup_content() {
			clearstatcache();
			if (!file_exists($this->path_htaccess_backup)) return '';
			$content = @file_get_contents($this->path_htaccess_backup);
			return $content;			
		}


		public function delete_htaccess_backup() {
			clearstatcache();
			if (!file_exists($this->path_htaccess_backup)) return false;
			unlink($this->path_htaccess_backup);
			return true;
		}


		public function save_htaccess($content) {
			if (!chmod($this->path_htaccess, 0644)) {
				return false;
			}
			if (!unlink($this->path_htaccess)) {
				return false;
			}
			if (file_put_contents($this->path_htaccess, $content) === false) {
				return false;
			}
			return true;
		}


		public function save_htaccess_backup($content) {
			$folder_exists = true;
			if (!file_exists($this->folder_htaccess_backup)) {
				$folder_exists = mkdir($this->folder_htaccess_backup, 0755);
			} else {
				#trigger_error('FOLDER ALREADY EXISTS', E_USER_WARNING);
			}
			if ($folder_exists) {
				#trigger_error('FOLDER WAS CREATED', E_USER_WARNING);
				$created_file = file_put_contents($this->path_htaccess_backup, $content, LOCK_EX);
				if ($created_file !== false) {
					#trigger_error('FILE WAS CREATED', E_USER_WARNING);
					chmod($this->path_htaccess_backup, 0644);	
					return true;
				} else {
					#trigger_error('FILE COULD NOT BE CREATED', E_USER_WARNING);
				}
			} else {
				#trigger_error('FOLDER COULD NOT BE CREATED', E_USER_WARNING);
			}
			return false;
		}


		# UTILITIES


		public function is_classicpress() {
			return function_exists('classicpress_version');
		}


		public function get_params() {
			$this->params = array();
			foreach ($_REQUEST as $key=>$value) {
				$this->params[$key] = $value;
				if (!is_array($this->params[$key])) { $this->params[$key] = strip_tags(stripslashes(trim($this->params[$key]))); }
				# need to sanitise arrays as well really
			}
			if (!is_numeric(@$this->params['zigpage'])) { $this->params['zigpage'] = 1; }
			if ((@$this->params['zigaction'] == '') && (@$this->params['zigaction2'] != '')) { $this->params['zigaction'] = $this->params['zigaction2']; }
			$this->result = '';
			$this->result_type = '';
			$this->result_message = '';
			if ($this->result = base64_decode(@$this->params['r'])) list($this->result_type, $this->result_message) = explode('|', $this->result); # base64 for ease of encoding
		}


		public function show_result($strType, $strMessage) {
			$strOutput = '';
			if ($strMessage != '') {
				$strClass = '';
				switch (strtoupper($strType)) {
					case 'OK' :
						$strClass = 'updated';
					break;
					case 'INFO' :
						$strClass = 'updated highlight';
					break;
					case 'ERR' :
						$strClass = 'error';
					break;
					case 'WARN' :
						$strClass = 'error';
					break;
				}
				if ($strClass != '') {
					$strOutput .= '<div class="msg ' . $strClass . '" title="Click to hide"><p>' . $strMessage . '</p></div>';
				}
			}
			return $strOutput;
		}


	} # END OF CLASS


} else {


	wp_die('Namespace clash! Class zightaccess already exists.');


}


$zightaccess = zightaccess::getinstance();


# EOF
