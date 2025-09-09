<?php

	//Third
	$inData = getRequestInfo();
	
	$searchResults = "";
	$searchCount = 0;
	
	//I have no idea what our SQL sign in is so yeah... - Justin 9/8/25
	$conn = new mysqli("localhost", "Poos22", "WeLovePoos22", "Poos22");
	if( $conn->connect_error )
	{
		returnWithError( $conn->connect_error );
	}
	else
	{
		//clean up search input
		$searchTerm = trim($inData["search"]);
		$userId = $inData["userId"];
		
		if( empty($searchTerm) )
		{
			returnWithError("Search term cannot be empty");
		}
		else
		{
			// First try exact matches and partial matches
			$stmt = $conn->prepare("SELECT Name, Phone, Email FROM Contacts WHERE UserID=? AND (Name LIKE ? OR Phone LIKE ? OR Email LIKE ?)");
			$searchPattern = "%" . $searchTerm . "%";
			$stmt->bind_param("isss", $userId, $searchPattern, $searchPattern, $searchPattern);
			$stmt->execute();
			$result = $stmt->get_result();
			
			$contacts = array();
			while( $row = $result->fetch_assoc() )
			{
				$contacts[] = $row;
			}
			$stmt->close();
			
			// If no results found, try fuzzy matching for typos
			if( count($contacts) == 0 )
			{
				$contacts = performFuzzySearch($conn, $userId, $searchTerm);
			}
			
			if( count($contacts) > 0 )
			{
				returnWithInfo($contacts);
			}
			else
			{
				returnWithError("No Records Found");
			}
		}
		
		$conn->close();
	}
	
	//Function to work for typos
	function performFuzzySearch($conn, $userId, $searchTerm)
	{
		$contacts = array();
		
		// Get all contacts for the user
		$stmt = $conn->prepare("SELECT Name, Phone, Email FROM Contacts WHERE UserID=?");
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$result = $stmt->get_result();
		
		while( $row = $result->fetch_assoc() )
		{
			// Check similarity with name, phone, and email
			$nameDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['Name']));
			$phoneDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['Phone']));
			$emailDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['Email']));
			
			$minDistance = min($nameDistance, $phoneDistance, $emailDistance);
			
			// Allow up to 2 character differences for typos
			//TO-DO: send confirmation to User - Justin 9/9/25
			$maxAllowedDistance = min(2, floor(strlen($searchTerm) * 0.3));
			
			if( $minDistance <= $maxAllowedDistance )
			{
				$contacts[] = $row;
			}
		}
		
		$stmt->close();
		return $contacts;
	}

	//Helper function to see how similar inputs are to users
	function levenshteinDistance($str1, $str2)
	{
		$len1 = strlen($str1);
		$len2 = strlen($str2);
		
		if( $len1 == 0 ) return $len2;
		if( $len2 == 0 ) return $len1;
		
		$matrix = array();
		
		// Initialize first row and column
		for( $i = 0; $i <= $len1; $i++ )
		{
			$matrix[$i][0] = $i;
		}
		for( $j = 0; $j <= $len2; $j++ )
		{
			$matrix[0][$j] = $j;
		}
		
		// Fill the matrix
		for( $i = 1; $i <= $len1; $i++ )
		{
			for( $j = 1; $j <= $len2; $j++ )
			{
				$cost = ($str1[$i-1] == $str2[$j-1]) ? 0 : 1;
				
				$matrix[$i][$j] = min(
					$matrix[$i-1][$j] + 1,        // deletion
					$matrix[$i][$j-1] + 1,        // insertion
					$matrix[$i-1][$j-1] + $cost   // substitution
				);
			}
		}
		
		return $matrix[$len1][$len2];
	}
	
	// === JSON HANDLING FUNCTIONS === ( honestly shoulda put this on Register since I did that first)
	// These functions handle converting between PHP data and JSON (JavaScript Object Notation)
	// JSON is like a text-based way to represent objects/structs, similar to serialization in Java
	
	// Function to decode JSON input from request body
	function getRequestInfo()
	{
		// === READING REQUEST DATA ===
		// Read raw input from php://input stream and decode JSON to associative array
		// This is like:
		//   - In Java: ObjectMapper.readValue(inputStream, Map.class) 
		//   - In C: reading from stdin and parsing manually
		// 
		// Example: '{"search":"john","userId":1}' becomes PHP array: ["search" => "john", "userId" => 1]
		return json_decode(file_get_contents('php://input'), true);
	}

	// Function to send JSON response with proper content type header
	function sendResultInfoAsJson( $obj )
	{
		// === SET RESPONSE FORMAT ===
		// Set response content type to JSON (tells browser/client this is JSON data)
		// Like setting Content-Type header in HTTP response
		// Similar to response.setContentType("application/json") in Java servlets
		header('Content-type: application/json');
		
		// === SEND THE DATA ===
		// Output the JSON string to the client
		// Like System.out.print() in Java or printf() in C, but goes to web client
		echo $obj;
	}
	
	// === ERROR RESPONSE FUNCTION ===
	// Function to format and send error response for search
	function returnWithError( $err )
	{
		// === CREATE ERROR JSON ===
		// Create JSON error response with empty results array and error message
		// This creates a standardized error format that client can expect
		// Like: {"results": [], "error": "No Records Found"}
		$retValue = '{"results":[],"error":"' . $err . '"}';
		
		// === SEND ERROR RESPONSE ===
		// Send the formatted JSON response to client
		sendResultInfoAsJson( $retValue );
	}
	
	// === SUCCESS RESPONSE FUNCTION ===  
	// Function to format and send successful response with contact results
	function returnWithInfo( $contacts )
	{
		// === CONVERT ARRAY TO JSON ===
		// Convert contacts array to JSON string
		// This is like ObjectMapper.writeValueAsString(contactsList) in Java
		// Converts PHP array of contacts into JSON format for sending to client
		// Example: [{"Name":"John","Phone":"123","Email":"j@j.com"}] 
		$resultsJson = json_encode($contacts);
		
		// === CREATE SUCCESS JSON ===
		// Create JSON success response with results array and empty error field
		// Format: {"results": [contact1, contact2, ...], "error": ""}
		$retValue = '{"results":' . $resultsJson . ',"error":""}';
		
		// === SEND SUCCESS RESPONSE ===
		// Send the formatted JSON response to client
		sendResultInfoAsJson( $retValue );
	}
	
?>