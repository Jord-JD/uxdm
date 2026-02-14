<?php

namespace JordJD\uxdm\TestUnitClasses\Doctrine;

/**
 * @Entity
 *
 * @Table(name="users")
 */
class User
{
    /**
     * @var string
     *
     * @Column(name="name", type="string", length=15, nullable=false)
     *
     * @Id
     */
    protected $name;

    /**
     * @var int
     *
     * @Column(name="value", type="integer", length=15, nullable=false)
     */
    protected $value;

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}
