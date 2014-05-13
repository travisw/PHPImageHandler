<?php
	/*
	 * Because this is a demo, everything has been put in a single file.
	 */

	// ini_set('display_errors', '1');

	$submitUrl = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];

	// Check for uploaded data
	if (!empty($_FILES) && !empty($_FILES['imageFile'])) {
		require_once('image_handler.php');
		$myImageHandler = new ImageHandler($_FILES['imageFile']);

		// Validate image type
		if ($myImageHandler->isValidImage()) {
			// Create new image sizes
			$newImages = $myImageHandler->createNewImageSizes();

			if(isAjaxRequest()) {
				// Return json
				ajaxResponse($newImages, 1);
			}
		} else {
			if(isAjaxRequest()) {
				// Return json
				ajaxResponse(array(), 0);
			}
		}
	}

	/**
	 * Check to see if the current request came in using ajax (xmlhttprequest)
	 *
	 * @return  bool  True if the request is ajax. False otherwise.
	 */
	function isAjaxRequest() {
		return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	/**
	 * Send a Ajax/json response
	 *
	 * @param  array  The data to return in the "content" property
	 * @param  int    The value to return in the "status" property. 1 = Success, 0 = Fail
	 */
	function ajaxResponse($ajaxData, $ajaxStatus) {
		$ajaxResponse = new stdClass();
		$ajaxResponse->content = (isset($ajaxData) ? $ajaxData : '');
		$ajaxResponse->status = (isset($ajaxStatus) ? $ajaxStatus : 1);

		// Return Ajax response
		echo json_encode($ajaxResponse);
		exit();
	}
?>
<html>
	<head>
		<title>Image Handler Demo - TwoClaw.com</title>
		<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
		<style type="text/css">
			body {
				font-family: Helvetica ,Verdana, Arial;
				color: #615951;
			}
			
			#loading {
				position: fixed;
				left: 80px;
				top: 153px;
				display: none;
				background: url("loading2_s.gif") no-repeat;
				z-index: 1000;
				height: 50px;
				width: 50px;
			}

			#uploadForm {
				border: solid 1px #DDDDDD;
				padding: 10px;
				width: 400px;
			}

			#uploadForm input[type=submit] {
				margin-top: 30px;
			}

			#errorContainer {
				color: #cc2929;
			}
		</style>
	</head>

	<body>
		<div id="header"><img src="http://twoclaw.com/wp-content/uploads/2014/04/twoclaw_logo3_s.png" /></div>
		<h3>Image Handler Demo</h3>
		<form id="uploadForm" action="<?php echo $submitUrl; ?>" method="post" enctype="multipart/form-data">
			<label for="imageFile">Image: </label><input name="imageFile" id="imageFile" type="file" /><br/>
			<input type="submit" value="Upload" id="uploadBtn"/>
		</form>
		
		<div id="errorContainer"></div>

		<div id="newImages">
			<?php
				if (!empty($newImages)) {
					echo '<h4>Success!</h4>';
					foreach($newImages as $key => $image) {
						echo $key.'<br/><img src="'.$image.'" /><br/><br/>';
					}
				}
			?>
		</div>

		<!-- "Loading" spinner image -->
		<div id="loading">&nbsp;</div>

		<!-- Some javascript -->
		<script type="text/javascript">
			var files;
 
			// Get the file
			$('input[type=file]').change(function(event) {
				files = event.target.files;
			});

			$( "#uploadForm" ).submit(function(event) {
				// Stop the normal form submit
				event.preventDefault();

				if (typeof files != 'undefined' && files != null) {
					var myData = new FormData();
					// Add the file to our form data
					myData.append('imageFile', files[0]);

					// Submit ajax form
					$.ajax({
						type: 'POST',
						url: '<?php echo $submitUrl; ?>',
						data: myData,
						dataType: 'json',
						cache: false,
						contentType: false,
						processData: false,
						beforeSend: function() {
							$('#loading').show();
							$('#errorContainer').empty();
						},
						error: function(jqXHR, textStatus, errorThrown) {

						}
					})
					.done(function(response) {
						if (response.status == 1) {
							// Success
							images = response.content;
							$('#newImages').empty().hide();

							// Show all the images
							for (var key in images) {
								if (images.hasOwnProperty(key)) {
									$('#newImages').append('<br/><br/>'+key+':<br/><img src="'+images[key]+'"/>');
								}
							}
							$('#newImages').fadeIn();
						} else {
							// Error
							onAjaxError('Invalid image');
						}
						$('#loading').hide();
						reset($('#imageFile'));
					})
					.fail(function(jqXHR, textStatus, errorThrown) {
						onAjaxError(errorThrown);
						$('#loading').hide();
						reset($('#imageFile'));
					});
				}
			});

			function onAjaxError(error) {
				$('#errorContainer').empty().append('Something went wrong: ' + error);
			}

			function reset(e) {
				e.wrap('<form>').closest('form').get(0).reset();
				e.unwrap();
				files = null;
			}
		</script>
	</body>
</html>