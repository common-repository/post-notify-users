<?php
/**
 * Post Notify Users
 *
 * @package    PostNotifyUsers
 * @subpackage Post Notify Users Main function
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

$postnotifyusers = new PostNotifyUsers();

/** ==================================================
 * Main Functions
 */
class PostNotifyUsers {

	/** ==================================================
	 * Post custom types
	 *
	 * @var $post_custom_types  post_custom_types.
	 */
	private $post_custom_types;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		do_action( 'post_notify_users_settings' );
		$postnotifyusers_settings = get_option( 'postnotifyusers' );
		foreach ( $postnotifyusers_settings['types'] as $custom_type ) {
			add_action( 'publish_' . $custom_type, array( $this, 'pnu_notify' ), 10, 2 );
		}
	}

	/** ==================================================
	 * Send Mail
	 *
	 * @param int    $post_id  post_id.
	 * @param object $post  post.
	 * @return int $post_id
	 * @since 1.00
	 */
	public function pnu_notify( $post_id, $post ) {

		/* get the Userdata */
		$users = get_users();

		$title = esc_html( $post->post_title );
		if ( empty( $post->post_excerpt ) ) {
			if ( function_exists( 'mb_substr' ) ) {
				$excerpt = mb_substr( $post->post_content, 0, 100 ) . '...';
			} else {
				$excerpt = substr( $post->post_content, 0, 100 ) . '...';
			}
		} else {
			$excerpt = esc_html( $post->post_excerpt );
		}
		$cats = get_the_category( $post_id );
		if ( $cats ) {
			$category = $cats[0]->cat_name;
			$sub_cat = ' - ' . $category;
		} else {
			$category = null;
			$sub_cat = null;
		}
		$subject = $title . ' - ' . get_option( 'blogname' ) . $sub_cat;
		$author = $post->post_author;
		$name = get_the_author_meta( 'display_name', $author );
		$permalink = get_permalink( $post_id );

		/* thumbnail */
		$img_width = '100';
		$img_height = '100';

		global $wpdb;
		$thumb_id = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT	meta_value
				FROM	{$wpdb->prefix}postmeta
				WHERE	post_id = %d
				AND		meta_key = '_thumbnail_id'
				",
				$post_id
			)
		);

		if ( $thumb_id ) {
			$img = wp_get_attachment_image_src( $thumb_id, array( $img_width, $img_height ) );
			$img_tag = '<img style="border-radius: 5px;" src="' . $img[0] . '" alt="' . $title . '" width="' . $img[1] . ' " height="' . $img[2] . '" />';
		} else if ( get_option( 'site_icon' ) ) {
			$siteicon_id = get_option( 'site_icon' );
			$img = wp_get_attachment_image_src( $siteicon_id, array( $img_width, $img_height ) );
			$img_tag = '<img style="border-radius: 5px;" src="' . $img[0] . '" alt="' . $title . '" width="' . $img[1] . ' " height="' . $img[2] . '" />';
		} else {
			$img_tag = '<img style="border-radius: 5px;" src="' . includes_url() . 'images/w-logo-blue.png" alt="' . $title . '" width="' . $img_width . ' " height="' . $img_height . '" />';
		}

		/* blog card */
		$blog_card = '
<div style="border:1px solid #ddd; word-wrap:break-word; max-width:100%; border-radius:5px; margin: 30px;">
	<a style="text-decoration: none;" href="' . $permalink . '">
		<div style="float: right; padding: 10px;">' . $img_tag . '</div>
		<div style="line-height:120%; padding: 10px;">
			<div style="padding: 0.25em 0.25em; color: #494949; background: transparent; border-left: solid 5px #7db4e6; ">
				<div style="font-weight: bold; display: block;">' . $subject . ' </div>
				<div style="color: #333; ">' . $excerpt . '</div>
			</div>
		</div>
		<div style="clear: both;"></div>
	</a>
</div>';

		$postcontent = __( 'Title' ) . ': ' . $title . '<br />' .
						__( 'Author' ) . ': ' . $name . '<br />' .
						__( 'Category' ) . ': ' . $category . '<br />' .
						__( 'Content' ) . ':<br />' .
						$post->post_content;
		$postcontent .= '<hr>' . $blog_card;

		/* Mail Use HTML-Mails */
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		/* for Current roles */
		global $wp_roles;
		$roles = $wp_roles->get_names();
		$current_roles = array();
		foreach ( $roles as $role_value => $role_name ) {
			$current_roles[] = $role_value;
		}
		/* Save roles */
		$postnotifyusers_settings = get_option( 'postnotifyusers' );
		/* Send mail */
		foreach ( $users as $user ) {
			foreach ( $user->roles as $user_role_value ) {
				if ( in_array( $user_role_value, $postnotifyusers_settings['roles'] )
					|| in_array( $user_role_value, $current_roles )
					&& get_post_meta( $post_id, 'pnu_notified', true ) != '1'
					) {
					wp_mail( $user->user_email, $subject, $postcontent );
					/* update post metadata */
					update_post_meta( $post_id, 'pnu_notified', '1', true );
				}
			}
		}

		/* Mail default */
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		return $post_id;
	}

	/** ==================================================
	 * Mail content type
	 *
	 * @return string 'text/html'
	 * @since 1.00
	 */
	public function set_html_content_type() {
		return 'text/html';
	}
}
