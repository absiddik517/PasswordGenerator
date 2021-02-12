<?php
    namespace App;
    include_once('Controller.php');
    include_once('Config.php');
    
    if($_SERVER['REQUEST_METHOD'] == "POST"){
        $hmm = new Controller($_POST); 
    }