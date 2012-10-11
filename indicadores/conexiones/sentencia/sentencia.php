<?php
function agregar_sentencia(){
$cnx=bdd_conectar();
?>
<form action="<?php echo $_SERVER['PHP_SELF']?>?action=guardarnuevo" method="POST" id="add" name="add">
<h3 class="frm-title-x">Agregar Sentencia SQL</h3>
	<fieldset ><legend> Ingreso de Sentencia SQL</legend>
	<table width="100%" align="center">
		<TR class="frm-fld-x-odd">
			<TD width="20%">Conexi&oacute;n a la Base de Datos</TD>
			<TD width="80%">
				<select name="id_conexion" tabindex="1" size="1" id="id_conexion">
					<option selected="selected"> [Seleccione Uno ..]</option>
					<?php general_fillCmbS('conexion', 'id_conexion', 'id_conexion', 'nombre_conexion', NULL, NULL, $cnx); ?>
				</select>
			</TD>
		</TR>
		<TR class="frm-fld-x">
			<TD width="20%">Nombre de la Sentencia</TD>
			<TD>
				<input type='text' tabindex="2" name="nombre_consulta" id="nombre_consulta" maxlength="100" size="100" />
			</TD>
		</TR>
		<TR class="frm-fld-x-odd">
			<TD width="20%">Sentencia SQL</TD>
			<TD>
				<textarea tabindex="3" name="sentencia_sql" id="sentencia_sql" rows="7" cols="80"></textarea>
			</TD>
		</TR>
		<TR class="frm-fld-x">
			<TD width="20%">Comentario</TD>
			<TD width="80%">
				<textarea tabindex="4" name="comentario" id="comentario" rows="7" cols="80"></textarea>
			</TD>			
		</TR>
	</table>
	<table width="100%">	
		<tr class="frm-fld-x-odd" colspan="1">
			<TD width="100%"  align="center">
				<input type="hidden" name="action" value="guardarnuevo" />
				<label for="Add">&nbsp;</label>
				<input tabindex="5" class="frm-btn-x" type="submit" name="add" title="Agregar Nuevo" id="Add" value="Adicionar" />
				<input tabindex="6" class="frm-btn-x" type="button" name="cancel" title="Cancelar" id="Cancel" value="Cancelar" onclick="javascript:window.location=('index.php');" />
				<input  tabindex="7" class="frm-btn-x" type="button" name="probar" title="Verifica si realiza la consiulta de los datos con la sentencia" id="Probar Sentencia" value="Probar Sentencia" onclick="probar_sentencia()" />
			</TD>
		</tr>
</table>
<div id="resultado"></div>


</fieldset>
</form>
<script language="JavaScript" type="text/javascript"> var frmvalidator = new Validator("add");
frmvalidator.addValidation("id_conexion","dontselect=0","El nombre de la conexion es requerido");
frmvalidator.addValidation("nombre_consulta","req","Nombre de la consulta es requerido"); 
frmvalidator.addValidation("sentencia_sql","req","La sentencia SQL es requerida"); 
</script>
<?php
bdd_cerrar($cnx);
}


function grabar_nuevo_sentencia(){
$cnx = bdd_conectar();
$q = "INSERT INTO sentencia (id_conexion, nombre_consulta, sentencia_sql, comentario  ) VALUES (
  ".$_POST['id_conexion'].", 
  '".$_POST['nombre_consulta']."', 
  '".$_POST['sentencia_sql']."', 
  '".$_POST['comentario']."')";
	$rs = bdd_pg_query($cnx, $q);
	$af = bdd_pg_affected_rows($rs);
	if ($af){ 
		?>
		<p class="ok">Registro modificado!</p>
		<p > Regresar a <a href="/indicadores/conexiones/sentencia/">Sentencia SQL</a></p>
		<?php
	} else { 
		?>
		<p class="error">El registro no ha sufrido modificacion<br />
		<p > Regresar a <a href="/indicadores/conexiones/sentencia/">Sentencia SQL</a></p>
		<?php echo pg_error($cnx);?></p>
		<?php
	}
	bdd_cerrar($cnx);
}


