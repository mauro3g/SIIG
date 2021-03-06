<?php

namespace MINSAL\GridFormBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class FormularioAdminController extends Controller {

    public function mostrarPlantilla(Request $request, $codigoFrm, $pk, $titulo, $mostrar_resumen, $plantilla, $editable = true) {
        $em = $this->getDoctrine()->getManager();
        $meses = Array();

        $periodo = (is_null($request->get('periodo_estructura')) ) ? '-1' : $request->get('periodo_estructura');
        $numero_carga = (is_null($request->get('periodo_estructura')) ) ? '1' : '2';
        $tipo_periodo = null;
        if ($periodo != '-1') {
            $periodos_sel = explode('_', $periodo);
            $tipo_periodo = $periodos_sel[0];
            if ($periodos_sel[0] == 'pu') {
                $periodoSeleccionado = $em->getRepository("GridFormBundle:PeriodoIngresoDatosFormulario")->find($periodos_sel[1]);
            } else {
                $periodoSeleccionado = $em->getRepository("GridFormBundle:PeriodoIngresoGrupoUsuarios")->find($periodos_sel[1]);
            }
        } else {
            $periodoSeleccionado = null;
        }

        $parametros = $this->getParametros2($periodoSeleccionado, $tipo_periodo);

        $parametros['tipo_periodo'] = $tipo_periodo;
        $periodos = array();
        $orden_ = array();

        // Si es el código de formulario de captura de datos, 
        // pueden haber varios formularios para el usuario
        $periodosEstructura = array();
        if ($codigoFrm == 'captura_variables') {
            $Frm = null;
            if ($periodo != '-1') {
                //Si ya se eligió un periodo ya se puede determinar el formulario, seleccionado
                $Frm = $periodoSeleccionado->getFormulario();
            }
            //Buscar todos los formularios del areaCosteo almacen_datos asignados al usuario
            $aux = $em->getRepository("GridFormBundle:PeriodoIngresoDatosFormulario")
                    ->findBy(array('usuario' => $this->getUser()), array('formulario' => 'ASC', 'periodo' => 'ASC'));

            foreach ($aux as $p) {
                if ($p->getFormulario()->getAreaCosteo() == 'almacen_datos' or $p->getFormulario()->getAreaCosteo() == 'calidad')
                    $periodosEstructura [] = $p;
            }

            //Buscar los permisos para el formulario asignado por grupo de usuarios
            //Verificar que el usuario tiene una unidad principal para ingreso de datos
            if ($this->getUser()->getEstablecimientoPrincipal() != null) {
                foreach ($this->getUser()->getGroups() as $g) {
                    $aux_ = $em->getRepository("GridFormBundle:PeriodoIngresoGrupoUsuarios")
                            ->findBy(array('grupoUsuario' => $g), array('formulario' => 'ASC', 'periodo' => 'ASC'));
                    foreach ($aux_ as $p) {
                        $llave = $p->getPeriodo()->getAnio() . $this->getUser()->getEstablecimientoPrincipal()->getId() . $p->getFormulario()->getId();
                        $orden = $this->getUser()->getEstablecimientoPrincipal()->getId() . $p->getPeriodo()->getAnio() . $p->getFormulario()->getPosicion();
                        $periodos[$llave] = array('id' => 'pg_' . $p->getId(),
                            'periodo_anio' => $p->getPeriodo()->getAnio(),
                            'periodo_mes' => $p->getPeriodo()->getMesTexto(),
                            'unidad' => $this->getUser()->getEstablecimientoPrincipal(),
                            'formulario' => $p->getFormulario()
                        );
                        $orden_[$llave] = (float) $orden;
                        $meses[$p->getPeriodo()->getAnio()][] = $p->getPeriodo()->getMes();
                    }
                }
            }
        } else {
            $Frm = $em->getRepository('GridFormBundle:Formulario')->findOneBy(array('codigo' => $codigoFrm));
            $periodosEstructura = $em->getRepository("GridFormBundle:PeriodoIngresoDatosFormulario")
                    ->findBy(array('usuario' => $this->getUser(), 'formulario' => $Frm), array('periodo' => 'ASC', 'unidad' => 'ASC', 'formulario' => 'ASC'));
        }
        $i = 0;
        foreach ($periodosEstructura as $p) {
            $mes = ($p->getFormulario()->getPeriodoLecturaDatos() == 'mensual') ? $p->getPeriodo()->getMes() : '';

            $llave = $p->getPeriodo()->getAnio() . $mes . $p->getUnidad()->getId() . $p->getFormulario()->getId();
            $orden = $p->getUnidad()->getId() . $p->getPeriodo()->getAnio() . $p->getFormulario()->getPosicion();
            $periodos[$llave] = array('id' => 'pu_' . $p->getId(),
                'periodo_anio' => $p->getPeriodo()->getAnio(),
                'periodo_mes' => $p->getPeriodo()->getMesTexto(),
                'unidad' => $p->getUnidad(),
                'formulario' => $p->getFormulario(),
                'orden' => $orden
            );
            $orden_[$llave] = (float) $orden;
            if ($Frm == $p->getFormulario())
                $meses[$p->getPeriodo()->getAnio()][] = $p->getPeriodo()->getMes();
        }
        array_multisort($orden_, SORT_ASC, $periodos);
        //Agrupar los periodos por unidad
        $periodos_aux = array();
        foreach ($periodos as $p) {
            $periodos_aux[$p['unidad']->getCodigo()]['unidad'] = $p['unidad'];
            $periodos_aux[$p['unidad']->getCodigo()]['datos'][] = $p;
        }

        $parametrosPlantilla = array(
            'url' => 'get_grid_data',
            'url_save' => 'set_grid_data',
            'parametros' => $parametros,
            'periodos' => $periodos_aux,
            'tipo_periodo' => $tipo_periodo,
            'periodoSeleccionado' => $periodoSeleccionado,
            'titulo' => $titulo,
            'numero_carga' => $numero_carga,
            'editable' => $editable,
            'encabezado' => array(),
            'mostrar_resumen' => $mostrar_resumen,
            'cantidad_formularios' => 0,
            'pk' => $pk);
        if ($periodo != '-1') {
            $formularios = $em->getRepository('GridFormBundle:Formulario')->findBy(array('formularioSup' => $Frm), array('codigo' => 'ASC'));
            if ($formularios == null or count($formularios) == 0) {
                $formularios[] = $Frm;
            }

            $parametrosPlantilla['Frm'] = $Frm;
            $parametrosPlantilla['llave_primaria'] = $pk;
            $parametrosPlantilla['cantidad_formularios'] = count($formularios);
            $parametrosPlantilla['Formularios'] = $this->ajustarFormulas($formularios);
            $parametrosPlantilla['meses_activos'] = $meses[$periodoSeleccionado->getPeriodo()->getAnio()];
            //Recuperar los datos de encabezado, si los tiene
            if ($tipo_periodo == 'pg') {
                $unidad = $this->getUser()->getEstablecimientoPrincipal()->getCodigo();
            } else {
                $unidad = $periodoSeleccionado->getUnidad()->getCodigo();
            }
            $parametrosPlantilla['encabezado'] = $em->getRepository("GridFormBundle:Formulario")->obtenerEncabezado($periodoSeleccionado, $unidad);

            foreach ($formularios as $frm) {
                $parametrosPlantilla['origenes'][$frm->getId()] = $this->getOrigenes($frm, $parametros);
                $parametrosPlantilla['pivotes'][$frm->getId()] = $this->getPivotes($frm, $parametros);
                $parametrosPlantilla['tipos_datos_por_filas'][$frm->getId()] = $em->getRepository('GridFormBundle:Formulario')->tipoDatoPorFila($frm);
            }
        }

        return $this->render($plantilla, $parametrosPlantilla);
    }

