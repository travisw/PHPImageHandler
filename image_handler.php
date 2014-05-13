<?php
	class ImageHandler {
		
		// Maximum file size
		const MAX_IMAGE_FILE_SIZE = 10000000; // 10MB

		// Allowed image file types
		private $allowedFileTypes = array('image/jpeg', 'image/gif', 'image/png', 'image/pjpeg');

		private $fileInfo = array();

		/**
		 * Constructor
		 */
		function __construct($fileArray) {
			$this->fileInfo = $fileArray;
			
		}

		/**
		 * Check to see if the file is a valid image
		 *
		 * @return  bool  Returns true if the image is valid, false otherwise
		 */
		public function isValidImage() {
			return ($this->isImageErrorOk() && $this->isValidImageSize() && $this->isValidImageType());
		}

		/**
		 * Check to see if the file has an upload error
		 *
		 * @return  bool  Returns true if the file uploaded successfully, false otherwise
		 */
		public function isImageErrorOk() {
			return (array_key_exists('error', $this->fileInfo) && $this->fileInfo['error'] == UPLOAD_ERR_OK);
		}

		/**
		 * Check to see if the file size is valid
		 * File size should be > 0 and <= MAX_IMAGE_FILE_SIZE
		 *
		 * @return  bool  Returns true if the file size is valid, false otherwise
		 */
		public function isValidImageSize() {
			return (!empty($this->fileInfo['size']) && $this->fileInfo['size'] > 0 && $this->fileInfo['size'] <= self::MAX_IMAGE_FILE_SIZE);
		}

		/**
		 * Check to see if the image type is valid
		 * File type should be one of the values in $this->allowedFileTypes
		 *
		 * @return  bool  Returns true if the image type is valid, false otherwise
		 */
		public function isValidImageType() {
			return (!empty($this->fileInfo['type']) && in_array($this->fileInfo['type'], $this->allowedFileTypes));
		}
		
		
		/**
		 * Take the original image and create different sizes as specified in the array below
		 * Uses the ImageMagick command line tools
		 *
		 * If height is not specified for any of the sizes, the resized image will be square, using the given
		 * width for both width nd height. Any padding will be filled in with BGCOLOR.
		 *
		 * Images will not be enlarged. If resize dimensions are larger than the original image, it won't be resized.
		 * 
		 * @param 	string	The full path and filename of the original uploaded file
		 * @return	array	Returns an array containing the names of the resized images, false on failure
		 * 
		 */
		public function createNewImageSizes() {
			
			/*****************************************************/
			/**                Config Options                   **/
			
			define('BIN_PATH', '/usr/bin'); // Location of the 'convert' tool
			define('BGCOLOR', 'white');
			define('RELATIVE_PATH', dirname($_SERVER['REQUEST_URI']).'/images/'); // This directory should be writable by the web server
			define('SAVE_PATH', $_SERVER['DOCUMENT_ROOT'].RELATIVE_PATH);
			define('HTTP_PATH', 'http://'.$_SERVER['SERVER_NAME'].RELATIVE_PATH);
			define('NEW_FILE_EXT', 'jpg');
			
			/*
			 * Define the different image sizes to be created
			 * The array key will be used as the ending of the filename (ie. ..._large.jpg)
			 * Values are (width, height), height is optional
			 */
			$imageSizes = array(
				'small' => array(150, 150),
				'med'   => array(480),
				'large' => array(520)
			);
			
			/*****************************************************/

			$origImage = $this->fileInfo['tmp_name'];
			
			// Get width and height of original image
			list($srcWidth, $srcHeight) = getimagesize($origImage);
			
			// Create an image name using a hash, not hash browns
			$imageBaseName = sha1(mt_rand(10000, 99999).time());
			
			// Move uploaded file, if it fails return false
			$movedOrigImage = $imageBaseName.'.'.NEW_FILE_EXT;
			if (!move_uploaded_file($origImage, SAVE_PATH.$movedOrigImage)) {
				return false;
			}
			
			// Create array to hold the different image names, add original image to it
			$newImages = array('orig' => HTTP_PATH.$movedOrigImage);
			$newImagesToTransfer = array('orig' => SAVE_PATH.$movedOrigImage);
			
			// Create all the different sized images
			foreach($imageSizes as $sizeText => $sizeDim) {
				$newImage = $imageBaseName.'_'.$sizeText.'.'.NEW_FILE_EXT;
				
				// If height is not given just resize to width, preserving aspect ratio
				if (!isset($sizeDim[1])) {
					$dimStr = '"'.$sizeDim[0].'>"';
					
				// If height is given pad the sides to match exact width x height
				} else {
					$dimStr = '"'.$sizeDim[0].'x'.$sizeDim[1].'>" -extent '.$sizeDim[0].'x'.$sizeDim[1];
				}

				// Resize image
				exec(BIN_PATH.'/convert ' . SAVE_PATH.$movedOrigImage . '[0] -background '.BGCOLOR.' -gravity center -thumbnail '.$dimStr.' '.SAVE_PATH.$newImage);
				
				$newImages[$sizeText] = HTTP_PATH.$newImage;
				
			}
			
			return $newImages;

		}
	}
?>
