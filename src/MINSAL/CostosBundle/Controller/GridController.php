<?php

namespace MINSAL\CostosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use MINSAL\CostosBundle\Entity\Formulario;

class GridController extends Controller
{
    /**
     * @Route("/grid/{id}/{periodo_ingreso}", name="get_grid_data", options={"expose"=true})
     * @Template()
     */
    public function getGridDataAction(Formulario $Frm, $periodo_ingreso, Request $request)
    {
        $response = new Response();
        $em = $this->getDoctrine()->getManager();
        
        $tipo_periodo = null;
        if (strpos($periodo_ingreso, '_') !== false){
            $periodos_sel = split('_', $periodo_ingreso);
            $tipo_periodo = $periodos_sel[0];
            if ($periodos_sel[0]=='pu'){
                $periodoEstructura = $em->getRepository("CostosBundle:PeriodoIngresoDatosFormulario")->find($periodos_sel[1]);
            }else {
                $periodoEstructura = $em->getRepository("CostosBundle:PeriodoIngresoGrupoUsuarios")->find($periodos_sel[1]);
            }
        } else{
            $periodoEstructura = $em->getRepository("CostosBundle:PeriodoIngresoDatosFormulario")->find($periodo_ingreso);
        }
                
        if (!$periodoEstructura) {
            //$response->setContent('{"estado" : "ok", "data": []}');
            $response->setContent('{"estado" : "error", "msj": "' . $this->get('translator')->trans('_parametros_no_establecidos_') . '"}');
        } else{
            $data = $em->getRepository('CostosBundle:Formulario')->getDatos($Frm, $periodoEstructura->getId(),$tipo_periodo , $request);
            if (count($data) > 0){
                $data_ = '';
                $ultimo = array_pop($data);
                foreach ($data as $f){
                    $data_ .= '{'.  str_replace('=>', ':', $f['datos']). '},';
                }
                $data_ .= '{'.  str_replace('=>', ':', $ultimo['datos']). '}';

                $response->setContent('{"estado" : "ok", "data": ['. $data_. ']}');
            }
        }
        return $response;
    }
    
    /**
     * @Route("/grid/save/{id}/{periodo_ingreso}", name="set_grid_data", options={"expose"=true})
     * @Template()
     */
    public function setGridDataAction(Formulario $Frm, $periodo_ingreso, Request $request)
    {
        $data_ = '';
        $response = new Response();
        $em = $this->getDoctrine()->getManager();
        
        $tipo_periodo = null;
        if (strpos($periodo_ingreso, '_') !== false){
            $periodos_sel = split('_', $periodo_ingreso);
            $tipo_periodo = $periodos_sel[0];
            if ($periodos_sel[0]=='pu'){
                $periodoEstructura = $em->getRepository("CostosBundle:PeriodoIngresoDatosFormulario")->find($periodos_sel[1]);
            }else {
                $periodoEstructura = $em->getRepository("CostosBundle:PeriodoIngresoGrupoUsuarios")->find($periodos_sel[1]);
            }
        } else{
            $periodoEstructura = $em->getRepository("CostosBundle:PeriodoIngresoDatosFormulario")->find($periodo_ingreso);
        }
        
        $datos = json_decode($request->get('fila'), true);        
        //var_dump(in_array('regla_validacion', $datos));
        if ($datos['regla_validacion'] != ''){
            $regla = $datos['regla_validacion'];
            
            foreach ($datos as $n=>$d){
                //Buscar campos pivote: cant_mensual y importe_mensual, por el momento
                if (strpos($n, 'cant_mensual_') !== false or strpos($n, 'importe_mensual_') !== false){
                    $r_ = true;
                    //aplicar la regla de validación
                    $regla_ = str_replace('value', $d, $regla);
                    $regla_msj = str_replace('value', 'valor_celda', $regla);
                    
                    eval('$r_ = ('.$regla_.');');
                    if (!$r_){
                        $response->setContent('{"estado" : "error", "msj": "' . $this->get('translator')->trans('_error_validation_') .' <h3>'.$regla_msj. '</h3>"}');
                        return $response;
                    }
                }
            }
        }
        
        //$periodoEstructura =  $em->getRepository('CostosBundle:PeriodoIngresoDatosFormulario')->find($periodo_ingreso);
        if (!$periodoEstructura) {
            $response->setContent('{"estado" : "error", "msj": "' . $this->get('translator')->trans('_parametros_no_establecidos_') . '"}');
        } else{
            $guardar = $em->getRepository('CostosBundle:Formulario')->setDatos($Frm, $periodoEstructura->getId(), $tipo_periodo, $request);
        
            if ($guardar == false){
                $response->setContent('{"estado" : "error", "msj": "' . $this->get('translator')->trans('_error_datos_no_guardados_') . '"}');
            }
            else{
                $fila = array_pop($guardar);            
                $data_ .= '{'.  str_replace('=>', ':', $fila['datos']). ', "local": "si"}';

                $response->setContent('{"estado" : "ok", "data": '. $data_. '}');
            }
        }
        return $response;
    }
    
    /**
     * @Route("/estructura/establecimiento/{codigo_establecimiento}/dependencias", name="get_dependencias", options={"expose"=true})
     */
    public function getDependencias($codigo_establecimiento, Request $request)
    {
        $response = new Response();
        $em = $this->getDoctrine()->getManager();
        
        // Dejaré las consultas aquí porque aún no está definido cómo será la 
        // estructura de tablas que tendrán estos datos
        $sql = "SELECT A.codigo, A.nombre, B.nombre as nombre_unidad_organizativa
                    FROM costos.estructura A
                    INNER JOIN costos.estructura B ON (A.parent_id = B.id)                    
                    WHERE B.parent_id IN 
                        (SELECT id FROM costos.estructura
                            WHERE codigo = '$codigo_establecimiento' 
                        )
                    ORDER BY B.nombre, A.nombre
                        ";
        $dependencias = $em->getConnection()->executeQuery($sql)->fetchAll();
        
        $dependencias_html = "<OPTION VALUE=''>".$this->get('translator')->trans('_seleccione_dependencia_')."</option>";
        foreach ($dependencias as $d){
            $dependencias_html .= "<OPTION VALUE='$d[codigo]'>$d[nombre_unidad_organizativa] -- $d[nombre]</OPTION>";
        }
        $response->setContent($dependencias_html);
        
        return $response;
        
    }
}