<?php
namespace AppBundle\Controller;



//include 'fileupload.php';

class GoogleUpload extends FileUpload
{

	private $service;

	function __construct($client)
	{
		$this->service = new \Google_Service_Drive($client);
		var_dump($client->getAccessToken());
	}
	function uploadFiles()
	{
		foreach ($this->files as $name => $file) {
			foreach ($file as $index => $path) {
				$this->insertFile($name,  $parentID = null, $path);
					
			}
		}
		
	}

	/**
	 * Inserts new file into google drive
	 * @param  Google_Service_Drive $service   
	 * @param  string $name      
	 * @param  int $parentId  
	 * @param  string $fileStream 
	 */
	function insertFile($name, $parentId, $path) {
		$file = new \Google_Service_Drive_DriveFile();
		$file->setName($name);
		try{
			$parentId = $this->getApplicationFolderId();
		}
		catch(Exception $e){
			print $e->getMessage();
		}
		if ($parentId != null) {
			var_dump($parentId);
			$file->setParents(array($parentId));
		}

		try {
			$data = file_get_contents($path);
			print("<pre>".print_r($file,true)."</pre>");
			$createdFile = $this->service->files->create($file, array(
				'data' => $data,
				));
			
			return $createdFile;
		} catch (Exception $e) {
			print "An error occurred: " . $e->getMessage(); // redirect na ziskanie noveho tokenu
		}
	}

	function getApplicationFolderId()
	{
	
		$file = $this->service->files->listFiles(array('q' => "name = 'Survio' and mimeType = 'application/vnd.google-apps.folder'")); //define name
		if(empty($file['modelData']['files']))
		{
			$file = $this->createNewFolder();
		}
		//print("<pre>".print_r($file,true)."</pre>");
		//var_dump($file);
		
		return $file->current("modelData")->id;
	}

	function createNewFolder()
	{
		$fileMetadata = new \Google_Service_Drive_DriveFile(array(
  				'name' => 'Survio',				//define name
  				'mimeType' => 'application/vnd.google-apps.folder'));
		$file = $this->service->files->create($fileMetadata, array(
  				'fields' => 'id'));
		return $file;
	}
}