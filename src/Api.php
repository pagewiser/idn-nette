<?php

namespace Pagewiser\Idn\Nette;

class Api extends \Pagewiser\Idn\Client\Api
{


	public function latteImage($path, $args)
	{
		return call_user_func_array(array($this, 'image'), array_merge(array($path), $args));
	}


}
