<?php
/**
 * Content Provider base class, extended by all the future content providers
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
 * Base class for content providers, meant to be extended and not used as is
 */
abstract class WePContentProvider
{
	/**
	 * Stores the standard information about this block
	 */
	protected static $name;
	protected static $input_parameters = null; // Parameters as set
	protected $id_instance;
	protected $title;
	protected $parameters; // The parameters as passed
    protected $holder_parameters;
	protected $holder;
	protected $portal;
	protected $enabled;
	protected $info = array();

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
	 * This function returns the parameter as needed in the ACP
	 */
	abstract protected static function getParameters();

	/**
	 * Constructor, takes the basic information and sets them up
	 *
	 * @access public
	 * @param array $parameters The parameters as set in ACP
	 * @param WePHolder $bar The instance of the holder this content proivder belongs to
	 * @param WePortal $portal The instance of the portal
	 * @param string $title The title of this block's instance
	 * @param int $id The ID of this block's instance
	 * @param bool $enabled Whether this block is enabled or not
	 * @param array $info All the information, also includes some of the above (title, id etc)
	 * @return void
	 */
	public function __construct(array $parameters, WePHolder $holder, WePortal $portal, $title, $id, $enabled, array $info)
	{
		// Set the parameters as required by this block
		$this->parameters = $parameters['provider'];
        $this->holder_parameters = $parameters['holder'];

		// Set the bar and portal for future use(If any)
		$this->holder = $holder;
		$this->portal = $portal;

		$this->enabled = $enabled;

		// Title for this block
		$this->title = $title;
		$this->id_instance = $id;

		$this->info = $info;
	}

    /**
     * Return the holder parameters
     *
     * @access public
     * @return array
     */
    public function getHolderParameters()
    {
        return $this->holder_parameters;
    }
    
	/**
	 * Static function, returns the name of this block's controller for acp purposes
	 *
	 * @static
	 * @access public
	 * @return string
	 */
	public static function getName()
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
	 * Extend this if you want to add a special clause or processing
	 *
	 * @access public
	 * @return bool
	 */
	public function enabled()
	{
		return $this->enabled;
	}

	/**
	 * Returns the info of this block
	 *
	 * @access public
	 * @return array
	 */
	public function info()
	{
		return $this->info;
	}
}