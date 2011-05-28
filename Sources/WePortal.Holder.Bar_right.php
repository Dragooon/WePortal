<?php
/****************************************************************
* WePortal														*
* © Shitiz "Dragooon" Garg										*
*****************************************************************
* WePortal.Holder.Bar_right.php   								*
*****************************************************************
* Users of this software are bound by the terms of the			*
* WePortal license. You can view it in the license_wep.txt		*
* file															*
*																*
* For support and updates, don't come to me						*
****************************************************************/

/**
 * Right bar class
 */
class WePHolder_Bar_right extends WePHolder_Bar
{
	// We're the left bar!
	public static $bar = 'right';

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
		return (bool) $this->portal->getSetting('bar_enabled_right');
	}

	/**
	 * Renders the sidebar, sets the templates and stuff
	 *
	 * @access public
	 * @return void
	 */
	public function render()
	{
		global $context;

		$context['weportal_right_blocks'] = $this->blocks;
		$context['template_layers'][] = 'weportal_bar_right';
	}
}
?>