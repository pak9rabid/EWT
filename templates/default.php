<!DOCTYPE html>

<html>

<!-- head -->
<head>
<title><?=$page->name()?></title>

<!-- css -->
<style type="text/css">
<?=$page->style()?>
</style>

<!-- javascript -->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
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
