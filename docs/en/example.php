<?php

// create a bundle
// putting it for example on your Module class is a good idea
$bundle = Foomo\RequireJS\Bundle::create(
		// module name
		My\Module::NAME, 
		// name of the bindle
		'example',
		// use versions - they make deployments of new app versions
		// cache and proxy proof
		'0.0.1a'
	)
	->addScripts(
		My\Module::NAME, // or another modules name
		array(
			// the order of files is relevant !!!
			'relative/path/to/js-1.js',
			'relative/path/to/js-2.js'
		)
	)
	// add RequireJS modules in a directory
	// this will be scanned recursively
	// RequireJS is not bundled - add it yourself with addScripts()
	->addRequireJSDirs(My\Module::NAME, array('js/app'))
	// every single js file is linked to the html document 
	// - this is how you want at dev time
	->debug()
	// only relevant in non debug mode - do not use in production
	// check if there was any changes in your js
	// use this if you want to debug the concatenated js
	->watch()
	// enabled by default, but not active in debug mode
	// disable for debugging purposes
	->compress()
;

// use it
$bundle->linkToDoc();