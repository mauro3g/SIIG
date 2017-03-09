<?php

namespace MINSAL\CalidadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use MINSAL\CalidadBundle\Entity\PlanMejora;
use MINSAL\CalidadBundle\Entity\Criterio;
use MINSAL\CalidadBundle\Entity\Actividad;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

/**
 * Institucion controller.
 *
 * @Route("/calidad/planmejora")
 */
class PlanMejoraController extends Controller {

    /**
     * @Route("/")
     */
    public function indexAction(Request $request) {
        $admin_pool = $this->get('sonata.admin.pool');
        $establecimiento = null;
        $em = $this->getDoctrine()->getManager();

        $datos = array();
        $formB = $this->createFormBuilder()
                ->add('periodo', EntityType::class, array(
                    'label' => '_periodo_evaluacion_',
                    'class' => 'GridFormBundle:PeriodoIngreso',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->getPeriodosEvaluadosCalidad();
                    },
                ))
                ->add('continuar', SubmitType::class, array('label' => '_cargar_', 'attr' => array('class' => 'btn btn-success')))
        ;

        $form = $formB->getForm();
        $form->handleRequest($request);


        if ($form->isSubmitted()) {
            $datos = $form->getData();
            $periodo = $datos['periodo'];

            if ($this->getUser()->getUsername() === 'admin') {
                //Recuperar las unidades que tienen evaluaciones en el periodo seleccionado
                $formB->add('establecimiento', EntityType::class, array(
                    'class' => 'CostosBundle:Estructura',
                    'query_builder' => function (EntityRepository $er) use ($periodo) {
                        return $er->getEstablecimientosEvaluadosCalidad($periodo);
                    }
                ));
            } else {
                //El establecimiento será el asignado al usuario
                $establecimiento = $this->getUser()->getEstablecimientoPrincipal();
            }

            //Actualizar el formulario con el campo agregado
            $form = $formB->getForm();
            $form->handleRequest($request);
            $datos = $form->getData();

            //Verificar si ya se eligió la unidad (sería el segundo envio)
            if (array_key_exists('establecimiento', $datos)) {
                // por el administrador
                $establecimiento = $datos['establecimiento'];
            }
            
            if ($establecimiento !== null) {
                
                $per = $periodo->getAnio().'_'. ltrim($periodo->getMes(), '0');
                // Evaluaciones por lista de chequeo
                $data = $em->getRepository('GridFormBundle:Indicador')->getEvaluaciones($establecimiento->getCodigo(), $per);
                
                //Obtener las otras evaluaciones que no son lista de chequeo
                //$data2 = $em->getRepository('GridFormBundle:Indicador')->getEvaluacionesNOListaChequeo($establecimiento, $periodo);
                
                $evaluaciones = array();
                foreach ($data as $f) {
                    if ($f['calificacion'] < $f['meta']){
                        array_push($evaluaciones, $f);
                    }
                }

            }
            $formB->setData($datos);
        }

