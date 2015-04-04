<?php

namespace Pagewiser\Idn\Nette;

use Latte\MacroNode;
use Latte\PhpWriter;
use Latte\Compiler;


class IdnMacros extends \Latte\Macros\MacroSet
{


	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);

		$me->addMacro('idn', array($me, 'imageLink'));
	}


	/**
	 * Get IDN image url
	 *
	 * {idn destination [,] [params]}
	 *
	 * @param \Nette\Latte\MacroNode $node Macro node
	 * @param \Nette\Latte\PhpWriter $writer PHP Writer
	 *
	 * @return string
	 */
	public function imageLink(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('echo %escape(%modify($_presenter->getContext()->getByType(\'Pagewiser\Idn\Nette\Api\')->latteImage(%node.word, %node.array?)))');

		return $writer->write('echo %escape(%modify(' . ($node->name === 'plink' ? '$_presenter' : '$_control') . '->link(%node.word, %node.array?)))');
		return $writer->write('if ($_l->tmp = array_filter(%node.array)) echo \' id="\' . %escape(implode(" ", array_unique($_l->tmp))) . \'"\'');
	}


}