<?php
/**
 * This file implements the item type cache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

/**
 * ItemTypeCache Class
 *
 * @package evocore
 */
class ItemTypeCache extends DataObjectCache
{
	/**
	 * Item type cache for each collection
	 */
	var $col_cache = array();

	/**
	 * Default item type for each collection
	 */
	var $col_default = array();


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function ItemTypeCache()
	{
		// Call parent constructor:
		parent::DataObjectCache( 'ItemType', true, 'T_items__type', 'ityp_', 'ityp_ID', 'ityp_name', 'ityp_ID' );
	}


	/**
	 * Returns a form option list which only contains post types that can
	 * be used by the current user (and in the current blog's context).
	 *
	 * The user cannot use any post type IDs listed in the {@see $posttypes_reserved_IDs}
	 * array; to use the "Page", "Intro-*", "Podcast" and "Sidebar link"
	 * post types, the current blog must grant the blog_page, blog_intro,
	 * blog_podcast and blog_sidebar permission, respectively (see blog
	 * user/group permissions).
	 * 
	 * @param integer The selected ID.
	 * @param boolean Provide a choice for "none" with ID ''
	 * @param string  Callback method name.
	 * @return string
	 */
	function get_option_list_usable_only( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		global $posttypes_reserved_IDs, $posttypes_perms, $current_User, $Blog;

		// Compile an array of post type IDs to exclude:
		$exclude_posttype_IDs = $posttypes_reserved_IDs;

		foreach( $posttypes_perms as $l_permname => $l_posttype_IDs )
		{
			if( ! $current_User->check_perm( 'blog_'.$l_permname, 'edit', false, $Blog->ID ) )
			{	// No permission to use this post type(s):
				$exclude_posttype_IDs = array_merge( $exclude_posttype_IDs, $l_posttype_IDs );
			}
		}

		return $this->get_option_list( $default, $allow_none, $method, $exclude_posttype_IDs );
	}

	/**
	 * For use by Universal Item List widget
	 */
	function get_option_array()
	{
		global $posttypes_reserved_IDs;

		return parent::get_option_array( 'get_name', $posttypes_reserved_IDs );
	}
}

?>