<?php

namespace MINSAL\CalidadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MINSAL\GridFormBundle\Entity\PlanMejora
 *
 * @ORM\Table(name="calidad.plan_mejora")
 * @ORM\Entity(repositoryClass="MINSAL\CalidadBundle\Repository\PlanMejoraRepository")
 */
class PlanMejora
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="calidad.planmejora_id_seq")
     */
    private $id;
    
    
    /**
     * @ORM\ManyToOne(targetEntity="Estandar", inversedBy="planesMejora")
     * 
     * */
    private $estandar;
    
    /**
     * @ORM\ManyToOne(targetEntity="MINSAL\CostosBundle\Entity\Estructura")
     * */
    private $establecimiento;
    
    /**
     * @ORM\ManyToOne(targetEntity="MINSAL\GridFormBundle\Entity\PeriodoIngreso", cascade={"persist", "remove"})
     * 
     * @ORM\JoinColumns({
     *                   @ORM\JoinColumn(name="anio_periodo", referencedColumnName="anio"), 
     *                   @ORM\JoinColumn(name="mes_periodo", referencedColumnName="mes")
     *                  })     
     **/
    private $periodo;
    
    /**
    * @ORM\OneToMany(targetEntity="Criterio", mappedBy="planMejora", cascade={"all"}, orphanRemoval=true)
    */
    private $criterios;
  
   
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->criterios = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set estandar
     *
     * @param \MINSAL\CalidadBundle\Entity\Estandar $estandar
     *
     * @return PlanMejora
     */
    public function setEstandar(\MINSAL\CalidadBundle\Entity\Estandar $estandar = null)
    {
        $this->estandar = $estandar;

        return $this;
    }

    /**
     * Get estandar
     *
     * @return \MINSAL\CalidadBundle\Entity\Estandar
     */
    public function getEstandar()
    {
        return $this->estandar;
    }

    /**
     * Set establecimiento
     *
     * @param \MINSAL\CostosBundle\Entity\Estructura $establecimiento
     *
     * @return PlanMejora
     */
    public function setEstablecimiento(\MINSAL\CostosBundle\Entity\Estructura $establecimiento = null)
    {
        $this->establecimiento = $establecimiento;

        return $this;
    }

    /**
     * Get establecimiento
     *
     * @return \MINSAL\CostosBundle\Entity\Estructura
     */
    public function getEstablecimiento()
    {
        return $this->establecimiento;
    }

    /**
     * Add criterio
     *
     * @param \MINSAL\CalidadBundle\Entity\Criterio $criterio
     *
     * @return PlanMejora
     */
    public function addCriterio(\MINSAL\CalidadBundle\Entity\Criterio $criterio)
    {
        $this->criterios[] = $criterio;

        return $this;
    }

    /**
     * Remove criterio
     *
     * @param \MINSAL\CalidadBundle\Entity\Criterio $criterio
     */
    public function removeCriterio(\MINSAL\CalidadBundle\Entity\Criterio $criterio)
    {
        $this->criterios->removeElement($criterio);
    }

    /**
     * Get criterios
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCriterios()
    {
        return $this->criterios;
    }

    

    /**
     * Set periodo
     *
     * @param \MINSAL\GridFormBundle\Entity\PeriodoIngreso $periodo
     *
     * @return PlanMejora
     */
    public function setPeriodo(\MINSAL\GridFormBundle\Entity\PeriodoIngreso $periodo = null)
    {
        $this->periodo = $periodo;

        return $this;
    }

    /**
     * Get periodo
     *
     * @return \MINSAL\GridFormBundle\Entity\PeriodoIngreso
     */
    public function getPeriodo()
    {
        return $this->periodo;
    }
    
    public function getPorcentajeAvance() {
        $avance = null;
        $cantCriterios = 0;
        
        foreach($this->getCriterios() as $c){
            if ($c->getPorcentajeAvance() != null){
                $avance += $c->getPorcentajeAvance();
                $cantCriterios++;
            }
            
        }
        
        return ( $avance == null) ? null : ($avance / $cantCriterios); 
    }
}
