<?php

// check system requirements
$requirements = [

	'PHP' => '8.0',
	'functions' => [
		'curl_init',
		'openssl_encrypt'
	]
];


// message to screen
function screen_display($message, string $type = '')
{
	// reset color
	$reset = "\033[0m";

	// get type
	$color = $type == 'error' ? "\033[31m" : ($type == 'success' ? "\033[32m" : '');

	// print message
	fwrite(STDOUT,  $color . $message . $reset . "\n");
}

// convert to readable size
function convertToReadableSize($size, &$sbase=null)
{
	$size_ = is_finite($size) ? (int)$size : 0;
	if ($size_ > 0) {
		$base = log($size_) / log(1024);
		$suffix = array("Byte", "KB", "MB", "GB", "TB");
		$f_base = floor($base);
		$convert = round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];

		$sbase = strtolower($suffix[$f_base]);

		if ($convert > 0) return $convert;
	}

    return 0 . 'KB';
}

// read line
function readInput()
{
    if (PHP_OS == "WINNT") return trim(stream_get_line(STDIN, 1024));
    
    // not windows
    return trim(readline());
}


// do check now
foreach ($requirements as $target => $val) :

	// check for PHP
	switch ($target):

		// check php version
		case 'PHP':
			// check current php version
			if (phpversion() < floatval($val)) return screen_display('Your PHP Version is less than the required "'.$val.'" version', 'error');
		break;

		// check for functions
		case 'functions':
			// check the functions
			foreach ($val as $func) :
				// does function exists
				if (!function_exists($func)) return screen_display('Missing Required function ' . $func . ', installation could not be complete.', 'error');
			endforeach;
		break;

	endswitch;

endforeach;

// get the arguments
$argv = $_SERVER['argv'];

// update flag used ?
$updateInstaller = false;

// get version from the user
$version = 'master';

// @var array $updates
$updates = [];

// check for update flag
foreach ($argv as $command) :

	// look for --update flag
	if (strtolower($command) == '--update') $updateInstaller = true;

	// look for version
	if (strpos($command, '--version=') !== false) :

		// get the version
		$command_r = explode('=', $command);

		// pass the version
		$version = end($command_r);

	endif;

	// look for repo
	if (strpos($command, '--repo=') !== false) :

		// get repos
		$command_r = explode('=', $command);

		// get the repo
		$repo = end($command_r);

		// convert to an array
		$updates = explode(',', $repo);

	endif;

endforeach;


if (!$updateInstaller && !defined("DIRECT_INSTALLATION")) :

	// ask user 
	fwrite(STDOUT, PHP_EOL . 'What version of moorexa should we install? (Hit Enter to install the latest) : ');

	// read input
	$input = readInput();

	// assign version
	$version = $input != '' ? $input : $version;

endif;

// get working directory
$workingDirectory = isset($_SERVER['PWD']) ? $_SERVER['PWD'] : '/';

// get the home directory
$homeDirectory = isset($_SERVER['HOME']) ? $_SERVER['HOME'] : '/';

// run except for direct installer
if (!defined('DIRECT_INSTALLATION')) :

	// create directory
	if (!is_dir($homeDirectory . '/moorexa')) mkdir($homeDirectory . '/moorexa');

	// create a new file here
	$moorexaFile = $homeDirectory . '/moorexa/moorexa';

	// put content inside a new file
	file_put_contents($moorexaFile, file_get_contents('https://raw.githubusercontent.com/moorexa/installer/'.$version.'/moorexa'));

	if (!$updateInstaller) :

	// create path
	screen_display('Checking if PATH has been registered.', 'success');

	// get the os
	$os = preg_replace('/[s]+/', '', php_uname('s'));

	// path file
	$pathFile = $homeDirectory . '/moorexa/'.$os.'_path.d';

	// path format for different os
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') :

		// window profile
		$profile = 'pathman /au c:\\' . $homeDirectory . '\\moorexa';

	else:

		// profile name
		$profileName = '.bashrc';

		// check if this file exists
		if (file_exists($homeDirectory . '/.bash_profile')) $profileName = '.bash_profile';

		// get the profile name
		$profile = 'echo "alias moorexa=\"php '.$homeDirectory.'/moorexa/moorexa"\" >> ~/' . $profileName . ';source ~/' . $profileName;

	endif;

	// check if path has been created
	if (file_exists($pathFile)) return screen_display('PATH Added previously. Installation would not continue.', 'error');

	endif;

endif;

