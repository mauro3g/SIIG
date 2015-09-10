<?php

namespace MINSAL\IndicadoresBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class OrigenDatosAdmin extends Admin
{
    protected $datagridValues = array(
        '_page' => 1, // Display the first page (default = 1)
        '_sort_order' => 'ASC', // Descendant ordering (default = 'ASC')
        '_sort_by' => 'nombre' // name of the ordered field (default = the model id field, if any)
    );

    protected function configureFormFields(FormMapper $formMapper)
    {
        $esFusionado = $this->getSubject()->getEsFusionado();
        $origenActual = $this->getSubject();

        $formMapper
                ->tab($this->getTranslator()->trans('datos_generales'))
                    ->with('', array('class' => 'col-md-12'))->end()
                ->end()
                ->tab($this->getTranslator()->trans('_origen_datos_'))
                    ->with($this->getTranslator()->trans('origen_datos_sql'), array('class' => 'col-md-8'))->end()
                    ->with($this->getTranslator()->trans('origen_datos_archivo'), array('class' => 'col-md-4'))->end()
                ->end()
        ;
        
        $formMapper            
                ->tab($this->getTranslator()->trans('datos_generales'), array('collapsed' => false))
                    ->with('', array('class' => 'col-md-12'))
                        ->add('nombre', null, array('label' => $this->getTranslator()->trans('nombre')))
                        ->add('descripcion', null, array('label' => $this->getTranslator()->trans('descripcion'), 'required' => false))                        
                    ->end()
                ->end()
        ;
        if ($esFusionado == false)
            $formMapper
                    ->tab($this->getTranslator()->trans('datos_generales'), array('collapsed' => false))
                        ->with('', array('class' => 'col-md-12'))
                            ->add('esCatalogo', null, array('label' => $this->getTranslator()->trans('es_catalogo')))
                            ->add('areaCosteo', 'choice', array('label' => $this->getTranslator()->trans('_area_costeo_'),
                                'choices' => array('rrhh'=>$this->getTranslator()->trans('_rrhh_'),
                                    'ga_af'=>$this->getTranslator()->trans('_ga_af_')),
                                'required' => false
                                ))
                        ->end()
                    ->end()
                    ->tab($this->getTranslator()->trans('_origen_datos_'), array('collapsed' => true))
                        ->with($this->getTranslator()->trans('origen_datos_sql'))
                            ->add('conexiones', null, array('label' => $this->getTranslator()->trans('nombre_conexion'), 'required' => false, 'expanded' => false))
                            ->add('sentenciaSql', null, array('label' => $this->getTranslator()->trans('sentencia_sql'),
                                'required' => false,
                                'attr' => array('rows' => 7, 'cols' => 50)
                            ))
                        ->end()
                        ->with($this->getTranslator()->trans('origen_datos_archivo'))
                            ->add('archivoNombre', null, array('label' => $this->getTranslator()->trans('archivo_asociado'), 'required' => false, 'read_only' => true))
                            ->add('file', 'file', array('label' => $this->getTranslator()->trans('subir_nuevo_archivo'), 'required' => false))
                        ->end()
                    ->end()                    
            ;
        
        $acciones = explode('/', $this->getRequest()->server->get("REQUEST_URI"));
        $accion = array_pop($acciones);
        if ($accion == 'create') {
            $formMapper
                    ->setHelps(array(
                        'campoLecturaIncremental' => $this->getTranslator()->trans('_debe_guardar_para_ver_campos_')
                    ))
            ;
        }else {
            $formMapper
                    ->tab($this->getTranslator()->trans('_carga_incremental_'), array('collapsed' => true))
                        ->with('', array('class' => 'col-md-12'))
                            ->add('campoLecturaIncremental', null, array('label' => $this->getTranslator()->trans('_campo_lectura_incremental_'), 'expanded' => false,
                                'class' => 'IndicadoresBundle:Campo',
                                'query_builder' => function ($repository) use($origenActual) {
                                    if($origenActual->getId() == null){
                                        //no mostrar campos hasta que se guarde el origen
                                        return $repository->createQueryBuilder('c')
                                            ->where('1 = 2 ')
                                                ;
                                    } else {
                                        return $repository->createQueryBuilder('c')
                                            ->innerJoin('c.significado', 's')
                                            ->where('c.origenDato = :origenActual ')
                                            ->andWhere('s.codigo = :codigoSignificado1 OR s.codigo = :codigoSignificado2')
                                            ->orderBy('c.nombre')
                                            ->setParameter('origenActual', $origenActual)
                                            ->setParameter('codigoSignificado1', 'fecha')
                                            ->setParameter('codigoSignificado2', 'anio');
                                    }
                                }                                
                                ))
                            ->add('ventanaLimiteInferior', null, array('label' => $this->getTranslator()->trans('_ventana_limite_inferior_'), 'required' => false))
                            ->add('ventanaLimiteSuperior', null, array('label' => $this->getTranslator()->trans('_ventana_limite_superior_'), 'required' => false))
                        ->end()
                    ->end()
                    ->setHelps(array(
                        'campoLecturaIncremental' => $this->getTranslator()->trans('_debe_ser_tipo_fecha_'). '<BR/><IMG src="/bundles/indicadores/images/carga_incremental.png" />'
                    ))
            ;
        }
        
        $formMapper
            ->setHelps(array(
                'ventanaLimiteInferior' => $this->getTranslator()->trans('_ayuda_ventana_limite_inferior_'),
                'ventanaLimiteSuperior' => $this->getTranslator()->trans('_ayuda_ventana_limite_superior_')
            ));
        
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
                ->add('nombre', null, array('label' => $this->getTranslator()->trans('nombre')))
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
                ->addIdentifier('nombre', null, array('label' => $this->getTranslator()->trans('nombre')))
                ->add('descripcion', null, array('label' => $this->getTranslator()->trans('descripcion')))
                ->add('esFusionado', null, array('label' => $this->getTranslator()->trans('fusion.es_fusionado')))
                ->add('esCatalogo', null, array('label' => $this->getTranslator()->trans('es_catalogo')))                
                ->add('sentenciaSql', null, array('label' => $this->getTranslator()->trans('sentencia_sql'),
                    'template'=>'IndicadoresBundle:CRUD:list_sentencia_sql.html.twig'))
                ->add('archivoNombre', null, array('label' => $this->getTranslator()->trans('archivo_asociado')))
                ->add('_action', 'actions', array(
                    'actions' => array(
                        'load_data' => array('template' => 'IndicadoresBundle:OrigenDatosAdmin:list__action_load_data.html.twig')
                    )
                ))
        ;
    }

