<?php

namespace MINSAL\IndicadoresBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


/**
* @Route("/reportes")
*/
class MatrizSeguimientoController extends Controller {

    /**
     * @Route("/matriz_seguimiento", name="matriz-seguimiento")
     * 
     */
    public function matrizSeguimientoAccion(Request $request){
        $admin_pool = $this->get('sonata.admin.pool');

        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->add('desde', HiddenType::class, array('label'=>$this->get('translator')->trans('_desde_')))
            ->add('hasta', HiddenType::class, array('label'=>$this->get('translator')->trans('_hasta_')))
            ->add('send', SubmitType::class, array('label'=>$this->get('translator')->trans('_cargar_reporte_')))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            return $this->mostrarMatrizSeguimientoAccion($data['desde'], $data['hasta']);
        }
        return $this->render('IndicadoresBundle:Reportes:matrizSeguimientoParametros.html.twig',
                    array ('form'=>$form->createView(),
                            'admin_pool' => $admin_pool,
                    ));
    }
    
    /**
     * @Route("/matriz_seguimiento/mostrar/{periodoInicio}/{peridoFin}", name="matriz-seguimiento-mostrar")
     */
    public function mostrarMatrizSeguimientoAccion($periodoInicio, $peridoFin) {
        $admin_pool = $this->get('sonata.admin.pool');
        
        $em = $this->getDoctrine()->getManager();
        
        //Obtener todos los datos del webform 12
        $Frm = $em->find('GridFormBundle:Formulario', 12);
        $datosFrm = $em->getRepository('GridFormBundle:Formulario')->getDatosRAW($Frm);
                
        
        $params = $this->getParametros($periodoInicio, $peridoFin);
        //var_dump($params); exit;
        
        $anios_ = array();
        // **************** OBTENCION DE DATOS DESDE ORIGENES DE DATOS
        //Información de los datos del NUMERADOR, obtenidos de orígenes de datos del etab
        $idOrigenesR = array(array('id'=>131, 'descripcion'=>'Embarazadas captadas antes 12 semanas del total esperado', 
                                    'codigo'=>'emb_cap_12_sema_tot_espe', 'acumular'=>true),
                            array('id'=>193, 'descripcion'=>'# de parto institucional atendidos por todos los prestadores de salud en los 14 municipios (medico/enfermera)', 
                                    'codigo'=>'partos_por_personal_calificado', 'acumular'=>true),
                            array('id'=>198, 'descripcion'=>'# de puérperas captadas antes de los 7 días', 
                                    'codigo'=>'captadas_antes_7_dias', 'acumular'=>true),
                            array('id'=>359, 'descripcion'=>'18376 niños de 12 a 59 meses reciben tratamiento dos dosis de tratamiento antiparasitarios al año', 
                                    'codigo'=>'ninos_2_dosis_antipari_anual', 'acumular'=>true),
                            array('id'=>195, 'descripcion'=>'% de niños con diarrea en UCSF y UROC tratados con SRO y Zinc', 
                                    'codigo'=>'ninos_diarrea_sro_zinc', 'acumular'=>true),
                            array('id'=>195, 'descripcion'=>'% de niños con diarrea en UCSF y UROC tratados con SRO y Zinc', 
                                    'codigo'=>'ninos_diarrea_sro_zinc', 'acumular'=>true),
                            array('id'=>385, 'descripcion'=>'Número de usuarias activas captadas para métodos de PF (anual)', 
                                    'codigo'=>'usu_act_captadas_pf', 'acumular'=>true)
                            );
        foreach ($idOrigenesR as $varR){
            $anios_[] = $this->getDatosFormateados($varR, 'real');
        }
        
        //Información de los datos del DENOMINADOR, obtenidos de orígenes de datos del etab
        $idOrigenesP = array(array('id'=>192, 'descripcion'=>'# de parto institucional atendidos por todos los prestadores de salud en los 14 municipios (medico/enfermera)', 
                                    'codigo'=>'partos_por_personal_calificado', 'acumular'=>true),
                            array('id'=>199, 'descripcion'=>'# de puérperas captadas antes de los 7 días', 
                                    'codigo'=>'captadas_antes_7_dias', 'acumular'=>true),
                            array('id'=>362, 'descripcion'=>'18376 niños de 12 a 59 meses reciben tratamiento dos dosis de tratamiento antiparasitarios al año', 
                                    'codigo'=>'ninos_2_dosis_antipari_anual', 'acumular'=>true)
                            );
        foreach ($idOrigenesP as $varP){
            $anios_[] = $this->getDatosFormateados($varP, 'planificado');
        }
        
        
        // ********* OBTENCIÓN DE DATOS DESDE INDICADORES
        //Información de los datos del NUMERADOR, obtenidos de orígenes de datos del etab
        $idOrigenesIndR = array(array('id'=>140, 'descripcion'=>'% de cobertura de vacunación con SPR', 
                                    'codigo'=>'vacunacion_spr', 'acumular'=>false, 'denominador'=>'NINIOS_12M_23M_SM2015')
                            );
        foreach ($idOrigenesIndR as $varR){
            $anios_[] = $this->getFromIndicador($varR, 'real');
        }
        
        
        // ********* OBTENCIÓN DE DATOS FIJOS
        $idOrigenesFijosP = array(
                                array('descripcion'=>'Embarazadas captadas antes 12 semanas del total esperado', 
                                        'codigo'=>'emb_cap_12_sema_tot_espe',
                                        'datos'=>array(
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_01_p', 'calculo'=>200),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_02_p', 'calculo'=>415),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_03_p', 'calculo'=>646),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_04_p', 'calculo'=>895),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_05_p', 'calculo'=>1162),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_06_p', 'calculo'=>1449),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_07_p', 'calculo'=>1757),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_08_p', 'calculo'=>2089),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_09_p', 'calculo'=>2446),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_10_p', 'calculo'=>2829),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_11_p', 'calculo'=>3242),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_12_p', 'calculo'=>3620)                                            
                                                )
                                    ),
                                array('descripcion'=>'Número de usuarias activas captadas para métodos de PF (anual)', 
                                        'codigo'=>'usu_act_captadas_pf',
                                        'datos'=>array(
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_01_p', 'calculo'=>2154),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_02_p', 'calculo'=>4154),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_03_p', 'calculo'=>6154),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_04_p', 'calculo'=>8654),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_05_p', 'calculo'=>11654),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_06_p', 'calculo'=>15154),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_07_p', 'calculo'=>19154),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_08_p', 'calculo'=>23654),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_09_p', 'calculo'=>28654),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_10_p', 'calculo'=>34154),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_11_p', 'calculo'=>37154),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_12_p', 'calculo'=>40531)                                            
                                                )
                                    ),
                                array('descripcion'=>'% de cobertura de vacunación con SPR', 
                                        'codigo'=>'vacunacion_spr',
                                        'datos'=>array(
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_01_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_02_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_03_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_04_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_05_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_06_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_07_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_08_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_09_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_10_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_11_p', 'calculo'=>95),
                                                    array('anio'=>2016, 'mes'=> 'cant_mensual_calidad_12_p', 'calculo'=>95)                                            
                                                )
                                    )            
                            );
        											

        foreach ($idOrigenesFijosP as $var){
            $anios_[] = $this->formatearDatos($var['datos'], $var); 
        }
        
        foreach ($anios_ as $aa){
            foreach ($aa as $a){
                array_push($datosFrm, $a);
            }
        }
        
        
        $datos_ = array();
        foreach ($datosFrm as $f){
            if (array_key_exists($f['anio'], $params)){
                
                foreach ($f as $k => $sf){
                    $mesVarR = str_replace('cant_mensual_calidad_', '', $k);
                    if (in_array($mesVarR, $params[$f['anio']])){
                        $datos_[$f['codigo_variable']]['real'][$mesVarR.'/'.$f['anio']] = $sf;                        
                    } else {
                        $mesVarP = str_replace('_p', '', str_replace('cant_mensual_calidad_', '', $k));
                        if (in_array($mesVarP, $params[$f['anio']])){
                            $datos_[$f['codigo_variable']]['planificado'][$mesVarP.'/'.$f['anio']] = $sf;
                        }
                    }
                }
                //Si no existia el mes requerido, no se creó el arreglo, crearlo vacio
                if (!array_key_exists($f['codigo_variable'],$datos_)){
                    $datos_[$f['codigo_variable']] = array();
                }

                if (array_key_exists('planificado', $datos_[$f['codigo_variable']])){
                    foreach ($datos_[$f['codigo_variable']]['planificado'] as $k=>$v){
                        $datos_[$f['codigo_variable']]['estatus'][$k] = ($v > 0) ? 
                        array_key_exists('real', $datos_[$f['codigo_variable']]) ?
                                array_key_exists($k, $datos_[$f['codigo_variable']]['real']) ? 
                                number_format(($datos_[$f['codigo_variable']]['real'][$k] / $v) * 100,0): null: null : 
                                null; 
                    }
                    $datosFrmFormat[$f['codigo_variable']]['datos']= $datos_[$f['codigo_variable']];
                    $datosFrmFormat[$f['codigo_variable']]['descripcion'] = $f['descripcion_variable'];
                    //$datosFrmFormat[$f['codigo_variable']]['categoria'] = $f['descripcion_categoria_variable'];
                }
            }
        }
        
        $categorias = array(array('descripcion'=>array('Porcentaje de mujeres en edad reproductiva (15-49) que actualmente utilizan (o cuya pareja utiliza) un método moderno de planificación familia'),
                                'datos'=> array('usu_act_captadas_pf', 'met_pla_fam', 'com_met_pf', 'met_pf_dist', 'car_didac_diu',
                                            'per_cap_diu'
                                        )
                                ),
                            array('descripcion'=>array('Porcentaje de mujeres en edad reproductiva (15-49) que recibieron su primer control prenatal por médico o enfermera antes de las 12 semanas de gestación en su embarazo más reciente en los últimos dos años.', 'Porcentaje de mujeres en edad reproductiva (15-49) que recibieron cuatro atenciones prenatales de acuerdo a las mejores prácticas por médico o enfermera según las mejores prácticas propuestas en su embarazo más reciente en los últimos dos años'),
                                'datos'=> array('emb_cap_12_sema_tot_espe', '90_expe_revisados', 'prueba_emb', 'compra_pruebas_emb', 'insumos_diag_emb'
                                        )
                                ),
                            array('descripcion'=>array('Porcentaje de embarazadas con atención institucional de parto referidas por los ECOS como parte de las actividades del plan de parto.', 
                                                    'Porcentaje de mujeres en edad reproductiva (15-49) cuyo parto más reciente fue realizado por personal capacitado en una unidad de salud en los dos últimos años.', 
                                                    'Porcentaje de mujeres en edad reproductiva (15-49) que en su embarazo más reciente tuvieron una visita por personal de salud, incluyendo personal médico y promotores, a la semana del parto.'),
                                'datos'=> array('partos_por_personal_calificado', 'captadas_antes_7_dias', 'exp_plan_parto', 'lineamientos_sociali', 
                                                'compra_insumos', 'recep_insumos'
                                        )
                                ),
                            array('descripcion'=>array('Porcentaje de niños de 12 a 59 meses que recibieron dos dosis de tratamiento antiparasitario en el último año.',  
                                                    'Porcentaje de niños de 6 a 23 meses de edad que tienen un valor de hemoglobina < 110 g/L ',
                                                    'Porcentaje de madres que dieron a sus niños de 0 a 59 meses SRO y zinc en el último episodio de diarrea.'),
                                'datos'=> array('ninos_2_dosis_antipari_anual', 'atipar_necesarios', 'proceso_compra_ini', 'distribucion_ini', 
                                                'zn_sro_imple', 'registro_consumo'
                                        )
                                ),
                            array('descripcion'=>array('Porcentaje de niños de 12 a 24 meses de edad con vacuna para Sarampión, Paperas y Rubeola (SPR)'),
                                'datos'=> array('vacunacion_spr', 'vigi_cartilla')
                                ),
                            array('descripcion'=>array('Aumento del gasto del primer nivel de atención'),
                                'datos'=> array('asigna_presupu', 'ejecucion_presupu', 'presupu_aprob')
                                ),
                            array('descripcion'=>array('Fortalecidos los 75 Ecos-F y 3 Ecos-E de salud en su Gestión logística y Abastecidos'),
                                'datos'=> array('ucsf_abastecidos', 'ucsf_abastecid_ninio')
                                ),
                            array('descripcion'=>array('Producto. Implementado el proceso de mejora continua de la calidad en los ECOS'),
                                'datos'=> array('proc_mejora_conti', 'proc_mejora_funci', 'colab_desarr')
                                ),
                            array('descripcion'=>array('Piloto de reconocimiento al desempeño funcionando en los municipios de la SM2015'),
                                'datos'=> array('piloto_firmado', 'medicios_semes')
                                ),
                            array('descripcion'=>array('Implementada la estrategia de comunicación para cambio de comportamiento'),
                                'datos'=> array('compra_mat_educ', 'mat_educ_distri', 'cart_dic_ela', 'capa_facilitadores', 'capa_per_salud')
                                ),
                            array('descripcion'=>array('Ejecución Físico y Financiera'),
                                'datos'=> array('eje_fisica', 'eje_finan')
                                )
                            ); 

        return $this->render('IndicadoresBundle:Reportes:matrizSeguimiento.html.twig', 
                                array(
                                    'admin_pool' => $admin_pool,
                                    'datosFrm' => $datosFrmFormat,
                                    'categorias' => $categorias,
                                    'parms' => $params
                                ));
    }
    
    protected function getDatosFormateados($var,  $tipo = null) {
        $em = $this->getDoctrine()->getManager();
        
        $planf = ($tipo=='planificado') ? "||'_p'" : '';
        
        $sql = "SELECT anio::integer, mes::varchar, id_mes::integer, SUM(calculo::numeric) AS calculo FROM 
                       (SELECT datos->'anio' as anio, datos->'id_mes' AS id_mes,
                            'cant_mensual_calidad_'||lpad(datos->'id_mes', 2, '0')$planf AS mes, 
                            datos->'calculo' AS calculo 
                        FROM origenes.fila_origen_dato_$var[id] 
                        WHERE datos->'calculo' != ''
                        ) AS A  
                    GROUP BY anio::integer, mes::varchar, id_mes::integer ";
        if ($var['acumular']){
            $sql = "SELECT anio, mes, (SELECT SUM(calculo) FROM ($sql) AS BB WHERE BB.id_mes <= AC.id_mes and BB.anio = AC.anio) AS calculo
                        FROM ($sql) AS AC ";
        }
        $datos = $em->getConnection()->executeQuery($sql)->fetchAll();

        return $this->formatearDatos($datos, $var);
    }
    
    
    private function getFromIndicador($var, $tipo) {
        $em = $this->getDoctrine()->getManager();
        
        $ind = $em->find("IndicadoresBundle:FichaTecnica", $var['id']);
        $formula = str_replace(array('{', '}'), array('', ''), strtolower($ind->getFormula()));
        $denominador = strtolower($var['denominador']);
        
        $planf = ($tipo=='planificado') ? "||'_p'" : '';
        
        $sql = "SELECT anio::integer, mes::varchar, id_mes::integer, SUM(calculo::numeric) AS calculo FROM 
                       (SELECT anio, id_mes,
                            'cant_mensual_calidad_'||lpad(id_mes, 2, '0')$planf AS mes, 
                            $formula AS calculo 
                        FROM tmp_ind_$var[id] 
                        WHERE $denominador is not null 
                            AND $denominador > 0 
                        ) AS A  
                    GROUP BY anio::integer, mes::varchar, id_mes::integer ";
        if ($var['acumular']){
            $sql = "SELECT anio, mes, (SELECT SUM(calculo) FROM ($sql) AS BB WHERE BB.id_mes <= AC.id_mes and BB.anio = AC.anio) AS calculo
                        FROM ($sql) AS AC ";
        }
        $datos = $em->getConnection()->executeQuery($sql)->fetchAll();

        return $this->formatearDatos($datos, $var);
    }
    private function formatearDatos($datos, $var) {
        $resp = array();
        foreach ($datos as $d){
            $resp[$d['anio']]['anio'] = $d['anio'];
            $resp[$d['anio']]['codigo_variable'] = $var['codigo'];
            $resp[$d['anio']]['descripcion_variable'] = $var['descripcion'];
            $resp[$d['anio']][$d['mes']] = $d['calculo'];
        }
        
        return $resp;
    }
    
    protected function getParametros($periodoInicio, $periodoFin) {
        list($mesInicio, $anioInicio) = explode('/', $periodoInicio);
        list($mesFin, $anioFin) = explode('/', $periodoFin);
        $params = array();
        if ($anioInicio == $anioFin){
            for($i = $mesInicio; $i <= $mesFin;  $i++){
                $mes = str_pad($i, 2, "0", STR_PAD_LEFT);
                $params[$anioInicio][] = $mes;
            }
        } else {
            for($i = $mesInicio; $i <= 12;  $i++){
                $mes = str_pad($i, 2, "0", STR_PAD_LEFT);
                $params[$anioInicio][] = $mes;
            }
            for($i=$anioInicio + 1; $i <= $anioFin - 1; $i++){
                $params[$i] = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
            }
            for($i=1; $i <= $mesFin; $i++){
                $mes = str_pad($i, 2, "0", STR_PAD_LEFT);
                $params[$anioFin][] = $mes;
            }
            
        }
        return $params;
    }
}