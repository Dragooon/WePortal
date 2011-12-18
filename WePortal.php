<?php
/**
 * WePortal's central file, handles main loading and integrating
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

// Stupid wrapper
function WePortalAction()
{
	return WePortal::instance()->action();
}
// Stupid wrapper
function WePortalProviders()
{
	return WePortal::instance()->adminProviders();
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
	 * "actions" hook callback, adds the portal action into the action list
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function hook_actions()
	{
		global $action_list;

		$action_list['portal'] = array('Subs.php', 'WePortalAction');
	}

    /**
     * "admin_areas" hook callback, adds WePortal admin areas into the menu
     *
     * @static
     * @access public
     * @return void
     */
    public static function hook_admin_areas()
    {
        global $admin_areas, $txt;

        // This isn't a shameless copy of the instruction in Admin.php, I just want to
        // add it after the second menu
        $admin_areas = array_merge(array_splice($admin_areas, 0, 2), array(
            'weportal' => array(
                'title' => $txt['weportal'],
                'permission' => array('manage_weportal'),
                'areas' => array(
                    'providers' => array(
                        'label' => $txt['wep_providers'],
                        'file' => array('Dragooon:WePortal', 'WePortal'),
                        'function' => 'WePortalProviders',
                        'icon' => 'delete.png',
                        'bigicon' => 'delete.png',
                        'subsections' => array(
                            'index' => array($txt['wep_providers_list']),
                            'add' => array($txt['wep_add_provider']),
                        ),
                    ),
                ),
            ),
        ), $admin_areas);
    }

    /**
	 * Constructor, initialises the portal, also responds to load_theme hook call
	 *
	 * @access protected
	 * @return void
	 */
	protected function __construct()
	{
		global $sourcedir, $scripturl, $context, $boarddir;

		if (WEDGE == 'SSI')
			return true;

		require_once($boarddir . '/SSI.php');

		// Load the essential files
		loadPluginSource('Dragooon:WePortal', 'WePortal.ContentProvider');
		loadPluginSource('Dragooon:WePortal', 'WePortal.Holder');

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
	public function registerArea($area, $callback)
	{
		if (!is_callable($callback) || empty($area))
			return false;

		if (empty($this->areas[$area]))
			$this->areas[$area] = array();

		$this->areas[$area][] = $callback;
	}

	/**
	 * Removes an area if registered
	 *
	 * @access public
	 * @param string $area The area to unregister
	 * @return void
	 */
	public function unregisterArea($area)
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
		// Nuke the default sidebar
		wetem::get('sidebar_wrap')->remove();

		// Load the templates and languages
		loadPluginLanguage('Dragooon:WePortal', 'languages/WePortal');
		loadPluginTemplate('Dragooon:WePortal', 'templates/WePortal');
		add_plugin_css_file('Dragooon:WePortal', 'templates/styles/portal', true);
		add_plugin_js_file('Dragooon:WePortal', 'templates/scripts/portal.js', true);

		// Render all the content holders
		foreach ($this->content_holders as $holder)
            //if ($holder->enabled())
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
        // Poor hack :(
        loadPluginSource('Dragooon:WePortal', 'WePortal.Holder.GenericBar');

		$holders = array();

		call_hook('weportal_holders', array(&$holders));

		foreach (array_unique($holders) as $holder)
		{
			$class_name = 'WePHolder_' . $holder;

			if (!class_exists($class_name))
				continue;

			$holder = new $class_name($this);
			if (!($holder instanceof WePHolder))
				fatal_error('WePortal::loadContentHolders - Invalid holder : ' . $class_name);

			$this->content_holders[$holder->getHolderID()] = $holder;
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
	public static function fetchContentProviders($enabled_check = false, $holder = null, $groups = array(), $id_object = 0)
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
			SELECT c.id_object, c.holder, c.title, c.controller, c.position, c.adjustable,
					c.parameters, c.groups, c.enabled
			FROM {db_prefix}wep_contents AS c' . (!empty($clauses) ? '
			WHERE ' . implode('
				AND ', $clauses)  : '') . '
			ORDER BY c.position ASC',
			array(
				'enabled' => '1',
                'object' => $id_object,
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
				'position' => $row['position'],
				'adjustable' => (bool) $row['adjustable'],
				'parameters' => unserialize($row['parameters']),
				'groups' => empty($row['groups']) ? array() : explode(',', $row['groups']),
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

		$providers = array();

		call_hook('weportal_providers', array(&$providers));

		// Get the provider's files
		foreach (array_unique($providers) as $provider)
		{
			$class_name = 'WePContentProvider_' . $provider;

			if (!class_exists($class_name))
				continue;

			// Store this content provider's information
			$this->content_providers[strtolower($provider)] = array(
				'id' => strtolower($provider),
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
		if (!isset($_REQUEST['area']) || !isset($this->areas[$_REQUEST['area']]))
			redirectexit();

		foreach ($this->areas[$_REQUEST['area']] as $callback)
			call_user_func($callback);
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
			return false;

		if (empty($controller))
			fatal_error('WePortal::initateContentProvider - Undefined controller : ' . $info['controller']);

		$block_instance = new $controller['class']($info['parameters'], $holder, $this, $info['title'], $info['id'], $info['enabled'], $info);

		// Return the instance
		return $block_instance;
	}

    /**
     * Content provider's admin area, accessed via index.php?action=admin;area=providers
     *
     * @access public
     * @return void
     */
    public function adminProviders()
    {
        global $txt, $context;

        $context[$context['admin_menu_name']]['tab_data'] = array(
            'title' => $txt['wep_providers'],
            'description' => $txt['wep_providers_desc'],
            'tabs' => array(
                'index' => array(),
                'add' => array(
                    'description' => $txt['wep_provider_add'],
                 ),
            ),
        );

        $sub_actions = array(
            'add' => 'adminProvidersAdd',
            'edit' => 'adminProvidersEdit',
            'delete' => 'adminProvidersDelete',
            'toggle' => 'adminProvidersToggle',
        );
        if (isset($_REQUEST['sa']) && isset($sub_actions[$_REQUEST['sa']]))
            return call_user_func(array($this, $sub_actions[$_REQUEST['sa']]));

        // Otherwise, this is the default "index" or list providers sub-action
        $context['wep_providers'] = self::fetchContentProviders();

        // Group by holders
        $context['wep_holders'] = array();
        foreach ($context['wep_providers'] as $provider)
        {
            if (!isset($context['wep_holders'][$provider['holder']]))
                $context['wep_holders'][$provider['holder']] = array(
                    'id' => $provider['holder'],
                    'name' => $this->content_holders[$provider['holder']]->getHolderID(),
                    'providers' => array(),
                );

            $context['wep_holders'][$provider['holder']]['providers'][] = $provider;
        }

        wetem::load('weportal_admin_providers_index');
    }

    /**
     * Provides an interface to add a content provider, it's a 2 step proces
     *
     * Step 1 : Asks for provider and it's holder
     * Step 2 : Takes in parameters from the providers and holders and ask for them
     *
     * @access protected
     * @return void
     */
    protected function adminProvidersAdd()
    {
        global $context;

        $groups = array();

        // Get the basic group data.
        $request = wesql::query('
            SELECT id_group, group_name
            FROM {db_prefix}membergroups
            WHERE min_posts = -1' . (allowedTo('admin_forum') ? '' : '
                AND group_type != {int:is_protected}') . '',
            array(
                'is_protected' => 1,
            )
        );
        while ($row = wesql::fetch_assoc($request))
            $groups[$row['id_group']] = array(
                'id_group' => $row['id_group'],
                'group_name' => $row['group_name'],
            );
        wesql::free_result($request);

        $holders = array_keys($this->content_holders);
        $providers = array_keys($this->content_providers);

        $holder = !empty($_POST['holder']) ? $_POST['holder'] : '';
        $provider = !empty($_POST['provider']) ? $_POST['provider'] : '';

        if ((!empty($holder) && !in_array($holder, $holders)) || (!empty($provider) && !in_array($provider, $providers)))
            fatal_lang_error('weportal_invalid_holder_provider');

        $provider_params = array();
        if (!empty($provider))
            $providerparams = call_user_func(array($this->content_providers[$provider]['class'], 'getParameters'));

        $holder_params = array();
        if (!empty($holder))
            $holder_params = call_user_func(array(get_class($this->content_holders[$holder]), 'getProviderParameters'));

        // Are we saving this provider?
        if (!empty($_POST['save']))
        {
            $save_params = array('provider' => array(), 'holder' => array());
            foreach ($provider_params as $k => $param)
                $save_params['provider'][$k] = $_POST['provider_' . $k];
            foreach ($holder_params as $k => $param)
                $save_params['holder'][$k] = $_POST['holder_' . $k];
            
            $_POST['title'] = htmlspecialchars($_POST['title']);
            $save_groups = array();
            foreach ($_POST['groups'] as $group)
                if (!in_array($group, array_keys($groups)))
                    $save_groups[] = $group;

            $_POST['adjustable'] = (bool) $_POST['adjustable'];

            wesql::insert('insert', '{db_prefix}wep_contents',
                array('holder' => 'string', 'title' => 'string', 'controller' => 'string', 'adjustable' => 'int', 'parameters' => 'string', 'groups' => 'string', 'enabled' => 'int'),
                array($holder, $_POST['title'], $provider, (int) $_POST['adjustable'], serialize($save_params), implode(',', $save_groups), 1)
            );

            redirectexit('action=admin;area=providers');
        }
        // Step 2?
        elseif (!empty($_POST['step']) && !empty($holder) && !empty($provider))
        {
            $context['wep_holders'] = $holders;
            $context['wep_providers'] = $providers;
            $context['wep_params'] = $params;
            $context['wep_holder'] = $holder;
            $context['wep_provider'] = $provider;
            $context['wep_groups'] = $groups;

            wetem::load('weportal_admin_providers_add_step2');
        }
        else
        {
            $context['wep_holders'] = $holders;
            $context['wep_providers'] = $providers;

            wetem::load('weportal_admin_providers_add_step1');
        }
    }

    /**
     * Toggle a content provider's status(enable or disable)
     *
     * @access public
     * @return void
     */
    public function adminProvidersToggle()
    {
        $provider = self::fetchContentProviders(false, null, array(), (int) $_GET['id']);
        if (empty($provider))
            fatal_lang_error('wep_invalid_provider');

        $provider = $provider[$_GET['id']];

        $enable = 0;
        if (!$provider['enabled'])
            $enable = 1;

        wesql::query('
            UPDATE {db_prefix}wep_contents
            SET enabled = {int:enabled}
            WHERE id_object = {int:id}',
            array(
                'enabled' => $enable,
                'id' => $provider['id'],
           )
        );

        redirectexit('action=admin;area=providers');
    }
}

/**
 * Walks an array and finds every key and then removes it
 *
 * @param array $array
 * @param string $key
 * @return array
 */
function removeValueByKey($array, $key)
{
 	foreach ($array as $k => $v)
 	{
 		if ($k == $key)
 			unset($array[$k]);
 		elseif (is_array($v))
 			$array = removeValueByKey($v, $key);
 	}

	return $array;
}