<?php
/****************************************************************
* WePortal														*
* © Shitiz "Dragooon" Garg										*
*****************************************************************
* WePortal.php - Main Portal file, contains the bootstrap for   *
*				the portal and sets everything up				*
*****************************************************************
* Users of this software are bound by the terms of the			*
* WePortal license. You can view it in the license_wep.txt		*
* file															*
*																*
* For support and updates, don't come to me						*
****************************************************************/

// Stupid wrapper
function WePortalAction()
{
	return WePortal::instance()->action();
}

/**
 * Our main portal class, a new instance is called at index.php for the portal to start.
 * Handles everything here
 */
class WePortal
{
	/**
	 * Stores the block controllers cache
	 */
	protected $block_controllers = array();

	/**
	 * Stores the settings related to the portal
	 */
	protected $settings = array();

	/**
	 * Stores the instances of the various bars
	 */
	protected $bars = array();

	/**
	 * Stores overall blocks and member's blocks which can be used by the bars
	 */
	protected $blocks = array();
	protected $member_blocks = array();

	/**
	 * Stores the instance of this portal
	 */
	protected static $_instance = null;

	/**
	 * List for all the areas and their methods that can be called
	 */
	protected static $areas = array(
		'blockupdate' => 'blockupdate',
	);

	/**
	 * Returns(or creates) the instance of the portal
	 *
	 * @static
	 * @access public
	 * @return WePortal
	 */
	public static function instance()
	{
		if (is_null(self::$_instance))
			self::$_instance = new self();

		return self::$_instance;
	}

	/**
	 * Constructor, initialises the portal.
	 *
	 * @access protected
	 * @return void
	 */
	protected function __construct()
	{
		global $sourcedir, $scripturl, $context, $boarddir;

		require_once($boarddir . '/SSI.php');

		// Load the essential files
		loadSource('WePortal.Bar');
		loadSource('WePortal.Block');

		// Load the settings
		$this->loadSettings();

		// Load the block controllers
		$this->loadBlockControllers();

		// Load the blocks themselves along with user block preference
		$this->loadBlocks();
		$this->loadMemberBlocks();

		// The possible bar's positions that exist, initiate and render them
		$positions = array('left', 'right');
		foreach ($positions as $pos)
		{
			if ((int) $this->getSetting('bar_enabled_' . $pos) == 1)
			{
				$class_name = 'WePBar_' . $pos;
				$this->bars[$pos] = new $class_name($this);
			}
		}

		// Render the final little leftover templates
		$this->render();
	}

	/**
	 * Renders the little leftovers
	 *
	 * @access public
	 * @return void
	 */
	public function render()
	{
		global $context;

		// Load the templates and languages
		loadLanguage('WePortal');
		loadTemplate('WePortal');
		add_css_file('portal', true);
		add_js_file('scripts/portal.js');

		// Render the bars
		foreach ($this->bars as $bar)
			$bar->render();
	}

	/**
	 * Loads all the blocks viewable by this user
	 *
	 * @access protected
	 * @return void
	 */
	protected function loadBlocks()
	{
		global $user_info;

		$this->blocks = self::fetchBlocks(false, $user_info['groups']);
	}

	/**
	 * Loads this user's blocks customizations
	 *
	 * @access protected
	 * @return void
	 */
	protected function loadMemberBlocks()
	{
		global $user_info;

		$this->member_blocks = self::fetchMemberBlocks($user_info['id']);
	}

