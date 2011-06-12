<?php
/****************************************************************
* WePortal														*
* © Shitiz "Dragooon" Garg										*
*****************************************************************
* WePortal.Block.PHP.php - Contains the PHP block				*
*****************************************************************
* Users of this software are bound by the terms of the			*
* WePortal license. You can view it in the license_wep.txt		*
* file															*
*																*
* For support and updates, don't come to me						*
****************************************************************/

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