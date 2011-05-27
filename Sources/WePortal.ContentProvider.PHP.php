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
 * Our first block ever! A PHP block!
 */
class WePBlock_PHP extends WePBlock
{
	/**
	 * Stores the evaluated output from the block's content
	 */
	protected $output = null;

	/**
	 * Our name
	 */
	protected static $name = 'PHP';

	/**
	 * Parses the content and stores it
	 *
	 * @access public
	 * @return void
	 */
	public function prepare()
	{
		ob_start();
		eval($this->parameters['content']);
		$this->output = ob_get_contents();
		ob_end_clean();
	}

	/**
	 * Sets the parameters which are later used by the ACP
	 *
	 * @static
	 * @access protected
	 * @return void
	 */
	protected static function set_parameters()
	{
		global $txt;

		self::$input_parameters = array(
			'content' => array(
				'type' => 'textbox',
				'subtype' => 'php',
				'label' => $txt['wep_php_code'],
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
		echo $this->output;
	}
}