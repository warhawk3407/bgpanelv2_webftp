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

	// SSH Obj (PHPSECLIB)
	private $remote = FALSE;

	/**
	 * Prepare driver before mount volume.
	 * Return true if volume is ready.
	 *
	 * @return bool
	 **/
	protected function init()
	{
		$parts = parse_url($this->root);

		$ssh = new Net_SSH2($parts['host'], $parts['port']);
		if ($ssh->login($parts['user'], $parts['pass'])) {
			$this->remote = $ssh;
		}

		return true;
	}

	/**
	 * Configure after successfull mount.
	 *
	 * @return void
	 **/
	protected function configure() {
		elFinderVolumeDriver::configure();

		// Disable archive browsing
		$this->archivers['extract'] = array();
		$this->disabled[] = 'extract';
		$this->disabled[] = 'unpack';

		// Disable creating symlinks
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
		return $dir . $this->separator . $name;
	}

	/**
	 * Return normalized path, this works the same as os.path.normpath() in Python
	 *
	 * @param  string  $path  path
	 * @return string
	 **/
	protected function _normpath($path)
	{
		// Explode URL
		$url_parts = parse_url($path);
		$path = $url_parts['path'];

		$path =  preg_replace("/^\//", "", $path);
		$path =  preg_replace("/\/\//", "/", $path);
		$path =  preg_replace("/\/$/", "", $path);

		// Rebuild URL
		$url =
			$url_parts['scheme'] . '://' .
			$url_parts['user'] . ':' .
			$url_parts['pass'] . '@' .
			$url_parts['host'] . ':' .
			$url_parts['port'] . '/' . $path;

		return $url;
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

	/**
	 * Return true if $path is children of $parent
	 *
	 * @param  string  $path    path to check
	 * @param  string  $parent  parent path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _inpath($path, $parent)
	{
		if ($parent)
		{
			$path = parse_url($path, PHP_URL_PATH);
			$parent = parse_url($parent, PHP_URL_PATH);

			if (strpos($path, $parent.'/') === FALSE) {
				return FALSE;
			}
		}
		return TRUE;
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
	 * @param  array     $stat file stat (required by some virtual fs)
	 * @return bool|string
	 **/
	protected function _save($fp, $dir, $name, $stat) {
		$path = $dir.$this->separator.$name;

		if (@file_put_contents($path, $fp) === false) {
			return false;
		}

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
					$p = $path . $this->separator . $name;
					if (is_link($p) || !$this->nameAccepted($name)) {
						return true;
					}
					if (is_dir($p) && $this->_findSymlinks($p)) {
						return true;
					} elseif (is_file($p)) {
						$this->archiveSize += sprintf('%u', filesize($p));
					}
				}
			}
		} else {
			
			$this->archiveSize += sprintf('%u', filesize($path));
		}
		
		return false;
	}

	/********************  archive manipulations *************************/

	/**
	 * Detect available archivers
	 *
	 * @return void
	 **/
	protected function _checkArchivers()
	{
		$arcs = array(
			'create'  => array()
			);

		// Cache method
		if (empty($_SESSION['ELFINDER']['ARCS']))
		{
			// Get remote-fs utils
			$ssh = $this->remote;

			if ($ssh)
			{
				$tar = $ssh->exec('tar --version');

				if (strstr($tar, "(GNU tar)") !== FALSE) {
					$arcs['create']['application/x-tar']  = array('cmd' => 'tar', 'argc' => '-cf', 'ext' => 'tar');

					$gzip = $ssh->exec('gzip --version');

					if (strstr($gzip, "Jean-loup Gailly") !== FALSE) {
						$arcs['create']['application/x-gzip']  = array('cmd' => 'tar', 'argc' => '-czf', 'ext' => 'tgz');
					}

					$bzip2 = $ssh->exec('bzip2 --version');

					if (strstr($bzip2, "bzip2, a block-sorting file compressor.") !== FALSE) {
						$arcs['create']['application/x-bzip2']  = array('cmd' => 'tar', 'argc' => '-cjf', 'ext' => 'tbz');
					}
				}

				$zip = $ssh->exec('zip -v');

				if (strstr($zip, "Info-ZIP") !== FALSE) {
					$arcs['create']['application/zip']  = array('cmd' => 'zip', 'argc' => '-r9', 'ext' => 'zip');
				}
			}
			unset($ssh);

			// PclZip as last resort
			if (function_exists('gzopen') && empty($arcs['create']['application/zip']))
			{
				$arcs['create']['application/zip'] = array('cmd' => 'zlib', 'argc' => 'pclzip', 'ext' => 'zip');
			}

			// Cache it
			$_SESSION['ELFINDER']['ARCS'] = $arcs;
		}
		else
		{
			// Retrieve it
			$arcs = $_SESSION['ELFINDER']['ARCS'];
		}

		$this->archivers = $arcs;
	}

	/**
	 * Create archive and return its path
	 *
	 * @param  string  $dir    target dir
	 * @param  array   $files  files names list
	 * @param  string  $name   archive name
	 * @param  array   $arc    archiver options
	 * @return string|bool
	 **/
	protected function _archive($dir, $files, $name, $arc)
	{
		$files = array_map('escapeshellarg', $files);
		$archive_path = $dir . $this->separator . $name;

		switch ($arc['argc'])
		{
			// last resort
			case 'pclzip':
				@set_time_limit(60);
				$uniqid = uniqid();

				// Tmp folder
				$uniqfolder = ELFINDER_TMP_PATH . DIRECTORY_SEPARATOR . $uniqid; // Local
				@mkdir($uniqfolder);

				// Download from remote-fs to local-fs
				foreach ($files as $file) {
					$remote_path = $dir . $this->separator . $file;

					if (is_dir($remote_path)) {
						@mkdir( $uniqfolder . DIRECTORY_SEPARATOR . $file );
					}
					
					@xcopy( $remote_path, $uniqfolder . DIRECTORY_SEPARATOR . $file );			
				}

				$archive_path = ELFINDER_TMP_PATH . $this->separator . $name;
				$archive = new PclZip( $archive_path );

				$v_list = $archive->create( $uniqfolder, PCLZIP_OPT_REMOVE_PATH, $uniqfolder, PCLZIP_OPT_NO_COMPRESSION );

				// Clean tmp folder
				@rrmdir($uniqfolder);

				if (!$v_list) {
					return FALSE;
				}

				// Push to remote-fs
				copy( $archive_path, $dir . $this->separator . $name );
				unlink( $archive_path );
				
				break;

			// tar/gzip/bz2
			default:
				$cmd = $arc['cmd'].' '.$arc['argc'].' '.escapeshellarg($name).' '.implode(' ', $files);

				$ssh = $this->remote;

				if ($ssh)
				{
					$ssh->exec( 'cd ' . $dir . '; ' . $cmd );
				}
				unset($ssh);
	
				break;
		}

		return file_exists($archive_path) ? $archive_path : FALSE;
	}

	protected function _unpack($path, $arc) {
	}

	protected function _extract($path, $arc) {
		return FALSE;
	}
}

?>