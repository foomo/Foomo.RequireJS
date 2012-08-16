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
 * a bundle of js files and RequireJS modules
 *
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author Jan Halfar jan@bestbytes.com
 */
class Bundle
{
	private $scripts = array();
	private $scriptsVar = array();
	private $dirs = array();
	/**
	 * @var boolean
	 */
	private $uglify = true;
	/**
	 * @var boolean
	 */
	private $watch = false;
	/**
	 * @var boolean
	 */
	private $debug = false;
	/**
	 * my module
	 *
	 * @var string
	 */
	private $module;
	/**
	 * bundle name
	 *
	 * @var string
	 */
	private $name;
	/**
	 * bundle version
	 *
	 * @var string
	 */
	private $version;
	/**
	 * create a bundle
	 *
	 * @param string $module
	 * @param string $name name of the bindle
	 * @param string $version update this, if you want a safe deployment
	 *
	 * @return Foomo\RequireJS\Bundle
	 */
	public function create($module, $name, $version)
	{
		$ret = new self;
		$ret->name = $name;
		$ret->version = $version;
		$ret->module = $module;
		return $ret;
	}
	/**
	 * add var scripts
	 *
	 * @param string $module
	 * @param string[] $scripts relative paths - keep the right order!
	 *
	 * @return Foomo\RequireJS\Bundle
	 */
	public function addVarScripts($module, array $scripts)
	{
		return $this->addScriptsToTargetArray($this->scriptsVar, $module, $scripts);
	}
	/**
	 * add scripts
	 *
	 * @param string $module
	 * @param string[] $scripts relative paths - keep the right order!
	 *
	 * @return Foomo\RequireJS\Bundle
	 */
	public function addScripts($module, array $scripts)
	{
		return $this->addScriptsToTargetArray($this->scripts, $module, $scripts);
	}
	/**
	 * dry helper method
	 *
	 * @param array $targetArray
	 * @param string $module
	 * @param string[] $scripts
	 *
	 * @return \Foomo\RequireJS\Bundle
	 */
	private function addScriptsToTargetArray(array &$targetArray, $module, $scripts)
	{
		if(!isset($targetArray[$module])) {
			$targetArray[$module] = array();
		}
		$targetArray[$module] = array_merge($targetArray[$module], $scripts);
		return $this;
	}
	/**
	 * add directories to scan for RequireJS module definiing .js files
	 *
	 * @param string $module
	 * @param string[] $dirs directories to scan for js files in
	 *
	 * @return Foomo\RequireJS\Bundle
	 */
	public function addReqireJSDirs($module, array $dirs)
	{
		if(!isset($this->dirs[$module])) {
			$this->dirs[$module] = array();
		}
		$this->dirs[$module] = array_merge($this->dirs[$module], $dirs);
		return $this;
	}
	/**
	 * turn on debugging
	 *
	 * @param boolean $debug
	 *
	 * @return Foomo\RequireJS\Bundle
	 */
	public function debug($debug = true)
	{
		$this->debug = $debug;
		return $this;
	}
	/**
	 * turn on uglifying
	 *
	 * @param boolean $uglify
	 *
	 * @return Foomo\RequireJS\Bundle
	 */
	public function uglify($uglify = true)
	{
		$this->uglify = $uglify;
		return $this;
	}
	/**
	 * turn on watching
	 *
	 * @param boolean $watch
	 *
	 * @return Foomo\RequireJS\Bundle
	 */
	public function watch($watch = true)
	{
		$this->watch = $watch;
		return $this;
	}

	/**
	 * link the bundle to a HTMLDocument
	 *
	 * @param boolean $debug add every external js file or load the bundle as a minified js file
	 * @param Foomo\HTMLDocument $doc
	 *
	 * @return Foomo\RequireJS\Bundle
	 */
	public function linkToDoc(\Foomo\HTMLDocument $doc = null)
	{
		if(is_null($doc)) {
			$doc = \Foomo\HTMLDocument::getInstance();
		}

		if($this->debug) {
			$scripts = array();
			foreach(array('scripts', 'scriptsVar') as $prop) {
				foreach ($this->$prop as $module => $moduleScripts) {
					foreach($moduleScripts as $moduleScript) {
						$scripts[] = self::getRootHttp($module, $prop == 'scriptsVar') . '/' . $moduleScript;
					}
				}
			}
			$templateDefs = '';
			foreach($this->dirs as $module => $dirs) {
				$htdocsDir = self::getHtdocsdDir($module);
				foreach($dirs as $dir) {
					$templateDefs .= R::concatHTMLTemplateFiles($htdocsDir . DIRECTORY_SEPARATOR . $dir);
					$jsFiles = R::getDefiningJSFiles($htdocsDir . DIRECTORY_SEPARATOR . $dir);
					foreach($jsFiles as $jsFile) {
						$relativePath = implode('/', explode(DIRECTORY_SEPARATOR, substr($jsFile, strlen($htdocsDir) + 1)));
						$scripts[] =  self::getRootHttp($module) . '/' . $relativePath;
					}
				}
			}
			if(count($scripts) > 0) {
				$doc->addJavascripts($scripts);
			}
			$doc->addJavascript($templateDefs);
		} else {
			$this->check($this->watch, $this->uglify);
			$doc->addJavascripts(array($this->getEndpoint()));
		}
		return $this;
	}
	private function getEndpoint()
	{
		return Module::getHtdocsVarPath() . '/' . basename($this->getCompiledFilename());
	}
	private function getCompiledFilename()
	{
		return Module::getHtdocsVarDir() . DIRECTORY_SEPARATOR . $this->module . '-' . $this->name . '-' . $this->version . '.minified.js';
	}

