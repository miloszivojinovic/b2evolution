<?php
/**
 * This file implements the UI view for the user properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Fran�ois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

// Begin payload block:
$AdminUI->dispPayloadBegin();

$allowed_to_edit = ( $current_User->check_perm( 'users', 'edit' )
											|| ($user_profile_only && $edited_User->ID == $current_User->ID) );

/*
 * fplanque>>blueyed: Daniel I am removing the user switch code because it doesn't fit in
 * with the rest of the app (planned move to Widget/Results class), creates extra DB requests and
 * most of all, assumes that users are sorted by ID which obviously won't be the case on
 * large user bases. I hope you won't mind...
 */

$Form = & new Form( 'b2users.php', 'form' );

$Form->global_icon( $allowed_to_edit ? T_('Cancel editing!') : T_('Close user profile!'), 'close',
										regenerate_url( 'user_ID,action' ) );

if( $edited_User->get('ID') == 0 )
{	// Creating new user:
	$creating = true;
	$Form->begin_form( 'fform', T_('Create new user profile') );
}
else
{	// Editing existing user:
	$creating = false;
	$Form->begin_form( 'fform', T_('Profile for:').' '.$edited_User->dget('fullname')
				.' ['.$edited_User->dget('login').']' );
}

$Form->hidden( 'action', 'userupdate' );
$Form->hidden( 'edited_user_ID', $edited_User->ID );


$Form->fieldset( T_('User rights'), 'fieldset clear' );

$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://b2evolution.net/man/user_levels.html"' );
if( $user_profile_only )
{
	$Form->info( T_('Level'), $edited_User->dget('level'), $field_note );
}
else
{
	$Form->text( 'edited_user_level', $edited_User->level, 2, T_('Level'), $field_note, 2 );
}
if( $edited_User->get('ID') != 1 && !$user_profile_only )
{	// This is not Admin and we're not restricted: we're allowed to change the user group:
	$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get('newusers_grp_ID') : $edited_User->Group->get('ID');
	$Form->select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_('User group') );
}
else
{
	echo '<input type="hidden" name="edited_user_grp_ID" value="'.$edited_User->Group->ID.'" />';
	$Form->info( T_('User group'), $edited_User->Group->dget('name') );
}

$Form->fieldset_end();


$Form->fieldset( T_('User') );

$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Send an email').'" class="middle" /></a>';

if( ($url = $edited_User->get('url')) != '' )
{
	if( !preg_match('#://#', $url) )
	{
		$url = 'http://'.$url;
	}
	$url_fieldnote = '<a href="'.$url.'" target="_blank"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Visit the site').'" class="middle" /></a>';
}
else
	$url_fieldnote = '';

if( $edited_User->get('icq') != 0 )
	$icq_fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Search on ICQ.com').'" class="middle" /></a>';
else
	$icq_fieldnote = '';

if( $edited_User->get('aim') != '' )
	$aim_fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello"><img src="img/play.png" height="14" width="14" alt="&gt;" title="'.T_('Instant Message to user').'" class="middle" /></a>';
else
	$aim_fieldnote = '';


