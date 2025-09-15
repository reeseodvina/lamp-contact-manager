<?php

	//Second
	$inData = getRequestInfo();
	
	$id = 0;
	$firstName = "";
	$lastName = "";

	// Debug: Log what we received (remove this in production)
	error_log("Received data: " . json_encode($inData));
	error_log("Login value: '" . ($inData["login"] ?? 'NULL') . "'");
	error_log("Password value: '" . ($inData["password"] ?? 'NULL') . "'");

	//I have no idea what our SQL sign in is so yeah... - Justin 9/8/25
	$conn = new mysqli("localhost", "lampapi", "Sup3rSh1nyMudk1p", "LampStackProject");
	if( $conn->connect_error )
	{
		returnWithError( $conn->connect_error );
	}
	else
	{
		// Check missing/empty inputs FIRST - also check for null
		if( !isset($inData["login"]) || !isset($inData["password"]) || 
		    empty(trim($inData["login"])) || empty(trim($inData["password"])) )
		{
			returnWithError("Umm you forgot to put something...");
		}
		else
		{
			$stmt = $conn->prepare("SELECT ID, firstName, lastName FROM Users WHERE Login=? AND Password=?");
			$stmt->bind_param("ss", $inData["login"], $inData["password"]);
			$stmt->execute();
			$result = $stmt->get_result();

			if( $row = $result->fetch_assoc() )
			{
				returnWithInfo( $row['firstName'], $row['lastName'], $row['ID'] );
			}
			else
			{
				//Possibly re-route to register (Justin 9/9/25)
				returnWithError("WOMP WOMP: wrong username or password");
			}

			$stmt->close();
		}
		
		$conn->close();
	}
	
	// Function to decode JSON input from request body
	function getRequestInfo()
	{
		// Read raw input from php://input stream and decode JSON to associative array
		$input = file_get_contents('php://input');
		error_log("Raw input: " . $input); // Debug line - remove in production
		$decoded = json_decode($input, true);
		
		// Check for JSON decode errors
		if (json_last_error() !== JSON_ERROR_NONE) {
			error_log("JSON decode error: " . json_last_error_msg());
			return [];
		}
		
		return $decoded;
	}

	// Function to send JSON response with proper content type header
	function sendResultInfoAsJson( $obj )
	{
		// Set response content type to JSON
		header('Content-type: application/json');
		// Output the JSON string
		echo $obj;
	}
	
	// Function to format and send error response
	function returnWithError( $err )
	{
		// Create JSON error response with empty user data and error message
		$retValue = '{"id":0,"firstName":"","lastName":"","error":"' . $err . '"}';
		// Send the formatted JSON response
		sendResultInfoAsJson( $retValue );
	}
	
	// Function to format and send successful response with user data
	function returnWithInfo( $firstName, $lastName, $id )
	{
		// Create JSON success response with user data and empty error field
		$retValue = '{"id":' . $id . ',"firstName":"' . $firstName . '","lastName":"' . $lastName . '","error":""}';
		// Send the formatted JSON response
		sendResultInfoAsJson( $retValue );
	}
	
?>