	/**
	 * Fetches blocks from the database
	 *
	 * @static
	 * @access public
	 * @param bool $enabled_check Whether to check if the block is enabled or not
	 * @param array $groups Member groups to check, null to skip
	 * @return array The array containing blocks
	 */
	public static function fetchBlocks(bool $enabled_check, $groups)
	{
		global $user_info;

		// Get the blocks from the database
		$request = wesql::query('
			SELECT b.id_block, b.title, b.controller, b.bar, b.position, b.adjustable,
					b.parameters, b.groups, b.enabled
			FROM {db_prefix}wep_blocks AS b' . ($enabled_check ? '
			WHERE b.enabled = 1' : '') . '
			ORDER BY b.position ASC',
			array()
		);
		$blocks = array();
		while ($row = wesql::fetch_assoc($request))
		{
			if (!$user_info['is_admin'] && !is_null($groups) && count(array_intersect($groups, explode(',', $row['groups']))) == 0)
				continue;
			if (empty($row['controller']))
				continue;

			$blocks[$row['id_block']] = array(
				'id' => $row['id_block'],
				'title' => $row['title'],
				'controller' => $row['controller'],
				'bar' => $row['bar'],
				'position' => $row['position'],
				'adjustable' => (bool) $row['adjustable'],
				'parameters' => unserialize($row['parameters']),
				'groups' => explode(',', $row['groups']),
				'enabled' => (bool) $row['enabled'],
			);
		}
		wesql::free_result($request);

		// Return the result set
		return $blocks;
	}

	/**
	 * Fetches the member's block preferences from the database
	 *
	 * @static
	 * @access public
	 * @param int $id_member The ID of the member to fetch
	 * @return array The list of blocks with the adjusted parameters
	 */
	public static function fetchMemberBlocks(int $id_member)
	{
		// Empty member? Die hard.
		if (empty($id_member))
			return false;

		// Fetch the member's blocks
		$request = wesql::query('
			SELECT ba.id_member, ba.id_block, ba.bar, ba.position, ba.enabled
			FROM {db_prefix}wep_block_adjustments AS ba
			WHERE id_member = {int:member}
			ORDER BY ba.position',
			array(
				'member' => $id_member,
			)
		);
		$member_blocks = array();
		while ($row = wesql::fetch_assoc($request))
		{
			$member_blocks[$row['id_block']] = array(
				'block' => $row['id_block'],
				'member' => $row['id_member'],
				'bar' => $row['bar'],
				'position' => $row['position'],
				'enabled' => (bool) $row['enabled'],
			);
		}
		wesql::free_result($request);

		return $member_blocks;
	}

	/**
	 * Loads settings from modSettings and stores them in WePortal::settings
	 *
	 * @access protected
	 * @return void
	 */
	protected function loadSettings()
	{
		global $modSettings;

		foreach ($modSettings as $k => $v)
			if (substr($k, 0, 3) == 'wep')
			{
				$name = substr($k, 4);
				$this->settings[$name] = $v;
			}
	}

	/**
	 * Loads block controllers from the sources folder
	 *
	 * @access protected
	 * @return void
	 */
	protected function loadBlockControllers()
	{
		global $sourcedir, $context;

		// Get the block files
		foreach (glob($sourcedir . '/WePortal.Block.*.php') as $file)
		{
			require_once($file);

			preg_match('/WePortal\.Block\.([a-zA-Z0-9\-\_]+).php/i', basename($file), $matches);
			$class_name = 'WePBlock_' . str_replace('-', '_', $matches[1]);

			// Store this block's information
			$this->block_controllers[strtolower($matches[1])] = array(
				'id' => strtolower($matches[1]),
				'name' => call_user_func(array($class_name, 'name')),
				'parameters' => call_user_func(array($class_name, 'parameters')),
				'class' => $class_name,
			);
		}
	}

	/**
	 * Returns a setting
	 *
	 * @access public
	 * @param string $name The name of the setting
	 * @return mixed String or int
	 */
	public function getSetting($name)
	{
		if (!isset($this->settings[$name]))
			return false;
		return $this->settings[$name];
	}

	/**
	 * Returns loaded blocks
	 *
	 * @access public
	 * @return array
	 */
	public function getBlocks()
	{
		return $this->blocks;
	}

	/**
	 * Returns loaded block controllers
	 *
	 * @access public
	 * @return array
	 */
	public function getBlockControllers()
	{
		return $this->block_controllers;
	}

