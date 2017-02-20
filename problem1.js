// Suggested Global Variables
var pick1; //initial door selected.
var opened; //door opened.
var pick2; //final door selected.
var prize; //prize door
var messageBox;
var gameID;

// Event Listener for DOM Loading
window.onload = gameSetup();


// Choose Door Callback Function
// This function should be called whenever a door is clicked, 
// and should trigger appropriate AJAX calls.
var chooseDoor = function(choice) {
    //Makes AJAX call for first choice of door
    if(opened == null){ //checks if a door hasn't already been opened
        ajax("cmd=firstChoice&game_id="+gameID+"&choice="+choice, function(response) {
            console.log(response);
            opened = response.data.opened_door;
            pick1 = response.data.initial_selected;
            messageBox = "Door "+ (parseInt(opened) + 1) +" doesn't have the prize.<br>Choose your first door again, or switch?";
            document.getElementsByClassName("message")[0].innerHTML = messageBox;
             openDoor();
        });
    }
    //Makes AJAX call for final choice of door
    else if(prize == null) {//checks if price hasn't already been stated
        ajax("cmd=finalChoice&game_id="+gameID+"&choice="+choice, function(response) {
             console.log(response);
             pick2 = response.data.final_selected;
             prize = response.data.prize_door;
             if(pick2 == prize){
                 messageBox = "You won! Door "+ (parseInt(prize) + 1) +" had the prize!";
             } else {
                 messageBox = "You lost. Door "+ (parseInt(prize) + 1) +" had the prize.";
             }
             document.getElementsByClassName("message")[0].innerHTML = messageBox;
             updateTable();
             openDoor();
        });
    }
    //Resets the game
    else {
        resetGame();
    }
}

// You will need to define various other functions and callbacks.

//Function to update table stats.

function gameSetup(){
    ajax("cmd=newGame", function(response) {
         console.log(response);
         gameID = response.data.game_id;
         console.log(gameID);
         updateTable();
    });
}

function updateTable(){
    ajax("cmd=stats", function(response) {
         console.log(response);
         document.getElementById("winS").innerHTML = response.data.switchWins;
         document.getElementById("loseS").innerHTML = response.data.switchLoss;
         document.getElementById("winNS").innerHTML = response.data.noSwitchWins;
         document.getElementById("loseNS").innerHTML = response.data.noSwitchLoss;
         });
}

function openDoor(){
    var doors = document.getElementsByClassName("door");
    doors[parseInt(opened)].disabled = true;
    doors[parseInt(opened)].src = "openDoorEmpty.jpg";
    
    if(prize != null){
        for(var i = 0; i < 3; i++){
            if(parseInt(prize) == i){
               doors[i].src = "openDoorFilled.jpg";
            } else {
                doors[i].src = "openDoorEmpty.jpg";
            }
            doors[i].disabled = false;
        }
    }
}

function resetGame() {
    var doors = document.getElementsByClassName("door");
    for(var i = 0; i < 3; i++){
        doors[i].disabled = false;
        doors[i].src = "closedDoor.jpg";
    }
    pick1 = null;
    pick2 = null;
    opened = null;
    prize = null;
    gameID = null;
    messageBox = "New Game! Choose a door!";
    document.getElementsByClassName("message")[0].innerHTML = messageBox;
    gameSetup();
}

// ajax helper function
// This function can be used as-is to initiate AJAX calls to the API back-end.
// usage:
//    ajax("cmd=yourcommand&option=value", callbackFunctionName);
//
// callbackFunctionName should then be defined like:
//    var callbackFunctionName = function(response) {
//       console.log(response);
//    }
//
//    The response variable will receive the response from the Server for the
//    given API call.
function ajax(url, callback) {
  url = "http://52.35.157.11/hw6/server.php?" + url;
  url = url + "&seed=" + (new Date()).getTime();
  
  // Create a new XMLHttpRequest Object
  var req = new XMLHttpRequest();
  
  // Pass Cookie Credentials along with request
  req.withCredentials = true;
  
  // Create a callback function when the State of the Connection changes
  req.onreadystatechange = function() {
    if (req.readyState == 4)       // state of 4 is 'done'. The request has completed
    {
      callback( JSON.parse(req.responseText) );  // The .responseText property of the request object
    } else {                                     // contains the Text returned from the request.
      // console.log(req.readyState);
    }
  };
  
  // Set up our HTTP Request
  req.open('GET', url, true);
  
  // Finally initiate the request
  req.send();

}

