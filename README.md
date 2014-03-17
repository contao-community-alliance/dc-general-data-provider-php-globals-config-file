PHP GLOBALS configuration file provider
=======================================

This data provider for the [dc-general](https://github.com/contao-community-alliance/dc-general) load and store the
data in the PHP `$GLOBALS` array, using a single php file.

DCA Usage example
-----------------

```php
use ContaoCommunityAlliance\DcGeneral\DataProvider\PhpGlobalsConfigFileProvider;

$GLOBALS['TL_DCA']['my_dca'] = array
(
	'config' => array(
		'dataContainer' => 'General',
	),
	'dca_config'   => array
	(
		'data_provider' => array
		(
			'default' => array
			(
				'class' => 'ContaoCommunityAlliance\DcGeneral\DataProvider\PhpGlobalsConfigFileProvider',

				/**
				 * The source filename.
				 */
				'source' => 'path/to/the/config.php',

				/**
				 * The namespace definition (default=null, optional).
				 */
				'namespace' => 'my/dca',

				/**
				 * The property key name pattern (default="*", optional).
				 */
				'pattern' => 'my_*',

				/**
				 * Save mode (default="diff", optional)
				 */
				'mode' => PhpGlobalsConfigFileProvider::MODE_ALL,
			)
		),
	),
);
```

Output example
--------------

The produces file look like this:

```php
<?php

// updated at: Mon, 17 Mar 2014 17:56:24 +0100

$GLOBALS['my']['dca']['my_field1'] = 'value 1';
$GLOBALS['my']['dca']['my_field2'] = 'value 2';
$GLOBALS['my']['dca']['my_field3'] = 'value 3';
```

Namespace and key name patterns
-------------------------------

Compromise the `$GLOBALS` array directly is no good idea at all. You can use a custom namespace, which define a
path inside of the `$GLOBALS` array. Each namespace part is separated by `/`, e.g. `my/dca` which will be
decoded to `$GLOBALS['my']['dca']`.

Also you may want to filter properties by their name, this is useful if the provider share a namespace with
another php globals config file provider. The pattern allow you to filter accepted property names.
For example `my_*` will only accept and store properties that starts with `my_`. Wildcards are allowed through
[fnmatch](http://de1.php.net/fnmatch).

Save mode
---------

The data provider has two generation modes.

In **diff** mode it will read the state *before* including the config file. This state is supposed as the "default"
state. When generating the php file, only values that differ from the "default" view will be included.

In **all** mode all properties will be stored, regardless of the previous state.

As developer you must respect, that the **diff** mode only work, if you **not** include the php file by yourself.
The php file will be automatically loaded through `require` in the loading process. In **all** mode the php will
also be loaded, by through `require_once`.
