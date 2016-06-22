<?php
/**
 * Akeeba Engine
 * The modular PHP5 site backup engine
 *
 * @copyright Copyright (c)2006-2016 Nicholas K. Dionysopoulos
 * @license   GNU GPL version 3 or, at your option, any later version
 * @package   akeebaengine
 *
 */

namespace Akeeba\Engine\Util;

// Protection against direct access
defined('AKEEBAENGINE') or die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;

/**
 * Quirk detection helper class
 */
class ConfigurationCheck
{
	/**
	 * The configuration checks to perform
	 *
	 * @var  array
	 */
	protected $configurationChecks = array
	(
		array('code' => '001', 'severity' => 'critical', 'callback' => array(null, 'q001'), 'description' => 'Q001'),
		array('code' => '003', 'severity' => 'critical', 'callback' => array(null, 'q003'), 'description' => 'Q003'),
		array('code' => '004', 'severity' => 'critical', 'callback' => array(null, 'q004'), 'description' => 'Q004'),

		array('code' => '101', 'severity' => 'high', 'callback' => array(null, 'q101'), 'description' => 'Q101'),
		array('code' => '103', 'severity' => 'high', 'callback' => array(null, 'q103'), 'description' => 'Q103'),
		array('code' => '104', 'severity' => 'high', 'callback' => array(null, 'q104'), 'description' => 'Q104'),

		array('code' => '201', 'severity' => 'high', 'callback' => array(null, 'q201'), 'description' => 'Q201'),
		array('code' => '202', 'severity' => 'medium', 'callback' => array(null, 'q202'), 'description' => 'Q202'),
		array('code' => '204', 'severity' => 'medium', 'callback' => array(null, 'q204'), 'description' => 'Q204'),

		array('code' => '203', 'severity' => 'low', 'callback' => array(null, 'q203'), 'description' => 'Q203'),
		array('code' => '401', 'severity' => 'low', 'callback' => array(null, 'q401'), 'description' => 'Q401'),
	);

	/**
	 * The public constructor replaces the missing object reference in the configuration check callbacks
	 */
	function __construct()
	{
		$temp = array();

		foreach ($this->configurationChecks as $check)
		{
			$check['callback'] = array($this, $check['callback'][1]);
			$temp[] = $check;
		}

		$this->configurationChecks = $temp;
	}

	/**
	 * Returns the output & temporary folder writable status
	 *
	 * @return  array  A hash array with the writable status
	 */
	public function getFolderStatus()
	{
		static $status = null;

		if (is_null($status))
		{
			$stock_dirs = Platform::getInstance()->get_stock_directories();

			// Get output writable status
			$registry = Factory::getConfiguration();
			$outdir = $registry->get('akeeba.basic.output_directory');

			foreach ($stock_dirs as $macro => $replacement)
			{
				$outdir = str_replace($macro, $replacement, $outdir);
			}

			$status['output'] = @is_writable($outdir);
		}

		return $status;
	}

	/**
	 * Returns the overall status. It's true when both the temporary and output directories are writable and there are
	 * no critical configuration check failures.
	 *
	 * @return  boolean
	 */
	public function getShortStatus()
	{
		// Base the status on directory writeable status
		$status = $this->getFolderStatus();
		$ret = $status['output'];

		// Scan for high severity configuration check errors
		$detailedStatus = $this->getDetailedStatus();

		if (!empty($detailedStatus))
		{
			foreach ($detailedStatus as $configCheck)
			{
				if ($configCheck['severity'] == 'critical')
				{
					$ret = false;
				}
			}
		}

		// Return status
		return $ret;
	}

	/**
	 * Add a configuration check definition
	 *
	 * @param   string  $code         The configuration check code (three digit number)
	 * @param   string  $severity     The severity (low, medium, high, critical)
	 * @param   string  $description  The description key for this configuration check
	 * @param   null    $callback     The callback used to determine the status of the configuration check
	 *
	 * @return  void
	 */
	public function addConfigurationCheckDefinition($code, $severity = 'low', $description = null, $callback = null)
	{
		if (!is_callable($callback))
		{
			$callback = array($this, 'q' . $code);
		}

		if (empty($description))
		{
			$description = 'Q' . $code;
		}

		$newConfigurationCheck = array(
			'code'        => $code,
			'severity'    => $severity,
			'description' => $description,
			'callback'    => $callback,
		);

		$this->configurationChecks[$code] = $newConfigurationCheck;
	}

	/**
	 * Remove a configuration check definition
	 *
	 * @param   string $code The code of the configuration check to remove
	 *
	 * @return  void
	 */
	public function removeConfigurationCheckDefinition($code)
	{
		if (isset($this->configurationChecks[$code]))
		{
			unset($this->configurationChecks[$code]);
		}
	}

