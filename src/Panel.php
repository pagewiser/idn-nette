<?php

namespace Pagewiser\Idn\Nette;

use Nette;
use Tracy;

class Panel extends Nette\Object implements Tracy\IBarPanel
{

	/** @var \SystemContainer */
	protected $container;

	/** @var \Pagewiser\Idn\Nette\Panel */
	protected $idnApi;

	/** @var array */
	private $calls = array();


	protected function onCurlCall()
	{
		$this->calls[] = array(
			'start_time' => microtime(true),
			'params' => func_get_args(),
		);
	}


	protected function onCurlFinished($json)
	{
		end($this->calls);
		$key = key($this->calls);
		reset($this->calls);

		$this->calls[$key]['time'] = microtime(true) - $this->actualCall['time'],
		$this->calls[$key]['response'] = $json;
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
		$count = count($this->calls);
		foreach ($this->calls as $event) {
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
		$h = 'htmlSpecialChars';
		foreach ($this->events as $event) {
			$totalTime += $event->time;
			$explain = NULL; // EXPLAIN is called here to work SELECT FOUND_ROWS()
			if ($this->explain && $event->type === \DibiEvent::SELECT) {
				try {
					$backup = array($event->connection->onEvent, dibi::$numOfQueries, dibi::$totalTime);
					$event->connection->onEvent = NULL;
					$cmd = is_string($this->explain) ? $this->explain : ($event->connection->getConfig('driver') === 'oracle' ? 'EXPLAIN PLAN' : 'EXPLAIN');
					$explain = dibi::dump($event->connection->nativeQuery("$cmd $event->sql"), TRUE);
				} catch (\DibiException $e) {}
				list($event->connection->onEvent, dibi::$numOfQueries, dibi::$totalTime) = $backup;
			}

			$s .= '<tr><td>' . sprintf('%0.3f', $event->time * 1000);
			if ($explain) {
				static $counter;
				$counter++;
				$s .= "<br /><a href='#tracy-debug-DibiProfiler-row-$counter' class='tracy-toggle tracy-collapsed' rel='#tracy-debug-DibiProfiler-row-$counter'>explain</a>";
			}

			$s .= '</td><td class="tracy-DibiProfiler-sql">' . dibi::dump(strlen($event->sql) > self::$maxLength ? substr($event->sql, 0, self::$maxLength) . '...' : $event->sql, TRUE);
			if ($explain) {
				$s .= "<div id='tracy-debug-DibiProfiler-row-$counter' class='tracy-collapsed'>{$explain}</div>";
			}
			if ($event->source) {
				$s .= Tracy\Helpers::editorLink($event->source[0], $event->source[1]);//->class('tracy-DibiProfiler-source');
			}

			$s .= "</td><td>{$event->count}</td><td>{$h($event->connection->getConfig('driver') . '/' . $event->connection->getConfig('name'))}</td></tr>";
		}

		return empty($this->events) ? '' :
			'<style> #tracy-debug td.tracy-DibiProfiler-sql { background: white !important }
			#tracy-debug .tracy-DibiProfiler-source { color: #999 !important }
			#tracy-debug tracy-DibiProfiler tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>
			<h1>Queries: ' . count($this->events) . ($totalTime === NULL ? '' : sprintf(', time: %0.3f ms', $totalTime * 1000)) . '</h1>
			<div class="tracy-inner tracy-DibiProfiler">
			<table>
				<tr><th>Time&nbsp;ms</th><th>SQL Statement</th><th>Rows</th><th>Connection</th></tr>' . $s . '
			</table>
			</div>';
	}


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
		Tracy\Debugger::getBlueScreen()->addPanel(array(__CLASS__, 'renderException'));

		$idnApi->onCurlCall[] = function () {
			GLOBAL $timeStart;
			$timeStart = microtime(true);
			echo '<b>Parameters:</b> '.var_export(func_get_args(), TRUE)."<br>";
		};

		$idnApi->onCurlFinished[] = function ($json) {
			GLOBAL $timeStart;
			$timeStop = microtime(true);
			echo '<b>Response:</b> '.var_export($json, TRUE)."<br>";
			echo '<b>Took:</b> '.(microtime(true) - $timeStart)."<br>";
		};

		$idnApi->onCurlFailed[] = function ($result) {
			$file = 'error.'.rand(1,1000).'.html';
			echo '<b>Stored error information to '.$file.'</b><br>';
			file_put_contents($file, $result);
		};
	}


}
