<?php
/**
 * Form to edit settings of a plugin.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @global Plugin
 */
global $edit_Plugin;

/**
 * @global Plugins_admin
 */
global $admin_Plugins;

global $edited_plugin_priority, $edited_plugin_code, $edited_plugin_apply_rendering, $admin_url;

/**
 * @global string Contents of the Plugin's help file, if any. We search there for matching IDs/anchors to display links to them.
 */
global $plugin_help_contents;
$plugin_help_contents = '';

if( $help_file = $edit_Plugin->get_help_file() )
{
	$plugin_help_contents = implode( '', file($help_file) );
}


$Form = & new Form( NULL, 'pluginsettings_checkchanges' );
$Form->hidden_ctrl();

// Help icons, if available:
if( ! empty( $edit_Plugin->help_url ) )
{
	$Form->global_icon( T_('Homepage of the plugin'), 'www', $edit_Plugin->help_url );
}
if( $edit_Plugin->get_help_file() )
{
	$Form->global_icon( T_('Local documentation of the plugin'), 'help', url_add_param( $admin_url, 'ctrl=plugins&amp;action=disp_help_plain&amp;plugin_ID='.$edit_Plugin->ID.'#'.$edit_Plugin->classname.'_settings' ), '', array('use_js_popup'=>true, 'id'=>'local_help_popup_'.$edit_Plugin->ID) );
}

$Form->global_icon( T_('Cancel edit!'), 'close', regenerate_url() );

$Form->begin_form( 'fform' );
$Form->hidden( 'plugin_ID', $edit_Plugin->ID );

// PluginSettings
if( $edit_Plugin->Settings )
{
	global $inc_path;
	require_once $inc_path.'_misc/_plugin.funcs.php';

	$Form->begin_fieldset( T_('Plugin settings'), array( 'class' => 'clear' ) );

	foreach( $edit_Plugin->GetDefaultSettings() as $l_name => $l_meta )
	{
		display_settings_fieldset_field( $l_name, $l_meta, $edit_Plugin, $Form, 'Settings' );
	}

	$admin_Plugins->call_method( $edit_Plugin->ID, 'PluginSettingsEditDisplayAfter', $tmp_params = array( 'Form' => & $Form ) );

	$Form->end_fieldset();
}

// Plugin variables
$Form->begin_fieldset( T_('Plugin variables').' ('.T_('Advanced').')', array( 'class' => 'clear' ) );
$Form->text_input( 'edited_plugin_code', $edited_plugin_code, 15, T_('Code'), array('maxlength'=>32, 'note'=>'The code to call the plugin by code. This is also used to link renderer plugins to items.') );
$Form->text_input( 'edited_plugin_priority', $edited_plugin_priority, 4, T_('Priority'), array( 'maxlength' => 4 ) );
$Form->select_input_array( 'edited_plugin_apply_rendering', $admin_Plugins->get_apply_rendering_values(), T_('Apply rendering'), array(
	'value' => $edited_plugin_apply_rendering,
	'note' => empty( $edited_plugin_code )
		? T_('Note: The plugin code is empty, so this plugin will not work as an "opt-out", "opt-in" or "lazy" renderer.')
		: NULL )
	);
$Form->end_fieldset();


// (De-)Activate Events (Advanced)
$Form->begin_fieldset( T_('Plugin events').' ('.T_('Advanced')
	.') <img src="'.get_icon('expand', 'url').'" id="clickimg_pluginevents" />', array('legend_params' => array( 'onclick' => 'toggle_clickopen(\'pluginevents\')') ) );
?>

<div id="clickdiv_pluginevents">

<?php
$enabled_events = $admin_Plugins->get_enabled_events( $edit_Plugin->ID );
$supported_events = $admin_Plugins->get_supported_events();
$registered_events = $admin_Plugins->get_registered_events( $edit_Plugin );
$count = 0;
foreach( array_keys($supported_events) as $l_event )
{
	if( ! in_array( $l_event, $registered_events ) )
	{
		continue;
	}
	$Form->hidden( 'edited_plugin_displayed_events[]', $l_event ); // to consider only displayed ones on update
	$Form->checkbox_input( 'edited_plugin_events['.$l_event.']', in_array( $l_event, $enabled_events ), $l_event, array( 'note' => $supported_events[$l_event] ) );
	$count++;
}
if( ! $count )
{
	echo T_( 'This plugin has no registered events.' );
}
?>

</div>

<?php
$Form->end_fieldset();
?>

<script type="text/javascript">
	<!--
	toggle_clickopen('pluginevents');
	// -->
</script>

<?php
if( $current_User->check_perm( 'options', 'edit', false ) )
{
	$Form->buttons_input( array(
		array( 'type' => 'submit', 'name' => 'actionArray[update_settings]', 'value' => T_('Save !'), 'class' => 'SaveButton' ),
		array( 'type' => 'submit', 'name' => 'actionArray[update_settings][review]', 'value' => T_('Save (and review)'), 'class' => 'SaveButton' ),
		array( 'type' => 'reset', 'value' => T_('Reset'), 'class' => 'ResetButton' ),
		array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'SaveButton' ),
		) );
}
$Form->end_form();


/* {{{ Revision log:
 * $Log$
 * Revision 1.4  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.3  2006/02/27 16:57:12  blueyed
 * PluginUserSettings - allows a plugin to store user related settings
 *
 * Revision 1.2  2006/02/24 23:38:55  blueyed
 * fixes
 *
 * Revision 1.1  2006/02/24 23:02:16  blueyed
 * Added _set_plugins_editsettings.form VIEW
 *
 * }}}
 */
?>