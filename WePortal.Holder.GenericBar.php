<?php
/**
 * Contains Generic Bar holder class, base for further specific bars
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
 * Base bar class, later extended by specific positioned classes
 */
abstract class WePHolder_Bar implements WePHolder
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
	protected static $holder_type = 'bar';

	/**
	 * Bar's ID, required to load blocks
	 */
	protected static $bar = '';

    /**
     * Stores the cached blocks and member blocks
     */
    protected $blocks_cache = null;
    protected $member_block_cache = null;

	/**
	 * We need to have a render function defined but since every bar has different render
	 * needs, we keep it abstracted
	 *
	 * The main purpose of this render function is to set the templates
	 */

	/**
	 * We also need to have an enabled function
	 */

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
		return 'bar_' . static::$bar;
	}

    /**
     * Loads all the blocks
     *
     * @access public
     * @return array
     */
    public function getBlocks()
    {
        global $user_info;

        if ($this->blocks_cache == null)
            $this->blocks_cache = WePortal::fetchContentProviders(true, 'bar_' . static::$bar, $user_info['groups']);

        return $this->blocks_cache;
    }

    /**
	 * Fetches the member's block preferences from the database
	 *
	 * @access public
	 * @param int $id_member The ID of the member to fetch
	 * @return array The list of blocks with the adjusted parameters
     */
    public function getMemberBlocks($id_member)
    {
		// Empty member? Die hard.
		if (empty($id_member))
			return false;

        if (is_array($this->member_block_cache) && isset($this->member_block_cache[$id_member]))
            return $this->member_block_cache[$id_member];

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

		$this->member_block_cache[$id_member] = $member_blocks;

        return $member_blocks;
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
        global $user_info;

		$this->portal = $portal;

		if (!$this->prelim_enabled())
			return false;

        $this->portal->registerArea('blockupdate', array($this, 'blockupdate'));

		// Load the blocks appropiate for this bar
		$blocks = $this->getBlocks();
		$member_blocks = $this->getMemberBlocks($user_info['id']);

		// Extend member blocks preference with blocks
		foreach ($member_blocks as $block => $pref)
		{
			if (!$blocks[$block]['adjustable'])
				continue;

			$blocks[$block] = array_merge($blocks[$block], $pref);
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

		unset($member_blocks);

		// Add the blocks to this bar
		$this->populateBlocks($blocks);
	}

    /**
     * Returns the per provider parameters
     *
     * @static
     * @access public
     * @return array
     */
    public static function getProviderParameters()
    {
        return array();
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
			$blocks[$k] = $this->portal->initiateContentProvider($block, $this);
			if ($blocks[$k] === false)
			{
				unset($blocks[$k]);
				continue;
			}

			if ($blocks[$k]->enabled())
				$blocks[$k]->prepare();
			else
				unset($blocks[$k]);
		}

		// Now that we got proper block controllers set, we can finally do something about them
		$this->blocks = $blocks;
	}

	/**
	 * Handles AJAX call for updating the user's blocks
	 * Since this is present for both, left and right or maybe even more bars,
	 * we call them all and then filter out block wise
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
				{
					if (!isset($this->blocks[(int) $id_block]))
						continue;

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
		}

		if (!empty($_POST['blocks_disabled']))
			foreach ($_POST['blocks_disabled'] as $disabled_block)
			{
				if (!isset($this->blocks[(int) $disabled_block]))
					continue;

				$blockinfo = $this->blocks[(int) $disabled_block]->info();

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
							'bar' => $blockinfo['parameters']['bar'],
							'position' => $blockinfo['position'],
							'enabled' => '0',
						)
					);
			}

		if (!empty($_POST['blocks_enabled']))
			foreach ($_POST['blocks_enabled'] as $enabled_block)
			{
				if (!isset($this->blocks[(int) $enabled_block]))
					continue;

				$blockinfo = $this->blocks[(int) $enabled_block]->info();

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
							'bar' => $blockinfo['parameters']['bar'],
							'position' => $blockinfo['position'],
							'enabled' => '1',
						)
					);
			}
		exit;
	}
}
?>