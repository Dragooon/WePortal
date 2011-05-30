<?php
/****************************************************************
* WePortal														*
* © Shitiz "Dragooon" Garg										*
*****************************************************************
* WePortal.Holder.php - Contains interface for content holders	*
*****************************************************************
* Users of this software are bound by the terms of the			*
* WePortal license. You can view it in the license_wep.txt		*
* file															*
*																*
* For support and updates, don't come to me						*
****************************************************************/

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