// download from github function
function download_from_github(string $link, string $fileName, string $version = 'master')
{
	// @var bool $installed
	$installed = false;

	// @var int $rand
	$rand = mt_rand(1, 100);

	// get the home directory
	$homeDirectory = $GLOBALS['homeDirectory'];

	// get the storage directory
	$storageDirectory = defined('DIRECT_INSTALLATION') ? __DIR__ . '/tmp/' : $homeDirectory . '/moorexa/storage/';

	// get updateInstaller bool
	$updateInstaller = $GLOBALS['updateInstaller'];

	// get a fake user agent
    $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:'.$rand.'.0) Gecko/20100101 Firefox/'.$rand.'.0';

	// get the master branch
	if ($version === 'master') :

		$endpoint = 'https://github.com/'.$link.'/archive/refs/heads/master.zip';

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);

        $content = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        $getError = trim($content);

        if (strtolower($getError) == 'not found') :
        
            screen_display(strtolower($getError) . ' error returned from github server. Please check repo name or tag name used.', 'error');
        
        else:
        
            screen_display(convertToReadableSize(strlen($content)). ' downloaded from @https://github.com/'.$link);

            // sleep
            sleep(1);


            // caching begins
            screen_display('Caching master branch, please wait for the next process..', 'success');
        
            $destination = $storageDirectory . $version . '-' .$fileName.'.zip';

            // delete existsing
            if ($updateInstaller) :

            	// check if file exists
            	if (file_exists($destination)) unlink($destination);

            endif;	

            $fh = fopen($destination, 'wb');
            fwrite($fh, $content);
            fclose($fh);

            sleep(1);

            // good
            $installed = true;

        endif;

	else:

		// search for version
		// get version
        $endpoint = 'https://api.github.com/repos/'.$link.'/releases';

        // send text to screen
        screen_display('Connecting to our official github repo. Will attempt to download release for ' . $version);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        screen_display('Getting response from repo @'.$link);

        sleep(1);

        if ($err) :
        
            screen_display('error: '. $err, 'error');

        else:

        	$json = json_decode($content);

        	// get the message
        	$canContinue = (is_object($json) && isset($json->message)) ? (strtolower($json->message) == 'not found' ? false : true) : true;

        	if ($canContinue) :

	            foreach ($json as $release) :
	                
	                if ($release->tag_name == $version) break;

	                $tag = doubleval($release->tag_name);
	                $equal = strpos($version, '=');

	                // remove ^
	                $version = preg_replace('/[^0-9.]/', '', $version);
	                $version = doubleval($version);

	                // check if $tag is greater than $version
	                if ($tag > $version) :
	                
	                    $version = $release->tag_name;
	                    break;

	                elseif ($equal !== false) :
	                
	                    if ($tag >= $version) :
	                    
	                        $version = $release->tag_name;
	                        break;
	                 	endif;

	                else:
	                
	                    $version = null;
	                endif;
	            
	           	endforeach;

	            $message = (is_object($json) && isset($json->message)) ? strtolower($json->message) : '';
	            $error = true;

	            if ($version !== null) :
	            
	                if ($message == '') :
	                
	                    // success
	                    $error = false;
	                    $endpoint = 'https://github.com/'.$link.'/archive/refs/heads/'.$version.'.zip';
	                    screen_display('trying to fetch archive with @'.$endpoint, 'success');
	                    sleep(1);

	                    $ch = curl_init($endpoint);
	                    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	                    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
	                        'If-Modified-Since: Thu, 05 Jul 2012 15:31:30 GMT'
	                    ]);

	                    $content = curl_exec($ch);
	                    $err = curl_error($ch);
	                    curl_close($ch);

	                    screen_display(convertToReadableSize(strlen($content)). ' downloaded from @https://github.com/'.$link);

	                    if ($err) :
	                    
	                        screen_display('error: '. $err, 'error');
	                    
	                    else:
	                    
	                        // caching begins
				            screen_display('Caching version '.$version.', please wait for the next process..', 'success');
	        
	                        $destination = $storageDirectory . preg_replace('/[s]+/', '', $version).'-'.$fileName.'.zip';

	                        // delete existsing
				            if ($updateInstaller) :

				            	// check if file exists
				            	if (file_exists($destination)) unlink($destination);
				            	
				            endif;	

	                        $fh = fopen($destination, 'wb');
	                        fwrite($fh, $content);
	                        fclose($fh);

	                        sleep(1);

	                        // installed 
	                        $installed = true;

	                    endif;

	                endif;

	            endif;

	            // error 
	            if ($error) : screen_display(strtolower($message) . ' error returned from github server. Please check repo name or tag name used.', 'error'); endif;

	        else:

	        	screen_display($json->message .' error was returned from github server. Please check repo name or tag name used.', 'error');

	        endif;

        endif;

	endif;

	// return bool
	return $installed;
}

