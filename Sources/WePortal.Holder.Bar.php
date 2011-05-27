<?php
/****************************************************************
* WePortal														*
* © Shitiz "Dragooon" Garg										*
*****************************************************************
* WePortal.Bar.php - Contains classes for the bars				*
*****************************************************************
* Users of this software are bound by the terms of the			*
* WePortal license. You can view it in the license_wep.txt		*
* file															*
*																*
* For support and updates, don't come to me						*
****************************************************************/

/**
 * Left bar class
 */
class WePBar_left extends WePBar
{
	// We're the left bar!
	public static $bar = 'left';

	/**
	 * Renders the sidebar, sets the templates and stuff
	 */
	public function render()
	{
		global $context;

		$context['weportal_left_blocks'] = $this->blocks;
		$context['template_layers'][] = 'weportal_bar_left';
	}
}

/**
 * Right bar class
 */
class WePBar_right extends WePBar
{
	// We're the left bar!
	public static $bar = 'right';

	/**
	 * Renders the sidebar, sets the templates and stuff
	 */
	public function render()
	{
		global $context;

		$context['weportal_right_blocks'] = $this->blocks;
		$context['template_layers'][] = 'weportal_bar_right';
	}
}


/**
 * Base bar class, later extended by specific positioned classes
 */
abstract class WePBar implements WePHolder
{
	/**
	 * Stores the blocks combined with member's block adjustments for this bar
	 */
	protected $blocks = array();

	/**
	 * Stores the Portal's instance
	 */
	protected $portal = null;

	/**
	 * Holder type
	 */
	final protected static $holder_type = 'bar';

	/**
	 * Bar's ID, required to load blocks
	 */
	protected static $bar = '';

	/**
	 * We need to have a render function defined but since every bar has different render
	 * needs, we keep it abstracted
	 *
	 * The main purpose of this render function is to set the templates
	 */
	abstract public function render();

	/**
	 * Returns the holder's type
	 *
	 * @final
	 * @static
	 * @access public
	 * @return string
	 */
	final public static function getHolderType()
	{
		return self::$holder_type;
	}

	/**
	 * Returns the holder's ID, bar's position in this case
	 *
	 * @final
	 * @static
	 * @access public
	 * @return string
	 */
	final public static function getHolderID()
	{
		return static::$bar;
	}

	/**
	 * Constructor, loads this bar from the passed portal instance. Sets the blocks and
	 * updates them with member's block preferences
	 *
	 * @access public
	 * @param WePortal $portal The instance of the portal to use
	 * @return void
	 */
	public function __construct(WePortal $portal)
	{
		// Load the blocks appropiate for this bar
		$blocks = $portal->getBlocks();
		$member_blocks = $portal->getMemberBlocks();

		// Extend member blocks preference with blocks
		foreach ($member_blocks as $block => $pref)
		{
			if (!$blocks[$block]['adjustable'])
				continue;

			$blocks[$block] = array_merge($blocks[$block], $pref);
		}

		// Now only keep the blocks needed for this bar
		foreach ($blocks as $block => $info)
		{
			if ($info['bar'] != static::$bar)
				unset($blocks[$block]);
		}
		// Sort by position
		$position_index = array();
		foreach ($blocks as $k => $v)
			$position_index[$k] = $v['position'];
		asort($position_index);

		$_blocks = $blocks;
		$blocks = array();
		foreach ($position_index as $block => $v)
			$blocks[$block] = $_blocks[$block];
		unset($position_index, $_blocks);
			
		$this->portal = $portal;
		unset($member_blocks);

		// Add the blocks to this bar
		$this->populateBlocks($blocks);
	}

	/**
	 * Takes a list of block, initiates them and then adds them to the bar
	 *
	 * @access protected
	 * @param array $blocks The list of blocks to add
	 * @return void
	 */
	protected function populateBlocks(array $blocks)
	{
		foreach ($blocks as $k => $block)
		{
			$blocks[$k] = $this->portal->initiateContentProvider($block, 'block');
			if ($blocks[$k]->enabled())
				$blocks[$k]->prepare();
			else
				unset($blocks[$k]);
		}

		// Now that we got proper block controllers set, we can finally do something about them
		$this->blocks = $blocks;
	}
}
?>