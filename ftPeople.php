<?php

/*

Plugin Name: Featured People

Plugin URI: http://cameronpreston.com/projects/plugins/featured-people/
Description: A plugin which lists featured people for a Wordpress site.
Version: 0.1
Author: Timothy Ting, Cameron Preston
Author URI: http://www.cameronpreston.com/
*/

//Check to make sure there are no crashes with other classes with the same name



if (!class_exists('ft_people'))

{
	class ft_people extends WP_Widget {
		function ft_people() { // constructor
			$widget_ops = array('classname' => 'ft_people', 'description' => __( "Widget showing featured people for Wordpress post or page") );
		    $this->WP_Widget('ft_people', __('Featured People'), $widget_ops);
			$this -> plugin_name = 'ftPeople';
		}


		function widget($args, $instance) { //outputs widget
			global $wpdb;
			extract($args);
			// Before the widget
		    echo $before_widget;
		    // The title
//		    echo $before_title . 'Featured People' . $after_title;
		    //Get data

		    $query =   'SELECT * FROM ' . $wpdb->prefix . 'ft_people';
			$results = $wpdb->get_results($query);

			if ($results == FALSE) {
				echo '<br />';
			}

			else {	

				// Make the widget

				echo '<script type="text/javascript">';
				foreach ($results as $animateDiv) {
					echo 'animatedcollapse.addDiv(\''.$animateDiv->id.'\', \'fade=1\');';
				}

/*				echo 'animatedcollapse.ontoggle=function($, divobj, state){'; //fires each time a DIV is expanded/contracted

					//$: Access to jQuery

					//divobj: DOM reference to DIV being expanded/ collapsed. Use "divobj.id" to get its ID

					//state: "block" or "none", depending on state

					echo 'if (this.$divobj.css(\'display\')!=\'none\'){';

						$readmore = "Read More";

					echo '} else {';

						$readmore = "Read Less";

					echo '}';

				echo '}';*/

				$readmore = "Read More/Less";
				echo 'animatedcollapse.init()';

				echo '</script>';


				echo '<ul class="ft_people">';
				$current_level = null;
				foreach ($results as $result) {
					$output = '<li>';
					if ($result->person_extension != NULL) {

						$output .= '<br /><h4><a href="' . WP_CONTENT_URL . '/uploads/ftPerson' . $result->id . $wpdb->prefix . '.' . $result->person_extension . '" rel="lightbox"><img alt="' . $result->person_name . '" title="' . $result->person_name . '" src="' . WP_CONTENT_URL . '/uploads/ftPerson' . $result->id . $wpdb->prefix . '.' . $result->person_extension . '" width=50/></a>';

					} else {
						$output .= '<br /><h4><img alt="' . $result->person_name . '" title="' . $result->person_name . '" src="' . WP_CONTENT_URL . '/uploads/mystery-man.jpg" width=50/>';

					}

					if ($result->person_link != NULL) {
						$output .= ' <a href="' . $result->person_link . '" alt="' . $result->person_link . '">' . $result->person_name . '</a></h4>';
					}

					else {
						$output .= $result->person_name . '</h4>';

					}

					//Work on bio - split into blurb and rest

					$biolength = 120;
					if (strlen(stripslashes($result->person_bio)) > $biolength) {
						$length = strpos(strtolower(stripslashes($result->person_bio)), (strtolower('.')|strtolower('. ')), 118) + 1;
						$blurb = substr(stripslashes($result->person_bio), 0, $length);
						/*this is fix for strange error when length comes out to 1 */
						if ($length < $biolength) {
							$output .= '<p><strong>Bio:</strong> ' . stripslashes($result->person_bio) . '</p></li>';
						}
						else {
						$rest = '<br /><div id="'.stripslashes($result->id).'" class="ft_toggle"><div>' . substr(stripslashes($result->person_bio), $length) . '</div></div>';

						$rdmore = '<a href="javascript:animatedcollapse.toggle(\''.stripslashes($result->id).'\')"> '.$readmore.' </a>';				
						$output .= '<p class="ftperson_bio"><strong>Bio:</strong> ' . $blurb . '' . $rest . '</p>'.$rdmore.'</li>';
						}

					} else {
						$output .= '<p><strong>Bio:</strong> ' . stripslashes($result->person_bio) . '</p></li>';
					}
					echo $output;

				}
				echo '</ul>';

			}

		    // After the widget
		    echo $after_widget;
		}

		function update($new_instance, $old_instance) { //updates widget; editing is done in admin page, so not much change needed
			return $new_instance;
		}	

		function form($instance) { //Creates form 
			?>
			To edit options for this widget, please go to the <a href="<?php echo get_option('siteurl') . '/wp-admin/options-general.php?page=person_options'; ?>">Featured People page</a> under the Settings main page.
			<?php
		}

		function add_pages() { // register menu function for admin menu
			add_options_page('Featured People', 'Featured People', 8, 'person_options', array(&$this, 'create_menu_page'));
			add_action( "admin_print_scripts-$my_plugin_page", 'admin_head');

		}

		function add_widget() { //register the widget
			register_widget('ft_people');
		}

		function admin_head() {
			global $wpdb;

			// You may notice array('jquery') at the end, that means jQuery is required to make that particular file function, and WP will include it automatically
			$this -> enqueue_scripts();
			$this -> enqueue_styles();

		}


		function create_menu_page() { // output actual code for menu page
			global $wpdb;
			//Check for POST

			//Adding person

			if ($_POST['add_person'] == 'Y') {
				//Check fields are filled; if so, insert into wp_sw_persons and transfer file to uploads directory

				if ($_POST['person_name'] == '' || $_POST['person_bio'] == '') {

					echo '<div class="updated"><p>You tried to add a featured person, but some fields are missing. Name bio and photo are required. Please recheck and resubmit.</p></div>';

				} else {

					//If file is chosen, upload file and change table; otherwise just change tables

					if ($_FILES['person_image']['name'] != NULL) {

						//if dir exists or creating the directory succeeds

						if (!file_exists(WP_CONTENT_DIR . '/uploads') && !mkdir(WP_CONTENT_DIR . '/uploads', 0777)) {
							echo '<div class="updated"><p>There was an error creating the uploads directory. Check that permissions are correct, create the directory yourself under wp-content, or contact your Webmaster.</p></div>';
						} else {
							$file_ext = explode('.', $_FILES['person_image']['name']);
							$file_ext = strtolower(array_pop($file_ext));
							//make sure extension of image file is correct - lazy way of file checking
							if ($file_ext != 'jpg' && $file_ext != 'bmp' && $file_ext != 'gif' && $file_ext != 'png') {
								echo '<div class="updated"><p>Your file is not a recognized image file. Please double-check the file.</p></div>';
							} else {
								$result = $wpdb->insert($wpdb->prefix . 'ft_people', array( 'person_name' => $_POST['person_name'], 'person_bio' => $_POST['person_bio'], 'person_extension' => $file_ext, 'person_link' => $_POST['person_link']));
								if ($result === FALSE) {
									echo '<div class="updated"><p>There was an error inserting your featured person into the database. Please contact your Webmaster.</p></div>';
								} else {
									//Get id of last insertion for naming image
									$id = $wpdb->get_var('select LAST_INSERT_ID()');
									//move image to directory

									$target_path = WP_CONTENT_DIR . '/uploads/ftPerson' . $id . $wpdb->prefix . '.' . $file_ext;
									if (!move_uploaded_file($_FILES['person_image']['tmp_name'], $target_path)) {
									    echo '<div class="updated"><p>There was an error uploading your file. Check that your file exists, or contact your Webmaster.</p></div>';

									} else{
									    echo '<div class="updated"><p>New featured person added.</p></div>';
									}
								}
							}
						}

					} else {
						$result = $wpdb->insert($wpdb->prefix . 'ft_people', array( 'person_name' => $_POST['person_name'], 'person_topic' => $_POST['person_topic'], 'person_bio' => $_POST['person_bio'], 'person_link' => $_POST['person_link']));
						if ($result === FALSE) {

							echo '<div class="updated"><p>There was an error inserting your featured person into the database. Please contact your Webmaster.</p></div>';
						} else {
							echo '<div class="updated"><p>New featured person added.</p></div>';
						}
					}
				}
			}

			//Edit/Remove person

			//check if edit_remove_person variable has a number (which is the id of the person being edited/removed

			if (is_numeric($_POST['edit_remove_person'])) {
				if ($_POST['submit'] == 'Remove Person') {
					//Remove db entry
					$query = "DELETE FROM " . $wpdb->prefix . "ft_people WHERE id = " . $_POST['edit_remove_person'] . " LIMIT 1";
					$result = $wpdb->query($query);
					if ($result === FALSE) {
						echo '<div class="updated"><p>There was an error removing this person from the database. Please contact your Webmaster. </p></div>';
					} else {
						//Remove files
						if (file_exists(WP_CONTENT_DIR . '/uploads/ftPerson' . $_POST['edit_remove_person'] . '.' . $_POST['person_extension'])) {
							unlink(WP_CONTENT_DIR . '/uploads/ftPerson' . $_POST['edit_remove_person'] . '.' . $_POST['person_extension']);
						}
						echo '<div class="updated"><p>Person successfully removed.</p></div>';
					}

				} else if ($_POST['submit'] == 'Save Changes') {
					//Update wp_sw_persons and transfer file to uploads directory, overwriting old file, if a new image is chosen
					if ($_FILES['person_image']['name'] == NULL) {
						$result = $wpdb->update($wpdb->prefix . 'ft_people', array( 'person_name' => $_POST['person_name'], 'person_link' => $_POST['person_link'], 'person_bio' => $_POST['person_bio']), array('id' => $_POST['edit_remove_person']));
						if ($result === FALSE) {
							echo '<div class="updated"><p>There was an error updating this person. Please contact your Webmaster.</p></div>';
						} else {
							echo '<div class="updated"><p>Featured person updated.</p></div>';
						}
					} else {
						$file_ext = explode('.', $_FILES['person_image']['name']);
						$file_ext = strtolower(array_pop($file_ext));
						if ($file_ext == 'jpg' || $file_ext == 'bmp' || $file_ext == 'gif' || $file_ext == 'png') {
							$result = $wpdb->update($wpdb->prefix . 'ft_people', array( 'person_name' => $_POST['person_name'], 'person_link' => $_POST['person_link'], 'person_bio' => $_POST['person_bio'], 'person_extension' => $file_ext), array('id' => $_POST['edit_remove_person']));
							if ($result === FALSE) {
								echo '<div class="updated"><p>There was an error updating this person. Please contact your Webmaster.</p></div>';						 	

							} else {

								//make directory if it doesn't exist
								if (!file_exists(WP_CONTENT_DIR . '/uploads')) {
									if (!mkdir(WP_CONTENT_DIR . '/uploads', 0777)) {

										echo '<div class="updated"><p>There was an error creating the uploads directory. Check that permissions are correct, or contact your Webmaster.</p></div>';
									}
								}

								//move image to directory

								$target_path = WP_CONTENT_DIR . '/uploads/ftPerson' . $_POST['edit_remove_person'] . $wpdb->prefix . '.' . $file_ext;
								if (move_uploaded_file($_FILES['person_image']['tmp_name'], $target_path)) {
								    echo '<div class="updated"><p>Featured Person updated.</p></div>';
								} else{
								    echo '<div class="updated"><p>There was an error uploading your new image file. Check that your file exists, or contact your Webmaster.</p></div>';



								}



								



							}



						} else {



							echo '<div class="updated"><p>Your file is not an image file. Please double-check the file.</p></div>';	



						}



					}



					



				}



			}



			



			//Create Forms



			echo '<div class="wrap">';

			echo '<div id="icon-options-general" class="icon32"><br /></div> ';

			echo '<h2>Featured Person Options</h2>';



			//Add person



			?>



			<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" enctype="multipart/form-data">

				<input name="add_person" type="hidden" id="add_person" value="Y" />



				<h3>Add a Featured Person</h3>



				<table class="form-table">



					<tr>

						<th scope="row"><label for="person_name">Name: </label></th>

						<td><input name="person_name" type="text" id="person_name" value="" class="regular-text" /></td>

					</tr>



					<tr>

						<th scope="row"><label for="person_link">Link: </label></th>

						<td><input name="person_link" type="text" id="person_link" value="" class="regular-text" /></td>

					</tr>



					<tr>

						<th scope="row"><label for="person_bio">Bio: </label></th>

						<td><textarea name="person_bio" id="person_bio" class="regular-text" rows="5" cols="50"></textarea></td>

					</tr>

					<tr>



						<th scope="row"><label for="person_image">Headshot: </label></th>

						<td><input name="person_image" type="file" id="person_image" class="regular-text" /> please aim for an image 50 x 50 pixels in size.</td>

					</tr>

				



				</table>



				<p class="submit"> 

					<input type="submit" name="submit" class="button-primary" value="Add Person" />

				</p>



			</form>



				<hr />



				<h3>Edit/Remove a person</h3>



			<?php



			



				//Edit/Remove a person



				//Get results from db - if not, encourage to add persons; if persons, populate rows with persons.



				$results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'ft_people');



				if ($results == NULL) { //If no preexisting persons

					echo 'No featured people yet. Add some above!';

				} else {

					foreach ($results as $result) {

						?>



						<h4><?php if ($result->person_extension != NULL) { 

							echo '<a href="' . WP_CONTENT_URL . '/uploads/ftPerson' . $result->id . $wpdb->prefix . '.' . $result->person_extension . '">

								<img src="' . WP_CONTENT_URL . '/uploads/ftPerson' . $result->id . $wpdb->prefix . '.' . $result->person_extension . '" width=50/></a>';

							} else {

							echo '<img src="' . WP_CONTENT_URL . '/uploads/mystery-man.jpg" width=50 />';

							} ?> <?php echo $result->person_name; ?></h4>



						<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" enctype="multipart/form-data">



							<input name="edit_remove_person" type="hidden" id="edit_remove_person" value="<?php echo $result->id; ?>" />



							<input name="person_extension" type="hidden" id="person_extension" value="<?php echo $result->person_extension; ?>" />



							<table class="form-table">
								<tr>
									<th scope="row"><label for="person_name">Name: </label></th>
									<td><input name="person_name" type="text" id="person_name" value="<?php echo $result->person_name;?>" class="regular-text" /></td>
									<td><input type="submit" name="submit" class="button-primary" value="Save Changes" /></td> 
								</tr>
								<tr>
									<th scope="row"><label for="person_link">Link: </label></th>
									<td><input name="person_link" type="text" id="person_link" value="<?php echo $result->person_link;?>" class="regular-text" /></td>
									<td><input type="submit" name="submit" class="button-secondary" value="Remove Person" /></td> 
								</tr>
								<tr>
									<th scope="row"><label for="person_bio">Bio: </label></th>
									<td><textarea name="person_bio" id="person_bio" class="regular-text" rows="5" cols="50"><?php echo stripslashes($result->person_bio); ?></textarea></td>
								</tr>
								<tr>
									<th scope="row"><label for="person_image">Image: </label></th>
									<td><input name="person_image" type="file" id="person_image" class="regular-text" /> please aim for an image 50 x 50 pixels in size.</td>
								</tr>
							</table>
						</form>
						<br />
						<br />
						<?php
					}
				}

			echo '</div>';
		} // end function create_menu_page

		//Create table when activating plugin
		function create_tables() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'ft_people';
			if ($wpdb->get_var('SHOW TABLES LIKE ' . $table_name) != $table_name) {
				$sql = 'CREATE TABLE ' . $table_name . '(
					id int NOT NULL AUTO_INCREMENT, PRIMARY KEY  (id),
					person_name VARCHAR(50) NOT NULL COLLATE utf8_general_ci,
					person_link VARCHAR(50) COLLATE utf8_general_ci,
					person_bio TEXT NOT NULL COLLATE utf8_general_ci,
					person_extension VARCHAR(50) COLLATE utf8_general_ci
					);';
			}

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		} // end function create_tables
		//Drop the table when deactivating plugin

		function drop_tables() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'ft_people';
			$sql = 'DROP TABLE ' . $table_name;
			$wpdb->query($sql);
		} // end function drop_tables

		function enqueue_scripts() {	
			if (function_exists('wp_enqueue_script')) {
				wp_enqueue_script($this -> plugin_name, '/' . PLUGINDIR . '/' . $this -> plugin_name . '/js/animatedcollapse.js', array('jquery'));
			}
			return true;
		}
		function enqueue_styles() {	
			if (function_exists('wp_enqueue_style')) {
				wp_enqueue_style($this -> plugin_name, '/' . PLUGINDIR . '/' . $this -> plugin_name . '/css/mainstyles.css', null, '1.0', 'screen');
			}
			return true;
		}
	} // end class ft_people
}


//Instantiates class

if (class_exists('ft_people')) {
	$ft_people_widget = new ft_people();
}

//Actions and Filters

if (isset($ft_people_widget)) {
	//Actions
	add_action('admin_menu', array(&$ft_people_widget, 'add_pages')); //add page to admin menu
	add_action('widgets_init', array(&$ft_people_widget, 'add_widget')); //register widget
	add_action('init', array(&$ft_people_widget, 'enqueue_scripts')); //register scripts
	add_action('init', array(&$ft_people_widget, 'enqueue_styles')); //register styles
	register_activation_hook(__FILE__, array(&$ft_people_widget, 'create_tables')); //when plugin is activated, add tables to db
	register_deactivation_hook(__FILE__, array(&$ft_people_widget, 'drop_tables')); //when plugin is deactivated, remove tables from db

	//Filters



/*	$src = '/' . PLUGINDIR . '/' . $this -> plugin_name . '/';*/



}



?>