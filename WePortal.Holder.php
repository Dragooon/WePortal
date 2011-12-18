<?php
/**
 * Contains Holder's interface, guideline for all the holders
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
 * Interface for all the holders out there
 */
interface WePHolder
{
	/**
	 * There will always be a constructor
	 */
	public function __construct(WePortal $portal);

	/**
	 * This returns the holder's type, this function cannot be extended
	 * since the parent holder controls everything
	 */
	public static function getHolderType();

	/**
	 * Returns the per-provider parameters
	 */
	public static function getProviderParameters();

	/**
	 * Returns an ID associated to the holder, can be same as holder type if none exist
	 */
	public static function getHolderID();

	/**
	 * You got to render a holder, correct?
	 */
	public function render();

	/**
	 * This is a check whether this holder is enabled or not
	 */
	public function enabled();
}