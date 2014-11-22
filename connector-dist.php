<?php

error_reporting(0); // Set E_ALL for debuging

define('ELFINDER_TMB_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.'.tmb');
define('ELFINDER_TMP_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.'.tmp');

// ELFINDER CORE
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR. 'php' .DIRECTORY_SEPARATOR.'elFinderVolumeSFTP.class.php';

// PCLZIP LIB
require dirname(__FILE__). DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'pclzip' . DIRECTORY_SEPARATOR . 'pclzip.lib.php';

// PHPSECLIB
require dirname(__FILE__). DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'phpseclib' . DIRECTORY_SEPARATOR . 'SSH2.php';
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

/**
 * Copy a file, or recursively copy a folder and its contents
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @param       string   $permissions New folder creation permissions
 * @return      bool     Returns true on success, false on failure
 */
function xcopy($source, $dest, $permissions = 0755)
{
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        xcopy("$source/$entry", "$dest/$entry");
    }

    // Clean up
    $dir->close();
    return true;
}

/**
 * http://php.net/manual/en/function.rmdir.php#98622
 */
function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
} 



$opts = array(
	'roots' => array(
		array(
			'driver'        => 'SFTP',
			'alias'			=> 'Remote-FS',
			'path'			=> '/home/user/',
			'stream'		=> 'sftp://user:password@ip:22',
			'separator'		=> '/',
			'accessControl' => 'access',
			'tmbPath' 		=> ELFINDER_TMB_PATH,
			'tmpPath'		=> ELFINDER_TMP_PATH
		)
	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();

?>