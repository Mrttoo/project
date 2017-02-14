<?php
namespace AppBundle\Controller;

//include 'fileupload.php';
use \Dropbox as dbx;
class DbxUpload extends FileUpload
{
	private $client;

	private $path;

	function setPath($path)
	{
		$this->path = $path;
	}

	function uploadFiles()
	{
		foreach ($this->files as $name => $file) {
			foreach ($file as $index => $path) {
				$fileStream = fopen($path, 'rb');
				$createdFile = $this->client->uploadFile("/surivo/".$name, dbx\WriteMode::add(), $fileStream);
			}
		}
	}

	function __construct($accessToken, $clientId)
	{
		$this->client = new dbx\Client($accessToken, $clientId);
	}

}