function listarTodos($table, $data, $url , $fields = '*', $per_page = 10) {
	$cnx = bdd_conectar();
	$actions = "Acciones";
	$aAdd = "Activar";
	$aEdit = "Bloquear";
	$aDelete = "Asignar";
	$q = '
	SELECT 
		sentencia.id_sentencia, 
		sentencia.nombre_consulta, 
		conexion.nombre_conexion,
		conexion.ip, 
		conexion.nombre_base_datos, 
		motor_bd.nombre_motor,
		sentencia.sentencia_sql, 
		sentencia.comentario 
	FROM 
		public.sentencia, 
		public.conexion, 
		public.motor_bd
	WHERE 
		sentencia.id_conexion = conexion.id_conexion AND
		conexion.id_motor = motor_bd.id_motor';
	$rs = bdd_pg_query($cnx, $q);
	if ($rs) { 
		?><div id="paginador">
  		<?php 
		$url = $_SERVER['PHP_SELF'];	
		$start = 0;
		$start = (isset($_REQUEST['start'])) ? $_REQUEST['start'] : 0;
if ($start!=0){
	$q1=$q." LIMIT ".$per_page." offset ".$start;
}else{
$q1=$q." LIMIT 10";
}
		$result = bdd_pg_query($cnx, $q1);  
		$rs2 = bdd_pg_query($cnx, $q);
		$total_items = bdd_pg_num_rows($rs2);
		$range = 20;
		$pagination = paginacion($url, $total_items, $per_page, $start, 3, 'pageoftotal');
		echo $pagination;
		?></div>
		<?php
		$num = bdd_pg_num_rows($rs);
		if ($num > 0) { 
			?>
			<table  border="0" cellpadding="2" cellspacing="0" class="dataTable" width="100%">
  			<thead>
    			<?php
			$numf = pg_num_fields($rs);
			?>
    			<tr>
      			<?php
			$i = 0;
			while ($i < $numf  ) {
				$meta = pg_field_name($rs,$i); 
				$fname = $meta;
//	nombre_conexion 	 	sentencia_sql 		
				switch ($fname) {
					case 'id_sentencia':
						$fname = "ID";
					break;
					case 'nombre_consulta':
						$fname = "Nombre de Consulta";
					break;
					case 'ip':
						$fname = "Direccion IP";
					break;
					case 'nombre_motor':
						$fname = "Motor Base de Datos";
					break;
					case 'nombre_base_datos':
						$fname = "Nombre de la Base de Datos";
					break;
					case 'comentario':
						$fname = "Comentario";
					break;
					case 'nombre_conexion':
						$fname = "Nombre de la Conexion";
					break;
					case 'sentencia_sql':
						$fname = "Sentencia SQL";
					break;
				}
				?>
				<th rowspan="2"><?php echo $fname; ?></th>
      				<?php
				$i++;
			} 
			?>
			<?php echo (count($data)>0)? "<th colspan=\"".count($data)."\" >".$actions."</th>" : NULL; ?> </tr>
			<tr>
      			<?php
			foreach($data as $value) {
				?>
      				<th><img align="middle" src="../../lib/<?php echo $value; ?>.png" alt="<?php echo $value; ?>" width="16" height="16" /></th>
     				<?php
			}
			?>
    			</tr>
 			</thead>
			<tbody>
    			<?php

			while ($reg = bdd_pg_fetch_row($result)) {
				?>
    				<tr>
      				<?php
				$i = 0;
				while ($i < $numf ) {
 					switch ($i) {
						case 20:
							?>
      <td><?php 
							$idx = $reg[$i];
							$datosmotor = general_sacarRegistroPorCondicion('motor_bd', 'id_motor = '.$idx, $cnx, 'nombre_motor');
							echo $datosmotor[0];
							
							?></td>
      <?php
						break;



						default:
							?>
      							<td><?php 
							echo $reg[$i]; 
							?></td>
      							<?php
						break;
					}	
					$i++;
				}
				foreach($data as $value) {
					if ($value =='Borrar'){
						?>
						<td><a href="#" onClick="disp_confirm('index.php?action=borrar&id=<?php echo $reg[0] ?>','no','&iquest; Esta seguro que quiere eliminar este registro ID:<?php echo $reg[0]?>?');"><?php echo $value; ?> </a></td>
						<?php 
					} 
					else 
					{ ?>
					<td><a href="<?php  $_SERVER['PHP_SELF']?> index.php?action=<?php echo strtolower($value); ?>&amp;id=<?php echo $reg[0]?>"> <?php echo $value; ?></a></td>
					<?php 
					}
				}
				?>
				</tr>
    				<?php
			} 
			?>
  			</tbody>
  			<?php
			?>
			</table>
			<?php
		} 
	} 
bdd_cerrar($cnx);
}