    /**
     * 
     * @param Formulario[] $formularios
     * @return Formulario[] $frm_ajustados
     * 
     * Esta funciones cambia las fórmulas, que fueron ingresados con el código
     * de la variable, al correspondiente número de fila en el grid. En el grid
     * la fórmula se ejecuta por número de fila que ocupan.
     */
    protected function ajustarFormulas($formularios) {
        $em = $this->getDoctrine()->getManager();
        $frm_ajustados = array();
        foreach ($formularios as $f) {
            
            $buscarPor = ($f->getVersion() != null) ? array('formulario' => $f, 'versionFormulario'=>$f->getVersion()) : array('formulario' => $f);
            $var_ = $em->getRepository("GridFormBundle:VariableCaptura")->findBy($buscarPor, array('posicion' => 'ASC'));
            
            $i = 0;
            $formula = $f->getCalculoFilas();
            $busqueda = array();
            $reemplazo = array();
            foreach ($var_ as $v) {
                //Fórmula de cálculo
                //Si hay varios espacios en blanco sustituirlo por uno solo
                $f_aux = str_replace (array('if(', 'if ('), 'ifX(' , preg_replace ('/[ ]+/', ' ', $v->getFormulaCalculo()) );                 
                //Verificar si se ingresó la fórmula completa (cuando existe la combinación := )
                $f_ = ( ( $f_aux !=  '' and strpos($f_aux, ':=') === false  ) ? '${'.$v->getCodigo().'} := ' : '' ) . $f_aux;
                $formulaVariable = ( $f_ != '') ? '::' . $f_ : '';
                
                //Lógica de salto
                //Si hay varios espacios en blanco sustituirlo por uno solo
                $l_aux = str_replace (array('if(', 'if ('), 'ifX(' , preg_replace ('/[ ]+/', ' ', $v->getLogicaSalto()) );                 
                //Verificar si se ingresó la fórmula completa (cuando existe la combinación := )
                $l_ = ( ( $l_aux !=  '' and strpos($l_aux, ':=') === false  ) ? '${'.$v->getCodigo().'}_LS := ' : '' ) . $l_aux;
                $logicaSalto = ( $l_ != '') ? '::' . $l_ : '';
                
                //Validaciones
                //Si hay varios espacios en blanco sustituirlo por uno solo
                $regla = str_replace('value', '${'.$v->getCodigo().'}', $v->getReglaValidacion());
                $chk_aux = str_replace (array('if(', 'if ('), 'ifX(' , preg_replace ('/[ ]+/', ' ', $regla) );                 
                //Verificar si se ingresó la fórmula completa (cuando existe la combinación := )
                $chk_ = ( ( $chk_aux !=  '' and strpos($chk_aux, ':=') === false  ) ? '${'.$v->getCodigo().'}_CHECK := ' : '' ) . $chk_aux;
                $reglaValidacion = ( $chk_ != '') ? '::' . $chk_ . '//' . $v->getMensajeValidacion() : '';
                
                $busqueda[] = '{' . $v->getCodigo() . '}';
                $reemplazo[] = '{F' . $i++ . '}';
                $formula = str_replace($busqueda, $reemplazo, $formula . $formulaVariable);
                $formula = str_replace($busqueda, $reemplazo, $formula . $logicaSalto);
                $formula = str_replace($busqueda, $reemplazo, $formula . $reglaValidacion);
            }
            $formula = trim($formula, '::');
            $f->setCalculoFilas($formula);
            
            $frm_ajustados[] = $f;
        }

        return $frm_ajustados;
    }

