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
			register_setting('general', 'xf2wp_forum_id');

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

			add_settings_field(
				'xf2wp_forum_id_field',
				'Target forum',
				'XenForo2Connector::forumIdFieldCb',
				'general',
				'xf2wp_settings_section',
				['label_for' => 'xf2wp_forum_id_field']
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

		public static function forumIdFieldCb() {
			$selectedForumId = get_option('xf2wp_forum_id');
			$apiResponse = XenForo2Connector::apiRequest('api/forums', 'get');

			if (empty($apiResponse)) {
			    ?>
                <p class="description">No forums found.</p>
                <?php
            } else {
			    ?>
                <select name="xf2wp_forum_id" id="xf2wp_forum_id_field">
					<?php
					foreach ( $apiResponse["forums"] as $forumId => $forum ) {
						?>
                        <option
							<?php echo ( $selectedForumId == $forumId ) ? 'selected="selected"' : '' ?>
                                value="<?php echo $forumId ?>">
							<?php echo $forum ?>
                        </option>
						<?php
					}
					?>
                </select>
                <p class="description">The forum where WordPress posts will be sent</p>
                <?php
            }
		}

		public static function apiRequest( string $canonicalUri, string $method ) {
			$forumBaseUrl = get_option('xf2wp_forum_base_url');

			if (empty($forumBaseUrl)) {
			    return '';
            }

			$absUrl = sprintf('%s/index.php?%s', $forumBaseUrl, $canonicalUri);

		    $authenticated = [
                'api/forums' => false,
                'api/threads/1,2' => false,
                'api/thread' => true
            ];

		    $httpOpts = [];
			$body = '';

		    switch ($method) {
                case 'get':
                    $response = wp_remote_get( $absUrl, $httpOpts );

                    if ($response["response"]["code"] >= 200 && $response["response"]["code"] <= 299) {
	                    $body = wp_remote_retrieve_body( $response );
                    }
                    break;
            }

            return json_decode( $body, true );
		}

	}

	XenForo2Connector::init();
}
