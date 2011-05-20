<?php
/****************************************************************
* WePortal														*
* © Shitiz "Dragooon" Garg										*
*****************************************************************
* WePortal.Block.php - Contains base class for blocks			*
*****************************************************************
* Users of this software are bound by the terms of the			*
* WePortal license. You can view it in the license_wep.txt		*
* file															*
*																*
* For support and updates, don't come to me						*
****************************************************************/

/**
 * Base class for blocks, meant to be extended and not used as is
 */
abstract class WePBlock
{
	/**
	 * Stores the standard information about this block
	 */
	protected static $name;
	protected static $input_parameters = null; // Parameters as set
	protected $id_instance;
	protected $title;
	protected $parameters; // The parameters as passed
	protected $bar;
	protected $portal;
	protected $enabled;

	/**
	 * Render function can never be pre-defined for a block
	 * The purpose of this function is to output the HTML for the block
	 * to display, and not set any templates. Dare you must set any templates
	 */
	abstract public function render();

	/**
	 * This function is called from the sources, the purpose is to prepare
	 * any data etc before render is called from the templates
	 */
	abstract public function prepare();

	/**
	 * This function sets the $input_parameters array, it is needed to provide
	 * input for the administrators
	 */
	abstract protected static function set_parameters();

	/**
	 * Constructor, takes the basic information and sets them up
	 *
	 * @access public
	 * @param array $parameters The parameters as set in ACP
	 * @param WePBar $bar The instance of the bar this block belongs to
	 * @param WePortal $portal The instance of the portal
	 * @param string $title The title of this block's instance
	 * @param int $id The ID of this block's instance
	 * @param bool $enabled Whether this block is enabled or not
	 * @return void
	 */
	public function __construct(array $parameters, WePBar $bar, WePortal $portal, string $title, int $id, bool $enabled)
	{
		// Set the parameters as required by this block
		$this->parameters = $parameters;

		// Set the bar and portal for future use(If any)
		$this->bar = $bar;
		$this->portal = $portal;

		$this->enabled = $enabled;

		// Title for this block
		$this->title = $title;
		$this->id_instance = $id;
	}

	/**
	 * Static function, returns the name of this block's controller for acp purposes
	 *
	 * @static
	 * @access public
	 * @return string
	 */
	public static function name()
	{
		return static::$name;
	}

	/**
	 * Returns the title of this block
	 *
	 * @access public
	 * @return string
	 */
	public function title()
	{
		return $this->title;
	}

	/**
	 * Returns the ID of this block
	 *
	 * @access public
	 * @return int
	 */
	public function id()
	{
		return $this->id_instance;
	}

	/**
	 * Returns whether this block is enabled or not
	 *
	 * @access public
	 * @return bool
	 */
	public function enabled()
	{
		return $this->enabled;
	}

	/**
	 * Static function, returns the parameters of this block
	 *
	 * @static
	 * @access public
	 * @return array	
	 */
	public static function parameters()
	{
		if (!is_array(self::$input_parameters))
			static::set_parameters();

		return self::$input_parameters;
	}
}