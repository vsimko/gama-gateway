<html>
	<head>
		<title>Simple interface</title>
	</head>
<body>

<?php foreach((array) @$requestParams['msg'] as $MSG): ?>
MESSAGE: <?php echo $MSG ?><br/>
<?php endforeach ?>

You are about to update the <b>"<?php echo $FILTER ?>"</b> filter.

<form action="<?php echo $SCRIPT ?>" method="post">
URI: <input type="text" name="uri"/>

<br/><input type="submit" name="action"value="Blacklist" />
 = adding the URI to the blacklist ensures that the URI will be ignored during the next ingest 

<br/><input type="submit" name="action" value="Allow" />
 = this will remove the URI from the blacklist so that it can be ingested again

<br/><input type="submit" name="action" value="Check" />
 = check if the URI is blacklisted

<br/><br/><input type="submit" name="action" value="Show" />
 = show list of blacklisted URIs

</form>
</body>
</html>