    public function almacenDatosAction(Request $request) {
        return $this->mostrarPlantilla($request, 'captura_variables', 'codigo_variable', '_captura_datos_', false, 'GridFormBundle:Formulario:parametros.html.twig');
    }

    protected function getOrigenes($Frm, $parametros) {
        $em = $this->getDoctrine()->getManager();
        $origenes = array();
        foreach ($Frm->getCampos() as $c) {
            if ($c->getOrigen() or $c->getSignificadoCampo()->getCatalogo() != '') {
                $origenes[$c->getSignificadoCampo()->getCodigo()] = $em->getRepository('GridFormBundle:Campo')->getOrigenCampo($c, $parametros);
            }
        }
        return $origenes;
    }

    protected function getPivotes($Frm, $parametros) {
        $em = $this->getDoctrine()->getManager();
        $pivotes = array();
        foreach ($Frm->getCampos() as $c) {
            if ($c->getOrigenPivote()) {
                $pivotes[$c->getSignificadoCampo()->getCodigo()] = $em->getRepository('GridFormBundle:Campo')->getOrigenPivote($c, $parametros);
            }
        }
        return $pivotes;
    }

    protected function getParametros2($periodoIngreso, $tipo_periodo) {
        $parametros = array();
        if ($tipo_periodo == 'pu') {
            $unidad = $periodoIngreso->getUnidad();
        } elseif ($tipo_periodo == 'pg') {
            $unidad = $this->getUser()->getEstablecimientoPrincipal();
        }
        if ($periodoIngreso != null) {
            if ($periodoIngreso->getFormulario()->getPeriodoLecturaDatos() == 'mensual')
                $parametros['mes'] = $periodoIngreso->getPeriodo()->getMes();
            $parametros['anio'] = $periodoIngreso->getPeriodo()->getAnio();
            if ($unidad->getNivel() == 1) {
                $parametros['establecimiento'] = $unidad->getCodigo();
            } elseif ($unidad->getNivel() == 2) {
                $parametros['establecimiento'] = $unidad->getParent()->getCodigo();
                $parametros['dependencia'] = $unidad->getId();
            } elseif ($unidad->getNivel() == 3) {
                $parametros['establecimiento'] = $unidad->getParent()->getParent()->getCodigo();
                $parametros['dependencia'] = $unidad->getCodigo();
            }
            $parametros['periodo_estructura'] = $tipo_periodo . '_' . $periodoIngreso->getId();
        } else {
            $parametros = array('anio_mes' => null,
                'anio' => null,
                'establecimiento' => null,
                'dependencia' => null,
                'periodo_estructura' => null
            );
        }
        return $parametros;
    }

    protected function getParametros($r) {
        return array('anio_mes' => $r->get('anio_mes'),
            'anio' => $r->get('anio'),
            'establecimiento' => $r->get('establecimiento'),
            'dependencia' => $r->get('dependencia'),
            'periodo_estructura' => $r->get('periodo_estructura')
        );
    }

    public function getFromKoboAction() {
        $em = $this->getDoctrine()->getManager();
        $ch = curl_init();
        $datos_capturados = array();

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, 'pna:pr1mern1vel');

