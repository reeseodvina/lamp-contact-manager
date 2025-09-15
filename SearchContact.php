<?php

	//Third
	$inData = getRequestInfo();
	
	// Debug logging (remove in production)
	error_log("Received data: " . json_encode($inData));
	
	$searchResults = "";
	$searchCount = 0;
	
	//I have no idea what our SQL sign in is so yeah... - Justin 9/8/25
	$conn = new mysqli("localhost", "lampapi", "Sup3rSh1nyMudk1p", "LampStackProject");
	if( $conn->connect_error )
	{
		returnWithError( $conn->connect_error );
	}
	else
	{
		// ADDED: Check if required fields exist and are not null
		if( !isset($inData["search"]) || !isset($inData["userId"]) )
		{
			returnWithError("Missing required fields: search and userId");
		}
		// ADDED: Validate userId is numeric
		else if( !is_numeric($inData["userId"]) )
		{
			returnWithError("Invalid userId - must be a number");
		}
		else
		{
			//clean up search input
			$searchTerm = trim($inData["search"]);
			$userId = (int)$inData["userId"];
			
			if( empty($searchTerm) )
			{
				returnWithError("Search term cannot be empty", $userId);
			}
			else
			{
				// First try exact matches and partial matches
				// Note: Your DB has FirstName + LastName, not a single Name column
				$stmt = $conn->prepare("SELECT FirstName, LastName, Phone, Email FROM Contacts WHERE UserID=? AND (FirstName LIKE ? OR LastName LIKE ? OR Phone LIKE ? OR Email LIKE ?)");
				if( !$stmt )
				{
					returnWithError("Database prepare error: " . $conn->error);
				}
				else
				{
					$searchPattern = "%" . $searchTerm . "%";
					$stmt->bind_param("issss", $userId, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
					$stmt->execute();
					$result = $stmt->get_result();
					
					$contacts = array();
					while( $row = $result->fetch_assoc() )
					{
						// Combine FirstName and LastName into a single Name field for response
						$row['Name'] = $row['FirstName'] . ' ' . $row['LastName'];
						$row['userId'] = $userId; // Add userId to each contact
						unset($row['FirstName']); // Remove individual fields
						unset($row['LastName']);
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
						returnWithInfo($contacts, $userId);
					}
					else
					{
						returnWithError("This dude not here gang", $userId);
					}
				}
			}
		}
		
		$conn->close();
	}
	
	//Function to work for typos
	function performFuzzySearch($conn, $userId, $searchTerm)
	{
		$contacts = array();
		
		// Get all contacts for the user
		$stmt = $conn->prepare("SELECT FirstName, LastName, Phone, Email FROM Contacts WHERE UserID=?");
		if( !$stmt )
		{
			return $contacts; // Return empty array on error
		}
		
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$result = $stmt->get_result();
		
		while( $row = $result->fetch_assoc() )
		{
			// Combine first and last name for searching
			$fullName = $row['FirstName'] . ' ' . $row['LastName'];
			
			// Check similarity with full name, first name, last name, phone, and email
			$fullNameDistance = levenshteinDistance(strtolower($searchTerm), strtolower($fullName));
			$firstNameDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['FirstName']));
			$lastNameDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['LastName']));
			$phoneDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['Phone']));
			$emailDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['Email']));
			
			$minDistance = min($fullNameDistance, $firstNameDistance, $lastNameDistance, $phoneDistance, $emailDistance);
			
			// Allow up to 2 character differences for typos
			//TO-DO: send confirmation to User - Justin 9/9/25
			$maxAllowedDistance = min(2, floor(strlen($searchTerm) * 0.3));
			
			if( $minDistance <= $maxAllowedDistance )
			{
				// Format the response to match expected structure
				$contact = array(
					'Name' => $fullName,
					'Phone' => $row['Phone'],
					'Email' => $row['Email'],
					'userId' => $userId // Add userId to each contact
				);
				$contacts[] = $contact;
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
	
	// Function to decode JSON input from request body
	function getRequestInfo()
	{
		$input = file_get_contents('php://input');
		error_log("Raw input: " . $input); // Debug line
		$decoded = json_decode($input, true);
		
		// Check for JSON decode errors
		if (json_last_error() !== JSON_ERROR_NONE) {
			error_log("JSON decode error: " . json_last_error_msg());
			return array(); // Return empty array instead of null
		}
		
		return $decoded ? $decoded : array();
	}

	// Function to send JSON response with proper content type header
	function sendResultInfoAsJson( $obj )
	{
		header('Content-type: application/json');
		echo $obj;
	}
	
	// Function to format and send error response for search
	function returnWithError( $err, $userId = null )
	{
		$retValue = '{"results":[],"error":"' . $err . '"';
		if( $userId !== null ) {
			$retValue .= ',"userId":' . $userId;
		}
		$retValue .= '}';
		sendResultInfoAsJson( $retValue );
	}
	
	// Function to format and send successful response with contact results
	function returnWithInfo( $contacts, $userId = null )
	{
		$resultsJson = json_encode($contacts);
		if( $resultsJson === false )
		{
			returnWithError("Error encoding results to JSON", $userId);
			return;
		}
		
		$retValue = '{"results":' . $resultsJson . ',"error":""';
		if( $userId !== null ) {
			$retValue .= ',"userId":' . $userId;
		}
		$retValue .= '}';
		sendResultInfoAsJson( $retValue );
	}
	
?>
