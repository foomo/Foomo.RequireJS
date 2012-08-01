<?php

/*
 * This file is part of the foomo Opensource Framework.
 * 
 * The foomo Opensource Framework is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License as
 * published  by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * The foomo Opensource Framework is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License along with
 * the foomo Opensource Framework. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Foomo\RequireJS;
 
/**
 * An alternative to r.js ;)
 * 
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author Jan Halfar jan@bestbytes.com
 */
class R
{
	//---------------------------------------------------------------------------------------------
	// ~ public API
	//---------------------------------------------------------------------------------------------
	/**
	 * concatenate a lot of javascript
	 * 
	 * @param string $dir directory to llok for .js files with a string define()
	 * 
	 * @return string (lot of) javascript
	 */
	public static function concatDefiningJSFiles($dir)
	{
		$js = '// concatenated by ' . __METHOD__ . ' ' . date('Y-m-d H:i:s', time());
		$jsFiles = array();
		self::collectDefiningJSFiles($dir, $jsFiles);
		foreach($jsFiles as $jsFile) {
			$jsFromFile = file_get_contents($jsFile);
			$js .= PHP_EOL . '// ' . basename($jsFile) . PHP_EOL . PHP_EOL . $jsFromFile;
		}
		return $js;
	}
	/**
	 * get .js files that do a RequireJS define()
	 * 
	 * @param string $dir directory to recursively to scan in
	 * 
	 * @return string[] array of files
	 */
	public static function getDefiningJSFiles($dir)
	{
		$jsFiles = array();
		self::collectDefiningJSFiles($dir, $jsFiles);
		return $jsFiles;
	}
	/**
	 * 
	 * 
	 * @param string $dir
	 * 
	 * @return integer timestamp of last change
	 */
	public static function getLastmodForDefiningJSFiles($dir)
	{
		$jsFiles = array();
		self::collectDefiningJSFiles($dir, $jsFiles);
		$lastmod = 0;
		foreach($jsFiles as $jsFile) {
			$mtime = filemtime($jsFile);
			$lastmod = ($lastmod < $mtime)?$mtime:$lastmod;
		}
		return $lastmod;
	}
	//---------------------------------------------------------------------------------------------
	// ~ private
	//---------------------------------------------------------------------------------------------
	/**
	 * collect defining js files
	 * 
	 * @param type $dir
	 * 
	 * @param array $jsFiles
	 */
	private static function collectDefiningJSFiles($dir, array &$jsFiles)
	{
		$dirIterator = new \DirectoryIterator($dir);
		foreach($dirIterator as $fileInfo) {
			/* @var $fileInfo \SplFileInfo */
			if(substr($fileInfo->getFilename(), 0, 1) === '.') {
				// skip dot files
				continue;
			}
			if($fileInfo->isFile() && substr($fileInfo->getFilename(), -3) === '.js') {
				// append js
				$jsFromFile = file_get_contents($fileInfo->getPathname());
				if(strpos($jsFromFile, 'define(') !== false) {
					$jsFiles[] = $fileInfo->getPathname();
				}
			} else if($fileInfo->isDir()) {
				// crawl deeper
				self::collectDefiningJSFiles($fileInfo->getPathname(), $jsFiles);
			}
		}
	}
}