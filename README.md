# Nette IDN extension

To register this extension into the Nette, add the latte macro extension to your config file.

**Nette 2.2.x**

	services:
		nette.latteFactory:
			setup:
				- Pagewiser\Idn\Nette\IdnMacros::install(::$service->getCompiler())

Then you need to configure your IDN account and API class.

	idn:
		class: Marten\NetteIdn\Api('username', 'password')