	/**
	 * serve the bundle - use this in the endpoint
	 *
	 * @param boolean $watch watch for changes in the bundle
	 * @param boolean $uglify uglify the javascript - uglifyjs needs to be available on the command line to the server
	 */
	public function check($watch, $uglify)
	{
		$lastModUglified = 0;
		$compiledFilename = $this->getCompiledFilename();
		$compiledExists = file_exists($compiledFilename);
		if($compiledExists) {
			$lastModUglified = filemtime($compiledFilename);
		} else {
			$this->sayIAmWorkingOnIt($compiledFilename);
		}
		if(!$compiledExists || $watch) {
			if($compiledExists) {
				$lastmod = $this->getLastmod();
			} else {
				$lastmod = 0;
			}
			if(!$compiledExists || $lastmod > $lastModUglified) {
				$this->sayIAmWorkingOnIt($compiledFilename);
				file_put_contents($compiledFilename, $this->getJS($uglify));
			}
		}
	}
	private function sayIAmWorkingOnIt($compiledFilename)
	{
		file_put_contents($compiledFilename, '// hang on - i am working on it ' . date('Y-m-d H:i:s'));
	}
	/**
	 *
	 * @param string $module
	 * @param array $scripts
	 * @param array $dirs
	 * @param boolean $uglify
	 *
	 * @return string
	 */
	private function getJS($uglify = false)
	{
		$js = '// app bundle in module ' . $this->module . ' ' . $this->name . PHP_EOL;
		foreach(array('scripts', 'scriptsVar') as $prop) {
			foreach($this->$prop as $module => $jsPaths) {
				$js .= PHP_EOL . '// ' . $prop . ' from module ' . $module . PHP_EOL . PHP_EOL;
				$htdocsDir = self::getHtdocsdDir($module, $prop == 'scriptsVar');
				foreach($jsPaths as $jsPath) {
					$js .= PHP_EOL . '// ' . $jsPath . PHP_EOL . PHP_EOL;
					$jsFile = $htdocsDir . DIRECTORY_SEPARATOR . $jsPath;
					$js .= file_get_contents($jsFile);
				}
			}
		}
		foreach($this->dirs as $module => $dirs) {
			foreach($dirs as $dir) {
				$js .= R::concatDefiningJSFiles(self::getHtdocsdDir($module) . DIRECTORY_SEPARATOR . $dir);
				$js .= PHP_EOL . '// templates in: ' . basename($dir) . PHP_EOL . R::concatHTMLTemplateFiles($dir) . PHP_EOL;
			}
		}
		return $this->compile($js, $uglify);
	}
	private function higher($a, $b)
	{
		return ($a > $b)?$a:$b;
	}
	private function getLastmod()
	{
		$lastmod = 0;
		foreach(array('scripts', 'scriptsVar') as $prop) {
			foreach($this->$prop as $module => $jsPaths) {
				$htdocsDir = self::getHtdocsdDir($module, $prop == 'scriptsVar');
				foreach($jsPaths as $jsPath) {
					$jsFile = $htdocsDir . DIRECTORY_SEPARATOR . $jsPath;
					$lastmod = $this->lastMod($lastmod, $jsFile);
				}

			}
		}
		foreach($this->dirs as $module => $dirs) {
			foreach($dirs as $dir) {
				$fullDir = self::getHtdocsdDir($module) . DIRECTORY_SEPARATOR . $dir;
				$lastmod = max(array(
					$lastmod,
					R::getLastmodForDefiningJSFiles($fullDir),
					R::getLastmodForHTMLTemplateFiles($fullDir)
				));
			}
		}
		return $lastmod;
	}
	private function lastMod($lastmod, $filename)
	{
		return $this->higher($lastmod, filemtime($filename));
	}
	private function compile($js, $uglify)
	{
		$compiledFilename = tempnam(Module::getVarDir(), 'bundle-compile');
		// uglify ...
		file_put_contents($compiledFilename, '// uglified ' . date('Y-m-d H:i:s', time()) . PHP_EOL . PHP_EOL . $js);
		if($uglify) {
			$call = \Foomo\CliCall::create('uglifyjs', array($compiledFilename));
			$call->execute();
			if($call->exitStatus === 0) {
				file_put_contents($compiledFilename, $call->stdOut);
			} else {
				trigger_error('uglify is ugly ' . $call->stdErr, E_USER_WARNING);
			}
		}
		$compiled = file_get_contents($compiledFilename);
		unlink($compiledFilename);
		return $compiled;
	}
	private static function getRootHttp($module, $var = false)
	{
		return \Foomo\ROOT_HTTP . '/modules' . ($var?'Var':'') . '/' . $module;
	}
	private static function getHtdocsdDir($module, $var = false)
	{
		if($var) {
			return \Foomo\Config::getVarDir() . DIRECTORY_SEPARATOR . 'htdocs'  . DIRECTORY_SEPARATOR . 'modulesVar' . DIRECTORY_SEPARATOR . $module;
		} else {
			return \Foomo\Config::getModuleDir($module) . DIRECTORY_SEPARATOR . 'htdocs';
		}
	}
}