sleep(1);

// create storage folder
if (!is_dir($homeDirectory . '/moorexa/storage/') && !defined('DIRECT_INSTALLATION')) mkdir($homeDirectory . '/moorexa/storage/');

// repo to download
$repos = [
	'moorexa/system' 	=> 'moorexaCore',
	'moorexa/micro' 	=> 'moorexaMicroService',
	'moorexa/default' 	=> 'moorexaDefault',
	'moorexa/src' 		=> 'moorexaSource',
	'moorexa/package' 	=> 'moorexaPackager'
];

// Load repo
$repos = defined('REPO_TO_INSTALL') ? REPO_TO_INSTALL : $repos;

// download a fresh copy
$downloadFreshCopy = true;

// manage repo for update
if ($updateInstaller) :

	// reset repos
	$repos = [];

	// run through the list of updates
	$updates = array_flip($updates);

	// check for core
	if (isset($updates['system'])) $repos['moorexa/system'] = 'moorexaCore';

	// check for micro service
	if (isset($updates['micro'])) $repos['moorexa/micro'] = 'moorexaMicroService';

	// check for frontend
	if (isset($updates['default'])) $repos['moorexa/default'] = 'moorexaDefault';

	// check for source
	if (isset($updates['src'])) $repos['moorexa/src'] = 'moorexaSource';

	// check for packagers
	if (isset($updates['package'])) $repos['moorexa/package'] = 'moorexaPackager';

	// no fetch ??
	if (isset($updates['no-fetch'])) $downloadFreshCopy = false;

endif;

// completed
$completed = 0;

// download repos
foreach ($repos as $link => $fileName) :

	if ($downloadFreshCopy) :

		if (download_from_github($link, $fileName, $version)) :

			// all good
			screen_display('Package ' . $link . '['.$version.'] '.($updateInstaller ? 'updated' : 'downloaded').' successfully', 'success');

			// sleep
			sleep(1);

			// increment
			$completed++;

		endif;

	else:

		$completed++;

	endif;

endforeach;


// download other required templates
$requiredTemplates = [
	'https://raw.githubusercontent.com/wekiwork/moorexa-installer/master/.global.config.frontend.txt' => '.global.config.frontend.txt',
	'https://raw.githubusercontent.com/wekiwork/moorexa-installer/master/.global.config.service.txt' => '.global.config.service.txt',
	'https://raw.githubusercontent.com/wekiwork/moorexa-installer/master/installer_directory' => 'installer_directory'
];	


// are we good
if ($completed == count($repos)) :

	if ($downloadFreshCopy && !defined('DIRECT_INSTALLATION')) :

		// run download
		foreach ($requiredTemplates as $url => $fileName) :

			// get content
			$content = file_get_contents($url);

			// what do we have
			if (strpos($content, '404') === false) :

				// get file full path
				$fileName = $homeDirectory . '/moorexa/' . $fileName;

				// replace content
				file_put_contents($fileName, $content);

			endif;

		endforeach;

	endif;

	// delete this installer file
	unlink(__DIR__ . '/installer.php');


	// fresh installation
	if (!$updateInstaller) :

		if (!defined('DIRECT_INSTALLATION')) :

			// adding to system paths
			screen_display('Adding moorexa to your system paths', 'success');

			// add profile
			pclose(popen($profile, "w"));

			// add path file
			file_put_contents($pathFile, $profile);

			// all done!
			screen_display('All done. You can enter "moorexa" on your terminal or cmd to see a list of options avaliable to you. Thank you for installing moorexa.

		You may have to restart your terminal or try any of this commands to update your system paths.' . "\n" .
		"
		[Mac] > source ~/.bash_profile
		[Ubuntu or Linux] > source ~/.bashrc

		Or just go on with closing and reopening your terminal before trying \"moorexa\" command.\n\n");

		endif;

	else:

		// update ran
		screen_display('All done. update was successfull', 'success');

	endif;

	// send a signal. Download was successfull
	$ch = curl_init('http://moorexa.com/installation-complete');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Moorexa Installer#successfull');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Installation-Signal: Complete']);
    curl_exec($ch);
    curl_close($ch);

else:
	// you may have to run this installation again
	screen_display('Oops! We could not download all the packages to your local machine. You may have to run this installation again');
endif; 

