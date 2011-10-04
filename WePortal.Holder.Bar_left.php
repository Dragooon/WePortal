<?php
/**
 * Left Bar holder
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
 * Hook callback to add left bar
 * 
 * @param array $content_holders The current content holders
 * @return void
 */
function weportal_holder_bar_left_hook(array &$content_holders)
{
	$content_holders[] = 'Bar_left';
}

/**
 * Left bar class
 */
class WePHolder_Bar_left extends WePHolder_Bar
{
	// We're the left bar!
	public static $bar = 'left';

	/**
	 * Checks whether this bar can be enabled or not
	 *
	 * @access public
	 * @return bool
 	 */
	public function enabled()
	{
		return (bool) !empty($this->blocks);
	}

	/**
	 * Checks whether this bar is enabled in ACP or not
	 *
	 * @access public
	 * @return bool
	 */
	public function prelim_enabled()
	{
		return (bool) $this->portal->getSetting('bar_enabled_left');
	}

	/**
	 * Renders the sidebar, sets the templates and stuff
	 */
	public function render()
	{
		global $context;

		$context['weportal_left_blocks'] = $this->blocks;
		loadLayer('weportal_bar_left', 'main_wrap');
	}
}
?>