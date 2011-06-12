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

function weportal_hook_actions()
{
    global $action_list;

    $action_list['portal'] = array('WePortal.php', 'WePortalAction');
}

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
	 * Stores the content provider's cache
	 */
	protected $content_providers = array();
	protected $content_holders = array();

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
	 * Stored in the format [area] => callback
	 */
	protected $areas = array();

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
		loadSource('WePortal.ContentProvider');
		loadSource('WePortal.Holder');

		// Load the settings
		$this->loadSettings();

		// Load the content controllers
		$this->loadContentProviders();

		// Load all the content holders and run them
		$this->loadContentHolders();

		// Render the final little leftover templates
		$this->render();
	}

	/**
	 * Registers an area, meant to be used only be a holder
	 *
	 * @access public
	 * @param string $area The area to register
	 * @param callback $callback The callback to perform
	 * @return void
	 */
	public function registerArea(string $area, $callback)
	{
		if (!is_callable($callback) || empty($area))
			return false;

		$this->areas[$area] = $callback;
	}

	/**
	 * Removes an area if registered
	 *
	 * @access public
	 * @param string $area The area to unregister
	 * @return void
	 */
	public function unregisterArea(string $area)
	{
		if (isset($this->areas[$area]))
			unset($this->areas[$area]);
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

		// Render all the content holders
		foreach ($this->content_holders as $holder)
			$holder->render();
	}

	/**
	 * Loads all the content holders and initiates them
	 *
	 * @access protected
	 * @return void
	 */
	protected function loadContentHolders()
	{
		global $sourcedir;

        // Poor hack :(
        loadSource('WePortal.Holder.GenericBar');

		foreach (glob($sourcedir . '/WePortal.Holder.*.php') as $file)
		{
			require_once($file);

			preg_match('/WePortal\.Holder\.([a-zA-Z0-9\-\_]+).php/i', basename($file), $matches);
			$class_name = 'WePHolder_' . str_replace('-', '_', $matches[1]);

			if (!class_exists($class_name))
				continue;

			$holder = new $class_name($this);
			if (!($holder instanceof WePHolder))
				fatal_error('WePortal::loadContentHolders - Invalid holder : ' . $class_name);

			if (!$holder->enabled())
				continue;

			$this->content_holders[] = $holder;
		}
	}

	/**
	 * Fetches content providers from the DB
	 *
	 * @static
	 * @access public
	 * @param bool $enabled_check Whether to check if the block is enabled or not
	 * @param string $holder The holder to fetch from
	 * @param array $groups Member groups to check, null to skip
	 * @param int $object Any specific object to load
	 * @return array The array containing blocks
	 */
	public static function fetchContentProviders(bool $enabled_check, $holder = 'bar', $groups, int $id_object)
	{
		global $user_info;

		$clauses = array();
		if ($enabled_check)
			$clauses[] = 'c.enabled = {string:enabled}';
		if (!empty($holder))
			$clauses[] = 'c.holder = {string:holder}';
		if (!empty($id_object))
			$clauses[] = 'c.id_object = {int:object}';

		// Get the blocks from the database
		$request = wesql::query('
			SELECT c.id_object, c.holder, c.title, c.controller, c.bar, c.position, c.adjustable,
					c.parameters, c.groups, c.enabled
			FROM {db_prefix}wep_contents AS c' . (!empty($clauses) ? '
			WHERE ' . implode('
				AND ', $clauses)  : '') . '
			ORDER BY c.position ASC',
			array(
				'enabled' => '1',
				'holder' => $holder,
			)
		);
		$content_providers = array();
		while ($row = wesql::fetch_assoc($request))
		{
			if (!$user_info['is_admin'] && !is_null($groups) && count(array_intersect($groups, explode(',', $row['groups']))) == 0)
				continue;
			if (empty($row['controller']))
				continue;

			$content_providers[$row['id_object']] = array(
				'id' => $row['id_object'],
				'holder' => $row['holder'],
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
		return $content_providers;
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
	protected function loadContentProviders()
	{
		global $sourcedir, $context;

		// Get the provider's files
		foreach (glob($sourcedir . '/WePortal.ContentProvider.*.php') as $file)
		{
			require_once($file);

			preg_match('/WePortal\.ContentProvider\.([a-zA-Z0-9\-\_]+).php/i', basename($file), $matches);
			$class_name = 'WePContentProvider_' . str_replace('-', '_', $matches[1]);

			if (!class_exists($class_name))
				continue;

			// Store this content provider's information
			$this->content_providers[strtolower($matches[1])] = array(
				'id' => strtolower($matches[1]),
				'name' => call_user_func(array($class_name, 'getName')),
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
	 * Returns loaded content providers
	 *
	 * @access public
	 * @return array
	 */
	public function getContentProviders()
	{
		return $this->content_providers;
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
		if (!isset($_REQUEST['area']) || !isset($this->$areas[$_REQUEST['area']]))
			redirectexit();

		call_user_func($this->$areas[$_REQUEST['area']]);
	}

	/**
	 * Initiates a content provider, loads the controllers and passes the parameters
	 *
	 * @access public
	 * @param array $info The information about the provider, basically the ID, controller
	 *						and parameters are useful.
	 * @param WePHolder $holder The holder that is calling the 
	 * @return WeBlock The instance of the newly rendered block
	 */
	public function initiateContentProvider(array $info, WePHolder $holder)
	{
		// Load the controller
		$controllers = $this->getContentProviders();
		$controller = $controllers[strtolower($info['controller'])];

		if (empty($controller))
			fatal_error('WePortal::initateContentProvider - Undefined controller : ' . $info['controller']);

		$block_instance = new $controller['class']($info['parameters'], $holder, $this, $info['title'], $info['id'], $info['enabled']);

		// Return the instance
		return $block_instance;
	}
}	