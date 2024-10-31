<?php
/**
 * Post Notify Users
 *
 * @package    Post Notify Users
 * @subpackage PostNotifyUsersAdmin Management screen
/*
	Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$postnotifyusersadmin = new PostNotifyUsersAdmin();

/** ==================================================
 * Management screen
 */
class PostNotifyUsersAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'post_notify_users_settings', array( $this, 'register_settings' ) );

		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'post-notify-users/postnotifyusers.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'options-general.php?page=postnotifyusers' ) . '">' . __( 'Settings' ) . '</a>';
		}
			return $links;
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_menu() {
		add_options_page( 'Post Notify Users Options', 'Post Notify Users', 'manage_options', 'postnotifyusers', array( $this, 'plugin_options' ) );
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_options() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();

		$scriptname = admin_url( 'options-general.php?page=postnotifyusers' );
		$postnotifyusers_settings = get_option( 'postnotifyusers' );

		?>
		<div class="wrap">
		<h2>Post Notify Users</h2>

			<details>
			<summary><strong><?php esc_html_e( 'Various links of this plugin', 'post-notify-users' ); ?></strong></summary>
			<?php $this->credit(); ?>
			</details>

			<h3><?php esc_html_e( 'Settings' ); ?></h3>	

			<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
			<?php wp_nonce_field( 'pnu_set', 'postnotifyusers_set' ); ?>
			<div style="margin: 15px;">
				<h3><?php esc_html_e( 'User roles to send mail with new post', 'post-notify-users' ); ?></h3>
				<?php
				global $wp_roles;
				$roles = $wp_roles->get_names();
				foreach ( $roles as $role_value => $role_name ) {
					?>
					<div style="margin: 5px; padding: 5px;">
						<input type="checkbox" name="roles[]" value="<?php echo esc_attr( $role_value ); ?>" <?php checked( in_array( $role_value, $postnotifyusers_settings['roles'] ), true ); ?>>
					<?php echo esc_html( translate_user_role( $role_name ) ); ?>
					</div>
					<?php
				}

				?>
				<h3><?php esc_html_e( 'Post types', 'post-notify-users' ); ?></h3>
				<div style="margin: 5px; padding: 5px;">
					<input type="checkbox" name="types[]" value="post" <?php checked( in_array( 'post', $postnotifyusers_settings['types'] ), true ); ?>><?php esc_html_e( 'Post' ); ?>
				</div>
				<div style="margin: 5px; padding: 5px;">
					<input type="checkbox" name="types[]" value="page" <?php checked( in_array( 'page', $postnotifyusers_settings['types'] ), true ); ?>><?php esc_html_e( 'Page' ); ?>
				</div>
				<?php
				$args = array(
					'public'   => true,
					'_builtin' => false,
				);
				$custom_post_types = get_post_types( $args, 'objects', 'and' );
				foreach ( $custom_post_types as $post_type ) {
					?>
					<div style="margin: 5px; padding: 5px;">
						<input type="checkbox" name="types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $postnotifyusers_settings['types'] ), true ); ?>>
					<?php echo esc_html( $post_type->label ); ?>
					</div>
					<?php
				}
				?>
			</div>
			<?php submit_button( __( 'Save Changes' ), 'large', 'Manageset', false ); ?>
			</form>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'post-notify-users' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'post-notify-users' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'post-notify-users' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		if ( isset( $_POST['Manageset'] ) && ! empty( $_POST['Manageset'] ) ) {
			if ( check_admin_referer( 'pnu_set', 'postnotifyusers_set' ) ) {
				$postnotifyusers_settings = get_option( 'postnotifyusers' );
				if ( ! empty( $_POST['roles'] ) ) {
					$postnotifyusers_settings['roles'] = array_map( 'sanitize_text_field', wp_unslash( $_POST['roles'] ) );
					update_option( 'postnotifyusers', $postnotifyusers_settings );
					$flag = true;
				} else {
					$flag = false;
				}
				if ( ! empty( $_POST['types'] ) ) {
					$postnotifyusers_settings['types'] = array_map( 'sanitize_text_field', wp_unslash( $_POST['types'] ) );
					update_option( 'postnotifyusers', $postnotifyusers_settings );
					$flag = true;
				} else {
					$flag = false;
				}
				if ( $flag ) {
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Settings saved.' ) ) . '</li></ul></div>';
				} else {
					echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'Please select at least one item to perform this action on.' ) . '</li></ul></div>';
				}
			}
		}
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( ! get_option( 'postnotifyusers' ) ) {
			$pnu_tbl = array(
				'roles' => array( 'subscriber' ),
				'types' => array( 'post' ),
			);
			update_option( 'postnotifyusers', $pnu_tbl );
		}
	}
}


