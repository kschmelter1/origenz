<!DOCTYPE html>

<html lang="en">

<head>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<div id="main-header">
	<?php
		get_template_part('templates/theme/part','topbar');
		get_template_part('templates/theme/part','navigation');
	?>
	</div>
