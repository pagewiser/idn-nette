# Nette IDN extension

IDN extension for Nette. Support uploading and image manipulation, adds macro for image handling into latte.

## Install

To register this extension into the Nette, add the latte macro extension to your config file.

**Nette 2.3.x**

    extensions:
        idn: Pagewiser\Idn\Nette\Bridges\Nette\Nette23Extension

    nette:
        latte:
            macros:
                - Pagewiser\Idn\Nette\IdnMacros::install

    idn:
        apiHost: 'http://idnapi.pwlab.tk/'
        apiKey: 'your-api-key'
        apiSecret: 'your-api-secret'
        imageHost: '//storage.pwlab.tk/'
        lazy: TRUE
        client: 'client-name'
        profiler: TRUE
        dirAliases:
            profilePhoto: 'user/photo'


**Nette 2.2.x**

	services:
		nette.latteFactory:
			setup:
				- Pagewiser\Idn\Nette\IdnMacros::install(::$service->getCompiler())

Then you need to configure your IDN account and API class.

	idn:
		class: Marten\NetteIdn\Api('username', 'password')

## Basic usage

### Image uploading

Presenter

    /**
     * @var \Pagewiser\Idn\Nette\Api $idnApi
     * @inject
     */
    public $idnApi

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        try
        {
            $uploadedFile = $this->idnApi->upload('photos/'.$values['id'].'.jpg', $values['file']->getTemporaryFile());

            if ($uploadedFile['status'] !== 'success')
            {
                // Rollback
            }

            // Commit
        }
        catch (\Api\Exception\ApiException $e)
        {
            $form->addError($e->getMessage());
        }
    }

If you reupload image with the same name, IDN server will clear the cache for this image automatically. It's still good practice to use generated filename, because it will handle local cache in browsers.

### Image display

Latte

    <img src="{idn 'photos', $job['logo'], '80x80'}">

Latte macro has 4 parameters.

* Directory where the image is stored
* Name of the file
* Required size
* Tranfromation options:
** f - Fill size with the image. Image can be bigger than provided size.
** e - Exact size of the image. Image will be stretched.
** c - Exact size of the image. Image will be cropped.
** b - Exact size of the image. Image will be resized to fit in the size, background will be added.
** a - Face detection. Image will be resized to required size, will be zoomed to face on the image.

### Deleting image

        try
        {
            try
            {
                $this->idnApi->delete('photos/', $photoName);
            }
            catch (\Pagewiser\Idn\Client\FileNotFoundException $ex)
            {
                // File not found, was already deleted?
            }
        }
        catch (\Pagewiser\Idn\Client\OperationException $ex)
        {
            $this->flashMessage($ex->getMessage(), 'warning');
        }