if( $allowed_to_edit )
{ // We can edit the values:
	$Form->text( 'edited_user_login', $edited_User->login, 20, T_('Login'), '', 20 );
	$Form->text( 'edited_user_firstname', $edited_User->firstname, 20, T_('First name'), '', 50 );
	$Form->text( 'edited_user_lastname', $edited_User->lastname, 20, T_('Last name'), '', 50 );
	$Form->text( 'edited_user_nickname', $edited_User->nickname, 20, T_('Nickname'), '', 50 );
	$Form->select( 'edited_user_idmode', $edited_User->get( 'idmode' ), array( &$edited_User, 'callback_optionsForIdMode' ), T_('Identity shown') );
	$Form->checkbox( 'edited_user_showonline', $edited_User->get('showonline'), T_('Show Online'), T_('Check this to be displayed as online when visiting the site.') );
	$Form->select( 'edited_user_locale', $edited_User->get('locale'), 'locale_options_return', T_('Preferred locale'), T_('Preferred locale for admin interface, notifications, etc.'));
	$Form->text( 'edited_user_email', $edited_User->email, 30, T_('Email'), $email_fieldnote, 100 );
	$Form->checkbox( 'edited_user_notify', $edited_User->get('notify'), T_('Notifications'), T_('Check this to receive a notification whenever one of <strong>your</strong> posts receives comments, trackbacks, etc.') );
	$Form->text( 'edited_user_url', $edited_User->url, 30, T_('URL'), $url_fieldnote, 100 );
	$Form->text( 'edited_user_icq', $edited_User->icq, 30, T_('ICQ'), $icq_fieldnote, 10 );
	$Form->text( 'edited_user_aim', $edited_User->aim, 30, T_('AIM'), $aim_fieldnote, 50 );
	$Form->text( 'edited_user_msn', $edited_User->msn, 30, T_('MSN IM'), '', 100 );
	$Form->text( 'edited_user_yim', $edited_User->yim, 30, T_('YahooIM'), '', 50 );
	$Form->text( 'edited_user_pass1', '', 20, T_('New password'), '', 50, T_('Leave empty if you don\'t want to change the password.'), 'password' );
	$Form->text( 'edited_user_pass2', '', 20, T_('Confirm new password'), '', 50, '', 'password' );

}
else
{ // display only
	$Form->_info( T_('Login'), $edited_User->dget('login') );
	$Form->_info( T_('First name'), $edited_User->dget('firstname') );
	$Form->_info( T_('Last name'), $edited_User->dget('lastname') );
	$Form->_info( T_('Nickname'), $edited_User->dget('nickname') );
	$Form->_info( T_('Identity shown'), $edited_User->dget('preferedname') );
	$Form->_info( T_('Show Online'), ($edited_User->dget('showonline')) ? T_('yes') : T_('no') );
	$Form->_info( T_('Locale'), $edited_User->dget('locale'), T_('Preferred locale for admin interface, notifications, etc.') );
	$Form->_info( T_('Email'), $edited_User->dget('email'), $email_fieldnote );
	$Form->_info( T_('Notifications'), ($edited_User->dget('notify')) ? T_('yes') : T_('no') );
	$Form->_info( T_('URL'), $edited_User->dget('url'), $url_fieldnote );
	$Form->_info( T_('ICQ'), $edited_User->dget('icq', '$Form->value'), $icq_fieldnote );
	$Form->_info( T_('AIM'), $edited_User->dget('aim'), $aim_fieldnote );
	$Form->_info( T_('MSN IM'), $edited_User->dget('msn') );
	$Form->_info( T_('YahooIM'), $edited_User->dget('yim') );
}

$Form->fieldset_end();


if( $allowed_to_edit )
{ // Edit buttons
	$Form->buttons( array( array( '', '', T_('Save !'), 'SaveButton' ),
												 array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

if( ! $creating )
{ // We're NOT creating a new user:
	$Form->fieldset( T_('User information') );

	$Form->info( T_('ID'), $edited_User->dget('ID') );

	if( $app_shortname == 'b2evo' )
	{ // TODO: move this out of the core
		$Form->info( T_('Posts'), ( $action != 'newtemplate' ) ? $edited_User->getNumPosts() : '-' );
	}
	$Form->info( T_('Created on'), $edited_User->dget('datecreated') );
	$Form->info( T_('From IP'), $edited_User->dget('ip') );
	$Form->info( T_('From Domain'), $edited_User->dget('domain') );
	$Form->info( T_('With Browser'), $edited_User->dget('browser') );

	$Form->fieldset_end();
}

$Form->end_form();

// End payload block:
$AdminUI->dispPayloadEnd();

/*
 * $Log$
 * Revision 1.58  2005/06/03 20:14:38  fplanque
 * started input validation framework
 *
 * Revision 1.57  2005/05/24 18:46:26  fplanque
 * implemented blog email subscriptions (part 1)
 *
 * Revision 1.56  2005/04/06 13:33:28  fplanque
 * minor changes
 *
 * Revision 1.55  2005/03/22 16:36:00  fplanque
 * refactoring, standardization
 * fixed group creation bug
 *
 * Revision 1.54  2005/03/21 18:57:22  fplanque
 * user management refactoring (towards new evocore coding guidelines)
 * WARNING: some pre-existing bugs have not been fixed here
 *
 */
?>