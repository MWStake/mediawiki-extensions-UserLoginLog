<?php
/**
 * UserLoginLog - MediaWiki extension to add Userlogin events to the log
 *
 * See http://www.mediawiki.org/wiki/Extension:UserLoginLog for installation and usage details
 * 
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'USER_LOGIN_LOG_VERSION', '1.0.1, 2012-15-17' );

$wgServerUser = 1; # User ID to use for logging if no user exists

$wgExtensionCredits['other'][] = array(
	'name'        => 'UserLoginLog',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Creates a new MediaWiki log for user logins and logout events',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:UserLoginLog',
	'version'     => USER_LOGIN_LOG_VERSION
);
$wgExtensionMessagesFiles['UserLoginLog'] = dirname( __FILE__ ) . '/' . 'UserLoginLog.i18n.php';

// Add a new log type
$wgLogTypes[]                      = 'userlogin';
$wgLogNames  ['userlogin']         = 'userloginlogpage';
$wgLogHeaders['userlogin']         = 'userloginlogpagetext';
$wgLogActions['userlogin/success'] = 'userlogin-success';
$wgLogActions['userlogin/error']   = 'userlogin-error';
$wgLogActions['userlogin/logout']  = 'userlogin-logout';

// Add hooks to the login/logout events
$wgHooks['UserLoginForm'][]      = 'wfUserLoginLogError';
$wgHooks['UserLoginComplete'][]  = 'wfUserLoginLogSuccess';
$wgHooks['UserLogout'][]         = 'wfUserLoginLogout';
$wgHooks['UserLogoutComplete'][] = 'wfUserLoginLogoutComplete';

function wfUserLoginLogSuccess( &$user ) {
	$log = new LogPage( 'userlogin', false );
	$log->addEntry( 'success', $user->getUserPage(), wfGetIP() );
	return true;
}

function wfUserLoginLogError( &$tmpl ) {
	global $wgUser, $wgServerUser;
	if( $tmpl->data['message'] && $tmpl->data['messagetype'] == 'error' ) {
		$log = new LogPage( 'userlogin', false );
		$tmp = $wgUser->mId;
		if( $tmp == 0 ) $wgUser->mId = $wgServerUser;
		$log->addEntry( 'error', $wgUser->getUserPage(), $tmpl->data['message'], array( wfGetIP() ) );
		$wgUser->mId = $tmp;
	}
	return true;
}

/**
 * Create a copy of the current user for logging after logout
 */
function wfUserLoginLogout( $user ) {
	global $wgUserBeforeLogout;
	$wgUserBeforeLogout = User::newFromId( $user->getID() );
	return true;
}

function wfUserLoginLogoutComplete( $user ) {
	global $wgUser, $wgUserBeforeLogout;
	$tmp = $wgUser->mId;
	$wgUser->mId = $wgUserBeforeLogout->getId();
	$log = new LogPage( 'userlogin', false );
	$log->addEntry( 'logout', $wgUserBeforeLogout->getUserPage(), $user->getName() );
	$wgUser->mId = $tmp;
	return true;
}
