<?php

/**
* Force user to input sudo password
*/
function enableSuperCow() {

	$cmd_output = shell_exec("sudo ls 2>&1");

}

/**
* Execute command
*
* @param String $command Command to execure
*/
function command($command) {

	output($command);
//	output(escapeshellcmd($command));

	$cmd_output = shell_exec($command." 2>&1");

//	$cmd_output = shell_exec(escapeshellcmd($command)." 2>&1");
	output($cmd_output);

}

/**
* Check is program is installed
*
* @param String $command Command to attempt
* @param Array $valid_responses Valid responses from system
* @return boolean
*/
function isInstalled($command, $valid_responses, $escape = true) {

//	print escapeshellcmd($command);
	if($escape) {
		$cmd_output = shell_exec(escapeshellcmd($command)." 2>&1");
	}
	else {
		$cmd_output = shell_exec($command." 2>&1");
	}
	print_r($cmd_output);

	foreach($valid_responses as $valid_response) {
		if(preg_match("/".$valid_response."/", $cmd_output)) {
			return true;
		}
	}
	return false;
}

/**
* Asks user and returns user input
* Checks if input matches valid_answers
*
* @param String $question Question to ask user
* @param Array $valid_answers Valid answers
* @return String valid answer
*/
function ask($question, $valid_answers = false, $no_echo = false) {

	output("$question:");

	// disable echoing of what is typed
	if($no_echo) {
		system('stty -echo');
	}

	if(!isset($GLOBALS['StdinPointer'])) {
		$GLOBALS['StdinPointer'] = fopen("php://stdin","r");
	}
	$cmd_input = trim(fgets($GLOBALS['StdinPointer'], "255"));

	// re-enable echoing after typing
	if($no_echo) {
		system('stty echo');
	}

	if($valid_answers) {
		$answer_is_valid = false;
		foreach($valid_answers as $valid_answer) {
			if(preg_match("/^".$valid_answer."$/", $cmd_input)) {
				$answer_is_valid = true;
			}
		}

		if(!$answer_is_valid){
			output("Invalid response! Try again!");
			$cmd_input = ask($question, $valid_answers);
		}
	}
	return $cmd_input;
}

/**
* Outputs to new line
*
* @param String $message Optional message to output to screen
*/
function output($message = ""){
	print $message."\n";
	flush();
}

/**
* Outputs goodbye and exits
*
* @param String $message Optional message to output to screen
*/
function goodbye($message = ""){
	output("\n\n$message\n\nSee you soon\n");
	exit();
}

/**
* Check if path exists
* If path does not exist - ask user for permission to create
*
* @param String $path Path to check existance of
* @param String $path Path to check existance of
*/
function checkPath($path, $sudo = "") {

//	$path = preg_replace("/\~/", $_SERVER['HOME'], $path);

	$path = getAbsolutePath($path);

	if(file_exists($path)) {
		output("$path - path exists");
	}
	else {
		$folders = explode("/", $path);
		$current_folder = "";
		foreach($folders as $folder) {

			if($folder) {
				$current_folder = $current_folder."/".$folder;
				if(file_exists($current_folder)) {
//					output("$current_folder - path exists");
				}
				else {
					$answer = ask("\nCreate missing path: $current_folder (yes/no)", array("(yes|no)"));
					if($answer == "yes") {
						command(($sudo ? "$sudo " : "") . "mkdir $current_folder");
						
						if(file_exists($current_folder)) {
							output("$current_folder - path created");
						}
						else {
							goodbye("Failed to create folder - please check it manually and try again");
						}
					}
					else {
						goodbye("Dev environment needs folder to be completed");
					}
				}
			}
			
		}
	}
	
}

/**
* Check if file exists
*
* @param String $path File to check existance of
*/
function checkFile($path, $message) {

//	$path = preg_replace("/\~/", $_SERVER['HOME'], $path);

	$path = getAbsolutePath($path);

	if(file_exists($path)) {
//		output("$path - file exists");
	}
	else {
		goodbye("$path is missing - ".$message);
	}
	
}

/**
* Check file exists, or create it from _conf version
*
* TODO: has not been tested
*/
function checkFileOrCreate($destination, $source) {

	// $source = preg_replace("/\~/", $_SERVER['HOME'], $source);
	// $destination = preg_replace("/\~/", $_SERVER['HOME'], $destination);

	$source = getAbsolutePath($source);
	$destination = getAbsolutePath($destination);

	if(!file_exists($destination)) {

		command("cp '$source' '$destination'");

	}

}

/**
* Check if file exists
*
* @param String $source Source to copy
* @param String $destination Destination to copy to
*/
function copyFile($source, $destination, $sudo = "") {

	// $source = preg_replace("/\~/", $_SERVER['HOME'], $source);
	// $destination = preg_replace("/\~/", $_SERVER['HOME'], $destination);

	$source = getAbsolutePath($source);
	$destination = getAbsolutePath($destination);

	command(($sudo ? "$sudo " : "") . "cp '$source' '$destination'");
}

/**
* Check if default file content exists - to update .bash_profile
* Connot update hosts due to permissions - don't have an easy solution yet
*
* @param String $path File to check content of
* @param String $default Default content file
*/
function checkFileContent($file, $default) {

	output("\nUpdating ".basename($file));

	// $file = preg_replace("/\~/", $_SERVER['HOME'], $file);
	// $default = preg_replace("/\~/", $_SERVER['HOME'], $default);

	$file = getAbsolutePath($file);
	$default = getAbsolutePath($default);

	$source_lines = file($file);
	$default_lines = file($default);
	$updated_lines = array();

	foreach($default_lines as $d_line) {
		$d_line = trim($d_line);
		if($d_line && !preg_match("/^\#/", $d_line)) {

			preg_match("/^\"([^\"]+)\" ([^$]+)/", $d_line, $d_parts);
			if(count($d_parts) == 3) {
				$d_line_match = $d_parts[1];
				$d_line_value = $d_parts[2];

				foreach($source_lines as $line_no => $s_line) {

					// REMOVE AUTO APPENDING LINE
					if(preg_match("/ADDED BY parentNode DEV TOOL/", $s_line)) {
						$source_lines[$line_no] = "";
					}

					// remove existing line if match exists
					if(preg_match("/".$d_line_match."/", $s_line)) {
						$source_lines[$line_no] = "";
					}
				}

				// add correct line to line updates
				$updated_lines[] = preg_replace("#TOOLPATH#", getAbsolutePath(__FILE__), $d_line_value)."\n";

			}
		}
	}

	$fp = fopen($file, "w+");
	foreach($source_lines as $line) {
		fwrite($fp, $line);
	}

	fwrite($fp, "\n# ADDED BY parentNode DEV TOOL\n");

	foreach($updated_lines as $line) {
		fwrite($fp, $line);
	}
	fclose($fp);

}


function getAbsolutePath($path) {

	// ~/Dropbox/...
	// ~/.bash_profile
	// /usr/bin/...
	// /opt/local/bin/...
	// _conf/...

	// (user dir paths, starting with "~/") - should be translated using $_SERVER['HOME']
	// (absolute paths starting with "/") - should not be translated
	// (relative paths like _conf) - should be translated using __FILE__

	// current user path
	if(preg_match("/^\~\//", $path)) {
		return preg_replace("/\~/", $_SERVER['HOME'], $path);
		
	}
	// absolute path
	else if(preg_match("/^\//", $path)) {
		return $path;
	}
	// relative path
	else {
		return preg_replace("/_tools\/".basename(__FILE__)."/", "", __FILE__).$path;
	}
	
}

?>