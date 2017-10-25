<?php
/**
 * UserLoginLog - MediaWiki extension to add Userlogin events to the log
 *
 * See http://www.mediawiki.org/wiki/Extension:UserLoginLog for
 * installation and usage details
 *
 * Copyright (C) 2007  Aran Dunkley
 * Copyright (C) 2017  J. David Gladstone Institutes
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @author Mark A. Hershberger <mah@nichework.com>
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
namespace MediaWiki\Extension\UserLoginLog;

class Hook {
	private $userBeforeLogout;

	/**
	 * Handler for the UserLoginComplete hook
	 *
	 * @param User $user to log
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserLoginComplete
	 */
	public static function logSuccess( User $user ) {
		global $wgRequest;
		$log = new LogPage( 'userlogin', false );
		$log->addEntry( 'success', $user->getUserPage(), $wgRequest->getIP() );
	}

	/**
	 * Handler for UserLoginForm hook
	 *
	 * @param QuickTemplate $tmpl template
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserLoginForm
	 * @deprecated since MW 1.27
	 */
	public static function logError( QuickTemplate $tmpl ) {
		global $wgUser;

		$config = Config::getInstance();

		if ( $tmpl->data['message'] && $tmpl->data['messagetype'] == 'error' ) {
			$log = new LogPage( 'userlogin', false );
			$tmp = $wgUser->mId;
			if ( $tmp == 0 ) {
				$wgUser->mId = $config->get( "ServerUser" );
			}
			$log->addEntry(
				'error', $wgUser->getUserPage(), $tmpl->data['message'],
				[ $wgRequest->getIP() ]
			);
			$wgUser->mId = $tmp;
		}
	}

	/**
	 * Create a copy of the current user for logging after logout
	 *
	 * @param User $user that is logging out
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserLogout
	 */
	public static function logout( User $user ) {
		self::$userBeforeLogout = User::newFromId( $user->getID() );
	}

	/**
	 * Log the user logout
	 *
	 * @param User $user that is logging out
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserLogoutComplete
	 */
	public static function logoutComplete( $user ) {
		global $wgUser;

		$tmp = $wgUser->mId;
		$wgUser->mId = self::$userBeforeLogout->getId();
		$log = new LogPage( 'userlogin', false );
		$log->addEntry(
			'logout', self::$userBeforeLogout->getUserPage(), $user->getName()
		);
		$wgUser->mId = $tmp;
	}
}
