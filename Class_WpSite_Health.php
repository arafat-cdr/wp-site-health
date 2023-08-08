<?php

/**
 *
 * @date 07/August/2023
 * 
 * @time 09:00 PM 
 * 
 * @package Class_WpSite_Health
 * 
 * @author arafat.dml@gmail.com
 * 
 * This Class Show Wp Site Health  Data
 *
 */

class Class_WpSite_Health
{

	static function info()
	{
		// Get all installed PHP modules
		$installed_modules = get_loaded_extensions();

		$recommended_modules = self::wp_requirements();

		// Check which modules are not installed
		$not_installed_modules = array_diff($recommended_modules['wp_all_module_list'], $installed_modules);

		$not_installed_modules = array_values($not_installed_modules);

		// Get the WordPress version
		$wordpress_version = get_bloginfo('version');

		$plugin_info = self::wp_get_plugin_info();

		$theme_info = self::wp_get_theme_info();

		$db_info =  self::wp_get_db_server_info();

		$server_info = self::server_info();

		$is_secure_communications = self::is_secure_communications();

		$is_automatic_update_enabled = apply_filters('automatic_updater_disabled', false);
		$is_auto_update = 'No';
		if ($is_automatic_update_enabled) {
			$is_auto_update = 'Yes';
		}

		# check if wp can contact wp.org
		$can_contact_wp_org = 'No';

		// URL to contact WordPress.org
		$wp_org_url = 'https://api.wordpress.org/';

		// Perform the GET request to WordPress.org
		$response = wp_remote_get($wp_org_url);

		// Check if the request was successful
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
			$can_contact_wp_org = 'Yes';
		}

		# checking wp_loopback
		$has_loopback = 'No';

		// Perform a loopback request to the site
		$response = wp_remote_get(home_url('?doing_wp_cron'));

