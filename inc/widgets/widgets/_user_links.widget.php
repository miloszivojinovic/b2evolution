<?php
/**
 * This file implements the user_links_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class user_links_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function user_links_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'user_links' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User links');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display user links.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => '',
				),
				'login' => array(
					'label' => T_('User login'),
					'note' => T_('leave blank to use author of current post or current collection.'),
					'size' => 20,
					'defaultvalue' => '',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $DB, $Item, $Blog;

		$this->init_display( $params );

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		$widget_User = & $this->get_widget_User();
		if( empty( $widget_User ) )
		{ // No user detected
			echo '<p class="red">'.sprintf( T_('User %s not found.'), '<b>'.format_to_output( $this->disp_params['login'], 'text' ).'</b>' ).'</p>';
		}

		if( ! empty( $widget_User ) )
		{ // If we really have found user
			// Get all user extra field values with type "url"
			$url_fields = $widget_User->userfields_by_type( 'url' );
			if( count( $url_fields ) )
			{
				echo '<div class="widget--social-media-links">';
				foreach( $url_fields as $field )
				{
					echo '<a href="'.$field->uf_varchar.'">'
							.'<span class="'.$field->ufdf_icon_name.'"></span>'
						.'</a>';
				}
				echo '</div>';
			}
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Get User that should be used for this widget now
	 *
	 * @return object User
	 */
	function & get_widget_User()
	{
		global $Item, $Blog;

		$widget_User = NULL;

		if( empty( $this->disp_params['login'] ) )
		{ // No defined user in widget settings
			if( ! empty( $Item ) )
			{ // Use an author of the current $Item
				$widget_User = & $Item->get_creator_User();
			}
			elseif( ! empty( $Blog ) )
			{ // Use an owner of the current $Blog
				$widget_User = & $Blog->get_owner_User();
			}
		}
		else
		{ // Try to get user by login from DB
			$UserCache = & get_UserCache();
			$widget_User = & $UserCache->get_by_login( $this->disp_params['login'] );
		}

		return $widget_User;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Blog;

		$cache_keys = array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new owner, new skin)
			);

		if( $widget_User = & $this->get_widget_User() )
		{
			$cache_keys['user_ID'] = $widget_User->ID; // Has the owner User changed? (name, avatar, etc..)
		}

		return $cache_keys;
	}
}

?>