	/**
	 * Clear the configuration check definitions
	 *
	 * @return  void
	 */
	public function clearConfigurationCheckDefinitions()
	{
		$this->configurationChecks = array();
	}

	/**
	 * Runs the configuration check scripts. These are potential problems related to server
	 * configuration, out of Akeeba's control. They are intended to give the user a
	 * chance to fix them before they cause the backup to fail.
	 *
	 * Numbering scheme:
	 * Q0xx    No-go errors
	 * Q1xx    Critical system configuration errors
	 * Q2xx    Medium and low system configuration warnings
	 * Q3xx    Critical software configuration errors
	 * Q4xx    Medium and low component configuration warnings
	 *
	 * @param   boolean  $low_priority       Should I include low priority quirks?
	 * @param   string   $help_url_template  The sprintf template from creating a help URL from a config check code
	 *
	 * @return  array
	 */
	public function getDetailedStatus($low_priority = false, $help_url_template = 'https://www.akeebabackup.com/documentation/warnings/q%s.html')
	{
		static $detailedStatus = null;

		if (is_null($detailedStatus))
		{
			$detailedStatus = array();

			foreach ($this->configurationChecks as $quirkDef)
			{
				if (!$low_priority && ($quirkDef['severity'] == 'low'))
				{
					continue;
				}

				$this->checkConfiguration($detailedStatus, $quirkDef, $help_url_template);
			}
		}

		return $detailedStatus;
	}

	/**
	 * Make a configuration check and adds it to the list if it raises a warning / error
	 *
	 * @param   array   $detailedStatus     The configuration checks status array
	 * @param   array   $quirkDef           The configuration check definition
	 * @param   string  $help_url_template  The sprintf template from creating a help URL from a quirk code
	 *
	 * @return  void
	 */
	protected function checkConfiguration(&$detailedStatus, $quirkDef, $help_url_template)
	{
		if (call_user_func($quirkDef['callback']))
		{
			$description = Platform::getInstance()->translate($quirkDef['description']);

			$detailedStatus[(string)$quirkDef['code']] = array(
				'code'        => $quirkDef['code'],
				'severity'    => $quirkDef['severity'],
				'description' => $description,
				'help_url'    => sprintf($help_url_template, $quirkDef['code']),
			);
		}
	}

	/**
	 * Q001 - HIGH - Output directory unwriteable
	 *
	 * @return  bool
	 */
	private function q001()
	{
		$status = $this->getFolderStatus();

		return !$status['output'];
	}

	/**
	 * Q003 - HIGH - Backup output or temporary set to site's root
	 *
	 * @return  bool
	 */
	private function q003()
	{
		$stock_dirs = Platform::getInstance()->get_stock_directories();

		$registry = Factory::getConfiguration();
		$outdir = $registry->get('akeeba.basic.output_directory');

		foreach ($stock_dirs as $macro => $replacement)
		{
			$outdir = str_replace($macro, $replacement, $outdir);
		}

		$outdir_real = @realpath($outdir);

		if (!empty($outdir_real))
		{
			$outdir = $outdir_real;
		}

		$siteroot = Platform::getInstance()->get_site_root();
		$siteroot_real = @realpath($siteroot);

		if (!empty($siteroot_real))
		{
			$siteroot = $siteroot_real;
		}

		return ($siteroot == $outdir);
	}

	/**
	 * Q004 - HIGH - Free memory too low
	 *
	 * @return bool
	 */
	private function q004()
	{
		// If we can't figure this out, don't report a problem. It doesn't
		// really matter, as the backup WILL crash eventually.
		if (!function_exists('ini_get'))
		{
			return false;
		}

		$memLimit = ini_get("memory_limit");
		$memLimit = $this->_return_bytes($memLimit);

		if ($memLimit <= 0)
		{
			return false;
		}

		// No limit?
		$availableRAM = $memLimit - memory_get_usage();

		// We need at least 12Mb of free memory
		return ($availableRAM <= (12 * 1024 * 1024));
	}

	/**
	 * Q101 - HIGH - open_basedir on output directory
	 *
	 * @return  bool
	 */
	private function q101()
	{
		$stock_dirs = Platform::getInstance()->get_stock_directories();

		// Get output writable status
		$registry = Factory::getConfiguration();
		$outdir = $registry->get('akeeba.basic.output_directory');

		foreach ($stock_dirs as $macro => $replacement)
		{
			$outdir = str_replace($macro, $replacement, $outdir);
		}

		return $this->checkOpenBasedirs($outdir);
	}

