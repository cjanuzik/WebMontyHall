<?php

require 'db.php';
require 'nicejson.php';


// Get Command
// Figure out what command is trying to be called by pulling in arguments
// from the URL query string.

$command;

if(isset($_GET["cmd"])) {
    $command = $_GET["cmd"];
}

// Do command based error handling.  Did they call a valid command?
if(empty($command)){
    doError("no command found");
} else{
// Process command: What command did they call? It might be clearer to 
// call a dedicated function to handle each different command here.
    switch($command) {
        case "newGame":
            doNewGame();
            break;
        case "firstChoice":
            doFirstChoice();
            break;
        case "finalChoice":
            doFinalChoice();
            break;
        case "stats":
            doStats();
            break;
        default:
            doError("no command found");
            break;
    }
}


/**********************************************************
 **********************************************************
 * End of program flow. Functions only beyond this point.
 **********************************************************
 **********************************************************/

/**
 * doNewGame
 *
 * Create a new empty session, and set up a new game. Pick the 
 * prize door randomly, and save a new game in the database 
 * with the prize door. Return a response that contains the 
 * new Game ID from the newly created database row.
 */
function doNewGame(){
    $prize = rand(0, 2);
    
    $sql = "INSERT INTO games (prize_door) VALUE ('$prize')";
    $db = getDB();
    $db->query($sql);
    
    //Selects current database (hw7)
    $lastID = dbLastInsertID();
    $connection = mysql_connect("localhost","root"); 
    mysql_select_db("hw7", $connection); 
    
    //Gets current row being edited...
    $result = mysql_query("SELECT * FROM games WHERE game_ID=('$lastID')");
    $row = mysql_fetch_row($result);
    
    $data[] = array(
        'game_id' => $row[0],
        'opened_door' => $row[2],
        'initial_selected' => $row[3],
        'final_selected' => $row[4]
    );
    
    $response[] = array(
    	'message' => "new game created",
    	'data' => $data
    );
    
    returnResponse($response, 200);
    
}

/**
 * openDoor
 *
 * server has marked the initial selected door by the user in the
 * database. Now we open a door that the user did not select and 
 * that does not have the prize in it.
 */
function openDoor($choice, $prize){
    //If user chose the prize door, choose a random unchosen door
    if($choice == $prize){
        switch($choice){
            //Returns 1 or 2
            case 0:
                return rand(1,2);
                break;
            //Returns 0 or 2
            case 1:
                return rand(0,1)*2;
                break;
            //Returns 0 or 1
            case 2:
                return rand(0,1);
                break;
        }
    } else {
        //Returns the door that does not have the prize or was chosen
        //NOTE mod 3 is there for error checking to guarantee a
        //return value of 0-2.
        return (3 - $choice - $prize)%3;
    }
}

/**
 * checkParameters
 *
 * Checks for all PHP parameter errors and returns a boolean value depending on
 * if an error was found or not. Called from doFirstChoice and
 * doFinalChoice.
 *
 * Possible Errors:
 *  1. The required parameters do not exist.
 *  2. The parameters are not integers.
 */
function checkParameters(){
    
    $gameID = null;
    $choice = null;
    
    if(isset($_GET["game_id"])) {
        $gameID = $_GET["game_id"];
    }
    
    if(isset($_GET["choice"])) {
        $choice = $_GET["choice"];
    }
    
    //If gameID is missing
    if(is_null($gameID)){
        doError("game_id parameter is required");
        return true;
    }
    
    //If choice is missing
    if(is_null($choice)){
        doError("choice parameter is required");
        return true;
    }
    
    //If gameID is not an integer
    if(!is_numeric($gameID)){
        doError("game_id must be an integer");
        return true;
    }
    
    //If choice is not an integer
    if(!is_numeric($choice)){
        doError("choice must be an integer");
        return true;
    }
    
    return false;
}

/**
 * doFirstChoice
 *
 * Player has made their first choice of a door. We need to pick
 * one of the remaining doors to 'open' and show no prize. We know
 * which door has the prize, (chozen in doNewGame). If the player 
 * picked the right door, just randomly pick one of the remaining 
 * doors to 'open'.  If the player didn't pick the correct door,
 * be sure to 'open' the only remaining door that doesn't have 
 * the prize.
 *
 * Error checking
 * We need to make sure of the following things in this function:
 *  1. The game_id exists in the db
 *      - If it doesn't return an error "no matching game found" with
 *        a return value of 404 (not found)
 *  2. The game hasn't already had a final choice made
 *      - If it does, return a 409 status code and "game complete"
 *  3. The game hasn't already had a first choice made
 *      - If it does, just return the already chosen door to 'open'
 *
 * We also need to do input validation for the game_id and choice 
 * parameters. We need to make sure they're both integers. Note that
 * They will initially be strings from the $_GET superglobal. We need
 * to convert them to ints first, and make sure that they did convert
 * correctly.
 */
function doFirstChoice(){
    //Selects current database (hw7)
    $connection = mysql_connect("localhost","root"); 
    mysql_select_db("hw7", $connection);
    
    if(checkParameters()){
        return;
    }
    
    $gameID = $_GET["game_id"];
    $choice = $_GET["choice"];
    
    //Gets current row being edited...
    $result = mysql_query("SELECT * FROM games WHERE game_ID=('$gameID')");
    $row = mysql_fetch_row($result);
    
    $prize = $row[1];
    
    //If final selection has already been made
    if(!is_null($row[4])){
        doError("game complete", 409);
        return;
    }
    else if(empty($row)){
        doError("no matching game found", 404);
        return true;
    }
    //Update initial choice if it is null.
    if(is_null($row[3])){
        $result = mysql_query("UPDATE games SET initial_selected=('$choice') WHERE game_ID=('$gameID')");
        $openedDoor = openDoor($choice, $prize);
        $result = mysql_query("UPDATE games SET opened_door=('$openedDoor') WHERE game_ID=('$gameID')");
    }
    
    //Updates instance of current row to new info
    $result = mysql_query("SELECT * FROM games WHERE game_ID=('$gameID')");
    $row = mysql_fetch_row($result);
    
    //Builds data array
    $data[] = array(
        'game_id' => $row[0],
        'opened_door' => $row[2],
        'initial_selected' => $row[3],
        'final_selected' => $row[4]
    );
    
    //Builds response array
    $response[] = array(
    	'message' => "opened door",
    	'data' => $data
    );
    
    //Returns response
    returnResponse($response, 200);
}


