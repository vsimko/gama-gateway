<?php
// configuration and initialization of the endpoint stuff
require_once 'config-endpoint.php';

function get_comment_filename($backupName)
{
	return dirname($backupName).DIRECTORY_SEPARATOR.'.'.basename($backupName);
}

// bacause we use passwords
GAMA_Utils::forceHttpsMode();

// request parameters handling
$password =	@$_POST['password'];
$username = trim(@$_POST['username']);
$action =	trim(@$_POST['action']);
$comment =  trim(@$_POST['comment']);

// substitute default values for some request parameters
if(empty($username))
{
	$username = 'root';
}

// handle the different actions
if($action == 'backup')
{
	$dbname = Config::get('repo.dbname');
	$backupName = Config::get('backup.prefix').$dbname.Config::get('backup.suffix');
	$backupLocationMadeByMysqlhotcopy = Config::get('backup.dir').DIRECTORY_SEPARATOR.$dbname;
	$backupLocation = Config::get('backup.dir').DIRECTORY_SEPARATOR.$backupName;

	// check the validity of backup name
	if(!preg_match('/^[a-zA-Z_0-9]+$/', $backupName))
	{
		die('Wrong format of the backup name:'.$backupName);
	}
	
	// call the shell command
	$SHELL_OUTPUT = shell_exec(
		'mysqlhotcopy'
		//.' --debug'
		.' -u '.escapeshellarg($username)
		.' -p '.escapeshellarg($password)
		.' '.escapeshellarg($dbname)
		.' '.escapeshellarg(Config::get('backup.dir'))
		.' &>/dev/stdout'
		);

	// rename the directory made by mysqlhotcopy
	@rename($backupLocationMadeByMysqlhotcopy, $backupLocation);
	
	// add some explanation of the shell output
	if(strpos($SHELL_OUTPUT, 'Cannot open dir') !== FALSE)
	{
		$SHELL_OUTPUT = 
		"The HTTP server is executing the mysqlhotcopy command ".
		"without sufficient permissions.";
	}
	if(strpos($SHELL_OUTPUT, 'Access denied') !== FALSE)
	{
		$SHELL_OUTPUT .= 
		"Wrong password or insufficient database privileges";
		
		// also remove the empty directory made by the mysqlhotcopy script
		@rmdir($backupLocation);
	}
	if(strpos($SHELL_OUTPUT, '--allowold or --addtodest') !== FALSE)
	{
		$SHELL_OUTPUT = 
		"There is a file or directory in the backup dir which has the same name as the database";
	}
	
	if(file_exists($backupLocation) && !empty($comment))
	{
		file_put_contents( get_comment_filename($backupLocation), $comment );
	}
}

// get the backup sizes
$listSizes = shell_exec('du -h '.Config::get('backup.dir').DIRECTORY_SEPARATOR);
$mapFilenameToSize = array();
foreach(explode("\n", $listSizes) as $line)
{
	//echo "LINE:$line\n";
	if(preg_match('/^(\S+)\s+(\S.*)$/', $line, $match))
	{
		$mapFilenameToSize[ $match[2] ] = $match[1];
	}
}

// get list of existing backups
$listOfBackups = array();
$dirContent = glob(Config::get('backup.dir').DIRECTORY_SEPARATOR.'*');
arsort($dirContent);
foreach($dirContent as $filename)
{
	$size = 'unknown';
	if(isset($mapFilenameToSize[$filename]))
	{
		$size = $mapFilenameToSize[$filename];
	} elseif(is_file($filename))
	{
		$size = (ceil(filesize($filename)/(1024*1024))).'M';
	}
	
	$listOfBackups[] = array(
		'name'	=> $filename,
		'size'	=> $size,
		);
}

?>
<?php // ==================================================================== ?>
<?php include 'design/page-header.php' ?>
<?php // ==================================================================== ?>
<h1>GAMA Repository backup management</h1>
	<form action="" method="post">
		<table>
			<tr>
				<td>Username:</td>
				<td><input tabindex="1" type="text" name="username"
					value="<?php echo htmlspecialchars($username) ?>" style="width:300px"/></td>
				<td rowspan="3"><input tabindex="4" type="submit" value="Backup"/></td>
			</tr>
			<tr>
				<td>Password:</td>
				<td>
					<input tabindex="2" type="password" name="password" style="width:300px"/>
				</td>
			</tr>
			<tr>
				<td>Comment:</td>
				<td>
					<textarea tabindex="3" name="comment" rows="3" cols="30" style="width:300px"></textarea>
					<input type="hidden" name="action" value="backup"/>
				</td>
			</tr>
			
		</table>
	</form>
	<?php if(!empty($SHELL_OUTPUT)): ?>
	<div class="note">
		<b>Result of the backup operation performed:</b>
		<div class="code"><?php echo preg_replace('/$/m', '<br/>', $SHELL_OUTPUT) ?></div>
	</div>
	<?php endif ?>
<h3>List of existing backups</h3>
<div class="intro">
	The endpoint is configured to save backups into the directory
	<b style="white-space: nowrap;"><?php echo Config::get('backup.dir') ?></b>.
	<p/>
	The backups are sorted in a descending order according to their filename.
	The last backup should therefore be on top.
	<p/>
	<a href="?action=refresh">You can refresh the list by clicking here</a>
	
</div>
<table>
	<tr>
		<th>Backup Name</th>
		<th>Backup Size</th>
		<th>Comment</th>
	</tr>
	<?php foreach($listOfBackups as $backupInfo): ?>
		<tr>
			<th><?php echo htmlspecialchars(basename($backupInfo['name'])) ?></th>
			<td><?php echo htmlspecialchars($backupInfo['size']) ?></td>
			<td><?php echo htmlspecialchars(
				@file_get_contents( get_comment_filename($backupInfo['name'])) ) ?></td>
		</tr>
	<?php endforeach ?>
</table>
<?php // ==================================================================== ?>
<?php include 'design/page-footer.php' ?>
<?php // ==================================================================== ?>
