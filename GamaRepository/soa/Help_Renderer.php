<?php

/**
 * Renders a help page for a given service signature
 */
class Help_Renderer extends RPC_Service
{
	/**
	 * The signature in the following format.
	 * 
	 * <code>
	 * { "description":string,
	 *   "name":string,
	 *   "result":string,
	 *   "params":[
	 *     { "name":string,
	 *       "description":string,
	 *       "required":boolean,
	 *       "default": any value,
	 *       "datatype":integer|text|label|uri|boolean|file } ...
	 *   ]
	 * }
	 * </code>
	 * 
	 * @datatype text
	 * @required
	 */
	static $PARAM_SIGNATURE	= 'signature';
		
	/**
	 * HTML page describing the service in order to allow
	 * developers use that service.
	 */
	public function execute()
	{
		$signature = $this->getParam(self::$PARAM_SIGNATURE);
		$signature = json_decode( $signature );
		
		if(!is_object($signature))
		{
			throw new Exception('The parameters description should be properly encoded using JSON');
		}
		
// ========================================================================== ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>RPC Help : <?php echo $signature->name ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta name="Author" content="Viliam Simko vlx@matfyz.cz"/>
	<style>
		.preformat { white-space: pre }
		ul {line-height: 50%}
		.param { width: 200pt }
		.helpsection {
			padding-left: 20pt;
		}
		.helpsection th {
			text-align: right;
		}
		.helpsection th,
		.helpsection td {
			border: solid 1px silver;
			padding: 5px;
			vertical-align: top;
		}
		h3 { margin-bottom: 5pt; }
		ul {margin:0}
	</style>
</head>
<body>
	<h2>Service: <?php echo $signature->name ?></h2>
	
	<h3>Description:</h3>
	<div class="helpsection preformat"><?php echo GAMA_Utils::prepareCommentForHtml($signature->description)  ?></div>
	
	<h3>Result format:</h3>
	<div class="helpsection preformat"><?php echo GAMA_Utils::prepareCommentForHtml($signature->result) ?></div>
	
	<h3>Parameters:</h3>
	<div class="helpsection">
		<form action="." method="post" enctype="multipart/form-data">
		<div>
			<?php if(empty($signature->params)): ?>
			No parameters required for this service
			<?php else: ?>
			<table>
				<?php foreach($signature->params as $param): ?>
				<tr>
					<th><?php echo $param->name ?></th>
					<td>
					<?php if($param->datatype == 'text'): ?>
						<textarea name="<?php echo $param->name ?>" cols="60" rows="8"><?php echo htmlspecialchars($param->default) ?></textarea>
					<?php elseif($param->datatype == 'boolean'): ?>
						<input type="checkbox" name="<?php echo $param->name ?>"<?php echo empty($param->default) ? '' : ' checked="checked"' ?>/>
					<?php elseif($param->datatype == 'file'): ?>
						<input type="file" name="<?php echo $param->name ?>"/>
					<?php else:?>
						<input class="param" type="text" name="<?php echo $param->name ?>" value="<?php echo htmlspecialchars($param->default) ?>"/>
					<?php endif ?>
					</td>
					<td class="preformat"><?php echo GAMA_Utils::prepareCommentForHtml($param->description) ?></td>
				</tr>
				<?php endforeach ?>
			</table>
			<?php endif ?>
			<input type="submit" value="Execute"/>
			<input type="hidden" name="<?php echo self::SERVICE_PARAM_NAME ?>"
				value="<?php echo $signature->name ?>"/>
		</div>
		</form>
	</div>
</body>
</html>
<?php // =======================================================================
	}
}

?>