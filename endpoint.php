 <?php
    // Validate basic POST information
    $error = (!isset($_POST['playerId']) ||
              !isset($_POST['coinsWon']) ||
              !isset($_POST['coinsBet']) ||
              !isset($_POST['hash']));
    TestError($error);
    
    // Retrieve POST data
    $playerId = $_POST['playerId'];
    $coinsWon = $_POST['coinsWon'];
    $coinsBet = $_POST['coinsBet'];
    $hash = $_POST["hash"];
    
    // Validate post data
    $error = (!is_numeric($playerId) ||
              !is_numeric($coinsWon) ||
              !is_numeric($coinsBet));
    TestError($error);
    
    // Attempt to update database
    $mysqli = new mysqli("localhost", "root", "", "joe");
    if($mysqli->connect_errno){
        TestError(true);
    }
    
    $sql = "UPDATE player " . 
           "SET credits = credits + " . ($coinsWon - $coinsBet) . ", " .
           "    lifetimespins = lifetimespins + 1, " .
           "    lifetimewinnings = lifetimewinnings + " . $coinsWon . " " .
           "WHERE playerid = " . $playerId . " " .
           "AND   saltvalue = '" . $hash . "'"; // <-- this field should be properly sanitized
    $error = !$mysqli->query($sql);
    TestError($error);
    
    $error = !$mysqli->affected_rows;
    TestError($error);
    
    // Get updated player information
    $sql = "SELECT lifetimewinnings / lifetimespins, name, credits, lifetimespins " .
           "FROM player " .
           "WHERE playerid = " . $playerId;
    $result = $mysqli->query($sql);
    $result->data_seek(0);
    $row = $result->fetch_row();
    $lifetimeReturn = $row[0];
    $name = $row[1];
    $credits = $row[2];
    $lifetimeSpins = $row[3];
    
    // Generate JSON response
    $playerData = array("Player ID" => $playerId, 
                        "Name" => $name,
                        "Credits" => $credits,
                        "Lifetime Spins" => $lifetimeSpins,
                        "Lifetime Average Return" => $lifetimeReturn);
    $json = json_encode($playerData);
    
    // Return JSON response and end
    echo($json);
    
    function TestError($error){
        if($error){
            die('{"Error"}');
        }
    }
?>