<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<Title>Home</Title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
        <link type="text/css" rel="stylesheet" href="css/feed.css">
				<link type="text/css" rel="stylesheet" href="css/styles.css">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>

	<body>

        <?php
            require "database.php";
            require "error.php";
            require "navbar.php";
			require "teacher.php";
			require "organisation.php";

						$teacher = null;
						$organisation = null;
						$profile_photo = null;

						$post_id_scroll = -1;

						if (isset($_GET['post_id'])) {
							$post_id_scroll = $_GET['post_id'];
						}

						if ($user_type == TEACHER) {
							$sql = "SELECT * FROM teachers WHERE username = ?;";

							if ($stmt = $conn->prepare($sql)) {
								$stmt->bind_param("s", $param_user);
								$param_user = $username;

								if ($stmt->execute()) {
									$result = $stmt->get_result();
									$row = $result->fetch_assoc();

									$teacher = new Teacher($username, $row['first_name'], $row['last_name'], $row['headline'],
									$row['about'], $row['location'], $row['profile_photo']);
								} else {
									die("Database Error: {$stmt->error}");
								}

								$stmt->close();
							} else {
								die("Database Error: {$conn->error}");
							}
						} else if ($user_type == ORGANISATION) {
							$sql = "SELECT * FROM organisations WHERE username = ?;";

							if ($stmt = $conn->prepare($sql)) {
								$stmt->bind_param("s", $param_user);
								$param_user = $username;

								if ($stmt->execute()) {
									$result = $stmt->get_result();
									$row = $result->fetch_assoc();

									$organisation = new Organisation($row['organisation_id'], $username,
									$row['name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
								} else {
									die("Database Error: {$stmt->error}");
								}

								$stmt->close();
							} else {
								die("Database Error: {$conn->error}");
							}
						}

						$default = null;
						if ($teacher != null) {
							$profile_photo = $teacher->profile_photo();
							$default = DEFAULT_TEACHER_PROFILE_PIC;
						} else if ($organisation != null) {
							$profile_photo = $organisation->profile_photo();
							$default = DEFAULT_ORG_PROFILE_PIC;
						} else {
							$default = "images/logo.png";
						}

						$profile_photo = ($profile_photo == null) ? $default:$profile_photo;

						$name = null;

						if ($teacher != null) {
							$name = $teacher->firstName() . " " . $teacher->lastName();
						} else if ($organisation != null) {
							$name = $organisation->name();
						}

						/**
							* Gets the count of unread notifications
							*/
						function getNotificationsCount() {
							global $conn;
							global $username;

							$sql = "SELECT COUNT(*) AS 'num_notifications' FROM notifications WHERE username = ? AND viewed = 0;";

							if ($stmt = $conn->prepare($sql)) {
								$stmt->bind_param("s", $param_username);
								$param_username = $username;

								$num_notifications = 0;
								if ($stmt->execute()) {
									$result = $stmt->get_result();
									$num_notifications = $result->fetch_assoc()['num_notifications'];
								}

								$stmt->close();
								return $num_notifications;
							} else {
								return 0;
							}
						}
        ?>

        <?php
            generateNavBar(HOME);
        ?>

        <div class="container">
			<div class="row">
				<div class="<?php echo ($user_type != ADMIN) ? 'col-3':'d-none'; ?>" id="profile-home-panel">
					<div class="card" style="margin-top:20px;">
                        <img class="side-bar-profile-pic rounded-circle m-auto" src="<?php echo $profile_photo; ?>" alt="Profile Pic">
						<div class="card-body text-center">
							<h4 class="card-title">
							    <?php
							   		echo $name;
                    			?>
							</h4>
							<p class="card-text" style="font-size:14px;">

								<?php
									if($user_type == TEACHER){
										$tag_query = mysqli_query($conn, "select * from teachers where username = '" . $_SESSION['username'] . "';");
										while($connection_row = mysqli_fetch_array($tag_query)){
											echo $connection_row['headline'];
										}
									} else if($user_type == ORGANISATION){
										$tag_query = mysqli_query($conn, "select * from organisations where username = '" . $_SESSION['username'] . "';");
										while($connection_row = mysqli_fetch_array($tag_query)){
											echo $connection_row['headline'];
										}
									}
								?>

							</p>
							<?php
								$noti_count = getNotificationsCount();
								if($noti_count == 0){
									echo '<button type="button" class="btn btn-primary" onclick="window.location.href=\'notifications.php\';">
										Notifications <span class="badge bg-secondary">'. $noti_count .'</span>
									</button>';
								} else {
									echo '<button type="button" class="btn btn-success" onclick="window.location.href=\'notifications.php\';">
										Notifications <span class="badge bg-secondary"> '. $noti_count .'</span>
									</button>';
								}

							?>
						</div>
					</div>

					<?php if ($user_type == TEACHER): ?>
					<div class="card">
						<div class="tags" style="text-align: center;">
							<h3><b><u>Tags</b></u></h3>
							<?php
								$tag_query = mysqli_query($conn, "select teacher_interests.username, tags.name from teacher_interests inner join tags on teacher_interests.tag_id = tags.tag_id where username = '" . $_SESSION['username'] . "';");
								while($connection_row = mysqli_fetch_array($tag_query)){
									echo '<p>' . $connection_row['name'] . '</p>';
								}
							?>
						</div>
					</div>

					<div class="card" style="margin-top:20px;">
						<button type="button" class="btn btn-primary btn-sm" style="margin:20px" onclick="window.location.href='edit_tags.php';">
							Add tags to follow
						</button>
					</div>

					<?php endif; ?>
            	</div>

            	<div class="<?php echo ($user_type != ADMIN) ? 'col-9':'col'; ?>" id="home-feed">
					<?php if ($user_type != ADMIN): ?>
					<div class="card" style="margin-top:20px;" id="post_creation">
						<div class="form-group" style="margin:10px;">
							<form id="post_creation_form">
								<textarea class="form-control" name="content" id="content" rows="3" placeholder="Share your thoughts" style="margin-bottom:10px;" required></textarea>
								<input class="form-control" name="tags" id="tags" placeholder="Add tags to your post (e.g. Math, English, Irish)" style="margin-bottom:5px;">
								<p style="font-size:12px; margin-left:4px; color:grey;">Enter tags to add in a comma-separated (,) list</p>
								<div class="card-body text-center">
									<button type="button" onclick="handlePostCreation();" class="btn btn-primary" id="post-button">Post</button>
								</div>
							</form>
						</div>
					</div>
				<?php endif; ?>


					<?php
						//this array is used to see teachers the user is friends with, companies they follow
						function getAccountType(string $account_username, $conn) {
							$account_query = mysqli_query($conn, "select * from accounts where username = '". $account_username ."'");
							if($account_row = mysqli_fetch_array($account_query)){
								return $account_row['type'];
							}
							return null;
						}

						function getOrgId(string $org_username, $conn){
							$org_query = mysqli_query($conn, "select * from organisations where username = '". $org_username ."'");
							if($org_row = mysqli_fetch_array($org_query)){
								return $org_row['organisation_id'];
							}
							return null;
						}

						function addToArray($param_query, string $post_column, $list){
							//populate list from sql query
							while($connection_row = mysqli_fetch_array($param_query)){
								array_push($list, $connection_row[$post_column]);
							}
							return $list;
						}

						$post_list = [];
						$tag_post_list = [];
						if($user_type == TEACHER){
							array_push($post_list, $_SESSION['username']);
						} else if($user_type == ORGANISATION){
							array_push($post_list, getOrgId($_SESSION['username'], $conn));
						}

						$profile_photo = null;
						$content = null;
						$post_name = null;

						$sql_friend_check_sender = mysqli_query($conn, "select * from connections where sender = '" . $_SESSION['username'] . "' and status = 'accepted';");
						$post_list = addToArray($sql_friend_check_sender, 'destination', $post_list);

						$sql_friend_check_des = mysqli_query($conn, "select * from connections where destination = '" . $_SESSION['username'] . "' and status = 'accepted';");
						$post_list = addToArray($sql_friend_check_des, 'sender', $post_list);

						$sql_org_check = mysqli_query($conn, "select * from followed_organisations where teacher_username = '" . $_SESSION['username'] . "';");
						$post_list = addToArray($sql_org_check, 'organisation_id', $post_list);

						$sql_tag_check = mysqli_query($conn, "select post_id from post_tags inner join teacher_interests on post_tags.tag_id = teacher_interests.tag_id where username ='" . $_SESSION['username'] . "';");
						$tag_post_list = addToArray($sql_tag_check, 'post_id', $tag_post_list);

						$query = mysqli_query($conn, "select * from posts order by created_at desc");

						function alreadyLiked($post_id, $username) {
							global $conn;

							$sql = "SELECT * FROM post_likes WHERE post_id = ? AND username = ?;";

							if ($stmt = $conn->prepare($sql)) {
							    $stmt->bind_param("is", $param_id, $param_username);
							    $param_id = $post_id;
							    $param_username = $username;

							    if ($stmt->execute()) {
								$result = $stmt->get_result();

								$exists = $result->num_rows > 0;
								$stmt->close();

								return $exists;
							    } else {
								die("Database Error: {$stmt->error}");
							    }
							} else {
							    die("Database Error: {$conn->error}");
							}
						}

						echo '<div id="posts-container">';
						while($row = mysqli_fetch_array($query)){

							$content = $row['content'];
							$username = $row['username'];
							$time_created = $row['created_at'];

							$time_created_original = strtotime($time_created);
							$time_created = date("H:i", $time_created_original);
							$time_created2 = date("d/m/Y", $time_created_original);

							$post_id = $row['post_id'];

							if(in_array($username, $post_list) or in_array(getOrgId($username, $conn), $post_list) or $user_type == ADMIN or in_array($post_id, $tag_post_list)){
								$profile_url = "";

								$account_type = getAccountType($username, $conn);
								if($account_type == 'teacher'){
									$sql = "select * from teachers where username = '$username';";
									$result = $conn->query($sql);
									while($row = $result->fetch_assoc()) { //teacher post
										$profile_photo = $row['profile_photo'];
										$profile_photo = ($profile_photo == null || empty($profile_photo)) ? DEFAULT_TEACHER_PROFILE_PIC:$profile_photo;
										$post_name = $row["first_name"] .' '. $row["last_name"];
									}

									$profile_url = "teacher_profile.php?username={$username}";
								} else if($account_type == 'organisation'){
									$sql = "select * from organisations where username = '$username';";
									$result = $conn->query($sql);
									while($row = $result->fetch_assoc()) { //org post
										$profile_photo = $row['profile_photo'];
										$profile_photo = ($profile_photo == null || empty($profile_photo)) ? DEFAULT_ORG_PROFILE_PIC:$profile_photo;
										$post_name = $row["name"];
									}

									$profile_url = "organisation_profile.php?username={$username}";
								}

								$liked = alreadyLiked($post_id, $_SESSION['username']);
								echo '<div class="card" id="post-card-'. $post_id . '">
								<div class="card-body">
									<div class="row">
										<div class="col-3">
											<img class="img-fluid rounded-circle" src="'. $profile_photo . '" alt="Profile image">
										</div>
										<div class="col-7">
											<a href="'. $profile_url . '"><h3 class="card-title"><b>'. $post_name .'</b></h3></a>
											<p class="card-text new-line-text">'. $content .'</p>';
											if ($liked) {
												echo '<button type="button" class="btn btn-danger btn-sm" id="post-'. $post_id . '" onclick="handlePostLike('. $post_id . ', \''. $username . '\');">Unlike 👍</button>';
											} else {
												echo '<button type="button" class="btn btn-primary btn-sm" id="post-'. $post_id . '" onclick="handlePostLike('. $post_id . ', \''. $username . '\');">Like 👍</button>';
											}

								$tag_query = mysqli_query($conn, "select tags.name from post_tags inner join tags on post_tags.tag_id = tags.tag_id where post_id = '". $post_id ."';");
								if (mysqli_num_rows($tag_query) > 0) {
									echo '<p style="margin-top:10px;"><b>Tags:</b> ';
									while($connection_row = mysqli_fetch_array($tag_query)){
										print $connection_row['name'] . " ";
									}

									echo '</p>';
								}

								echo '</div>
								<div class="col-2">
									<p><b>'. $time_created .'</b></p>
									<p><b>'. $time_created2 .'</b></p>'
									;

								if($_SESSION['username'] == $username or $user_type == ADMIN){
									$data = array('post_id' => $post_id, 'return_url' => "feed.php");
									$query_string = http_build_query($data);
									$url = "post_delete.php?".$query_string;

									echo '<button type="button" class="btn btn-outline-danger btn-sm" onclick="deletePost('.$post_id.');">Delete</button>';
								}

								echo'		</div>
										</div>
									</div>
								</div>';
							}
						}

						echo "</div>";
					?>
				</div>
			</div>
		</div>

		<script type="text/javascript" src="forms.js"></script>
    	<script type="text/javascript" src="ajax.js"></script>
		<script>
			const username = <?php echo json_encode($_SESSION['username']); ?>;

			const post_id_scroll = <?php echo json_encode($post_id_scroll); ?>;
			const post_button = document.getElementById('post-button');

			if (post_id_scroll != -1) {
				var post = document.getElementById(`post-card-${post_id_scroll}`);
				if (post != null) {
					post.scrollIntoView();
				}
			}

			function handleRefresh() {
				window.location.href = 'feed.php';
			}

			function createPost(data) {
				var post_id = data['post_id'];
				var post_username = data['username'];

				var card = document.createElement("div");
				card.classList.add('card');
				card.id = `post-card-${post_id}`;

				var card_body = document.createElement("div");
				card_body.classList.add('card-body');
				card.appendChild(card_body);

				var row = document.createElement("div");
				row.classList.add('row');
				card_body.appendChild(row);

				var col_3 = document.createElement("div");
				col_3.classList.add('col-3');
				row.appendChild(col_3);

				var img = document.createElement("img");
				img.classList.add('card-img-top', 'rounded-circle');
				img.src = data['profile_photo'];
				col_3.appendChild(img);

				var col_7 = document.createElement("div");
				col_7.classList.add('col-7');
				row.appendChild(col_7);

				var profile_url = document.createElement("a");
				profile_url.href = data['profile_url'];
				col_7.appendChild(profile_url);

				var h3 = document.createElement("h3");
				h3.classList.add('card-title');
				var b = document.createElement("b");
				b.innerHTML = data['post_name'];
				h3.appendChild(b);
				profile_url.appendChild(h3);

				var content = document.createElement("p");
				content.classList.add('card-text');
				content.innerHTML = data['content'];
				col_7.appendChild(content);

				var like_button = document.createElement("button");
				like_button.type = "button";
				like_button.classList.add('btn', 'btn-primary', 'btn-sm');
				like_button.innerHTML = "Like 👍";
				like_button.onclick = function() { handlePostLike(post_id, post_username); };
				like_button.id = `post-${post_id}`;
				col_7.appendChild(like_button);

				var tags = data['post_tags'];

				if (tags != null) {
					var tags_p = document.createElement("p");
					tags_p.style.marginTop = "10px;";
					var tags_b = document.createElement("b");
					tags_b.innerHTML = "Tags: ";
					tags_p.appendChild(tags_b);
					col_7.appendChild(tags_p);

					var tagString = "";
					for (var tag in tags) {
						tagString += tags[tag] + " ";
					}

					tags_p.appendChild(document.createTextNode(tagString));
				}

				var col_2 = document.createElement("div");
				col_2.classList.add('col-2');
				row.appendChild(col_2);

				var created_at_p = document.createElement("p");
				var created_at_b = document.createElement("b");
				created_at_b.innerHTML = data['time_created'];
				created_at_p.appendChild(created_at_b);
				col_2.appendChild(created_at_p);
				created_at_p = document.createElement("p");
				created_at_b = document.createElement("b");
				created_at_b.innerHTML = data['date_created'];
				created_at_p.appendChild(created_at_b);
				col_2.appendChild(created_at_p);

				if (username == post_username) {
					var delete_button = document.createElement("button");
					delete_button.classList.add('btn', 'btn-outline-danger', 'btn-sm');
					delete_button.onclick = function() { deletePost(post_id); };
					delete_button.innerHTML = "Delete";
					col_2.appendChild(delete_button);
				}

				var posts_container = document.getElementById('posts-container');
				posts_container.prepend(card);
			}

			function handlePostCreation() {
				post_button.disabled = true;
				var valid = validateForm('post_creation_form');
				if (valid) {
					var data = serializeForm('post_creation', 'textarea,input');
					data['username'] = username;
					data['edit_form'] = 'post_creation';

					var ajax = getAJAX();
					if (ajax != null) {
						ajax.onreadystatechange = function() {
							if (ajax.readyState == 4) {
								var response = ajax.response;

								try {
									var responseBody = JSON.parse(response);
									var success = responseBody.success;
									var message = responseBody.message;

									if (success && message == "CREATED") {
										var data = responseBody.data;
										createPost(data);

										addAlertMessage(true, "The post has been created successfully", "post_creation");
									} else {
										addAlertMessage(false, "An error occurred creating post: " + message, "post_creation");
									}

									var form = document.getElementById('post_creation_form');
									form.classList.remove('was-validated');
									form.reset();

									post_button.disabled = false;
								} catch (e) {
									alert(e);
								}
							}
						}

						ajax.open("POST", "feed-action-ajax.php", true);
						ajax.send(JSON.stringify(data));
					} else {
						post_button.disabled = false;
					}
				} else {
					post_button.disabled = false;
				}
			}

			function handlePostLike(post_id, creator_username) {
				var data = {};
				data['post_id'] = post_id;
				data['creator_username'] = creator_username;
				data['username'] = username;
				data['edit_form'] = 'post_like';
				data['like_source'] = 'feed.php';

				var ajax = getAJAX();
				if (ajax != null) {
					ajax.onreadystatechange = function() {
							if (ajax.readyState == 4) {
								var response = ajax.response;

								try {
									var responseBody = JSON.parse(response);
									var success = responseBody.success;
									var message = responseBody.message;

									if (success) {
										var data = responseBody.data;
										var post_id = data['post_id'];
										var button = document.getElementById(`post-${post_id}`);

										if (message == "LIKED") {
											button.classList.remove('btn-primary');
											button.classList.add('btn-danger');
											button.innerHTML = "Unlike 👍";
										} else if (message == "REMOVED") {
											button.classList.remove('btn-danger');
											button.classList.add('btn-primary');
											button.innerHTML = "Like 👍";
										}
									} else {
										addAlertMessage(false, "An error occurred liking post: " + message, `post-card-${post_id}`);
									}
								} catch (e) {
									alert(e);
								}
							}
						}

						ajax.open("POST", "feed-action-ajax.php", true);
						ajax.send(JSON.stringify(data));
				}
			}

			function deletePost(post_id) {
				var data = {};
				data['post_id'] = post_id;
				data['username'] = username;
				data['edit_form'] = 'post_delete';

				var ajax = getAJAX();
				if (ajax != null) {
					ajax.onreadystatechange = function() {
							if (ajax.readyState == 4) {
								var response = ajax.response;

								try {
									var responseBody = JSON.parse(response);
									var success = responseBody.success;
									var message = responseBody.message;

									if (success) {
										var data = responseBody.data;
										var post_id = data['post_id'];
										var element = document.getElementById(`post-card-${post_id}`);

										if (message == "REMOVED" && element != null) {
											element.remove();
										}
									} else {
										addAlertMessage(false, "An error occurred removing post: " + message, `post-card-${post_id}`);
									}
								} catch (e) {
									alert(e);
								}
							}
						}

						ajax.open("POST", "feed-action-ajax.php", true);
						ajax.send(JSON.stringify(data));
				}
			}
		</script>
<!--
		<script>
			if ( window.history.replaceState ) {
				window.history.replaceState( null, null, window.location.href );
			}
		</script> -->

	</body>
</html>