        // Estándares         1       2       3        4         5
        //$estandares = array(11=>96, 12=>97, 13=>98, 14 => 99, 15=>103);
        // Versión 2
        // Estándares         1           2        3         4         5        29
        $estandares = array(28 => 96, 41 => 97, 31 => 98, 32 => 99, 29 => 103, 44=>93);

        foreach ($estandares as $idFrmKobo => $idFrmCalidad) {
            curl_setopt($ch, CURLOPT_URL, "https://kc.kobo.salud.gob.sv/api/v1/data/$idFrmKobo?format=json");
            $response = curl_exec($ch);
            $data = json_decode($response, true);
            
            foreach ($data as $datos) {
                //Buscar llave del periodo evaluado
                $kFechaPeriodo = '';
                
                foreach ($datos as $k => $v) {
                    if (array_pop(explode('/', $k)) == 'fech_fin_periodo_eval')
                        $kFechaPeriodo = $k;
                }
                $formulario = $idFrmCalidad;

                $fechaEval = explode('-', $datos[$kFechaPeriodo]);

                //$fechaEval = explode('-', $datos['periodo_evaluacion/fech_fin_periodo_eval']);
                $mes = $fechaEval[1];
                $anio = $fechaEval[0];
                $establecimiento = $datos['establecimiento'];

                if (array_key_exists('group_expedientes_4_1', $datos)) {
                    //4.1
                    $expedientes = $this->getExpedientesKobo($datos['group_expedientes_4_1']);
                    $em->getRepository("GridFormBundle:Formulario")->setDatosFromKobo(100, $mes, $anio, $establecimiento, $expedientes);
                }
                if (array_key_exists('group_expedientes_4_2', $datos)) {
                    //4.2 
                    $expedientes = $this->getExpedientesKobo($datos['group_expedientes_4_2']);
                    $em->getRepository("GridFormBundle:Formulario")->setDatosFromKobo(101, $mes, $anio, $establecimiento, $expedientes);
                }
                if (array_key_exists('group_expedientes_4_3', $datos)) {
                    //4.3
                    $expedientes = $this->getExpedientesKobo($datos['group_expedientes_4_3']);
                    $em->getRepository("GridFormBundle:Formulario")->setDatosFromKobo(102, $mes, $anio, $establecimiento, $expedientes);
                }
                if (array_key_exists('group_expedientes', $datos)) {
                    $expedientes = $this->getExpedientesKobo($datos['group_expedientes']);
                    $em->getRepository("GridFormBundle:Formulario")->setDatosFromKobo($formulario, $mes, $anio, $establecimiento, $expedientes);
                }
            }
        }

        // Estándares                 6   
        $estandaresMensual = array(30 => 104);

        foreach ($estandaresMensual as $idFrmKobo => $idFrmCalidad) {
            curl_setopt($ch, CURLOPT_URL, "https://kc.kobo.salud.gob.sv/api/v1/data/$idFrmKobo?format=json");
            $response = curl_exec($ch);
            $data = json_decode($response, true);

            if (count($data) > 0) {
                foreach ($data as $datos) {

                    $formulario = $idFrmCalidad;
                    $fechaEval = explode('-', $datos['group_xf0ku39/fech_fin_periodo_eval']);
                    $mes = $fechaEval[1];
                    $anio = $fechaEval[0];
                    $establecimiento = $datos['establecimiento'];
                    $expedientes = $this->getDatosMensualKobo($datos, $mes);

                    $em->getRepository("GridFormBundle:Formulario")->setDatosFromKobo($formulario, $mes, $anio, $establecimiento, $expedientes);
                }
            }
        }


        return $this->render('GridFormBundle:Formulario:carga-from-kobo.html.twig', array());
    }

    protected function getExpedientesKobo($expedientesKobo) {
        $expedientes = array();
        foreach ($expedientesKobo as $num => $exp) {
            foreach ($exp as $k => $v) {
                $var = array_pop(explode('/', $k));
                $valor = $this->procesarValor($v);
                $expedientes[$var]['num_expe5_' . ($num + 1)] = $valor;
            }
        }
        return $expedientes;
    }

    protected function getDatosMensualKobo($datosKobo, $mes) {
        $dm = array();
        foreach ($datosKobo as $k => $valor) {
            $var = array_pop(explode('/', $k));
            $v = $this->procesarValor($valor);
            $dm[$var]['cant_mensual_' . $mes] = $v;
        }
        return $dm;
    }
    
    protected function procesarValor($valor) {
        $v = ($valor == 'Infinity') ? 0 : $valor;
        
        if (!(is_array($valor)) and preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/', $valor) === 1){
            $f = explode('-', $valor);
            $v = $f[2].'/'.$f[1].'/'.$f[0];
        }
        return $v;
    }

}