	/**
	 * Q103 - HIGH - Less than 10" of max_execution_time with PHP Safe Mode enabled
	 *
	 * @return  bool
	 */
	private function q103()
	{
		$exectime = ini_get('max_execution_time');
		$safemode = ini_get('safe_mode');

		if (!$safemode)
		{
			return false;
		}

		if (!is_numeric($exectime))
		{
			return false;
		}

		if ($exectime <= 0)
		{
			return false;
		}

		return $exectime < 10;
	}

	/**
	 * Q104 - HIGH - Temp directory is the same as the site's root
	 *
	 * @return  bool
	 */
	private function q104()
	{

		$siteroot = Platform::getInstance()->get_site_root();
		$siteroot_real = @realpath($siteroot);

		if (!empty($siteroot_real))
		{
			$siteroot = $siteroot_real;
		}

		$stockDirs = Platform::getInstance()->get_stock_directories();
		$temp_directory = $stockDirs['[SITETMP]'];
		$temp_directory = @realpath($temp_directory);

		if (empty($temp_directory))
		{
			$temp_directory = $siteroot;
		}

		return ($siteroot == $temp_directory);
	}

	/**
	 * Q201 - HIGH  - PHP 5.3.2 or lower detected
	 *
	 * @return  bool
	 */
	private function q201()
	{
		return version_compare(PHP_VERSION, '5.3.2', 'le');
	}

	/**
	 * Q202 - MED  - CRC problems with hash extension not present
	 *
	 * @return  bool
	 */
	private function q202()
	{
		$registry = Factory::getConfiguration();
		$archiver = $registry->get('akeeba.advanced.archiver_engine');

		if ($archiver != 'zip')
		{
			return false;
		}

		return !function_exists('hash_file');
	}

	/**
	 * Q203 - MED  - Default output directory in use
	 *
	 * @return  bool
	 */
	private function q203()
	{
		$stock_dirs = Platform::getInstance()->get_stock_directories();

		$registry = Factory::getConfiguration();
		$outdir = $registry->get('akeeba.basic.output_directory');

		foreach ($stock_dirs as $macro => $replacement)
		{
			$outdir = str_replace($macro, $replacement, $outdir);
		}

		$default = $stock_dirs['[DEFAULT_OUTPUT]'];

		$outdir = Factory::getFilesystemTools()->TranslateWinPath($outdir);
		$default = Factory::getFilesystemTools()->TranslateWinPath($default);

		return $outdir == $default;
	}

	/**
	 * Q204 - MED  - Disabled functions may affect operation
	 *
	 * @return  bool
	 */
	private function q204()
	{
		$disabled = ini_get('disabled_functions');

		return (!empty($disabled));
	}

	/**
	 * Q401 - LOW  - ZIP format selected
	 *
	 * @return  bool
	 */
	private function q401()
	{
		$registry = Factory::getConfiguration();
		$archiver = $registry->get('akeeba.advanced.archiver_engine');

		return $archiver == 'zip';
	}

	/**
	 * Checks if a path is restricted by open_basedirs
	 *
	 * @param   string  $check  The path to check
	 *
	 * @return  bool  True if the path is restricted (which is bad)
	 */
	public function checkOpenBasedirs($check)
	{
		static $paths;

		if (empty($paths))
		{
			$open_basedir = ini_get('open_basedir');

			if (empty($open_basedir))
			{
				return false;
			}

			$delimiter = strpos($open_basedir, ';') !== false ? ';' : ':';
			$paths_temp = explode($delimiter, $open_basedir);

			// Some open_basedirs are using environemtn variables
			$paths = array();

			foreach ($paths_temp as $path)
			{
				if (array_key_exists($path, $_ENV))
				{
					$paths[] = $_ENV[$path];
				}
				else
				{
					$paths[] = $path;
				}
			}
		}

		if (empty($paths))
		{
			return false; // no restrictions
		}
		else
		{
			$newcheck = @realpath($check); // Resolve symlinks, like PHP does

			if (!($newcheck === false))
			{
				$check = $newcheck;
			}

			$included = false;

			foreach ($paths as $path)
			{
				$newpath = @realpath($path);

				if (!($newpath === false))
				{
					$path = $newpath;
				}

				if (strlen($check) >= strlen($path))
				{
					// Only check if the path to check is longer than the inclusion path.
					// Otherwise, I guarantee it's not included!!
					// If the path to check begins with an inclusion path, it's permitted. Easy, huh?
					if (substr($check, 0, strlen($path)) == $path)
					{
						$included = true;
					}
				}
			}

			return !$included;
		}
	}

	private function _return_bytes($val)
	{
		$val = trim($val);
		$last = strtolower($val{strlen($val) - 1});

		switch ($last)
		{
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}
}