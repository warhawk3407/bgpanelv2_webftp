<?php

error_reporting(0); // Set E_ALL for debuging

define('ELFINDER_TMB_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.'.tmb');

include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinderVolumeSFTP.class.php';

require dirname(__FILE__). DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'phpseclib' . DIRECTORY_SEPARATOR . 'SFTP.php';
require dirname(__FILE__). DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'phpseclib' . DIRECTORY_SEPARATOR . 'SFTP_StreamWrapper.php';

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from  '.' (dot) AND SYMLINKS
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume) {
	return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
		|| is_link($path)							// Disable symlinks
		? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
		:  null;                                    // else elFinder decide it itself
}

$opts = array(
	'roots' => array(
		array(
			'driver'        => 'SFTP',
			'alias'			=> 'Remote-FS',
			'path'			=> '/home/asterix/',
			'stream'		=> 'sftp://asterix:pass@ip:port',
			'separator'		=> '/',
			'accessControl' => 'access',
			'tmbPath' 		=> ELFINDER_TMB_PATH
		)
	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();

?>