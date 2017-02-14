<?php

namespace AppBundle\Controller;

abstract class FileUpload 
{

	protected $files;



	/**
	 * Get files submitted through POST form and save 
	 * temporary path of files inside $files variable
	 * @return [type] [description]
	 */
	function getFiles()
	{
		if(isset($_POST['submit'])){
			if(count($_FILES['upload']['name']) > 0){
	        	//Loop through each file
				for($i=0; $i<count($_FILES['upload']['name']); $i++) {
	         	 	//Get the temp file path
					$tmpFilePath = $_FILES['upload']['tmp_name'][$i];
	            	//Make sure we have a filepath
					if($tmpFilePath != ""){
	               		//save the filename
						$shortname = $_FILES['upload']['name'][$i];
						$this->files[$shortname][] = $tmpFilePath;
					}
				}
			}
		}
	}

	abstract function uploadFiles();

}