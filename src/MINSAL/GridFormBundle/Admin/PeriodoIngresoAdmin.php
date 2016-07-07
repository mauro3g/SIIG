<?php

namespace MINSAL\GridFormBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin as Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class PeriodoIngresoAdmin extends Admin
{
    protected $datagridValues = array(
        '_page' => 1, // Display the first page (default = 1)
        '_sort_order' => 'ASC', // Descendant ordering (default = 'ASC')
        '_sort_by' => 'anio' // name of the ordered field (default = the model id field, if any)
    );

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('anio', null, array('label'=> $this->getTranslator()->trans('_anio_')))
            ->add('mes', 'choice', array('label' => $this->getTranslator()->trans('_mes_'),
                        'choices' => 
                            array( '00' => $this->getTranslator()->trans('_solo_lectura_'),
                                '01'=>$this->getTranslator()->trans('_enero_'),
                                '02' => $this->getTranslator()->trans('_febrero_'),
                                '03' => $this->getTranslator()->trans('_marzo_'),
                                '04' => $this->getTranslator()->trans('_abril_'),
                                '05' => $this->getTranslator()->trans('_mayo_'),
                                '06' => $this->getTranslator()->trans('_junio_'),
                                '07' => $this->getTranslator()->trans('_julio_'),
                                '08' => $this->getTranslator()->trans('_agosto_'),
                                '09' => $this->getTranslator()->trans('_septiembre_'),
                                '10' => $this->getTranslator()->trans('_octubre_'),
                                '11' => $this->getTranslator()->trans('_noviembre_'),
                                '12' => $this->getTranslator()->trans('_diciembre_'),
                                '01_p'=>$this->getTranslator()->trans('_enero_p_'),
                                '02_p' => $this->getTranslator()->trans('_febrero_p_'),
                                '03_p' => $this->getTranslator()->trans('_marzo_p_'),
                                '04_p' => $this->getTranslator()->trans('_abril_p_'),
                                '05_p' => $this->getTranslator()->trans('_mayo_p_'),
                                '06_p' => $this->getTranslator()->trans('_junio_p_'),
                                '07_p' => $this->getTranslator()->trans('_julio_p_'),
                                '08_p' => $this->getTranslator()->trans('_agosto_p_'),
                                '09_p' => $this->getTranslator()->trans('_septiembre_p_'),
                                '10_p' => $this->getTranslator()->trans('_octubre_p_'),
                                '11_p' => $this->getTranslator()->trans('_noviembre_p_'),
                                '12_p' => $this->getTranslator()->trans('_diciembre_p_')
                            )
                        ))
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('anio', null, array('label'=> $this->getTranslator()->trans('_anio_')))
            ->add('mes', null, array('label'=> $this->getTranslator()->trans('_mes_')))
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper            
            ->addIdentifier('anio', null, array('label'=> $this->getTranslator()->trans('_anio_')))
            ->addIdentifier('mes', null, array('label'=> $this->getTranslator()->trans('_mes_')))
        ;
    }

    public function getBatchActions()
    {
        $actions = parent::getBatchActions();
        $actions['delete'] = null;
    }
}
