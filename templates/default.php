<!DOCTYPE html>

<html>

<!-- head -->
<head>
<meta charset="UTF-8">
<title><?=$page->name()?></title>

<!-- css -->
<style type="text/css">
<?=$page->style()?>
</style>

<!-- javascript -->
<script type="text/javascript">
<?=$page->javascript()?>
</script>
<?=$page->head()?>
</head>
<body>

<!--  content -->
<?=$page->content()?> 
</body>
</html>