    public function validate(ErrorElement $errorElement, $object)
    {
        if ($object->getEsFusionado() == false) {
            if ($object->file == '' and $object->getArchivoNombre() == '' and $object->getSentenciaSql() == '') {
                $errorElement->with('sentenciaSql')
                        ->addViolation($this->getTranslator()->trans('validacion.sentencia_o_archivo'))
                        ->end();
            }
            if ($object->file != '' and $object->getSentenciaSql() != '') {
                $errorElement->with('sentenciaSql')
                        ->addViolation($this->getTranslator()->trans('validacion.sentencia_o_archivo_no_ambas'))
                        ->end();
            }
            echo count($object->getConexiones());
            if ($object->getSentenciaSql() != '' and count($object->getConexiones()) == 0) {
                $errorElement->with('conexiones')
                        ->addViolation($this->getTranslator()->trans('validacion.requerido'))
                        ->end();
            }
        }
        // Revisar la validación, no me reconoce los archivos con los tipos que debería
        /*
         * 'application/octet-stream',
          'text/comma-separated-values',
          'application/zip',
          'text/x-c++'
         */
        /* $errorElement
          ->with('file')
          ->assertFile(array(
          'mimeTypes' => array("application/vnd.ms-excel",
          "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
          'text/csv','application/vnd.oasis.opendocument.spreadsheet',
          'application/vnd.ms-office'
          )))
          ->end()
          ; */

        return true;
    }

    public function getBatchActions()
    {
        //$actions = parent::getBatchActions();
        $actions = array();

        $actions['load_data'] = array(
            'label' => $this->trans('action_load_data'),
            'ask_confirmation' => false // If true, a confirmation will be asked before performing the action
        );
        $actions['merge'] = array(
            'label' => $this->trans('action_merge'),
            'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
        );
        $actions['crear_pivote'] = array(
            'label' => $this->trans('_crear_pivote_'),
            'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
        );

        return $actions;
    }

    public function getTemplate($name)
    {
        switch ($name) {
            case 'edit':
                return 'IndicadoresBundle:CRUD:origen_dato-edit.html.twig';
                break;
            default:
                return parent::getTemplate($name);
                break;
        }
    }

    public function prePersist($origenDato)
    {
        $this->saveFile($origenDato);
        $this->setNombreCatalogo($origenDato);

        $this->guardarDrescripcion($origenDato);
        $origenDato->setVentanaLimiteInferior(0);
        $origenDato->setVentanaLimiteSuperior(0);
    }

    public function preUpdate($origenDato)
    {
        $this->saveFile($origenDato);
        $this->guardarDrescripcion($origenDato);
        $this->setNombreCatalogo($origenDato);
    }

    public function setNombreCatalogo($origenDato)
    {
        if ($origenDato->getEsCatalogo()) {
            // replace all non letters or digits by -
            $util = new \MINSAL\IndicadoresBundle\Util\Util();
            $origenDato->setNombreCatalogo('ctl_' . $util->slug($origenDato->getNombre()));
        }
    }

    public function guardarDrescripcion($origenDato)
    {
        if ($origenDato->getEsFusionado()) {
            $origenes_fusionados = '';
            foreach ($origenDato->getFusiones() as $origen) {
                $origenes_fusionados .= $origen->getNombre() . ', ';
            }
            $origenes_fusionados = trim($origenes_fusionados, ', ');

            $nueva_descripcion = $this->getTranslator()->trans('fusion.fusiona_siguientes_origenes') .
                    $origenes_fusionados;
            if (strpos($origenDato->getDescripcion(), $nueva_descripcion) === false)
                $origenDato->setDescripcion(trim($origenDato->getDescripcion() . '. ' . $nueva_descripcion, '. '));
        }
    }

    public function saveFile($origenDato)
    {
        $basepath = $this->getRequest()->getBasePath();
        $origenDato->upload($basepath);
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('merge_save', 'merge/save');
        $collection->add('load_data');
    }

}
