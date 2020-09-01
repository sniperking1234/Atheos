<?php

//////////////////////////////////////////////////////////////////////////////80
// User Controller
//////////////////////////////////////////////////////////////////////////////80
// Copyright (c) 2020 Liam Siira (liam@siira.io), distributed as-is and without
// warranty under the MIT License. See [root]/license.md for more.
// This information must remain intact.
//////////////////////////////////////////////////////////////////////////////80
// Authors: Codiad Team, @Fluidbyte, Atheos Team, @hlsiira
//////////////////////////////////////////////////////////////////////////////80

require_once 'class.user.php';

$activeUser = SESSION("user");
$User = new User($activeUser);

$username = POST('username');
$password = POST('password');
$language = POST('language');

switch ($action) {
	//////////////////////////////////////////////////////////////////////////80
	// Authenticate / LogIn
	//////////////////////////////////////////////////////////////////////////80
	case 'authenticate':
		if ($username && $password) {
			$theme = POST('theme');
			$languages = $i18n->codes();
			if (!$language || !isset($languages[$language])) $language = "en";

			// theme
			$User->authenticate($username, $password, $language, $theme);
		} elseif (!$username) {
			Common::sendJSON("error", "Missing username."); die;
		} else {
			Common::sendJSON("error", "Missing password."); die;
		}
		break;

	//////////////////////////////////////////////////////////////////////////80
	// Change Password
	//////////////////////////////////////////////////////////////////////////80
	case 'changePassword':
		if (!$username || !$password) {
			die(Common::sendJSON("error", "Missing username or password"));
		}

		if (Common::checkAccess("configure") || $username === SESSION("user")) {
			$User->changePassword($username, $password);
		} else {
			Common::sendJSON("E430u");
			die;
		}
		break;

	//////////////////////////////////////////////////////////////////////////80
	// Create User
	//////////////////////////////////////////////////////////////////////////80
	case 'create':
		if (!Common::checkAccess("configure")) {
			Common::sendJSON("E430u");
			die;
		}
		if (!$username || !$password) {
			die(Common::sendJSON("error", "Missing username or password"));
		}

		$User->create($username, $password);

		break;

	//////////////////////////////////////////////////////////////////////////80
	// Delete User
	//////////////////////////////////////////////////////////////////////////80
	case 'delete':
		if (!Common::checkAccess("configure")) {
			Common::sendJSON("E430u");
			die;
		}
		if (!$username) {
			die(Common::sendJSON("error", "Missing username"));
		}
		$User->delete($username);

		break;

	//////////////////////////////////////////////////////////////////////////80
	// Verify Session
	//////////////////////////////////////////////////////////////////////////80
	case 'keepAlive':
		$User->verify($activeUser);
		break;

	//////////////////////////////////////////////////////////////////////////80
	// Logout
	//////////////////////////////////////////////////////////////////////////80
	case 'logout':
		session_unset();
		session_destroy();
		session_start();
		break;

	//////////////////////////////////////////////////////////////////////////80
	// Save Active Project
	//////////////////////////////////////////////////////////////////////////80
	case 'saveActiveProject':
		$activeProject = POST('activeProject');

		if (!isset($activeProject)) {
			die(Common::sendJSON("error", "Missing project"));
		}
		$User->saveActiveProject($activeProject);
		break;

	//////////////////////////////////////////////////////////////////////////80
	// Set Project Access
	//////////////////////////////////////////////////////////////////////////80
	case 'updateACL':
		if (!Common::checkAccess("configure")) {
			Common::sendJSON("E430u");
			die;
		}
		if (!$username) {
			die(Common::sendJSON("error", "Missing username"));

			$userACL = POST('userACL');
			$User->updateACL($username, $userACL);
		}
		break;

	//////////////////////////////////////////////////////////////////////////80
	// Default: Invalid Action
	//////////////////////////////////////////////////////////////////////////80
	default:
		Common::sendJSON("E401i");
		break;
}