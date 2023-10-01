<?php
	require_once 'common.php';
	require_once 'instructor_lib.php';

	db_connect();

	$user = getUser(ADMIN_PRIV);
	// HACK
	$user["priv"] = ADMIN_PRIV;
	if (! $user) {
		// return;
	}

	$action = $_REQUEST['action'];
	if ($action == 'view_instructor_roles') {
		$response = '<xml version="1.0" ?>';
		$response .= '<root success="true"';
		$response .= ' message="it worked!"';
		$response .= '></root>';
		sendResponse($response);
	}

	function sendResponse($response) {
		header("Content-Type: text/xml");
		echo $response;
	}