/**
 * doFinalChoice
 *
 * Player selects their final choice. They could either stay with their
 * first choice, or switch to the other closed door. We don't really 
 * need to do anything other than record their final choice, and return
 * a response with a message of either "you win" or "you loose".
 *
 * Error checking
 * We'll need to check the same conditions as doFirstChoice. It might
 * be tricky, but its possible to abstract the validation into a separate
 * function rather than duplicating all the checks.
 *
 * Checking for if the game is over or not should be done separately,
 * so that the final game state is returned instead of an error.
 *
 * One new check that needs to be made is that the player cannot choose
 * the door we opened in the previous step. If the player chooses that
 * door don't store the value, and return an error "cannot choose opened door"
 * with a status code of 409.
 */
function doFinalChoice(){
    //Selects current database (hw7)
    $connection = mysql_connect("localhost","root"); 
    mysql_select_db("hw7", $connection);
    
    if(checkParameters()){
        return;
    }
    
    $gameID = $_GET["game_id"];
    $choice = $_GET["choice"];
    
    //Gets current row being edited...
    $result = mysql_query("SELECT * FROM games WHERE game_ID=('$gameID')");
    $row = mysql_fetch_row($result);
    
    //If chosen door is the opened door throw error
    if($choice == $row[2]){
        doError("cannot choose opened door", 409);
        return true;
    }
    
    else if(empty($row)){
        doError("no matching game found", 404);
        return true;
    }
    //Update initial choice if it is null.
    else if(is_null($row[4])){
        $result = mysql_query("UPDATE games SET final_selected=('$choice') WHERE game_ID=('$gameID')");
    }
    
    //Updates instance of current row to new info
    $result = mysql_query("SELECT * FROM games WHERE game_ID=('$gameID')");
    $row = mysql_fetch_row($result);
    
    //Builds data array
    $data[] = array(
        'game_id' => $row[0],
        'prize_door' => $row[1],
        'opened_door' => $row[2],
        'initial_selected' => $row[3],
        'final_selected' => $row[4]
    );
    $winLoss = "you lost";
    
    if($row[1] == $row[4]){
        $winLoss = "you won";
    }
    
    //Builds response array
    $response[] = array(
    	'message' => $winLoss,
    	'data' => $data
    );
    
    //Returns response
    returnResponse($response, 200);
}

/**
 * doStats
 *
 * Get stats on wins when the player switches and doesn't switch their choice
 */
function doStats(){
     //Selects current database (hw7)
    $connection = mysql_connect("localhost","root"); 
    mysql_select_db("hw7", $connection);
    
    //Gets current row being edited...
    $switchWins = mysql_query("SELECT COUNT(*) FROM games WHERE prize_door = final_selected AND initial_selected != final_selected;");
    $switchLoss = mysql_query("SELECT COUNT(*) FROM games WHERE prize_door != final_selected AND initial_selected != final_selected;");
    $noSwitchWins = mysql_query("SELECT COUNT(*) FROM games WHERE prize_door = final_selected AND initial_selected = final_selected;");
    $noSwitchLoss = mysql_query("SELECT COUNT(*) FROM games WHERE prize_door != final_selected AND initial_selected = final_selected;");
    
    $data[] = array(
        'switchWins' => mysql_fetch_row($switchWins)[0],
        'switchLoss' => mysql_fetch_row($switchLoss)[0],
        'noSwitchWins' => mysql_fetch_row($noSwitchWins)[0],
        'noSwitchLoss' => mysql_fetch_row($noSwitchLoss)[0]
    );
    
    $response[] = array(
    	'message' => "stats",
    	'data' => $data
    );
    
    //Returns response
    returnResponse($response, 200);
}


/**
 * doError
 *
 * Construct an error response, and send it to the user.
 */
function doError($data, $code = 400) {
  if(is_array($data)) {
    $errorText = json_encode($data);
  } else {
    $errorText = $data;
  }
  $error = array("error" => $errorText);
  returnResponse($error, $code);
}


/**
 * returnResponse
 *
 * Return a JSON encoded response to the user.
 */
function returnResponse($data, $code) {
  // Determine the error code, and send that to the client
  http_response_code($code);
  // Be sure to set the MIME type of the response to JSON
  header('Content-Type: text/json');
  // Convert the response data to JSON, and optionally style it by calling
  // the json_format() function (included in nicejson.php).
  $response = json_format($data);
  // The following bit if code is required to address the issue of security
  // that comes up when web pages from one site attempt to access resources
  // on another site. This bit of code should work as-is, and will only be 
  // needed if your web page and server are on different hosts.
  // See http://www.html5rocks.com/en/tutorials/cors/ for a more detailed
  // overview.
  if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    $urlParts = parse_url($referer);
    $accessHost = $urlParts['scheme'] . "://" . $urlParts['host'];

    header("Access-Control-Allow-Origin: {$accessHost}");
    header("Access-Control-Allow-Credentials: true");
  }
  
  // Cleans up JSON response then sends to the client.
  $response = str_replace(["[", "]"], "", $response);
  echo $response;
}


