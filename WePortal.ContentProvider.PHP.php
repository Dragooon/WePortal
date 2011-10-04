<?php
/**
 * PHP content provider
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
 * Hook callback for "weportal_providers" to add the PHP block
 * 
 * @param array &$content_providers List of current content providers
 * @return void
 */
function weportal_provider_php_hook(&$content_providers)
{
	$content_providers[] = 'PHP';
}

/**
 * Our first block ever! A PHP block!
 */
class WePContentProvider_PHP extends WePContentProvider
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
	 * Sets the parameters which are used by ACP
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function get_parameters()
	{
		global $txt;

		return array(
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