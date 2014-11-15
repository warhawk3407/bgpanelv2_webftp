<?php

/**
 * elFinder driver for remote SFTP.
 *
 * Archives are NOT SUPPORTED, as well as symlinks.
 *
 * @author Nikita ROUSSEAU (warhawk3407)
 **/
class elFinderVolumeSFTP extends elFinderVolumeLocalFileSystem {

	/**
	 * Driver id
	 * Must be started from letter and contains [a-z0-9]
	 * Used as part of volume id
	 *
	 * @var string
	 **/
	protected $driverId = 'sftp';

	/**
	 * Directory separator - required by client
	 *
	 * @var string
	 **/
	protected $separator = '/';

	/**
	 * Thumbnails dir path
	 *
	 * @var string
	 **/
	protected $tmbPath = ELFINDER_TMB_PATH;

	/**
	 * Prepare driver before mount volume.
	 * Return true if volume is ready.
	 *
	 * @return bool
	 **/
	protected function init()
	{
		// normalize root path
		$this->root = $this->options['path'] = $this->_normpath($this->options['path']);

		return true;
	}

	/**
	 * Configure after successfull mount.
	 *
	 * @return void
	 **/
	protected function configure() {
		elFinderVolumeDriver::configure();

		$this->disabled[] = 'archive';
		$this->disabled[] = 'extract';
		$this->disabled[] = 'unpack';

		$this->disabled[] = 'symlink';
	}

	/*********************** paths/urls *************************/

	/**
	 * Join dir name and file name and return full path
	 *
	 * @param  string  $dir
	 * @param  string  $name
	 * @return string
	 **/
	protected function _joinPath($dir, $name) {
		return $dir.$this->separator.$name;
	}

	/**
	 * Return normalized path, this works the same as os.path.normpath() in Python
	 *
	 * @param  string  $path  path
	 * @return string
	 **/
	protected function _normpath($path) {
		$stream_url = $this->options['stream'];

		if (empty($path)) {
			return '.';
		}

		if (strpos($path, '/') === 0) {
			$initial_slashes = true;
		} else {
			$initial_slashes = false;
		}

		if (($initial_slashes)
		&& (strpos($path, '//') === 0)
		&& (strpos($path, '///') === false)) {
			$initial_slashes = 2;
		}

		$initial_slashes = (int) $initial_slashes;

		$comps = explode('/', $path);
		$new_comps = array();
		foreach ($comps as $comp) {
			if (in_array($comp, array('', '.'))) {
				continue;
			}

			if (($comp != '..')
			|| (!$initial_slashes && !$new_comps)
			|| ($new_comps && (end($new_comps) == '..'))) {
				array_push($new_comps, $comp);
			} elseif ($new_comps) {
				array_pop($new_comps);
			}
		}
		$comps = $new_comps;
		$path = implode('/', $comps);
		if ($initial_slashes) {
			$path = str_repeat('/', $initial_slashes) . $path;
		}

		return $path ? $stream_url . $path : $stream_url . '.';
	}

	/**
	 * Convert path related to root dir into real path
	 *
	 * @param  string  $path  file path
	 * @return string
	 **/
	protected function _abspath($path) {
		return $path == DIRECTORY_SEPARATOR ? $this->root : $this->root.$this->separator.$path;
	}

	/***************** file stat ********************/

	/**
	 * Return true if path is dir and has at least one childs directory
	 *
	 * @param  string  $path  dir path
	 * @return bool
	 **/
	protected function _subdirs($path) {
		if (($dir = dir($path))) {
			$dir = dir($path);
			while (($entry = $dir->read()) !== false) {
				$p = $dir->path.$this->separator.$entry;
				if ($entry != '.' && $entry != '..' && is_dir($p) && !$this->attr($p, 'hidden')) {
					$dir->close();
					return true;
				}
			}
			$dir->close();
		}
		return false;
	}

	/******************** file/dir content *********************/

	/**
	 * Return symlink target file
	 *
	 * @param  string  $path  link path
	 * @return false
	 **/
	protected function readlink($path) {
		return FALSE;
	}

