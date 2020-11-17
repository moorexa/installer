<?php

// create function to help complete install.php process
function completeInstallProcess() : Closure
{
	// create read input function
	$readInput = function(string $question, string $default)
	{
	  // show 
	  fwrite(STDOUT, $question . ': ');
	  
	  // get input
	  $input = trim(fgets(STDIN));
   
	  // return input
	  return ($input == '') ? $default : $input;
	};

	// return closure
	return function(object $jsonData, string $saveTo) use (&$readInput)
	{
		// ask for content type
		$contentType = $readInput('Please enter a default content type. (Tap Enter to use default "'.$jsonData->contentType.'")', $jsonData->contentType);

		// ask for default time zone
		$defaultTimeZone = $readInput('Please enter a default time zone. (Tap Enter to use default "'.$jsonData->defaultTimeZone.'")', $jsonData->defaultTimeZone);

		// create constant for content type
		$jsonData->initContent .= "\n\n// default content type\n" . 'define(\'DEFAULT_CONTENT_TYPE\', \''.$contentType.'\');';

		// create constant for timezone
		$jsonData->initContent .= "\n\n// default timezone\n" . 'define(\'DEFAULT_TIME_ZONE\', \''.$defaultTimeZone.'\');';

		// get default controller 
		$defaultController = $readInput('Please enter a default controller name. (Tap Enter to use default "@starter")', '@starter');

		// get default view
		$defaultView = $readInput('Please enter a default view name. (Tap Enter to use default "home")', 'home');

		// create file in the root directory
		file_put_contents(__DIR__ . '/' . $saveTo, $jsonData->initContent);

		// delete file
		if (file_exists(__DIR__ . '/' . $saveTo)) :

			// delete the init file from src
			unlink(__DIR__ . '/src/' . $saveTo);

			// update config for micro project
			if ($jsonData->projectType == 'micro') :

				// set micro config body
				$microConfig  = '# Beautiful url' . "\n";
				$microConfig .= 'beautiful_url_target : __app_request__' . "\n";
				$microConfig .= '# Router global configuration' . "\n";
				$microConfig .= 'router :' . "\n";
				$microConfig .= ' # set default controller and view ' . "\n";
				$microConfig .= ' default : ' . "\n";
				$microConfig .= '  controller : \''.$defaultController.'\'' . "\n";
				$microConfig .= '  view : \''.$defaultView.'\'' . "\n";
				$microConfig .= '# actions for model' . "\n";
				$microConfig .= 'actions : ' . "\n";
				$microConfig .= " - add\n - create\n - edit\n - delete\n - show";
	  
				// update config.yaml file
				if (file_exists(__DIR__ . '/src/config.yaml')) file_put_contents(__DIR__ . '/src/config.yaml', $microConfig);
	  
			endif;
	  
			// update config for default project
			if ($jsonData->projectType == 'default' && file_exists(__DIR__ . '/src/config.yaml')) :
	
				// read config
				$configContent = file_get_contents(__DIR__ . '/src/config.yaml');
		
				// update controller
				$configContent = str_replace('@starter', $defaultController, $configContent);
		
				// update view
				$configContent = str_replace("'home'", $defaultView, $configContent);
		
				// save now
				file_put_contents(__DIR__ . '/src/config.yaml', $configContent);
	
			endif;

			// delete complete install process file
			unlink(__DIR__ . '/completeInstallProcess.php');

		endif;
	};
}