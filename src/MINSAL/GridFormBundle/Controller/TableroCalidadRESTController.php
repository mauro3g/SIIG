<?php

namespace MINSAL\GridFormBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\HttpFoundation\Request;

class TableroCalidadRESTController extends Controller {

    private $meses = array(1 => 'Ene.',
            2 => 'Feb.',
            3 => 'Mar.',
            4 => 'Abr.',
            5 => 'May.',
            6 => 'Jun.',
            7 => 'Jul.',
            8 => 'Ago.',
            9 => 'Sep.',
            10 => 'Oct.',
            11 => 'Nov.',
            12 => 'Dic.'
        );
    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/tablero-calidad/evaluaciones", options={"expose"=true})
     * @Rest\View
     */
    public function getPeriodosEvaluacionAction() {
        $response = new Response();

        $resp = array();

        $em = $this->getDoctrine()->getManager();
        
        $data = $em->getRepository('GridFormBundle:Formulario')->getPeriodosEvaluacion();
        //Verificar que existen las tablas necesarias
        $em->getRepository('GridFormBundle:Indicador')->crearTabla();
        
        $data_ = array();
        foreach ($data as $f) {
            $f['etiqueta'] = $this->meses[$f['mes']] . '/' . $f['anio'];
            $data_[] = $f;
        }
        if (count($data_) == 0) {
            $response->setContent('{"estado" : "error", "msj": "' . $this->get('translator')->trans('_no_datos_') . '"}');
        } else {
            $response->setContent(json_encode($data_));
        }

        return $response;
        //}
    }
    
    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/tablero-calidad/evaluaciones_comple", options={"expose"=true})
     * @Rest\View
     */
    public function getEvaluacionesComplementariasAction() {
        $response = new Response();

        $resp = array();

        $em = $this->getDoctrine()->getManager();
        
        $data[0]['nacional'] = $em->getRepository('GridFormBundle:Indicador')->getEvaluacionesComplementariasNacional();
        $data[0]['establecimiento'] = $em->getRepository('GridFormBundle:Indicador')->getEvaluacionesComplementarias(null, true);
        
        $response->setContent(json_encode($data));
        

        return $response;
        //}
    }

    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/tablero-calidad/establecimientos/{periodo}/{nivel}", options={"expose"=true})
     * @Rest\View
     */
    public function getEstablecimientosEvaluadosAction($periodo, $nivel) {

        $response = new Response();
        $em = $this->getDoctrine()->getManager();

        $eval_compl = $em->getRepository('GridFormBundle:Indicador')->getEvaluacionesComplementarias();
        $em->getRepository('GridFormBundle:Indicador')->getIndicadoresEvaluadosListaChequeo($periodo);
        
        $establecimientos = $em->getRepository('GridFormBundle:Indicador')->getEvaluacionEstablecimiento($periodo, $nivel);
        
        $resp = array();
        foreach ($establecimientos as $f){
            $f['category'] = $f['nombre_corto'];
            $f['measure'] = $f['calificacion'];
            $f['color'] = ($f['color'] != '') ? $f['color'] : '#0EAED8';
            $f['nombre'] = $f['nombre_establecimiento'];
            $f['evaluaciones_externas'] = (array_key_exists($f['establecimiento'], $eval_compl)) ? $eval_compl[$f['establecimiento']] : array();
            $resp[] = $f;
        }
        
        $response->setContent(json_encode($resp));

        return $response;
    }

    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/tablero-calidad/evaluaciones/{establecimiento}/{periodo}", options={"expose"=true})
     * @Rest\View
     */
    public function getEvaluacionesAction($establecimiento, $periodo) {
        $response = new Response();
        $em = $this->getDoctrine()->getManager();

        // Evaluaciones por lista de chequeo
        $data = $em->getRepository('GridFormBundle:Indicador')->getEvaluaciones($establecimiento, $periodo);
        
        //Obtener las otras evaluaciones que no son lista de chequeo
        $data2 = $em->getRepository('GridFormBundle:Indicador')->getEvaluacionesNOListaChequeo($establecimiento, $periodo);
        
        $resp = array();        
        foreach ($data as $f){            
            $f['descripcion_estandar'] = $f['descripcion'];
            $f['codigo_estandar'] = $f['codigo'];
            $f['tipo_evaluacion'] = $f['forma_evaluacion'];
            $f['brecha'] = $f['meta'] - $f['calificacion'];
            $f['meta'] = $f['meta'];
            $f['measure'] = $f['calificacion'];
            $f['color'] = $f['color'];
            $f['value'] = $f['calificacion'] / 100;            
            $f['category'] = $f['codigo'];
            $resp[] = $f;
        }
        
        foreach($data2 as $f){            
            $resp[] = $f;
        }
        
        $response->setContent(json_encode($resp));

        return $response;
    }

    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/tablero-calidad/criterios/{establecimiento}/{periodo}/{formulario}", options={"expose"=true})
     * @Rest\View
     */
    public function getCriteriosAction($establecimiento, $periodo, $formulario) {
        $response = new Response();
        $em = $this->getDoctrine()->getManager();

        $datos_resumen = $em->getRepository('GridFormBundle:Formulario')->getCriterios($establecimiento, $periodo, $formulario);
        $datos = $datos_resumen['datos'];

        $data = array();
        foreach ($datos as $d) {
            $data[$d['codigo']]['descripcion'] = $d['descripcion'];
            $data[$d['codigo']]['forma_evaluacion'] = $d['forma_evaluacion'];
            $data[$d['codigo']]['criterios'][] = json_decode('{' . str_replace('=>', ':', $d['datos'].', "codigo_indicador": "'.$d['codigo_indicador'].'"' ) . '}', true);
        }
        $data_ = array();
        foreach ($data as $d) {
            $data_[] = array('descripcion' => $d['descripcion'], 'forma_evaluacion' => $d['forma_evaluacion'],
                'criterios' => $d['criterios'],
                'resumen_expedientes' => $datos_resumen['resumen_expedientes'],
                'resumen_criterios' => $datos_resumen['resumen_criterios'],
                'resumen_indicadores' => $datos_resumen['resumen_indicadores'],
            );
        }
        $resp = json_encode($data_);

        $response->setContent($resp);

        return $response;
    }
    
    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/tablero-calidad/encabezado_evaluacion/{establecimiento}/{periodo}/{formulario}", options={"expose"=true})
     * @Rest\View
     */
    public function getEncabezadoAction($establecimiento, $periodo, $formulario) {
        $response = new Response();
        $em = $this->getDoctrine()->getManager();
     
        $encabezado = $em->getRepository('GridFormBundle:Formulario')->getEncabezado($establecimiento, $periodo, $formulario);
        $aux = array();
        foreach ($encabezado as $k=>$v){
            $aux[$this->get('translator')->trans('_'.$k.'_')] = $v;            
        }
        $resp[] = $aux; 

        $response->setContent(json_encode($resp));

        return $response;
    }

    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/tablero-calidad/historial/{establecimiento}/{periodo}", options={"expose"=true})
     * @Rest\View
     */
    public function getHistorialEstablecimientoAction($establecimiento, $periodo) {
        $response = new Response();
        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('GridFormBundle:Indicador')->getHistorialEstablecimiento($establecimiento, $periodo);
        $resp = array();        
        foreach ($data as $f){            
            $f['category'] = $this->meses[$f['mes']].'/'.$f['anio'];  
            $f['measure'] = $f['calificacion'];
            $resp[] = $f;
        }
        $response->setContent(json_encode($resp));

        return $response;
    }
    
    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/calidad/indicadores/{periodo}/{tipo}", options={"expose"=true})
     * @Rest\View
     */
    public function getIndicadoresCalidadEvaluadosAction($periodo, $tipo) {
        
        $response = new Response();
        $em = $this->getDoctrine()->getManager();

        if ($tipo == 2){
            $data = $em->getRepository('GridFormBundle:Indicador')->getIndicadoresEvaluadosNumericos($periodo);
            $aux = array();
            foreach ($data as $k=>$d){
                $aux = json_decode(str_replace(array('\\', '"{', '}"', '{{', '}}'), array('', '{', '}', '[{', '}]'), $d['historial']), true);
                $etiquetas = array();
                $valores = array();
                foreach ($aux as $f){
                    $etiquetas[] = $f['mes'];
                    $valores[] = $f['valor'];
                }
                $data[$k]['historial'] = array('etiquetas'=>$etiquetas, 'valores'=> $valores);
            }
        }
        else {
            $data = $em->getRepository('GridFormBundle:Indicador')->getIndicadoresEvaluadosListaChequeo($periodo);
        }
        $resp = (count($data) == 0)? array(): $data;
        
        $response->setContent(json_encode($resp));
        return $response;
    }
    
    /**
     * Obtener los datos del formulario
     * @Get("/rest-service/calidad/indicador/{periodo}/{id}", options={"expose"=true})
     * @Rest\View
     */
    public function getDetalleIndicadorCalidadAction($periodo, $id) {
        
        $response = new Response();
        $em = $this->getDoctrine()->getManager();

        
        $data[] = $em->getRepository('GridFormBundle:Indicador')->getDetalleIndicador($periodo, $id);        
        //$data[] = $em->getRepository('GridFormBundle:Indicador')->getDetalleIndicador($periodo, $id);
        
        $resp = (count($data) == 0)? array(): $data;
        
        $response->setContent(json_encode($resp));
        return $response;
    }

}