	/**
	 * Return files list in directory.
	 *
	 * @param  string  $path  dir path
	 * @return array
	 **/
	protected function _scandir($path) {
		$files = array();

		foreach (scandir($path) as $name) {
			if ($name != '.' && $name != '..') {
				$files[] = $path.$this->separator.$name;
			}
		}

		return $files;
	}

	/********************  file/dir manipulations *************************/

	/**
	 * Create dir and return created dir path or false on failed
	 *
	 * @param  string  $path  parent dir path
	 * @param string  $name  new directory name
	 * @return string|bool
	 **/
	protected function _mkdir($path, $name) {
		$path = $path.$this->separator.$name;

		if (@mkdir($path))
		{
			@chmod($path, $this->options['dirMode']);
			return $path;
		}

		return FALSE;
	}

	/**
	 * Create file and return it's path or false on failed
	 *
	 * @param  string  $path  parent dir path
	 * @param string  $name  new file name
	 * @return string|bool
	 **/
	protected function _mkfile($path, $name) {
		$path = $path.$this->separator.$name;

		$fp = fopen($path, 'a+');

		if ($fp !== FALSE)
		{
			fclose($fp);
			chmod($path, $this->options['fileMode']);
			return $path;
		}

		return FALSE;
	}

	/**
	 * Create symlink
	 *
	 * @param  string  $source     file to link to
	 * @param  string  $targetDir  folder to create link in
	 * @param  string  $name       symlink name
	 * @return FALSE
	 **/
	protected function _symlink($source, $targetDir, $name) {
		return FALSE;
	}

	/**
	 * Copy file into another file
	 *
	 * @param  string  $source     source file path
	 * @param  string  $targetDir  target directory path
	 * @param  string  $name       new file name
	 * @return bool
	 **/
	protected function _copy($source, $targetDir, $name) {
		return copy($source, $targetDir.$this->separator.$name);
	}

	/**
	 * Move file into another parent dir.
	 * Return new file path or false.
	 *
	 * @param  string  $source  source file path
	 * @param  string  $target  target dir path
	 * @param  string  $name    file name
	 * @return string|bool
	 **/
	protected function _move($source, $targetDir, $name) {
		$target = $targetDir.$this->separator.$name;
		return @rename($source, $target) ? $target : false;
	}

	/**
	 * Create new file and write into it from file pointer.
	 * Return new file path or false on error.
	 *
	 * @param  resource  $fp   file pointer
	 * @param  string    $dir  target dir path
	 * @param  string    $name file name
	 * @return bool|string
	 **/
	protected function _save($fp, $dir, $name, $mime, $w, $h) {
		$path = $dir.$this->separator.$name;

		if (!($target = @fopen($path, 'a+b'))) {
			return false;
		}

		while (!feof($fp)) {
			fwrite($target, fread($fp, 8192));
		}

		fclose($target);

		@chmod($path, $this->options['fileMode']);

		clearstatcache();
		return $path;
	}

	/**
	 * Write a string to a file
	 *
	 * PATCH: removed LOCK_EX for SFTP
	 *
	 * @param  string  $path     file path
	 * @param  string  $content  new file content
	 * @return bool
	 **/
	protected function _filePutContents($path, $content)
	{
		if (@file_put_contents($path, $content) !== false)
		{
			clearstatcache();
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Recursive symlinks search
	 *
	 * @param  string  $path  file/dir path
	 * @return bool
	 **/
	protected function _findSymlinks($path) {
		if (is_link($path)) {
			return true;
		}

		if (is_dir($path)) {
			foreach (scandir($path) as $name) {
				if ($name != '.' && $name != '..') {
					$p = $path.$this->separator.$name;
					if (is_link($p)) {
						return true;
					}
					if (is_dir($p) && $this->_findSymlinks($p)) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/********************  archive manipulations *************************/

	protected function _unpack($path, $arc) {
	}

	protected function _extract($path, $arc) {
		return FALSE;
	}

	protected function _archive($dir, $files, $name, $arc) {
		return FALSE;
	}
}

?>