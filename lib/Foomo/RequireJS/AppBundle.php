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
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author Jan Halfar jan@bestbytes.com
 */
class AppBundle
{
	private $scripts = array();
	private $dirs = array();
	private $module;
	private $endPoint;
    //---------------------------------------------------------------------------------------------
    // ~ Constructor
    //---------------------------------------------------------------------------------------------
 
    /**
	 *
	 */
    private function __construct()
    {
    }
	/**
	 * 
	 * @return Foomo\RequireJS\AppBundle
	 */
	public function create($module)
	{
		$ret = new self;
		$ret->module = $module;
		return $ret;
	}
	public function endPoint($endPoint)
	{
		$this->endPoint = $endPoint;
		return $this;
	}
	/**
	 * 
	 * @return Foomo\RequireJS\AppBundle
	 */
	public function addScripts($module, array $scripts)
	{
		if(!isset($this->scripts[$module])) {
			$this->scripts[$module] = array();
		}
		$this->scripts[$module] = array_merge($this->scripts[$module], $scripts);
		return $this;
	}
	/**
	 * @param string[] $dirs directories to scan for js files in
	 * 
	 * @return Foomo\RequireJS\AppBundle
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
	 * 
	 * @param \Foomo\HTMLDocument $doc
	 * 
	 * @return Foomo\RequireJS\AppBundle
	 */
	public function linkToDoc($debug = false, \Foomo\HTMLDocument $doc = null)
	{
		if(is_null($doc)) {
			$doc = \Foomo\HTMLDocument::getInstance();
		}
		if($debug) {
			$scripts = array();
			foreach ($this->scripts as $module => $moduleScripts) {
				foreach($moduleScripts as $moduleScript) {
					$scripts[] = self::getRootHttp($module) . '/' . $moduleScript;
				}
			}
			foreach($this->dirs as $module => $dirs) {
				$htdocsDir = self::getHtdocsdDir($module);
				foreach($dirs as $dir) {
					$jsFiles = R::getJSFiles($htdocsDir . DIRECTORY_SEPARATOR . $dir);
					foreach($jsFiles as $jsFile) {
						$relativePath = implode('/', explode(DIRECTORY_SEPARATOR, substr($jsFile, strlen($htdocsDir) + 1)));
						$scripts[] =  self::getRootHttp($module) . '/' . $relativePath;
					}
				}
			}
			if(count($scripts) > 0) {
				$doc->addJavascripts($scripts);			
			}
		} else {
			$doc->addJavascripts(array(self::getRootHttp($this->module) . '/' . $this->endPoint));
		}
		return $this;
	}
	private function getCompiledFilename()
	{
		return Module::getVarDir() . DIRECTORY_SEPARATOR . $this->module . '-' . md5(serialize($this)) . '-uglified.css';
	}
	public function serve($watch = false, $uglify = true)
	{
		header('Content-Type: application/javascript');
		$lastModUglified = 0;
		$compiledFilename = $this->getCompiledFilename();
		$compiledExists = file_exists($compiledFilename);
		if($compiledExists) {
			$lastModUglified = filemtime($compiledFilename);
		}
		if(!$compiledExists || $watch) {
			if($compiledExists) {
				$lastmod = $this->getLastmod();
			} else {
				$lastmod = 0;
			}
			if(!$compiledExists || $lastmod > $lastModUglified) {
				$this->save($compiledFilename, $this->getJS(), $uglify);
			}
		}
		echo file_get_contents($compiledFilename);
	}
	private function getJS()
	{
		$js = '// app bundle in module ' . $this->module . PHP_EOL;
		foreach($this->scripts as $module => $jsPaths) {
			$js .= PHP_EOL . '// scripts from module ' . $module . PHP_EOL . PHP_EOL;
			$htdocsDir = self::getHtdocsdDir($module);
			foreach($jsPaths as $jsPath) {
				$js .= PHP_EOL . '// ' . $jsPath . PHP_EOL . PHP_EOL;
				$jsFile = $htdocsDir . DIRECTORY_SEPARATOR . $jsPath;
				$js .= file_get_contents($jsFile);
			}

		}
		foreach($this->dirs as $module => $dirs) {
			foreach($dirs as $dir) {
				$js .= R::concatJS(self::getHtdocsdDir($module) . DIRECTORY_SEPARATOR . $dir);
			}
		}
		return $js;
	}

	private function getLastmod()
	{
		$lastmod = 0;
		foreach($this->scripts as $module => $jsPaths) {
			$htdocsDir = self::getHtdocsdDir($module);
			foreach($jsPaths as $jsPath) {
				$jsFile = $htdocsDir . DIRECTORY_SEPARATOR . $jsPath;
				$lastmod = $this->lastMod($lastmod, $jsFile);
			}

		}
		foreach($this->dirs as $module => $dirs) {
			foreach($dirs as $dir) {
				$dirMtime = R::getLastmod(self::getHtdocsdDir($module) . DIRECTORY_SEPARATOR . $dir);
				$lastmod = ($dirMtime > $lastmod)?$dirMtime:$lastmod;
			}
		}
		return $lastmod;
		
	}
	private function lastMod($lastmod, $filename)
	{
		$mtime = filemtime($filename);
		return ($mtime > $lastmod)?$mtime:$lastmod;
	}
	private function save($compiledFilename, $js, $uglify)
	{
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
		
	}

	
	private static function getRootHttp($module)
	{
		return \Foomo\ROOT_HTTP . '/modules/' . $module;
	}
	private static function getHtdocsdDir($module)
	{
		return \Foomo\Config::getModuleDir($module) . DIRECTORY_SEPARATOR . 'htdocs';
	}
}