<?php
/**
 * Recent topics/post content provider
 * 
 * @package Dragooon:WePortal
 * @author Shitiz "Dragooon" Garg <Email mail@dragooon.net> <Url http://smf-media.com>
 * @copyright Shitiz "Dragooon" Garg <mail@dragooon.net>
 * @license
 *		Without express written permission from the author, you cannot redistribute, in any form,
 *		modified or unmodified versions of the file or the package.
 *		The header in all the source files must remain intact
 * 
 *		Failure to comply with the above will result in lapse of the agreement, upon which you must
 *		destory all copies of this package, or parts of it, within 48 hours.
 * 
 *		THIS PACKAGE IS PROVIDED "AS IS" AND WITHOUT ANY WARRANTY. ANY EXPRESS OR IMPLIED WARRANTIES,
 *		INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 *		PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHORS BE LIABLE TO ANY PARTY FOR
 *		ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
 *		ARISING IN ANY WAY OUT OF THE USE OR MISUSE OF THIS PACKAGE.
 *
 * @version 0.1 "We're in the right direction!"
 */

/**
 * Hook callback for "weportal_providers" to add the Recent block
 * 
 * @param array &$content_providers List of current content providers
 * @return void
 */
function weportal_provider_recent_hook(&$content_providers)
{
	$content_providers[] = 'Recent';
}

/**
 * Recent posts and topics block
 */
class WePContentProvider_Recent extends WePContentProvider
{
	/**
	 * Stores the posts which are later rendered
	 */
	protected $posts = array();

	/**
	 * Our name
	 */
	protected static $name = 'Recent Posts/Topics';

	/**
	 * Parses the content and stores it
	 *
	 * @access public
	 * @return void
	 */
	public function prepare()
	{
		global $memberContext;

		// Are we fetching posts? If so, fetch posts
		// BTW, I managed to make a fool out of myself over this at {@link http://wedge.org}
		if ($this->parameters['mode'] == 1)
			$posts = ssi_recentPosts((int) $this->parameters['limit'], null, null, 'array');
		// Otherwise we're fetching topics
		else
			$posts = ssi_recentTopics((int) $this->parameters['limit'], null, null, 'array');

		$members = array();
		foreach ($posts as $k => $post)
		{
			if (!empty($post['poster']['id']))
				$members[] = $post['poster']['id'];

			if (strlen($post['preview']) > 125)
				$posts[$k]['preview'] = substr($post['preview'], 0, 125) . '...';
		}

		// Load the avatars
		loadMemberData($members);

		foreach ($posts as $k => $post)
		{
			loadMemberContext($post['poster']['id']);

			if (!isset($memberContext[$post['poster']['id']]))
				continue;

			$posts[$k]['poster']['avatar'] = $memberContext[$post['poster']['id']]['avatar']['href'];
		}
		$this->posts = $posts;
	}

	/**
	 * Returns the parameters used by ACP
	 *
	 * @static
	 * @access public
	 * @return array
	 */
	public static function get_parameters()
	{
		global $txt;

		return array(
			'type' => array(
				'type' => 'select',
				'label' => $txt['wep_type'],
				'options' => array(
					1 => $txt['wep_recent_posts'],
					2 => $txt['wep_recent_topics'],
				),
			),
			'limit' => array(
				'type' => 'text',
				'subtype' => 'int',
				'label' => $txt['wep_recent_limit'],
			),
		);
	}

	/**
	 * Outputs the output for this block
	 *
	 * @access public
	 * @return void
 	 */
	public function render()
	{
		echo template_weblock_recent($this->posts);
	}
}