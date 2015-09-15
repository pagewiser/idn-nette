<?php
namespace Pagewiser\Idn\Nette\Bridges\Nette;

use Nette;


class Nette23Extension extends Nette\DI\CompilerExtension
{

	public $databaseDefaults = array(
		'apiHost' => 'http://api.idn.pwr/',
		'imageHost' => '//storage.idn.pwr/',
		'lazy' => TRUE,
		'profiler' => FALSE,
		'dirAliases' => array(),
	);


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig();

		$config = array_merge($this->databaseDefaults, $config);

		$useProfiler = isset($config['profiler'])
			? $config['profiler']
			: class_exists('Tracy\Debugger') && $container->parameters['debugMode'];

		unset($config['profiler']);

		$connection = $container->addDefinition($this->prefix('connection'))
			->setClass('Pagewiser\Idn\Nette\Api', array($config['apiKey'], $config['apiSecret']))
			->addSetup('setApiUrl', array($config['apiHost']))
			->addSetup('setImageUrl', array($config['imageHost']));

		if (!empty($config['client']))
		{
			$connection->addSetup('loginClient', array($config['client']));
		}

		if (!empty($config['dirAliases']))
		{
			$connection->addSetup('setDirAliases', array($config['dirAliases']));
		}

		if ($useProfiler) {
			$panel = $container->addDefinition($this->prefix('panel'))
				->setClass('Pagewiser\Idn\Nette\Bridges\Tracy\Panel');
			$connection->addSetup(array($panel, 'register'), array($connection));
		}
	}


}
