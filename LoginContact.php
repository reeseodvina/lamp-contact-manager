<?php

	//Second
	$inData = getRequestInfo();
	
	$id = 0;
	$firstName = "";
	$lastName = "";

	//I have no idea what our SQL sign in is so yeah... - Justin 9/8/25
	$conn = new mysqli("localhost", "Poos22", "WeLovePoos22", "Poos22"); 	

	if( $conn->connect_error )
	{
		returnWithError( $conn->connect_error );
	}
	else
	{
		// Check if any empty inputs
		if( empty($inData["login"]) || empty($inData["password"]) )
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
				//TO-DO: Possibly re-route to register (Justin 9/9/25)
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
		return json_decode(file_get_contents('php://input'), true);
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
