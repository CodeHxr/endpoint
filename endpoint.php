 <?php
    /* 
        DOCUMENTATION:
            Error Codes:
                1 - Invalid post data
                2 - Non-numeric post data
                3 - Can not connect to MySQL
                4 - Hash mismatch
                5 - UPDATE query failed
                6 - UPDATE query affected 0 records
                7 - Player not found
                
            Last Edit:
                Joe Hicks, 8/11/2014
    */
    
    // Validate basic POST information
    $error = (!isset($_POST['playerId']) ||
              !isset($_POST['coinsWon']) ||
              !isset($_POST['coinsBet']) ||
              !isset($_POST['hash']));
    TestError($error, "1");
    
    // Retrieve POST data
    $playerId = $_POST['playerId'];
    $coinsWon = $_POST['coinsWon'];
    $coinsBet = $_POST['coinsBet'];
    $hash = $_POST["hash"];
    
    // Validate post data
    $error = (!is_numeric($playerId) ||
              !is_numeric($coinsWon) ||
              !is_numeric($coinsBet));
    TestError($error, "2");
    
    // Connect to database
    $mysqli = new mysqli("localhost", "root", "", "joe");
    $error = ($mysqli->connect_errno);
    TestError($error, "3");
    
    // Get player data - we have to pull the salt anyway, so save a 
    // second round-trip to the database by getting everything we need
    $sql = "SELECT name, credits, lifetimespins, lifetimewinnings, saltvalue " .
           "FROM player " .
           "WHERE playerid = " . $playerId;
    $result = $mysqli->query($sql);
    $result->data_seek(0);
    $row = $result->fetch_row(); 
    $error = ($row == NULL);
    TestError($error, "7");
    
    // Assign data to local variables for clarity
    $name = $row[0];
    $credits = $row[1];
    $lifetimeSpins = $row[2];
    $lifetimeWinnings = $row[3];
    $saltvalue = $row[4];
    
    // Check MD5 hash
    $prehash = $saltvalue . $playerId . $coinsWon . $coinsBet;
    $serverhash = md5($prehash);
    $error = ($serverhash != $hash);
    TestError($error, "4");
    
    // Hash is good - update data
    $lifetimeSpins++;
    $credits += ($coinsWon - $coinsBet);
    $lifetimeWinnings += $coinsWon;
    $lifetimeReturn = $lifetimeWinnings / $lifetimeSpins;
    
    $sql = "UPDATE player " . 
           "SET credits = " . $credits . ", " .
           "    lifetimespins = " . $lifetimeSpins . ", " .
           "    lifetimewinnings = " . $lifetimeWinnings . " " .
           "WHERE playerid = " . $playerId;
    $error = !$mysqli->query($sql);
    TestError($error, "5");
    
    $error = !$mysqli->affected_rows;
    TestError($error, "6");
    
    
    // Generate JSON response
    $playerData = array("Player ID" => $playerId, 
                        "Name" => $name,
                        "Credits" => $credits,
                        "Lifetime Spins" => $lifetimeSpins,
                        "Lifetime Average Return" => $lifetimeReturn);
    $json = json_encode($playerData);
    
    // Return JSON response and end
    echo($json);
    
    function TestError($error, $code){
        if($error){
            die('{"Error":"' . $code . '"}');
        }
    }
?>