
<?php
	$inData = getRequestInfo();
	
	$firstName = $inData["firstName"];
	$lastName = $inData["lastName"];
	$email = $inData["email"];
	$phone = $inData["phone"];
	$userId = $inData["userId"];

	$conn = new mysqli("root", "165.22.39.144", "Sup3rSh1nyMudk1p", "LampStackProject");
	if ($conn->connect_error) 
	{
		returnWithError($conn->connect_error);
	} 
	else
	{
		$stmt = $conn->prepare("INSERT into Contacts (FirstName, LastName, Email, Phone, UserID) VALUES (?, ?, ?, ?, ?)");
		$stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);
		$stmt->execute();

		$contactId = $stmt->insert_id;

		$stmt->close();
		$conn->close();

		returnWithSuccess($contactId);
	}

	function getRequestInfo()
	{
		return json_decode(file_get_contents('php://input'), true);
	}

	function sendResultInfoAsJson($obj)
	{
		header('Content-type: application/json');
		echo $obj;
	}
	
	function returnWithError($err)
	{
		$retValue = '{"success":false,"error":"' . $err . '"}';
		sendResultInfoAsJson($retValue);
	}

	function returnWithSuccess($contactId)
	{
		$retValue = '{"success":true,"error":"","contactId":' . $contactId . '}';
		sendResultInfoAsJson($retValue);
	}
?>
