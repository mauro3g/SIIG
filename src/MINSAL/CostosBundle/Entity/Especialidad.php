<?php

namespace MINSAL\CostosBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * MINSAL\CostosBundle\Entity\TipoRecurso
 *
 * @ORM\Table(name="catalogos.especialidad")
 * @UniqueEntity(fields="codigo", message="Código ya existe")
 * @ORM\Entity
 */
class Especialidad
{    

    /**
     * @var string $codigo
     * @ORM\Id
     * @ORM\Column(name="codigo", type="string", length=20, nullable=false)
     */
    private $codigo;
        

    /**
     * @var string $descripcion
     *
     * @ORM\Column(name="descripcion", type="text", nullable=true)
     */
    private $descripcion;
    

    public function __toString()
    {
        return $this->descripcion ? : '';
    }


    /**
     * Set codigo
     *
     * @param string $codigo
     * @return Especialidad
     */
    public function setCodigo($codigo)
    {
        $this->codigo = $codigo;

        return $this;
    }

    /**
     * Get codigo
     *
     * @return string 
     */
    public function getCodigo()
    {
        return $this->codigo;
    }

    /**
     * Set descripcion
     *
     * @param string $descripcion
     * @return Especialidad
     */
    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    /**
     * Get descripcion
     *
     * @return string 
     */
    public function getDescripcion()
    {
        return $this->descripcion;
    }
}