function editar_sentencia($id){
	$cnx = bdd_conectar();
	$q = '	SELECT 
			sentencia.id_sentencia, 
			sentencia.id_conexion, 
			sentencia.nombre_consulta, 
			sentencia.sentencia_sql, 
			sentencia.comentario
		FROM 
			public.sentencia
		where 
			sentencia.id_sentencia='.$id;
	$rs = bdd_pg_query($cnx, $q);
	if ($rs) { 
		$reg = bdd_pg_fetch_row($rs)
		?>
		<h3>Editar Una Sentencia SQL</h3>
		<form action="<?php echo $_SERVER['PHP_SELF']?>?action=guardar" method="POST" id="edit" name="edit" >
			<fieldset ><legend> Editar Una Sentencia SQL </legend>
				<table width="100%" align="center">
					<TR class="frm-fld-x-odd">
						<TD width="20%">Conexi&oacute;n a la Base de Datos</TD>
						<TD width="80%">
							<select name="id_conexion" tabindex="1" size="1" id="id_conexion">
								<option selected="selected"> [Seleccione Uno ..]</option>
								<?php general_fillCmbS('conexion', 'id_conexion', 'id_conexion', 'nombre_conexion', $reg[1], NULL, $cnx); ?>
							</select>
						</TD>
					</TR>
					<TR class="frm-fld-x">
						<TD width="20%">Nombre de la Sentencia</TD>
						<TD>
							<input type='text' tabindex="2" name="nombre_consulta" id="nombre_consulta" maxlength="100" size="100" value="<?php echo $reg[2] ;?>" />
						</TD>
					</TR>
					<TR class="frm-fld-x-odd">
						<TD width="20%">Sentencia SQL</TD>
						<TD>
							<textarea tabindex="3" name="sentencia_sql" id="sentencia_sql" rows="7" cols="80"><?php echo $reg[3] ;?></textarea>
						</TD>
					</TR>
					<TR class="frm-fld-x">
						<TD width="20%">Comentario</TD>
						<TD width="80%">
							<textarea tabindex="4" name="comentario" id="comentario" rows="7" cols="80"><?php echo $reg[4]; ?></textarea>
						</TD>			
					</TR>
				</table>
				
				<table width="100%">	
					<tr class="frm-fld-x-odd">
						<TD colspan="1" align="center">
							<input type="hidden" name="action" value="guardar" />
							<input type="hidden" name="id" value="<?php echo $reg[0];?>" />
							<label for="Add">&nbsp;</label>
							<input tabindex="4" class="frm-btn-x" type="submit" name="Edit" title="Guardar" id="Edit" value="Guardar" />
							<input tabindex="5" class="frm-btn-x" type="button" name="cancel" title="Cancelar" id="Cancel" value="Cancelar" onclick="javascript:window.location=('<?php echo $_SERVER['PHP_SELF'];?>');"/>
							<input  tabindex="7" class="frm-btn-x" type="button" name="probar" title="Verifica si realiza la consiulta de los datos con la sentencia" id="Probar Sentencia" value="Probar Sentencia" onclick="probar_sentencia()" />
						</TD>
					</tr>
				</table>
	<div id="resultado"></div>
			</fieldset>
		</form>
		<script language="JavaScript" type="text/javascript"> var frmvalidator = new Validator("edit"); 
			frmvalidator.addValidation("id_conexion","dontselect=0","El nombre de la conexion es requerido");
			frmvalidator.addValidation("nombre_consulta","req","Nombre de la consulta es requerido"); 
			frmvalidator.addValidation("sentencia_sql","req","La sentencia SQL es requerida"); 
		</script>
		<?php 
	} else { ?>
		<p class="error">Id not founded!</p>
		<?php 
	}
	bdd_cerrar($cnx);
}

