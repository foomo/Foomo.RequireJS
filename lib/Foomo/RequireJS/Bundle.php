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
use Foomo\JS;

class Bundle
{
	private $scripts = array();
	private $foomoScripts = array();
	private $scriptsVar = array();
	private $dirs = array();
	/**
	 * @var boolean
	 */
	private $compress = true;
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
	 * @return Bundle
	 */
	public static function create($module, $name, $version)
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
	 * @return Bundle
	 */
	public function addVarScripts($module, array $scripts)
	{
		return $this->addScriptsToTargetArray($this->scriptsVar, $module, $scripts);
	}

	/**
	 * add a Foomo.JS style script, which supports js includes
	 *
	 * @param string $module
	 * @param string $script
	 *
	 * @return $this
	 */
	public function addFoomoJSScripts($module, $scripts)
	{
		if(!isset($this->foomoScripts[$module])) {
			$this->foomoScripts[$module] = array();
		}
		foreach($scripts as $script) {
			$this->foomoScripts[$module][] = $script;
		}
		return $this;
	}
	/**
	 * add scripts
	 *
	 * @param string $module
	 * @param string[] $scripts relative paths - keep the right order!
	 *
	 * @return Bundle
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
	 * @return \Bundle
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
	 * @return Bundle
	 */
	public function addRequireJSDirs($module, array $dirs)
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
	 * @return Bundle
	 */
	public function debug($debug = true)
	{
		$this->debug = $debug;
		return $this;
	}
	/**
	 * turn on uglifying
	 *
	 * @param boolean $compress
	 *
	 * @return Bundle
	 */
	public function compress($compress = true)
	{
		$this->compress = $compress;
		return $this;
	}
	/**
	 * turn on watching
	 *
	 * @param boolean $watch
	 *
	 * @return Bundle
	 */
	public function watch($watch = true)
	{
		$this->watch = $watch;
		return $this;
	}

	/**
	 * link the bundle to a HTMLDocument
	 *
	 * @param \Foomo\HTMLDocument $doc
	 *
	 * @return Bundle
	 */
	public function linkToDoc(\Foomo\HTMLDocument $doc = null)
	{
		if(is_null($doc)) {
			$doc = \Foomo\HTMLDocument::getInstance();
		}
		if($this->debug) {
			$scripts = array();
			foreach($this->foomoScripts as $module => $foomoScripts) {
				foreach($foomoScripts as $script) {
					$scripts[] = JS::create(
							\Foomo\Config::getHtdocsDir($module) . DIRECTORY_SEPARATOR . $script
						)
						->watch(true)
						->compress(false)
						->compile()
						->getOutputPath()
					;
				}
			}
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
			$this->check($this->watch, $this->compress);
			$doc->addJavascripts(array($this->getEndpoint()));
		}
		return $this;
	}
	private function getEndpoint()
	{
		return Module::getHtdocsVarBuildPath() . '/' . basename($this->getCompiledFilename());
	}
	private function getCompiledFilename()
	{
		return 
			Module::getHtdocsVarDir() . DIRECTORY_SEPARATOR . 
			$this->module . '-' . $this->name . '-' . $this->version . 
			($this->compress?'.minified':'') . '.js'
		;
	}

	/**
	 * serve the bundle - use this in the endpoint
	 *
	 * @param boolean $watch watch for changes in the bundle
	 * @param boolean $compress compress the javascript - uglifyjs needs to be available on the command line to the server
	 */
	private function check($watch, $compress)
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
				file_put_contents($compiledFilename, $this->getJS($compress));
			}
		}
	}
	private function sayIAmWorkingOnIt($compiledFilename)
	{
		file_put_contents($compiledFilename, '// hang on - i am working on it ' . date('Y-m-d H:i:s'));
	}
	/**
	 *
	 * @param boolean $compress
	 *
	 * @return string
	 */
	private function getJS($compress = false)
	{
		$js = '// app bundle in module ' . $this->module . ' ' . $this->name . PHP_EOL;
		foreach($this->foomoScripts as $module => $scripts) {
			foreach($scripts as $script) {
				$js .= file_get_contents(
					JS::create(
						\Foomo\Config::getHtdocsDir($module) . DIRECTORY_SEPARATOR . $script
					)
						->watch(true)
						->compress($compress)
						->compile()
						->getOutputFilename()
				);
			}
		}
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
				$js .= PHP_EOL . '// templates in: ' . basename($dir) . PHP_EOL . R::concatHTMLTemplateFiles(self::getHtdocsdDir($module) . DIRECTORY_SEPARATOR . $dir) . PHP_EOL;
			}
		}
		return $this->compile($js, $compress);
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
		if(file_exists($filename)) {
			return $this->higher($lastmod, filemtime($filename));
		} else {
			trigger_error('missing file in bundle', E_USER_ERROR);
		}
	}
	private function compile($js, $compress)
	{
		$bundledFilename = tempnam(Module::getVarDir(), 'bundle-compile');
		file_put_contents($bundledFilename, '// bundled ' . date('Y-m-d H:i:s', time()) . PHP_EOL . PHP_EOL . $js);
		$compiledFilename = JS::create($bundledFilename)->compress($compress)->compile()->getOutputFilename();
		$compiled = file_get_contents($compiledFilename);
		unlink($compiledFilename);
		unlink($bundledFilename);
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