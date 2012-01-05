<?php

/**
 * Creates a sandboxed Kohana install for this module
 */

// Configure the Kohana core version and modules to use

$repos = array(
	'system' => array(
		'url' => 'git://github.com/kohana/core.git',
		'branch' => '3.2/master'
	),
	'auth' => array(
		'url' => 'git://github.com/kohana/auth.git',
		'branch' => '3.2/master',
	),
	'cache' => array(
		'url' => 'git://github.com/kohana/cache.git',
		'branch' => '3.2/master',
	),
	'codebench' => array(
		'url' => 'git://github.com/kohana/codebench.git',
		'branch' => '3.2/master',
	),
	'database' => array(
		'url' => 'git://github.com/kohana/database.git',
		'branch' => '3.2/master',
	),
	'image' => array(
		'url' => 'git://github.com/kohana/image.git',
		'branch' => '3.2/master',
	),
	'orm' => array(
		'url' => 'git://github.com/kohana/orm.git',
		'branch' => '3.2/master',
	),
	'unittest' => array(
		'url' => 'git://github.com/acoulton/unittest.git',
		'branch' => '3.2/master'
	),
	'userguide' => array(
		'url' => 'git://github.com/kohana/userguide.git',
		'branch' => '3.2/master',
	),
	'mimic' => TRUE,
);

/**
 * NO USER CHANGES BEYOND HERE!
 */
echo "Installing Kohana Sandbox for module\r\n";

// Create a location for the sandbox
if ( ! isset($_SERVER['KO_SANDBOX']))
{
	$_SERVER['KO_SANDBOX'] = $_SERVER['HOME'].'/ko_sandbox';
}

$root = $_SERVER['KO_SANDBOX'];

if (file_exists($root))
{
	print_r("*** PATH $root EXISTS! CANNOT CONTINUE");
	exit(1);
}

mkdir($root, 0777, TRUE);

// Checkout the core and modules
echo "Fetching required modules\n";
$modules=array();
foreach ($repos as $module => $repo)
{
	// Checkout from git unless this is the current module
	if ($repo !== TRUE)
	{
		// Module path should be modules/
		if ($module !== 'system')
		{
			$modpath = $root.'/modules/'.$module;
		}	
		else
		{
			$modpath = $root.'/system';
		}
		
		// Do the git checkout
                $cmd = 'git clone -b '.$repo['branch'].' '.$repo['url'].' '.$modpath;
                echo "Checking out $module to $modpath\n\t-$cmd\n";
		system($cmd, $return);
		if ($return)
		{
			echo "CHECKOUT FAILED!! Value $return";
			exit(1);
		}
	}
	else
	{
		// Local path is the path above this
		$modpath = realpath(__DIR__.'/../');
	}

	// Add to module array if required
	if ($module !== 'system')
	{
		$modules[$module] = $modpath;
	}
}

// Create an application folder and bootstrap
echo "Building application folder\n";
mkdir($root.'/application');
mkdir($root.'/application/cache');
mkdir($root.'/application/logs');
file_put_contents($root.'/application/sandbox_modules.php',"<?php \n return ".var_export($modules, TRUE).';');

copy('dev/bootstrap.php', $root.'/application/bootstrap.php');
echo "Sandbox install complete in $root\n";
