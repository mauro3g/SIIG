<html>
<head><meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>PhpMathPublisher : Online demo</title>
<meta name="keywords" content="mathematic,math,mathematics,mathématique,math renderer,php,formula,latex,mathml,publishing,pascal brachet,brachet">
<link rel="stylesheet" type="text/css" href="style.css">
<SCRIPT LANGUAGE="javascript">
formule = new Array();
formule[0]="Examples";

function choisir(n)
{
document.forme1.message.value=formule[n];
}
</SCRIPT>
</head>
<body>

<?
include("../mathpublisher.php") ;
$message='<p>A formula : <m>sqrt(2 ale * gabriel /chatia * 100 )</m>'; //$HTTP_GET_VARS['message'];
$size=15;
if ((!isset($size)) || $size<10) $size=14;
if ( isset($message) && $message!='' ) 
	{
	echo("<div style=\"font-family : 'Times New Roman',times,serif ; font-size :{60}pt;\">".mathfilter($message,$size,"../img/")."</div>");
	}
?>
</body>
</html>
