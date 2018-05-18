<?php
if ( !class_exists('XenForo2Connector') ) {

	class XenForo2Connector {

	    const VERSION = '0.1';

		const XF_2_WP_THREAD_ID = 'xf2wp_thread_id';

		const XF_2_WP_FORUM_USER_ID = 'xf2wp_forum_user_id';

		const XF_2_WP_FORUM_ID = 'xf2wp_forum_id';

		const XF_2_WP_FORUM_BASE_URL = 'xf2wp_forum_base_url';

		const XF_2_WP_API_USERNAME = 'xf2wp_api_username';

		const XF_2_WP_API_PASSWORD = 'xf2wp_api_password';

		public static function init() {
			add_action('admin_init', 'XenForo2Connector::settingsInit');

            add_action('show_user_profile', 'XenForo2Connector::userProfileForm');
            add_action('edit_user_profile', 'XenForo2Connector::userProfileForm');
            add_action('personal_options_update', 'XenForo2Connector::updateUserMetadata');
            add_action('edit_user_profile_update', 'XenForo2Connector::updateUserMetadata');
            add_action('transition_post_status', 'XenForo2Connector::postStatusUpdated', 10, 3);
			add_action('wp_enqueue_scripts', 'XenForo2Connector::registerScripts');
		}

		public static function registerScripts() {
			$urlToJs = plugins_url( 'xenforo2_connect/js/xenforo2_connect.js' );
            wp_enqueue_script('xf2wp_js', $urlToJs, [], self::VERSION, true);
		}

		public static function getPostExcerpt( WP_Post $publishedPost ): string {
			$excerpt = $publishedPost->post_excerpt;

			if ($excerpt === '') {
				$excerpt = wp_trim_words( $publishedPost->post_content );
			}

			$link = sprintf('<a href="%s" target="_blank">Continue reading...</a>', get_permalink($publishedPost));
			$excerpt .= "\n\n" . $link;

			return $excerpt;
        }

		public static function postStatusUpdated(string $new_status, string $old_status, WP_Post $publishedPost) {

		    if ($publishedPost->post_type !== 'post') {
		        return;
            }

            if ($new_status === 'publish' && $old_status !== 'publish') {

                $authorId = (int)$publishedPost->post_author;
	            $forumUserId = get_the_author_meta( self::XF_2_WP_FORUM_USER_ID, $authorId );
	            $forumId = get_option( self::XF_2_WP_FORUM_ID );

	            if (!empty($forumUserId) && !empty($forumId)) {

		            $requestBody = json_encode([
                        'userId' => (int)$forumUserId,
                        'threadTitle' => $publishedPost->post_title,
                        'threadBodyHtml' => self::getPostExcerpt( $publishedPost ),
                        'forumId' => (int)$forumId,
                    ]);

		            $response = XenForo2Connector::apiRequest( 'api/thread', 'post', $requestBody );

		            if ( !empty($response) ) {

		                $threadId = $response['thread'];
		                add_post_meta( $publishedPost->ID, self::XF_2_WP_THREAD_ID, $threadId, true );

                    }

                }

            } elseif ($old_status === 'publish' && $new_status !== 'publish' ) {

                $threadId = get_post_meta( $publishedPost->ID, self::XF_2_WP_THREAD_ID, true );

                if ( !empty($threadId) )
                {
	                $requestBody = json_encode([
		                'threadId' => (int)$threadId
	                ]);

	                $response = XenForo2Connector::apiRequest(
                        'api/thread',
                        'delete',
                        $requestBody
                    );

	                if (isset($response['deleted']) && $response['deleted'])
                    {
                        delete_post_meta( $publishedPost->ID, self::XF_2_WP_THREAD_ID, $threadId );
                    }
                }

            } elseif ($new_status === 'publish' && $old_status === 'publish') {

	            $authorId = (int)$publishedPost->post_author;
	            $forumUserId = get_the_author_meta( self::XF_2_WP_FORUM_USER_ID, $authorId );
	            $threadId = get_post_meta( $publishedPost->ID, self::XF_2_WP_THREAD_ID, true );

	            if ( !empty($forumUserId) && !empty($threadId) ) {

		            $requestBody = json_encode([
			            'userId' => (int)$forumUserId,
			            'threadId' => (int)$threadId,
			            'threadTitle' => $publishedPost->post_title,
			            'threadBodyHtml' => self::getPostExcerpt( $publishedPost ),
		            ]);

		            XenForo2Connector::apiRequest(
			            'api/thread',
			            'put',
			            $requestBody
		            );

                }

            }

        }

        public static function userProfileForm( WP_User $user ) {
            ?>
            <h2>XenForo 2 Connector</h2>
            <table class="form-table">
                <tbody>
                    <tr class="user-email-wrap">
                        <th><label for="xf2wp_forum_user_id">(Forum) User ID</th>
                        <td>
                            <input type="number" value="<?php echo esc_attr( get_the_author_meta( self::XF_2_WP_FORUM_USER_ID, $user->ID ) ); ?>" name="xf2wp_forum_user_id" id="xf2wp_forum_user_id" class="regular-text ltr">
                            <p class="description">Numeric ID of the user on the XenForo forums</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
		}

        public static function updateUserMetadata( $userId ) {
            if ( !current_user_can( 'edit_user', $userId ) ) {
                return false;
            }

            update_user_meta( $userId, self::XF_2_WP_FORUM_USER_ID, $_POST[ self::XF_2_WP_FORUM_USER_ID ] );
		}

		public static function settingsInit() {
			register_setting('general', self::XF_2_WP_FORUM_BASE_URL );
			register_setting('general', self::XF_2_WP_API_USERNAME );
			register_setting('general', self::XF_2_WP_API_PASSWORD );
			register_setting('general', self::XF_2_WP_FORUM_ID );

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
			$forumBaseUrl = get_option( self::XF_2_WP_FORUM_BASE_URL );
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
			$apiUsername = get_option( self::XF_2_WP_API_USERNAME );
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
			$apiPassword = get_option( self::XF_2_WP_API_PASSWORD );
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
            $selectedForumId = get_option( self::XF_2_WP_FORUM_ID );
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

        public static function apiRequest( string $canonicalUri, string $method, string $payload = '' ) {
            $forumBaseUrl = get_option( self::XF_2_WP_FORUM_BASE_URL );
            $username = get_option( self::XF_2_WP_API_USERNAME );
            $password = get_option( self::XF_2_WP_API_PASSWORD );

            if ( empty($forumBaseUrl) || empty($username) || empty($password) ) {
                return '';
            }

            $absUrl = sprintf('%s/index.php?%s', $forumBaseUrl, $canonicalUri);

            $httpOpts = [
                'headers' => sprintf('Authorization: Basic %s', base64_encode( $username . ":" . $password ))
            ];

            $body = '';

            $parseResponse = function ( $response ) use ( $body ): string {
	            if ($response["response"]["code"] >= 200 && $response["response"]["code"] <= 299) {
		            $body = wp_remote_retrieve_body( $response );
	            }

	            return $body;
            };

            switch ($method) {
                case 'get':
                    $response = wp_remote_get( $absUrl, $httpOpts );
                    $body = $parseResponse( $response );
                    break;
                case 'post':
                    $httpOpts['body'] = $payload;
	                $response = wp_remote_post( $absUrl, $httpOpts );
	                $body = $parseResponse( $response );
	                break;
	            case 'put':
		            $httpOpts['body'] = $payload;
		            $httpOpts['method'] = 'PUT';
		            $response = wp_remote_request( $absUrl, $httpOpts );
		            $body = $parseResponse( $response );
		            break;
                case 'delete':
	                $httpOpts['body'] = $payload;
	                $httpOpts['method'] = 'DELETE';
	                $response = wp_remote_request( $absUrl, $httpOpts );
	                $body = $parseResponse( $response );
            }

            return json_decode( $body, true );
        }

	}

	XenForo2Connector::init();
}
