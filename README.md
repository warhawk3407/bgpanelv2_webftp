elFinder - bgpanel v2 - standalone webapp
========

<pre>
      _ ______ _           _           
     | |  ____(_)         | |          
  ___| | |__   _ _ __   __| | ___ _ __ 
 / _ \ |  __| | | '_ \ / _` |/ _ \ '__|
|  __/ | |    | | | | | (_| |  __/ |   
 \___|_|_|    |_|_| |_|\__,_|\___|_|   
</pre>
For the Bright Game Panel Project, by warhawk3407

- nightly-2.1
- 22/11/2014

elFinder is an open-source file manager for web, written in JavaScript using
jQuery UI. Creation is inspired by simplicity and convenience of Finder program
used in Mac OS X operating system.


Install
--------
1. **Edit** `connector-dist.php`
	- `'path'			=> 'sftp://user:pass@ip:22/home/user'`
	- Modify user, pass, ip and the user home directory
	- Verify that the syntax is correct
	- [http://php.net/manual/en/function.parse-url.php](http://php.net/manual/en/function.parse-url.php "URL-Format")
2. **Rename** `connector-dist.php` to `connector.php`
3. **Make the `files/` folder writeable by the server**
	- For example, `chown -R www-data /elfinder/install/path/files/.`
4. **Enjoy :-)**

Requirements
------------

### Client
 * Modern browser. elFinder was tested in Firefox 12, Internet Explorer 8+,
   Safari 6, Opera 12 and Chrome 19

### Server
 * Any web server
 * PHP 5.2+ (for thumbnails - mogrify utility or GD/Imagick module)

Downloads
------------
 + [elFInder 2.1 (Nightly)](http://nao-pon.github.io/elFinder-nightly/latests/elfinder-2.1.zip)

Authors
-------

 * Chief developer: Dmitry "dio" Levashov <dio@std42.ru>
 * Maintainer: Troex Nevelin <troex@fury.scancode.ru>
 * Developers: Alexey Sukhotin <strogg@yandex.ru>, Naoki Sawada <hypweb@gmail.com>, Nikita Rousseau <devel [ a t ] bgpanel [ d o t ] net >
 * Icons: [PixelMixer](http://pixelmixer.ru), [Yusuke Kamiyamane](http://p.yusukekamiyamane.com)

License
-------

elFinder is issued under a 3-clauses BSD license.

<pre>
Copyright (c) 2009-2012, Studio 42
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the Studio 42 Ltd. nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL "STUDIO 42" BE LIABLE FOR ANY DIRECT, INDIRECT,
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
</pre>
