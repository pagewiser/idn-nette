<?php

namespace Pagewiser\Idn\Nette\Bridges\Tracy;

use Nette;
use Tracy;

class Panel extends Nette\Object implements Tracy\IBarPanel
{

	/** @var \SystemContainer */
	protected $container;

	/** @var \Pagewiser\Idn\Nette\Api */
	protected $idnApi;

	/** @var array */
	private $events = array();


	/**
	 * Register this panel
	 *
	 * @param \Pagewiser\Idn\Nette\Api $idnApi IDN Api
	 * @param int $layout Layout type
	 * @param int $height Popup height
	 *
	 * @return NULL
	 */
	public function register(\Pagewiser\Idn\Nette\Api $idnApi, $layout = NULL, $height = NULL)
	{
		$this->idnApi = $idnApi;

		Tracy\Debugger::getBar()->addPanel($this);

		$idnApi->onCurlCall[] = array($this, 'onCurlCall');

		$idnApi->onCurlFinished[] = array($this, 'onCurlFinished');

		$idnApi->onCurlFailed[] = array($this, 'onCurlFailed');
	}


	public function onCurlCall()
	{
		$this->events[] = array(
			'start_time' => microtime(true),
			'params' => func_get_args(),
		);
	}


	public function onCurlFinished($json)
	{
		end($this->events);
		$key = key($this->events);
		reset($this->events);

		$this->events[$key]['time'] = microtime(true) - $this->events[$key]['start_time'];
		$this->events[$key]['response'] = $json;
	}


	public function onCurlFailed($response)
	{
		var_dump($response);exit;
	}


	/**
	 * Return's panel ID.
	 * @return string
	 */
	public function getId()
	{
		return 'idnapi-panel';
	}


	/**
	 * Returns the code for the panel tab.
	 * @return string
	 */
	public function getTab()
	{
		$totalTime = 0;
		$count = count($this->events);
		foreach ($this->events as $event) {
			$totalTime += $event['time'];
		}
		return '<span title="idnapi"><svg viewBox="0 0 2048 2048" style="vertical-align: bottom; width:1.23em; height:1.55em"><path fill="' . ( $count ? '#b079d6' : '#aaa') . '" d="M1024 896q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-384q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-1152q208 0 385 34.5t280 93.5 103 128v128q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-128q0-69 103-128t280-93.5 385-34.5z"/></svg><span class="tracy-label">'
		. $count . ' calls'
		. ($totalTime ? sprintf(' / %0.1f ms', $totalTime * 1000) : '')
		. '</span></span>';
	}


	/**
	 * Returns the code for the panel
	 *
	 * @return string Panel HTML
	 */
	public function getPanel()
	{
		$totalTime = $s = NULL;
		foreach ($this->events as $event) {
			$totalTime += $event['time'];
			$s .= '<tr><td>' . sprintf('%0.3f', $event['time'] * 1000);
			$s .= '</td><td class="tracy-IdnApiProfiler-sql">' . var_export($event['params'], TRUE);
			$s .= '</td><td>' . var_export($event['response'], TRUE) . '</td></tr>';
		}

		return empty($this->events) ? '' :
			'<style> #tracy-debug td.tracy-IdnApiProfiler-sql { background: white !important }
			#tracy-debug .tracy-IdnApiProfiler-source { color: #999 !important }
			#tracy-debug tracy-IdnApiProfiler tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>
			<h1>Queries: ' . count($this->events) . ($totalTime === NULL ? '' : sprintf(', time: %0.3f ms', $totalTime * 1000)) . '</h1>
			<div class="tracy-inner tracy-DibiProfiler">
			<table>
				<tr><th>Time&nbsp;ms</th><th>Params</th><th>Response</th></tr>' . $s . '
			</table>
			</div>';
	}


}
