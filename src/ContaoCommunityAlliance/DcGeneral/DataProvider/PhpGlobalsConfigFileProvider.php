<?php

/**
 * PHP version 5
 * @package    generalDriver
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace ContaoCommunityAlliance\DcGeneral\DataProvider;

use ContaoCommunityAlliance\DcGeneral\Data\ConfigInterface;
use ContaoCommunityAlliance\DcGeneral\Data\DefaultModel;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\Data\NoOpDataProvider;
use ContaoCommunityAlliance\DcGeneral\Exception\DcGeneralRuntimeException;

/**
 * General purpose file reader and writer.
 */
class PhpGlobalsConfigFileProvider extends NoOpDataProvider
{
	/**
	 * Save only the differences, compared to defaults.
	 */
	const MODE_DIFF = 'diff';

	/**
	 * Save all properties.
	 */
	const MODE_ALL = 'all';

	/**
	 * Relative pathname of the file.
	 *
	 * @var string
	 */
	protected $fileName;

	/**
	 * The namespace inside of the $GLOBALS array.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Pattern for valid property names.
	 *
	 * @var string
	 */
	protected $pattern = '*';

	/**
	 * Default values.
	 *
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * The saving mode.
	 *
	 * @var string
	 */
	protected $mode = self::MODE_DIFF;

	/**
	 * The model instance.
	 *
	 * @var ModelInterface
	 */
	protected $model;

	public function getFileName()
	{
		return $this->fileName;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setBaseConfig(array $config)
	{
		parent::setBaseConfig($config);

		// Check Vars
		if (!$config["source"]) {
			throw new DcGeneralRuntimeException("Missing file name.");
		}

		$this->fileName = $config["source"];

		if (isset($config['namespace'])) {
			$this->namespace = (string) $config['namespace'];
		}
		if (isset($config['pattern'])) {
			$this->pattern = (string) $config['pattern'];
		}
		if (isset($config['mode'])) {
			$this->mode = (string) $config['mode'];
		}

		if ($this->mode == self::MODE_DIFF) {
			$this->defaults = $this->resolveValues($GLOBALS);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function fetch(ConfigInterface $objConfig)
	{
		return $this->getEmptyModel();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEmptyModel()
	{
		if ($this->model === null) {
			$this->model = parent::getEmptyModel();
			$this->model->setId(1);

			if (file_exists($this->fileName))
			{
				if ($this->mode == self::MODE_DIFF) {
					require_once $this->fileName;
				}
				else {
					require $this->fileName;
				}
			}

			$values = $this->resolveValues($GLOBALS);
			$this->model->setPropertiesAsArray($values);
		}

		return $this->model;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save(ModelInterface $model)
	{
		$datetime = date('r');

		$buffer = <<<EOF
<?php

// updated at: $datetime

EOF;

		$path = $this->parseNamespace();
		$pattern = $this->pattern;

		$var = PHP_EOL . '$GLOBALS';
		$target = &$GLOBALS;

		foreach ($path as $key) {
			$var .= sprintf('[%s]', var_export($key, true));

			if (!isset($target[$key]) || !is_array($target[$key])) {
				$target[$key] = array();
			}

			$target = &$target[$key];
		}
		$var .= '[%s] = %s;';

		$properties = $model->getPropertiesAsArray();
		foreach ($properties as $property => $value) {
			if (fnmatch($pattern, $property)) {
				if (
					$this->mode == self::MODE_ALL
					|| !array_key_exists($property, $this->defaults)
					&& $value !== null
					|| $this->defaults[$property] != $value
				) {
					$buffer .= sprintf($var, var_export($property, true), var_export($value, true));
				}

				$target[$property] = $value;
			}
		}

		$buffer .= PHP_EOL;

		file_put_contents($this->fileName, $buffer);
	}

	/**
	 * {@inheritdoc}
	 */
	public function fieldExists($strField)
	{
		return true;
	}

	protected function parseNamespace()
	{
		$path = (string) $this->namespace;
		$path = explode('/', $path);
		$path = array_map('trim', $path);
		$path = array_filter($path);

		return $path;
	}

	/**
	 * Resolve the value for a property from source array.
	 *
	 * @param string $propertyName
	 * @param array  $source
	 */
	protected function resolveValues(array &$source)
	{
		$path    = $this->parseNamespace();
		$pattern = $this->pattern;

		foreach ($path as $key) {
			if (is_array($source) && array_key_exists($key, $source)) {
				$source = &$source[$key];
			}
			else {
				return null;
			}
		}

		if ($pattern != '*') {
			$values = array();

			foreach ($source as $key => $value) {
				if (fnmatch($pattern, $key)) {
					$values[$key] = $value;
				}
			}
		}
		else {
			$values = array_merge($source);
		}

		return $values;
	}
}