	/**
	 * Returns loaded member's block's adjustments
	 *
	 * @access public
	 * @return array
	 */
	public function getMemberBlocks()
	{
		return $this->member_blocks;
	}

	/**
	 * Handles the ?action=portal call
	 *
	 * @access public
	 * @return void
	 */
	public function action()
	{
		if (!isset($_REQUEST['area']) || !isset(self::$areas[$_REQUEST['area']]))
			redirectexit();

		$this->{self::$areas[$_REQUEST['area']]}();
	}

	/**
	 * Handles AJAX call for updating the user's blocks
	 *
	 * @access public
	 * @return void
	 */
	public function blockupdate()
	{
		global $context, $txt;

		// Guest? Bah Humbug!
		if ($context['user']['is_guest'] || (empty($_POST['bars']) && empty($_POST['blocks_disabled']) && empty($_POST['blocks_enabled'])))
			redirectexit();

		// Flush all the current blocks
		if (!empty($_POST['bars']))
		{
			wesql::query('
				DELETE FROM {db_prefix}wep_block_adjustments
				WHERE id_member = {int:member}',
				array(
					'member' => (int) $context['user']['id'],
				)
			);

			// Let us update the blocks
			foreach ($_POST['bars'] as $bar => $blocks)
				foreach ($blocks as $pos => $id_block)
					wesql::query('
						INSERT IGNORE INTO {db_prefix}wep_block_adjustments
							(id_block, id_member, bar, position, enabled)
						VALUES
							({int:block}, {int:user}, {string:bar}, {int:position}, {string:enabled})',
						array(
							'block' => (int) $id_block,
							'user' => $context['user']['id'],
							'bar' => $bar,
							'position' => (int) $pos,
							'enabled' => isset($this->member_blocks[(int) $id_block]) ? ($this->member_blocks[(int) $id_block]['enabled'] ? '1' : '0') : ($this->blocks[(int) $id_block]['enabled'] ? '1' : '0'),
						)
					);
		}

		if (!empty($_POST['blocks_disabled']))
			foreach ($_POST['blocks_disabled'] as $disabled_block)
			{
				wesql::query('
					UPDATE {db_prefix}wep_block_adjustments
					SET enabled = {string:disabled}
					WHERE id_block  = {int:block}
						AND id_member = {int:member}',
					array(
						'disabled' => '0',
						'block' => (int) $disabled_block,
						'member' => $context['user']['id'],
					)
				);
				if (wesql::affected_rows() <= 0)
					wesql::query('
						INSERT IGNORE INTO {db_prefix}wep_block_adjustments
							(id_block, id_member, bar, position, enabled)
						VALUES
							({int:block}, {int:user}, {string:bar}, {int:position}, {string:enabled})',
						array(
							'block' => (int) $disabled_block,
							'user' => $context['user']['id'],
							'bar' => $this->blocks[$disabled_block]['bar'],
							'position' => $this->blocks[$disabled_block]['position'],
							'enabled' => '0',
						)
					);
			}

		if (!empty($_POST['blocks_enabled']))
			foreach ($_POST['blocks_enabled'] as $enabled_block)
			{
				wesql::query('
					UPDATE {db_prefix}wep_block_adjustments
					SET enabled = {string:enabled}
					WHERE id_block = {int:block}
						AND id_member = {int:member}',
					array(
						'enabled' => '1',
						'block' => (int) $enabled_block,
						'member' => $context['user']['id'],
					)
				);
				if (wesql::affected_rows() == 0)
					wesql::query('
						INSERT IGNORE INTO {db_prefix}wep_block_adjustments
							(id_block, id_member, bar, position, enabled)
						VALUES
							({int:block}, {int:user}, {string:bar}, {int:position}, {string:enabled})',
						array(
							'block' => (int) $enabled_block,
							'user' => $context['user']['id'],
							'bar' => $this->blocks[$enabled_block]['bar'],
							'position' => $this->blocks[$enabled_block]['position'],
							'enabled' => '1',
						)
					);
			}
		exit;
	}
}	