function actualizar_sentencia($id){
	$cnx = bdd_conectar();
	$id_conexion =(isset($_POST['id_conexion'])) ? $_POST['id_conexion'] : "";
	$nombre_consulta =(isset($_POST['nombre_consulta'])) ? $_POST['nombre_consulta'] : "";
	$sentencia_sql=(isset($_POST['sentencia_sql'])) ? $_POST['sentencia_sql'] : "";
	$comentario = (isset($_POST['comentario'])) ? $_POST['comentario'] : "";
	$q = "UPDATE sentencia SET
			id_conexion= 	".$id_conexion."
		,	nombre_consulta='".$nombre_consulta."'
		,	sentencia_sql= 	'".$sentencia_sql."' 
                ,	comentario= 	'".$comentario."'
		where 
			id_sentencia="	.$id;
	$rs = bdd_pg_query($cnx, $q);
	$af = bdd_pg_affected_rows($rs);
	//echo pg_error($cnx);
	if ($af){ 
		?>
		<p class="ok">Registro modificado!</p>
		<p > Regresar a  <a href="/indicadores/conexiones/sentencia/">Sentencia SQL</a></p>
		<?php
	} else { 
		?>
		<p class="error">El registro no ha sufrido modificacion<br />
		<p > Regresar a  <a href="/indicadores/conexiones/sentencia/">Sentencia SQL</a></p>
		  <?php echo mysqli_error($cnx);?></p>
		<?php
	}
	bdd_cerrar($cnx); 
}

function borrar_sentencia($id){
	if ($id == NULL) {
		?>
		<p class="error"> Id no v&aacute;lido, intente nuevamente.</p>
		<?php
	} else {
		$cnx = bdd_conectar();
                $q = "DELETE FROM sentencia WHERE id_sentencia=".$id;
                $rs = @bdd_pg_query($cnx, $q);
                if ($rs){
                    $num=@bdd_pg_num_rows($rs);
                        ?>
                        <p class="ok"> Borrado exitosamente.</p>
                        <p > Regresar a <a href="/indicadores/conexiones/sentencia/">Sentencias</a></p>
                        <?php
                } else {
                        ?>
                        <p class="error">ha ocurrido un error, no eliminado, intente nuevamente.</p>
                         <?php
                }
	}
        bdd_cerrar($cnx);
}


function general_fillCmbS($table, $field, $id, $desc, $selected= NULL, $disable =NULL, $cnx){
	$qry ="	SELECT 
			conexion.id_conexion, 
			conexion.nombre_conexion, 
			motor_bd.nombre_motor, 
			conexion.ip, 
			conexion.nombre_base_datos
		FROM 
			public.conexion, 
			public.motor_bd
		WHERE 
			conexion.id_motor = motor_bd.id_motor";
	$result = bdd_pg_query($cnx,$qry);
	while ($row = bdd_pg_fetch_row($result)) {
		if ($row[0]==$selected) {
			echo ('<option value="'.$row[0].'" selected>'.$row[1].'-'.$row[2].'-'.$row[3].'-'.$row[4].'</option>');
		} else {
			echo ('<option value="'.$row[0].'">'.$row[1].' - '.$row[2].' - '.$row[3].' - '.$row[4].'</option>');
		}
	}
}



?>