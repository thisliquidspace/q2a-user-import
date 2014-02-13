<?php

/*
	Question2Answer (c) 2011, Gideon Greenspan

	http://www.question2answer.org/


	File: qa-plugin/qa-user-import/qa-user-import.php
	Version: (see qa-plugin.php)
	Description: Module class for DB Backup plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_user_import {

	var $urltoroot;
	var $currentUrlDir;
	var $savedpath = "";
	var $error = "";
	var $msg = "";
	var $userimportDirUrl  ="";
	var $fileCount = 0;
	var $listedFiles = "";
	var $userimportDir = "";

	function load_module($directory, $urltoroot)
	{
		$this->urltoroot=$urltoroot;
		$this->currentUrlDir = $directory;
	}

	function option_default($option)
	{
		if ($option=='user_import_max_records') {
			return 5000;
		}
	}

	function admin_form(&$qa_content)
	{
		/*
		 * Add libraries
		 */

		require_once QA_INCLUDE_DIR.'qa-app-blobs.php';
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';

		/*
		 * Declare global variables
		 */

		global $qa_root_url_relative, $QA_CONST_PATH_MAP;

		/*
		 * Set up directory
		 */

		$this->userimportDirUrl = $qa_root_url_relative . "qa-content/userimport/";
		$this->userimportDir = QA_BASE_DIR."qa-content/userimport/";

		/* create dir if it doesn't exist */
		if (!is_dir($this->userimportDir))
			mkdir($this->userimportDir, 0755);

		/*
		 * File status
		 */

		$this->listedFiles = $this->listFiles();

		/**
		 * Events
		 */

		/*
		 * Delete All files
		 */

		if (qa_clicked('userimport_delete_files')) {
			$this->doDelete();
		}

		/*
		 * Upload file
		 */

		if (qa_clicked('userimport_send_upload_file')) {
			if (isset($_FILES["userimport_upload_file"])) {
				$this->uploadFile();
			}
		}//

		/*
		 * Test uploaded file
		 */

		if (qa_clicked('userimport_test_button')) {
			$this->doImport();
			}

		/*
		 * Import users
		 */

		if (qa_clicked('userimport_import_button')) {
			$this->doImport(TRUE);
			}

		/*
		 * Return HTML form for Admin panel/widget
		 */

		$this->listedFiles = $this->listFiles();

		return array(
		    'ok' => $this->error ? '<span style="color:#f00;">ERROR: '.$this->error.'</span>' : ($this->msg ? $this->msg : null),
		    'tags' => ' method="post" action="'.qa_admin_plugin_options_path(QA_PLUGIN_DIR . "qa-user-import/").'" enctype="multipart/form-data" ', // File upload form tags
		    'fields' => array(
				array(
					'label' => $this->listedFiles,
					'type' => 'static',
					'value' => '<input type="file" name="userimport_upload_file" onmouseout="this.className=\'qa-form-tall-button qa-form-tall-button-0\';" onmouseover="this.className=\'qa-form-tall-hover qa-form-tall-hover-0\';" class="qa-form-tall-button qa-form-tall-button-0" /> '.
							   '<input type="submit" value="Upload to server" name="userimport_send_upload_file" onmouseout="this.className=\'qa-form-tall-button qa-form-tall-button-0\';" onmouseover="this.className=\'qa-form-tall-hover qa-form-tall-hover-0\';" onclick="bck_t=\'\'" class="qa-form-tall-button qa-form-tall-button-0" /><br />'.
							   '<input type="submit" value="Delete all files" name="userimport_delete_files" onmouseout="this.className=\'qa-form-tall-button qa-form-tall-button-0\';" onmouseover="this.className=\'qa-form-tall-hover qa-form-tall-hover-0\';" onclick="bck_t=\'delete all files from a backup folder\'" class="qa-form-tall-button qa-form-tall-button-0" />',
					'tags' => 'NAME="userimport_importFile"',
				),
				array(
					'label' => '<span style="color:#f99; font-size:20px; text-align:center;">Caution! Ensure user CSV file is correctly formatted:<br />"&lt;email&gt;","&lt;password&gt;","&lt;username&gt;",',
					'type' => 'custom',
					'tags' => 'NAME="userimport_import_label"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Import selected file !',
					'tags' => 'NAME="userimport_import_button" onclick="confirm(\'Are you sure?\')" onmouseup="bck_t=\'It is recommended to make a backup first.\n\nNOTE: Backup files done with this plugin delete previous data and then do the import\'"',
				),
				array(
					'label' => 'Test selected file !',
					'tags' => 'NAME="userimport_test_button" onmouseup="bck_t=\'If you import data with the wrong format it won\'t work. \nIt is recommended to make a backup first.\n\nNOTE: Backup files done with this plugin delete previous data and then do the import\'"',
				),
			),
		);
	}//admin_form

	/**
	 * Upload a file
	 * Enter description here ...
	 */
	function uploadFile()
	{
		$allowedExtensions = array("csv");

		$filenameParts = explode(".", $_FILES["userimport_upload_file"]["name"]);

		$extension = end($filenameParts);

		if (!in_array($extension, $allowedExtensions)) {
			$this->error = "Import only works with CSV files.";
			return;
		}

		if ($_FILES["userimport_upload_file"]["size"] > 2*1024*1024) {
			$this->error = "File is too large (".$this->formatKB($_FILES["userimport_upload_file"]["size"]).").";
			return;
		}
		if (!$_FILES["userimport_upload_file"]["name"]) {
			$this->error = "No files selected. Please, select a file.<br />";
			return;
		}
		if ($_FILES["userimport_upload_file"]["type"] != "application/csv") {
			$this->error = "Wrong file type: ".$_FILES["userimport_upload_file"]["type"].". <br />You can only upload csv files!";
			return;
		}
		if ($_FILES["userimport_upload_file"]["error"]) {
			$this->error = " code: ".$_FILES["userimport_upload_file"]["error"]." - unexpected one...<br />";
			return;
		}

		$dir = $this->userimportDir;

		if (file_exists($dir . $_FILES["userimport_upload_file"]["name"]))
			@unlink($dir . $_FILES["userimport_upload_file"]["name"]);

		if (file_exists($dir . $_FILES["userimport_upload_file"]["name"]))
		{
			$this->error .= "Could not delete file ".$_FILES["userimport_upload_file"]["name"] . ". ";
		}
		else
		{
			move_uploaded_file($_FILES["userimport_upload_file"]["tmp_name"],
				$dir . $_FILES["userimport_upload_file"]["name"]);

			$this->msg = "File uploaded to: <br />" . $dir . $_FILES["userimport_upload_file"]["name"] . "<br />";
			$this->msg .= "(type: " . $_FILES["userimport_upload_file"]["type"] . ", ";
			$this->msg .= "size: " . ceil(($_FILES["userimport_upload_file"]["size"] / 1024)) . " KB)<br />";
		}
	}//uploadFile

	/**
	 * Delete files
	 * Enter description here ...
	 */

	function doDelete()
	{
		$fileArr = $this->getFiles($this->userimportDir);
		if (count($fileArr) == 0)
		{
			$this->msg = "There are no files to delete.";
			return;
		}
		for ($i=0; $i < count($fileArr); $i++)
		{
			if (file_exists($this->userimportDir.$fileArr[$i]))
				@unlink($this->userimportDir.$fileArr[$i]);
			if (file_exists($this->userimportDir.$fileArr[$i]))
				$this->error .= $this->userimportDir.$fileArr[$i] . "<br />";
		}
		if (strlen($this->error) > 0)
			$this->error = "Can't delete files (are they locked by another process?): <br />".$this->error;
		else
			$this->msg = 'Files deleted';
	}// doDelete


	/**
	 * Import process
	 * Enter description here ...
	 */
	function doImport($live = FALSE)
	{

		if ($this->fileCount == 0)
		{
			$this->error = "No files found. Please, upload first.";
			return;
		}

		$fileName = qa_post_text('file_name_selected');

		if (!$fileName)
		{
			$this->error = "No files selected. Please, select a file.";
			return;
		}

		$path = QA_BASE_DIR."qa-content/userimport" . "/" . $fileName;

		if (!file_exists($path))
		{
			$this->error = "File does not exist: ". $path;
			return;
		}

		/* import the file */
		$this->launchCSVFile($path, $this->error, $live);

	}

	/**
	 * Import users via a CSV file
	 * Enter description here ...
	 * @param unknown_type $path
	 * @param unknown_type $error
	 */
	function launchCSVFile($path, &$error, $live = FALSE)
	{
		/* set up vars*/
		$file = null;
		$row = 1;

		if (($handle = fopen($path, "r")) !== FALSE) {

			/* Check the file isn't empty */
			$size = filesize($path);

			if(!$size)
			{
				$this->error = "File is empty: ". $path;
				return;
			}

			/*
			 * Run through the file contents, line by line.
			 *
			 * While we still have rows appearing
			 */

			$importData = "<table>";
			$importData .= "<tr><th>Count</th><th>Email</th><th>Password</th><th>Username</th><th>Info</th></tr>";

		    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		        /* check the users is not already registered */

		        /*
		         * Filter handle/email
		         */

		        $errors = qa_handle_email_filter($data[2], $data[0]);

		        /* check for unique username (handle) */

		        if(qa_handle_make_valid($data[2])) {
		          $usernameOK = true;
		        } else {
		          $usernameOK = false;
		        }

		        /* check for unique email address */

		        if(false) {
		          $emailOK = true;
		        } else {
		          $emailOK = false;
		        }

		        /*
		         * Insert data
		         *
		         * Email               =     $data[0]
		         * Password            =     $data[1]
		         * Username (handle)   =     $data[2]
		         *
		         */

		        if(count($errors) == 0) {
		        	if($live) {
		        		$response = qa_create_new_user($data[0], $data[1], $data[2], QA_USER_LEVEL_BASIC, true);
		        	} else {
		        		$response = "Checked!";
		        	}
		        } else {
		          $response = "<pre>".print_r($errors, 1)."</pre>";
		        }

		        /* show the data */
		        $num = count($data);

				$importData .= "<tr>";
				$importData .= "<td>".$row."</td>";
				for ($c=0; $c < $num; $c++) {
					$importData .= "<td>" . $data[$c] . "</td>";
		        }
				$importData .= "<td>".$response."</td>";
				$importData .= "</tr>";

		        $row++;

		    }//while

			$importData .= "</table>";

		    fclose($handle);

		    $this->msg = $importData;

		} else {
			$this->error = "Error opening data file: ". $path;
			return;
		}//if fopen

	}//launchFile

	/**
	 * Get a list of files
	 */

	function getFiles($dirpath)
	{
		$myDirectory = opendir($dirpath);

		while($entryName = readdir($myDirectory))
			$dirArray[] = $entryName;

		closedir($myDirectory);
		sort($dirArray);

		$indexCount	= count($dirArray);

		$filesArr = array();
		for ($index=0; $index < $indexCount; $index++)
		{
			if (substr("$dirArray[$index]", 0, 1) != "." // don't list hidden files
				&& !$this->endsWith($dirArray[$index], "index.php") // ignore index.php
				&& !is_dir($dirpath.$dirArray[$index])  // don't list directories
				)
			{
				$filesArr[] = $dirArray[$index];
			}
		}
		return $filesArr;
	}


	/**
	 *
	 * get a list of stored files.
	 */
	function listFiles()
	{
		$fileArr = $this->getFiles($this->userimportDir);

		$strFiles = "";
		for ($i=0; $i < count($fileArr); $i++)
		{
			$strFiles .= "<input type=\"radio\" name=\"file_name_selected\" value=\"$fileArr[$i]\">".
							"<a href=\"".$this->userimportDirUrl.$fileArr[$i]."\" target=\"_blank\">$fileArr[$i]</a> (".$this->formatKB(@filesize($this->userimportDir.$fileArr[$i])).")".
						 "</input><br />";
		}

		$this->fileCount = count($fileArr);

		$res = "";

		if ($this->fileCount > 0)
		{
			$res .= '<div style="color:#000; font-size:12px; text-align:left; width:500px;">';
			$res .= 'Files in User Import folder:<br />';
			$res .= $strFiles;
			$res .= "</div>";
		}
		return $res;
	}

	function formatKB($number)
	{
		if (!$number)
			return 0;
		$number = ceil($number/1024);
		return number_format($number, 0, ",", " ")." KB";
	}

	function getDir($path)
	{
		$res = "";
		$slash1 = strrpos($path, "\\");
		$slash2 = strrpos($path, "/");
		$maxSlash = -1;
		if ($slash1) $maxSlash = $slash1;
		if ($slash2 && $slash2>$maxSlash) $maxSlash = $slash2;
		if ($maxSlash != -1)
			$res = substr($path, 0, $maxSlash+1);
		return $res;
	}

	function startsWith($string, $search)
	{
		return (strncmp($string, $search, strlen($search)) == 0);
	}

	function endsWith($string, $search)
	{
		$length = strlen($search);
		$start  = $length * -1; //negative
		return (substr($string, $start) === $search);
	}

}; // class


/*
	Omit PHP closing tag to help avoid accidental output
*/