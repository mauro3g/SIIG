<?php
$valor = (isset($_REQUEST['Valor'])) ? $_REQUEST['Valor'] : NULL;
if ($valor=='0'){
echo ('<p class="colorv"><input type="password" tabindex="5" name="clave1" id="clave1" value="abcdd" onblur="comparar()"/> Las Claves son iguales</p>');
}else{
echo ('<p class="colorr"><input type="password" disabled="true" tabindex="5" name="clave1" id="clave1" onblur="comparar1()"/> Las claves <b>NO</b> son iguales</p>');
}

?>
