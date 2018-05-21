<?php

class XenForo2Viewer {

	/**
	 * @var WP_Post[]
	 */
	private $posts;

	/**
	 * @var string
	 */
	private $forumUrl = '';

	/**
	 * @var array
	 */
	private $threads = [];

	/**
	 * @var array
	 */
	private $postsThreads = [];

	public function __construct() {
		$this->forumUrl = get_option( XenForo2Connector::XF_2_WP_FORUM_BASE_URL, '' );
	}

	/**
	 * @return XenForo2Viewer
	 */
	public static function model(): XenForo2Viewer {
		return new XenForo2Viewer();
	}

	/**
	 * @return WP_Post[]
	 */
	public function getPosts(): array {
		return $this->posts;
	}

	/**
	 * @param array $posts
	 *
	 * @return XenForo2Viewer
	 */
	public function setPosts( array $posts ): XenForo2Viewer {
		$this->posts = $posts;
		return $this;
	}

	private function getForumThreads(): bool {
		$threadIds = [];

		foreach ( $this->posts as $post ) {

			if ( $this->postsThreads[ $post->ID ] ) {
				$threadId = $this->postsThreads[ $post->ID ];
			} else {
				$threadId = get_post_meta( $post->ID, XenForo2Connector::XF_2_WP_THREAD_ID, true );
			}

			if ( !empty( $threadId ) && !isset( $this->threads[ $threadId ] ) ) {
				$this->postsThreads[ $post->ID ] = (int)$threadId;
				$threadIds[] = $threadId;
			}
		}

		if ( !empty($threadIds) ) {
			$url = sprintf('%s/index.php?api/threads/%s', $this->forumUrl, implode(',', $threadIds));
			$response = wp_remote_get( $url );

			if ( $response instanceof WP_Error ) {
				return false;
			}

			if ($response["response"]["code"] >= 200 && $response["response"]["code"] <= 299) {
				$body = @json_decode( wp_remote_retrieve_body( $response ), true );

				if ( !empty($body) && isset( $body['threads'] ) )
				{
					$this->threads = $body['threads'];
				}
			}
		}

		return true;
	}

	/**
	 * @param string $file
	 * @param array $options
	 *
	 * @return string
	 */
	private function render( string $file, array $options ) {
		ob_start();
		$pathToView = sprintf( '%s/views/%s.php', __DIR__, $file );
		include $pathToView;
		$view = ob_get_contents();
		ob_end_clean();
		return $view;
	}

	public function hasCommentsThread( WP_Post $post, &$threadId = -1 ): bool {
		if ( $post->post_type !== 'post' ) {
			return false;
		}

		$this->setPosts( [$post] );
		$reqForumThreads = $this->getForumThreads();

		if ( $reqForumThreads ) {
			if ( isset( $this->postsThreads[ $post->ID ] ) ) {
				$threadId = $this->postsThreads[ $post->ID ];
				return true;
			}
		}

		return false;
	}

	public function getCommentsStatus( WP_Post $post ): string {

		if ( $this->hasCommentsThread( $post, $threadId ) ) {

			return $this->render('_comments_status', [
				'replies' => $this->getThreadReplyCount( $threadId ),
				'threadId' => $threadId,
				'threadUrl' => $this->getThreadUrl( $threadId ),
			]);

		}

		return '';

	}

	public function threadHasComments( int $threadId ): bool {
		return $this->threads[$threadId]['replyCount'] > 0;
	}

	public function getThreadReplyCount( int $threadId ): int {
		return $this->threads[$threadId]['replyCount'];
	}

	public function getThreadUrl( int $threadId ): string {
		return $this->threads[$threadId]['url'];
	}

	public function getThreadsInPage( array $posts ) {
		if ( !empty( $posts ) ) {
			$this->setPosts( $posts );
			$this->getForumThreads();
		}
	}

}