		// Check if the loopback request was successful
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
			$has_loopback = 'Yes';
		}


		return $site_health_data = array(
			'Wordpress Version' => $wordpress_version,
			'Server'     => $server_info,
			'PHP-version' => phpversion(),
			'PHP-Modules' => array(
				'installed_modules' => $installed_modules,
				'not-instaled_modules' => $not_installed_modules,
			),
			'wp_supported_db_module_name' => $recommended_modules['wp_database_module_list'],
			'wp_version_requirement_info' => $recommended_modules['wp_version_require_info'],

			'object cache' => wp_using_ext_object_cache() ? true : false,
			'page_cache'   => self::wp_get_page_cache(),
			'plugins' => $plugin_info,
			'themes'  => $theme_info,
			'PHP-timezone' => date_default_timezone_get(),
			'SQL-up-to-date' => $db_info,
			'secure-communications' => $is_secure_communications,
			'CRON' => wp_get_schedules(),
			'debug-mode' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
			'File-uploads' => wp_upload_dir(),
			'automatic-update' => $is_auto_update,
			'Contact-wp-org' => $can_contact_wp_org,
			'loopback_enabled' => $has_loopback,
			'HTTPS' => is_ssl() ? 'Yes' : 'No',
		);
	}

	/**
	 *
	 * @method wp_requirements
	 * 
	 * This will return all requirement 
	 * php modules, database, cache
	 *
	 * @return all Modules as array
	 * 
	 */

	static function wp_requirements()
	{
		// Set Recommended Module

		$wp_required_module = array(
			'json',
			'mysqli',
			'mysqlnd',
		);

		$wp_highly_recommended_modules = array(
			'curl',
			'libcurl',
			'dom',
			'exif',
			'fileinfo',
			'hash',
			'igbinary',
			'imagick',
			'intl',
			'ICU',
			'mbstring',
			'openssl',
			'pcre',
			'xml',
			'libxml',
			'zip',
		);

		$wp_recommended_modules_for_cache = array(
			'apcu',
			'memcached',
			'opcache',
			'redis',
		);

		$wp_optional_modules = array(
			'bc',
			'filter',
			'image',
			'libgd',
			'zlib',
			'iconv',
			'simplexml',
			'libxml',
			'sodium',
			'libsodium',
			'xmlreader',
			'zlib',
		);

		$wp_file_change_modules = array(
			'ssh2',
			'ftp',
			'sockets',
		);

		$wp_system_package_modules = array(
			'ImageMagick',
			'Ghost Script'
		);

		$wp_database_modules = array(
			'MySQL 8.0',
			'MariaDB 10.6 / 10.11',
			'Percona MySQL Server 8.0',
			'Amazon Aurora',
			'Amazon RDS for MariaDB 10.6',
			'Amazon RDS for MySQL 8.0',
			'Azure Database for MySQL',
			'Google Cloud MySQL 8.0',
			'DigitalOcean MySQL',
			'IBM Cloud Databases for MySQL',
			'MySQL HeatWave',
		);

		$wp_version_require_php = array(
			'WordPress 6.3' => array(
				'PHP 8.1',
				'PHP 8.2',
				'IMPORTANT: WordPress 6.3 has beta support for PHP 8.2',
			),

			'WordPress 6.2' => array(
				'PHP 7.4',
				'PHP 8.0',
				'PHP 8.1',
				'PHP 8.2',
				'IMPORTANT: WordPress 6.2 has beta support for PHP 8.0, PHP 8.1 and PHP 8.2. If used some of these versions may get some Warnings.',
			),

			'WordPress 6.1' => array(
				'PHP 7.4',
				'PHP 8.0*',
				'PHP 8.1*',
				'PHP 8.2*',
				'IMPORTANT: WordPress 6.1 has beta support for PHP 8.0, PHP 8.1 and PHP 8.2. If used some of these versions may get some Warnings.'
			),

			'WordPress 6.0' => array(
				'PHP 7.4',
				'PHP 8.0*',
				'PHP 8.1*',
				'IMPORTANT: WordPress 6.0 has beta support for PHP 8.0 and PHP 8.1. If used some of these versions may get some Warnings.',
			),

		);

		$recommended_modules = array_merge($wp_required_module, $wp_highly_recommended_modules, $wp_recommended_modules_for_cache, $wp_optional_modules, $wp_file_change_modules, $wp_system_package_modules);

		return array(
			'wp_required_module' => $wp_required_module,
			'wp_highly_recommended_modules' => $wp_highly_recommended_modules,
			'wp_recommended_modules_for_cache' => $wp_recommended_modules_for_cache,
			'wp_optional_modules' => $wp_optional_modules,
			'wp_file_change_modules' => $wp_file_change_modules,
			'wp_system_package_modules' => $wp_system_package_modules,
			'wp_all_module_list' => $recommended_modules,
			'wp_database_module_list' => $wp_database_modules,
			'wp_version_require_info' => $wp_version_require_php
		);
	}

	/**
	 *
	 * @method wp_get_page_cache
	 * This funciton first get a random post id
	 * then it will check if page cache is enable or not
	 * @return bool true or false 
	 * 
	 * ** Note It Not poosible to detect page cache using 
	 * php ** it need client side language to detect it
	 * 
	 * 
	 */

	static function wp_get_page_cache()
	{

		$has_page_cache = false;

		$random_post_id = get_posts('numberposts=1&orderby=rand&fields=ids');

		$random_post_id = $random_post_id[0];

		$page_cache_key = 'page_cache_' . $random_post_id;

		if (false !== ($posts_page_cache = wp_cache_get($random_post_id))) {
			$has_page_cache = true;
		} else if (false !== ($page_cache_data = wp_cache_get($page_cache_key))) {
			$has_page_cache = true;
		}

		return $has_page_cache;
	}

	/**
	 *
	 * @method wp_get_theme_info
	 * 
	 * This will give use all install themes
	 * 
	 * all uptodate themes and need to update
	 * 
	 * theme list
	 * 
	 * @return array of install theme, uptodate theme 
	 * and need update theme 
	 */

	static function wp_get_theme_info()
	{
		$all_install_themes = wp_get_themes();

		$theme_need_to_update = get_site_transient('update_themes');

		$all_install_theme = $uptodate_theme = $need_update_theme = array();

		$all_install_theme_arr = array();
		$need_update_theme_arr = array();

		if ($all_install_themes) {
			foreach ($all_install_themes as $k => $theme) {

				$name = $theme->get('Name');
				$version = $theme->get('Version');

				$all_install_theme[] = array(
					'name' => $name,
					'version' => $version,
				);

				# For later use
				$all_install_theme_arr[$k] = array(
					'name' => $name,
					'version' => $version,
					'author'  => $theme->get('Author'),
				);
			}
		}

		# For Later Use
		$need_update_theme_arr = array();

		if (
			isset($theme_need_to_update->response) &&
			$theme_need_to_update->response
		) {
			foreach ($theme_need_to_update->response as $theme) {
				$name = $theme['theme'];
				$version = $theme['new_version'];

				$need_update_theme[] = array(
					'name' => $all_install_theme_arr[$name]['name'],
					'install_version' => $all_install_theme_arr[$name]['version'],
					'new_version' => $version,
				);

				# Later use
				$need_update_theme_arr[$name] = array(
					'name' => $name,
					'install_version' => $all_install_theme_arr[$name]['version'],
					'new_version' => $version,
				);
			}
		}

		$all_uptodate_theme = array_diff_key($all_install_theme_arr, $need_update_theme_arr);

		$uptodate_theme = array();

		if ($all_uptodate_theme) {
			foreach ($all_uptodate_theme as $k => $theme) {
				$uptodate_theme[] = array(
					'name' => $theme['name'],
					'version' => $theme['version'],
				);
			}
		}

		$active_theme_name = wp_get_theme()->get('Name');

		$active_theme_name_key = strtolower(str_replace(' ', '', $active_theme_name));
		$active_theme_name_key = preg_replace('/[^a-z0-9]/', '', $active_theme_name_key);

		$active_theme = array(
			'name' => $active_theme_name,
			'install_version' => $all_install_theme_arr[$active_theme_name_key]['version'],
			'new_version'    => isset($need_update_theme_arr[$active_theme_name_key]['new_version']) ? $need_update_theme_arr[$active_theme_name_key]['new_version'] : '',
		);

		return array(
			'total_installed_theme'       => count($all_install_theme),
			'total_upto_date_theme'       => count($uptodate_theme),
			'total_need_to_update_theme'  => count($need_update_theme),
			'active_theme'                => $active_theme,
			'all_theme_name_version'       => $all_install_theme,
			'uptodate_theme_name_version'  => $uptodate_theme,
			'need_update_theme_name_version' => $need_update_theme,
		);
	}

	/**
	 *
	 * @method wp_get_plugin_info
	 * 
	 * This function will get all install plugin
	 * 
	 * Then it will list all uptodate plugin
	 * 
	 * Then It will list the plugin that need to
	 * update
	 * @return array of all plugins, updated plugins ,
	 * need to update plugins
	 * 
	 */

	static function wp_get_plugin_info()
	{

		// Get all installed plugins
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed_plugins = get_plugins();

		# Get List of Need to Update Plugins
		$update_plugins = get_site_transient('update_plugins');

		$plugin_need_update = array();

		if (!empty($update_plugins->response)) {
			$plugin_need_update = $update_plugins->response;
		}

		$uptodate_plugins = array_diff_key($installed_plugins, $plugin_need_update);


		$all_plugin_name_version = $uptodate_plugin_name_version = $need_update_plugin_name_version = array();

		if ($installed_plugins) {
			foreach ($installed_plugins as $plugin) {
				$all_plugin_name_version[] = array(
					'name' => $plugin['Name'],
					'version' => $plugin['Version'],
				);
			}
		}

		if ($uptodate_plugins) {
			foreach ($uptodate_plugins as $plugin) {
				$uptodate_plugin_name_version[] = array(
					'name' => $plugin['Name'],
					'version' => $plugin['Version'],
				);
			}
		}

		# For Later Use
		$need_update_plugin_name_version_arr = array();
		if ($plugin_need_update) {
			foreach ($plugin_need_update as $key => $plugin) {
				$need_update_plugin_name_version[] = array(
					'name' => str_replace('-', ' ', $plugin->slug),
					'install_version' => $installed_plugins[$key]['Version'],
					'new_version' => $plugin->new_version,
				);

				# For later Use
				$need_update_plugin_name_version_arr[$key] = array(
					'name' => str_replace('-', ' ', $plugin->slug),
					'install_version' => $installed_plugins[$key]['Version'],
					'new_version' => $plugin->new_version,
				);
			}
		}

		# Now Adding New Version to the version of all 
		# Updated plugin


		# Get All active plugins .

		$all_active_plugins = $all_deactive_plugins = array();

		# For later Use
		$all_active_plugins_arr =  array();

		$active_plugins = get_option('active_plugins');

		if ($active_plugins) {
			foreach ($active_plugins as $key => $plugin) {

				$new_version = '';
				if (isset($need_update_plugin_name_version_arr[$plugin])) {
					$new_version = $need_update_plugin_name_version_arr[$plugin]['new_version'];
				}

				$all_active_plugins[] = array(
					'name' => $installed_plugins[$plugin]['Name'],
					'install_version' => $installed_plugins[$plugin]['Version'],
					'new_version' =>  $new_version,
				);

				# Use for Later Use 
				$all_active_plugins_arr[$plugin] = array(
					'name' => $installed_plugins[$plugin]['Name'],
					'install_version' => $installed_plugins[$plugin]['Version'],
					'new_version' =>  $new_version,
				);
			}
		}

		$all_deactive_plugins_arr = array_diff_key($installed_plugins, $all_active_plugins_arr);

		$all_deactive_plugins =  array();

		if ($all_deactive_plugins_arr) {
			foreach ($all_deactive_plugins_arr as $key => $plugin) {

				$new_version = '';
				if (isset($need_update_plugin_name_version_arr[$key])) {
					$new_version = $need_update_plugin_name_version_arr[$key]['new_version'];
				}

				$all_deactive_plugins[] = array(
					'name' => $plugin['Name'],
					'install_version' => $plugin['Version'],
					'new_version' =>  $new_version,
				);
			}
		}


		return array(
			'total_installed_plugins'       => count($installed_plugins),
			'total_active_plugins'          => count($all_active_plugins),
			'total_deactive_plugins'        => count($all_deactive_plugins),
			'total_upto_date_plugins'       => count($uptodate_plugins),
			'total_need_to_update_plugins'  => count($plugin_need_update),
			'all_plugin_name_version'       => $all_plugin_name_version,
			'active_plugin_name_version'    => $all_active_plugins,
			'deactive_plugin_name_version'  => $all_deactive_plugins,
			'uptodate_plugin_name_version'  => $uptodate_plugin_name_version,
			'need_update_plugin_name_version' => $need_update_plugin_name_version,
		);
	}

	/**
	 *
	 * @method wp_get_db_server_info
	 * 
	 * This function return sql version latest version
	 * and show if it need to update or it is latest version
	 *
	 * @return array of data
	 * 
	 */

	static function wp_get_db_server_info()
	{

		global $wpdb;

		$loaded_extensions = get_loaded_extensions();

		$database_extension = array();

		if (in_array('mysqli', $loaded_extensions)) {

			$version = phpversion('mysqli');

			$database_extension = array(
				'Extension' =>  'mysqli',
				'Server version' => $version,
			);
		} else if (in_array('pdo_mysql', $loaded_extensions)) {

			$version = phpversion('pdo_mysql');

			$database_extension = array(
				'Extension' =>  'pdo_mysql',
				'Server version' => $version,
			);
		}

		# Get Client name and version
		$database_extension['Client version'] = mysqli_get_client_info();

		$database_extension['databse_host'] = DB_HOST;
		$database_extension['databse_user_name'] = DB_USER;
		$database_extension['databse_name'] = DB_NAME;
		$database_extension['databse_prefix'] = $wpdb->prefix;
		$database_extension['databse_charset'] = DB_CHARSET;
		$database_extension['databse_collation'] = $wpdb->charset;

		$max_allwed_packet_size =  $wpdb->get_results("SHOW VARIABLES LIKE 'max_allowed_packet'");
		$packet_size = '';
		if (isset($max_allwed_packet_size[0]->Value)) {
			$packet_size  = $max_allwed_packet_size[0]->Value;
		}

		$database_extension['Max allowed packet size'] = $packet_size;

		$max_conn = $wpdb->get_results("SHOW VARIABLES LIKE 'max_connections'");
		if (isset($max_conn[0]->Value)) {
			$max_conn_num = $max_conn[0]->Value;
		}

		$database_extension['Max connections number  '] = $max_conn_num;

		return $database_extension;
	}

	/**
	 *
	 * @method server_info
	 * 
	 * This method show info about server
	 * 
	 * @return array
	 *
	 */

	static function server_info()
	{

		$curl = curl_version();

		return $server_info = array(
			'Server architecture' => php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('m'),
			'Web server' => $_SERVER['SERVER_SOFTWARE'],
			'PHP version' => phpversion(),
			'PHP SAPI'    => php_sapi_name(),
			'PHP max input variables' => ini_get('max_input_vars'),
			'PHP time limit'    => ini_get('max_execution_time'),
			'PHP memory limit'  => ini_get('memory_limit'),
			'Max input time'    => ini_get('max_input_time'),
			'Upload max filesize' => ini_get('upload_max_filesize'),
			'PHP post max size'  =>  ini_get('post_max_size'),
			'cURL version'      => $curl['version'] . ' ' . $curl['ssl_version'],
			'Is SUHOSIN installed' => extension_loaded('suhosin') ? 'Yes' : 'No',
			'Is the Imagick library available' => extension_loaded('imagick') ? 'Yes' : 'No',
			'Are pretty permalinks supported' => !empty(get_option('permalink_structure')) ? 'Yes' : 'No',
		);
	}


	/**
	 *
	 * @method is_secure_communications
	 * 
	 * Check if communications is secure
	 * 
	 * @return true/false
	 *
	 */

	static function is_secure_communications()
	{

		if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on')) {
			return 'Yes';
		}

		return 'No';
	}
}

# Get The Site Health Information
// $info = Class_WpSite_Health::info();
