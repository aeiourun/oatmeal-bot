<?php

/*
    Functions
*/

function generate_markov_table($text, $order) {
    
    // walk through the text and make the index table for words
    $wordsTable = explode(' ',trim($text)); 
	$table = array();
	$tableKeys = array();
	$i = 0;
	
	foreach($wordsTable as $key=>$word){
		$nextWord = "";
		for($j = 0; $j < $order; $j++){
			if($key + $j + 1 != sizeof($wordsTable) - 1)
				$nextWord .= " " . $wordsTable[$key + $j + 1];
		}
		if (!isset($table[$word . $nextWord])){
			$table[$word . $nextWord] = array();
		};
	}
	
    $tableLength = sizeof($wordsTable);
	
    // walk the array again and count the numbers
	for($i = 0; $i < $tableLength - 1; $i++){
		$word_index = $wordsTable[$i];		
		$word_count = $wordsTable[$i+1];
		if (isset($table[$word_index][$word_count])) {
			$table[$word_index][$word_count] += 1;
		} else {
			$table[$word_index][$word_count] = 1;	  
		}
	}
	
    return $table;
}

function sentenceBegin($str){
	return $str == ucfirst($str);
}

function generate_markov_text($length, $table) {
    // get first word
	do{
		$word = array_rand($table);
	}while(!sentenceBegin($word));
		
    $o = $word;

    while(strlen($o) < $length){
        $newword = return_weighted_word($table[$word]);            
        
        if ($newword) {
            $word = $newword;
            $o .= " " . $newword;
        } else {       
            do{
				$word = array_rand($table);
			}while(!sentenceBegin($word));
        }
    }
    
	
    return $o;
}
    
function return_weighted_word($array) {
    if (!$array) return false;
    
    $total = array_sum($array);
    $rand  = mt_rand(1, $total);
    foreach ($array as $item => $weight) {
        if ($rand <= $weight) return $item;
        $rand -= $weight;
    }
}
/*
    Code Body Start
    
    Generate the markov chain, other needed information, insert into database
*/

    // Connect to the myBB forum database
    $host="localhost"; // Host name, usually localhost
    $username="forum_database_username"; // Mysql username
    $password="PASSWORD"; // Mysql password
    $db_name="forum_database_name"; // Database name
    
    // Open our file with the input text
    $text = file_get_contents("text/data.txt");
    
    // Select a markov order of 2, 3, or 4
    $rand_order = rand(2,4);
    $order = $rand_order;
        
    // Choose the length of the thread from 10-500 words
    $rand_thread = rand(10,500);
    
    //Generate the thread body
    $markov_table = generate_markov_table($text, $order);
    $markov = generate_markov_text($rand_thread, $markov_table, $order);
    if (get_magic_quotes_gpc()) $markov = stripslashes($markov);
        
    // Choose the length of the title from 1-9 words
    $rand_title = rand(1,9);
    
    // Generate the title
    $markov_title = generate_markov_text($rand_title, $markov_table, $order);
    
    // Other needed information 
    $rand_icon = rand(0,20); // Choose thread icon, or 0 for no icon
    $fid="2"; // The fid (ID) of the forum you want to insert into
    $user="oatmeal"; // The username making the post
    $userid="1";// The userid for the user making the post
    $ridtid = time();// The id for the post (Current unix timestamp)
    $time = time();// The post time
    
    // Attempt to connect to the mySQL database
    mysql_connect("$host", "$username", "$password")or die("cannot connect");
    mysql_select_db("$db_name")or die("cannot select DB");
    
    // Load the mySQL queries
    $q1 = "INSERT into mybb_posts (tid, fid, subject, icon, uid, username, message, visible) VALUES('$ridtid', $fid, '$markov_title', $rand_icon, $userid, '$user', '$markov', '1')";
    $q2 = "INSERT into mybb_threads (tid, fid, subject, icon, uid, username, dateline, lastpost, lastposter, visible) VALUES('$ridtid', $fid, '$markov_title', $rand_icon, $userid, '$user', '$time', '$time', '$user', '1')";
    $q3 = "UPDATE mybb_forums SET lastposttid='$ridtid' WHERE fid=$fid";
    $q4 = "UPDATE mybb_forums SET lastpost='$time' WHERE fid=$fid";
    $q5 = "UPDATE mybb_forums SET lastpostsubject='$markov_title' WHERE fid=$fid";
    $q6 = "UPDATE mybb_forums SET threads=threads+1 WHERE fid=$fid";
    $q7 = "UPDATE mybb_forums SET posts=posts+1 WHERE fid=$fid";
    $q8 = "UPDATE mybb_forums SET lastposter='$user' WHERE fid=$fid";
    $q9 = "UPDATE mybb_forums SET lastposteruid='$userid' WHERE fid=$fid";
    
    // Execute the mySQL queries
    mysql_query($q1);
    mysql_query($q2);
    mysql_query($q3);
    mysql_query($q4);
    mysql_query($q5);
    mysql_query($q6);
    mysql_query($q7);
    mysql_query($q8);
    mysql_query($q9);
?>