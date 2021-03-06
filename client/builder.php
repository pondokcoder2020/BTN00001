<!DOCTYPE html>
<html lang="en" dir="ltr">


<?php
	$lastExist;
?>
<?php require 'head.php'; ?>
<body class="layout-default">
	
	<?php
		if(__PAGES__[0] == 'anjungan') {
			require 'pages/anjungan/index.php';
		} else if(__PAGES__[0] == 'display') {
			require 'pages/display/index.php';
		}
	?>
	<div class="mdk-header-layout js-mdk-header-layout">
		<?php require 'header.php'; ?>
		<div class="mdk-header-layout__content">

			<div class="mdk-drawer-layout js-mdk-drawer-layout">
				<div class="mdk-drawer-layout__content page" id="app-settings">
					<?php
						if(empty(__PAGES__[0])) {
							require 'pages/system/dashboard.php';
						} else {
							if(implode('/', __PAGES__) == 'system/logout') {
								require 'pages/system/logout.php';
							} else {
								/*echo '<pre>';
								print_r($_SESSION['akses_halaman_link']);
								echo '</pre>';*/
								if(is_dir('pages/' . implode('/', __PAGES__))) {
									$isInAccess = '';
									$allowAccess = false;
									foreach (__PAGES__ as $key => $value) {
										if($key == 0) {
											$isInAccess .= $value;
										} else {
											$isInAccess .= '/' . $value;
										}

										if (in_array($isInAccess, $_SESSION['akses_halaman_link'])) {
											$allowAccess = true;
											break;
										} else {
											if($allowAccess) {
												$allowAccess = false;
											}
										}
									}

									if($allowAccess) {
										require 'pages/' . implode('/', __PAGES__) . '/index.php';
									} else {
										if(!$allowAccess) {
											require 'pages/system/403.php';	
										} else {
											require 'pages/system/404.php';
										}
									}
								} else {
									if(file_exists('pages/' . implode('/', __PAGES__) . '.php')) {
										require 'pages/' . implode('/', __PAGES__) . '.php';
									} else {
										$isFile = 'pages';
										$isInAccess = '';
										$allowAccess = false;

										foreach (__PAGES__ as $key => $value) {
											if(file_exists($isFile . '/' . $value . '.php')) {
												$lastExist = $isFile . '/' . $value . '.php';
											}

											$isFile .= '/' . $value;
										}

										foreach (__PAGES__ as $key => $value) {
											if($key == 0) {
												$isInAccess .= $value;
											} else {
												$isInAccess .= '/' . $value;
											}

											//echo $isInAccess . '<br />';

											if (in_array($isInAccess, $_SESSION['akses_halaman_link'])) {
												$allowAccess = true;
												break;
											} else {
												if($allowAccess) {
													$allowAccess = false;
												}
											}
										}

										if(isset($lastExist) && $allowAccess) {
											//echo $allowAccess;
											require $lastExist;
										} else {
											if(!$allowAccess) {
												require 'pages/system/403.php';	
											} else {
												require 'pages/system/404.php';
											}
										}
									}
								}
							}
						}
					?>
				</div>
				<?php require 'sidemenu.php'; ?>
			</div>
		</div>
		<div class="preloader">
			<div class="sidemenu-shimmer">
				<?php
					for($sh = 1; $sh <= 10; $sh++) {
				?>
				<div class="shine"></div>
				<?php
					}
				?>
			</div>
			<div class="content-shimmer">
				<span>
					<img width="80" height="80" src="<?php echo __HOSTNAME__; ?>/template/assets/images/preloader4.gif" />
					<br />
					Loading...
				</span>
			</div>
		</div>
	</div>
	<div class="global-sync-container blinker_dc">
		<h4 class="text-center" style="font-family: Courier"><i class="fa fa-signal"></i><br /><br /><small>reconnecting</small></h4>
	</div>
	<div class="notification-container"></div>
	<!-- <div id="app-settings">
		<app-settings layout-active="default" :layout-location="{
	  'default': 'index.html',
	  'fixed': 'fixed-dashboard.html',
	  'fluid': 'fluid-dashboard.html',
	  'mini': 'mini-dashboard.html'
	}"></app-settings>
	</div> -->
	<?php require 'script.php'; ?>
	<!-- <div class="bsod">
		<div id="page">
			<div id="container">
				<h1>:(</h1>
				<h2>Your PC ran into a problem and needs to restart. We're just collecting some error info, and then we'll restart for you.</h2>
				<h2>
					<span id="percentage">0</span>% complete
				</h2>
				<div id="details">
					<div id="qr">
						<div id="image">
							<img src="http://xontab.com/experiments/Javascript/BSOD/qr.png" alt="QR Code" />
						</div>
					</div>
					<div id="stopcode">
						<h4>
							MAMPOS!!!
						</h4>
						<h5>
							If you call a support person, give them this info:<br/>Stop Code: 404 PAGE NOT FOUND
						</h5>
					</div>
				</div>
			</div>
		</div>
	</div> -->

	<script type="text/javascript">
		var Sync;
		$(function() {
			var parentList = [];

			$(".sidebar-menu-item.active").each(function(){
				var activeMenu = $(this).attr("parent-child");
				$("a[href=\"#menu-" + activeMenu + "\"]").removeClass("collapsed").parent().addClass("open");
				$("ul#menu-" + activeMenu).addClass("show");
			});

			$("ul.sidebar-submenu").each(function() {
				var hasMaster = $(this).attr("master-child");
				if (typeof hasMaster !== typeof undefined && hasMaster !== false && hasMaster > 0) {

					//$("a[href=\"#menu-" + hasMaster + "\"]").removeClass("collapsed").parent().addClass("open");
					$("ul#menu-" + hasMaster).addClass("show");
					
				}
			});

			//$("ul[master-child=\"" + activeMenu + "\"").addClass("open");
			

			var idleCheck;
			function reloadSession() {
				window.clearTimeout(idleCheck);
				idleCheck = window.setTimeout(function(){
					location.href = __HOSTNAME__ + "/system/logout";
				},30 * 60 * 1000);
			}

			$("body").on("click", function() {
				reloadSession();
			});

			$("body").on("keyup", function() {
				reloadSession();
			});

			$("body").on("mousemove", function() {
				reloadSession();
			});

			refresh_notification();

			$("body").on("click", "#clear_notif", function() {
				$.ajax({
					async: false,
					url:__HOSTAPI__ + "/Notification",
					type: "POST",
					data: {
						request: "clear_notif"
					},
					beforeSend: function(request) {
						request.setRequestHeader("Authorization", "Bearer " + <?php echo json_encode($_SESSION["token"]); ?>);
					},
					success: function(response) {
						refresh_notification();
					},
					error: function(response) {
						console.log(response);
					}
				});
				return false;
			});
		
			$("body").on("click", "a[href=\"#notifications_menu\"]", function() {
				$.ajax({
					async: false,
					url:__HOSTAPI__ + "/Notification",
					type: "POST",
					data: {
						request: "read_notif"
					},
					beforeSend: function(request) {
						request.setRequestHeader("Authorization", "Bearer " + <?php echo json_encode($_SESSION["token"]); ?>);
					},
					success: function(response) {
						refresh_notification();
					},
					error: function(response) {
						console.log(response);
					}
				});
			});

			if ("WebSocket" in window) {
				var serverTarget = "ws://" + __SYNC__ + ":" + __SYNC_PORT__;
				
				Sync = new WebSocket(serverTarget);
				Sync.onopen = function() {
					$(".global-sync-container").fadeOut();
				}

				/*Sync.onmessage = function(evt) {
					var signalData = evt.data;
					
				}*/

				Sync.onclose = function() {
					$(".global-sync-container").fadeIn();
					var tryCount = 1;
					setInterval(function() {
						console.clear();
						console.log("CPR..." + tryCount);
						var checkSocket = SocketCheck(serverTarget);
						tryCount++;
					}, 1000);
				}

				Sync.onerror = function() {
					$(".global-sync-container").fadeIn();
					var tryCount = 1;
					setInterval(function() {
						console.clear();
						console.log("CPR..." + tryCount);
						var checkSocket = SocketCheck(serverTarget);
						tryCount++;
					}, 1000);
				}

				return Sync;
			} else {
				console.log("WebSocket Not Supported");
			}

			function SocketCheck(__HOST__) {
				var checkSocket = new WebSocket(__HOST__);
				checkSocket.onopen = function() {
					location.reload();
				}
			}
		});

		function refresh_notification() {
			$.ajax({
				async: false,
				url:__HOSTAPI__ + "/Notification",
				type: "GET",
				beforeSend: function(request) {
					request.setRequestHeader("Authorization", "Bearer " + <?php echo json_encode($_SESSION["token"]); ?>);
				},
				success: function(response){
					var newCounter = 0;
					$("#notification-container").html("");
					var notifData = response.response_package.response_data;
					for(var notifKey in notifData) {
						if(notifData[notifKey].status == "N") {
							newCounter++;
						}
						var notifContainer = document.createElement("DIV");
						var notifSenderContainer = document.createElement("DIV");
						var notifContentContainter = document.createElement("DIV");
						$(notifSenderContainer).html(	"<div class=\"avatar avatar-sm\" style=\"width: 32px; height: 32px;\">" +
															"<img src=\"" + __HOSTNAME__ + "/template/assets/images/avatar/queue.png\" alt=\"Avatar\" class=\"avatar-img rounded-circle\">" +
														"</div>").addClass("mr-3");
						if(notifData[notifKey].receiver_type == "group") {
							$(notifContentContainter).html(notifData[notifKey].notify_content).addClass("flex");
						} else {
							$(notifContentContainter).html("<a href=\"\">A.Demian</a> left a comment on <a href=\"\">Stack</a><br>" +
															"<small class=\"text-muted\">1 minute ago</small>").addClass("flex");
						}
							
						$(notifContainer).addClass("dropdown-item d-flex");
						$(notifContainer).append(notifSenderContainer);
						$(notifContainer).append(notifContentContainter);

						$("#notification-container").append(notifContainer);
					}
					if(newCounter > 0) {
						$("#counter-notif-identifier").addClass("navbar-notifications-indicator");
					} else {
						$("#counter-notif-identifier").removeClass("navbar-notifications-indicator");
					}
				},
				error: function(response) {
					console.log(response);
				}
			});
		}

		function push_socket(sender, protocols, receiver, parameter, type) {
			var msg = {
				protocols: protocols,
				sender: sender,
				receiver: receiver,
				parameter: parameter,
				type: type
			};

			Sync.send(JSON.stringify(msg));
		}
	</script>
	<?php
		if(empty(__PAGES__[0])) {
			require 'script/system/dashboard.php';
		} else {
			if(is_dir('script/' . implode('/', __PAGES__))) {
				include 'script/' . implode('/', __PAGES__) . '/index.php';
			} else {
				if(file_exists('script/' . implode('/', __PAGES__) . '.php')) {
					include 'script/' . implode('/', __PAGES__) . '.php';
				} else {
					if(isset($lastExist)) {
						$getScript = explode('/', $lastExist);
						$getScript[0] = 'script';
						include implode('/', $getScript);
					} else {
						include 'script/system/404.php';	
					}
				}
			}
		}
	?>
	<script type="text/javascript">
		function inArray(needle, haystack) {
			var length = haystack.length;
			for(var i = 0; i < length; i++) {
				if(haystack[i] == needle) return true;
			}
			return false;
		}

		function notification (mode, title, time, identifier) {
			var alertContainer = document.createElement("DIV");
			var alertTitle = document.createElement("STRONG");
			var alertDismiss = document.createElement("BUTTON");
			var alertCloseButton = document.createElement("SPAN");

			$(alertContainer).addClass("alert alert-dismissible fade show alert-" + mode).attr({
				"role": "alert",
				"id": identifier
			});

			$(alertTitle).html(title);

			$(alertDismiss).attr({
				"type": "button",
				"data-dismiss": "alert",
				"aria-label": "Close"
			}).addClass("close");

			$(alertCloseButton).attr({
				"aria-hidden": true
			}).html("&times;");

			$(alertContainer).append(alertTitle);
			$(alertDismiss).append(alertCloseButton);
			$(alertContainer).append(alertDismiss);

			$(".notification-container").append(alertContainer);

			setTimeout(function() {
				$(alertContainer).fadeOut();
			}, time);
		}

		function number_format (number, decimals, dec_point, thousands_sep) {
			// Strip all characters but numerical ones.
			number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
			var n = !isFinite(+number) ? 0 : +number,
			prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
			sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
			dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
			s = '',
			toFixedFix = function (n, prec) {
				var k = Math.pow(10, prec);
				return '' + Math.round(n * k) / k;
			};
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
			if (s[0].length > 3) {
				s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
			}
			if ((s[1] || '').length < prec) {
				s[1] = s[1] || '';
				s[1] += new Array(prec - s[1].length + 1).join('0');
			}
			return s.join(dec);
		}

		$(function() {
			var sideMenu1 = <?php echo json_encode($sideMenu1); ?>;
			var sideMenu2 = <?php echo json_encode($sideMenu2); ?>;
			var sideMenu3 = <?php echo json_encode($sideMenu3); ?>;

			if(sideMenu1 > 0) {
				$("#sidemenu_1").show();
			} else {
				$("#sidemenu_1").hide();
			}

			if(sideMenu2 > 0) {
				$("#sidemenu_2").show();
			} else {
				$("#sidemenu_2").hide();
			}

			if(sideMenu3 > 0) {
				$("#sidemenu_3").show();
			} else {
				$("#sidemenu_3").hide();
			}


			$(".tooltip-custom").each(function() {
				var data = $(this).attr("data-toggle");
				$(this).tooltip({
					placement: "top",
					title: data
				});
			});
		});
	</script>
</body>

</html>