        return $this->render('CalidadBundle:PlanMejora:index.html.twig', array(
                    'admin_pool' => $admin_pool,
                    'form' => $formB->getForm()->createView(),
                    'establecimiento' => $establecimiento,
                    'evaluaciones' => $evaluaciones
        ));
    }

    /**
     * @Route("/{id}/detalle/")
     */
    public function detalleAction(PlanMejora $planMejora) {
        $admin_pool = $this->get('sonata.admin.pool');
        $em = $this->getDoctrine()->getManager();

        $prioridades = array();
        foreach ($em->getRepository('CalidadBundle:Prioridad')->findBy(array(), array('codigo' => 'ASC')) as $p) {
            $prioridades[$p->getId()] = $p->getDescripcion();
        }

        $tiposIntervencion = array();
        foreach ($em->getRepository('CalidadBundle:TipoIntervencion')->findBy(array(), array('codigo' => 'ASC')) as $p) {
            $tiposIntervencion[$p->getId()] = $p->getDescripcion();
        }

        return $this->render('CalidadBundle:PlanMejora:detalle.html.twig', array('admin_pool' => $admin_pool,
                    'prioridades' => json_encode($prioridades),
                    'tiposIntervencion' => json_encode($tiposIntervencion),
                    'planMejora' => $planMejora
                        )
        );
    }

    /**
     * @Route("/{id}/criterios" , name="calidad_planmejora_get_criterios", options={"expose"=true})
     */
    public function criteriosAction(PlanMejora $planMejora) {
        $em = $this->getDoctrine()->getManager();

        $codigoEstructura = $planMejora->getEstablecimiento()->getCodigo();
        $periodo = $planMejora->getPeriodo()->getAnio() . '_' . $planMejora->getPeriodo()->getMes();
        $codigoFormulario = $planMejora->getEstandar()->getFormularioCaptura()->getCodigo();

        $criteriosEstandar = $em->getRepository('CalidadBundle:Estandar')->getCriterios($codigoEstructura, $periodo, $codigoFormulario);

        $criteriosParaPlan = array();

        $limiteAceptacion = 80;

        foreach ($criteriosEstandar['datos'][$codigoFormulario]['resumen_criterios'] as $c) {

            if ($c['porc_cumplimiento'] < $limiteAceptacion) {
                $c['brecha'] = $limiteAceptacion - $c['porc_cumplimiento'];
                array_push($criteriosParaPlan, $c);
            }
        }

        //Guardar los criterios, por si hay nuevos
        $em->getRepository('CalidadBundle:PlanMejora')->agregarCriterios($planMejora, $criteriosParaPlan);

        //Recuperar los criterios del plan
        $criterios = array();
        foreach ($planMejora->getCriterios() as $c) {
            $criterios['rows'][] = array('id' => $c->getId(),
                'descripcion' => $c->getVariableCaptura()->getDescripcion(),
                'brecha' => $c->getBrecha(),
                'causaBrecha' => $c->getCausaBrecha(),
                'oportunidadMejora' => $c->getOportunidadMejora(),
                'factoresMejoramiento' => $c->getFactoresMejoramiento(),
                'tipoIntervencion' => ( $c->getTipoIntervencion() === null) ? null : $c->getTipoIntervencion()->getId(),
                'prioridad' => ( $c->getPrioridad() === null ) ? null : $c->getPrioridad()->getId()
            );
        }


        return new Response(json_encode($criterios));
    }

    /**
     * @Route("/{criterio}/actividades" , name="calidad_planmejora_get_actividades", options={"expose"=true})
     */
    public function actividadesAction(Criterio $criterio) {

        //Recuperar los criterios del plan
        $actividades = array();
        foreach ($criterio->getActividades() as $a) {
            $actividades['rows'][] = array('id' => $a->getId(),
                'nombre' => $a->getNombre(),
                'fechaInicio' => $a->getFechaInicio()->format('d/m/Y'),
                'fechaFinalizacion' => $a->getFechaFinalizacion()->format('d/m/Y'),
                'medioVerificacion' => $a->getMedioVerificacion(),
                'responsable' => $a->getResponsable(),
                'porcentajeAvance' => $a->getPorcentajeAvance()
            );
        }
        return new Response(json_encode($actividades));
    }

    /**
     * @Route("/detalle/{criterio}/actividad/guardar" , name="calidad_planmejora_set_actividad", options={"expose"=true})
     */
    public function setActividadAction(Criterio $criterio, Request $req) {

        $em = $this->getDoctrine()->getManager();

        if ($req->get('oper') === 'add') {
            $actividad = new Actividad();
            $actividad->setCriterio($criterio);
        } else {
            $actividad = $em->find('CalidadBundle:Actividad', $req->get('id'));
        }

        if ($req->get('oper') === 'del') {
            $em->remove($actividad);
        } else {
            $fecha = new \DateTime();
            $fi = $fecha->createFromFormat('d/m/Y', $req->get('fechaInicio'));
            $ff = $fecha->createFromFormat('d/m/Y', $req->get('fechaFinalizacion'));

            if ($fi > $ff) {
                return new Response(json_encode(array("error" => 'La fecha de inicio debe ser menor a la fecha de finalización')));
            }

            $actividad->setNombre($req->get('nombre'));
            $actividad->setFechaInicio($fi);
            $actividad->setFechaFinalizacion($ff);
            $actividad->setMedioVerificacion($req->get('medioVerificacion'));
            $actividad->setResponsable($req->get('responsable'));
            $actividad->setPorcentajeAvance($req->get('porcentajeAvance'));

            $em->persist($actividad);
        }


        $em->flush();

        //Si todo sale bien devolver el id de la actividad (escencial para nuevas actividades)
        return new Response(json_encode(array("id" => $actividad->getId())));
    }

    /**
     * @Route("/criterio/guardar" , name="calidad_planmejora_set_criterio", options={"expose"=true})
     */
    public function setCriterioAction(Request $req) {

        $em = $this->getDoctrine()->getManager();

        $criterio = $em->find('CalidadBundle:Criterio', $req->get('id'));
        $prioridad = $em->find('CalidadBundle:Prioridad', $req->get('prioridad'));
        $tipoIntervencion = $em->find('CalidadBundle:TipoIntervencion', $req->get('tipoIntervencion'));

        $criterio->setCausaBrecha($req->get('causaBrecha'));
        $criterio->setOportunidadMejora($req->get('oportunidadMejora'));
        $criterio->setFactoresMejoramiento($req->get('factoresMejoramiento'));
        $criterio->setPrioridad($prioridad);
        $criterio->setTipoIntervencion($tipoIntervencion);

        $em->persist($criterio);
        $em->flush();

        return new Response(json_encode(array("ok" => "ok")));
    }

}
