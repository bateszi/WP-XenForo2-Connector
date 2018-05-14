<?php
if ( !class_exists('XenForo2Connector') ) {

	class XenForo2Connector {

		public static function init() {
			add_action('admin_init', 'XenForo2Connector::settingsInit');
		}

		public static function settingsInit() {
			register_setting('general', 'xf2wp_forum_base_url');
			register_setting('general', 'xf2wp_api_username');
			register_setting('general', 'xf2wp_api_password');

			add_settings_section(
				'xf2wp_settings_section',
				'XenForo 2 Connector',
				'XenForo2Connector::generalSettingsSectionCb',
				'general'
			);

			add_settings_field(
				'xf2wp_forum_base_url_field',
				'Forum base URL',
				'XenForo2Connector::forumBaseUrlFieldCb',
				'general',
				'xf2wp_settings_section',
				['label_for' => 'xf2wp_forum_base_url_field']
			);

			add_settings_field(
				'xf2wp_api_username_field',
				'API username',
				'XenForo2Connector::usernameFieldCb',
				'general',
				'xf2wp_settings_section',
				['label_for' => 'xf2wp_api_username_field']
			);

			add_settings_field(
				'xf2wp_api_password_field',
				'Password',
				'XenForo2Connector::passwordFieldCb',
				'general',
				'xf2wp_settings_section',
				['label_for' => 'xf2wp_api_password_field']
			);
		}

		public static function generalSettingsSectionCb() {
			echo '<p>Connect WordPress to the XenForo 2 API</p>';
		}

		public static function forumBaseUrlFieldCb() {
			$forumBaseUrl = get_option('xf2wp_forum_base_url');
			?>
			<input
				id="xf2wp_forum_base_url_field"
				placeholder="e.g https://forums.animeuknews.net"
				type="url"
				class="regular-text code"
				name="xf2wp_forum_base_url"
				value="<?php echo isset( $forumBaseUrl ) ? esc_attr( $forumBaseUrl ) : ''; ?>">
			<p class="description">No trailing slash</p>
			<?php
		}

		public static function usernameFieldCb() {
			$apiUsername = get_option('xf2wp_api_username');
			?>
			<input
				id="xf2wp_api_username_field"
				type="text"
				class="regular-text code"
				name="xf2wp_api_username"
				value="<?php echo isset( $apiUsername ) ? esc_attr( $apiUsername ) : ''; ?>">
			<p class="description">Authenticates your XenForo 2 API requests</p>
			<?php
		}

		public static function passwordFieldCb() {
			$apiPassword = get_option('xf2wp_api_password');
			?>
			<input
				id="xf2wp_api_password_field"
				type="password"
				class="regular-text code"
				name="xf2wp_api_password"
				value="<?php echo isset( $apiPassword ) ? esc_attr( $apiPassword ) : ''; ?>">
			<?php
		}

	}

	XenForo2Connector::init();
}
