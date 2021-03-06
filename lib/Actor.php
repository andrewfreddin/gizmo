<?php

namespace Gizmo;

class Actor
{
    /* @var int*/
    public $id;
    /* @var int */
    public $createdAtFrameNumber;
    /* @var int */
    public $closedAtFrameNumber;
    /* @var string */
    public $archetype;
    /* @var string */
    public $class;
    /* @var Vector */
    public $rotator;
    /* @var Vector */
    public $position;
    /* @var Vector */
    public $orientation;
    /* @var array of ActorProperty */
    public $properties;

    /**
     * @return array map string archetype to string class
     */
    public static function getArchetypeToClassMap()
    {
        return [
            'GameInfo_Soccar.GameInfo.GameInfo_Soccar:GameReplicationInfoArchetype' => 'TAGame.GRI_TA',
            'Archetypes.Teams.Team0' => 'TAGame.Team_Soccar_TA',
            'Archetypes.Teams.Team1' => 'TAGame.Team_Soccar_TA',
            'Archetypes.GameEvent.GameEvent_SoccarSplitscreen' => 'TAGame.GameEvent_SoccarSplitscreen_TA',
            'TAGame.Default__PRI_TA' => 'TAGame.PRI_TA',
            'Archetypes.GameEvent.GameEvent_SoccarPrivate' => 'TAGame.GameEvent_SoccarPrivate_TA',
            'Archetypes.GameEvent.GameEvent_Soccar' => 'TAGame.GameEvent_Soccar_TA',
            'Archetypes.Ball.Ball_Default' => 'TAGame.Ball_TA',
            'Archetypes.Car.Car_Default' => 'TAGame.Car_TA',
            'Archetypes.CarComponents.CarComponent_Boost' => 'TAGame.CarComponent_Boost_TA',
            'Archetypes.CarComponents.CarComponent_Jump' => 'TAGame.CarComponent_Jump_TA',
            'Archetypes.CarComponents.CarComponent_DoubleJump' => 'TAGame.CarComponent_DoubleJump_TA',
            'Archetypes.CarComponents.CarComponent_Dodge' => 'TAGame.CarComponent_Dodge_TA',
            'Archetypes.CarComponents.CarComponent_FlipCar' => 'TAGame.CarComponent_FlipCar_TA',
        ];
    }

    /**
     * @param int $id
     * @param string $archetype
     * @param int $frameNumber
     */
    public function __construct($id, $archetype, $frameNumber)
    {
        $this->id = $id;
        $this->createdAtFrameNumber = $frameNumber;
        $this->archetype = $archetype;
        $map = self::getArchetypeToClassMap();
        if (array_key_exists($this->archetype, $map)) {
            $class = $map[$this->archetype];
        }
        else {
            $class = $this->archetype;
        }
        $this->class = $class;
        $this->properties = [];
    }

    /**
     * @param int $frameNumber
     */
    public function close($frameNumber)
    {
        $this->closedAtFrameNumber = $frameNumber;
    }

    /**
     * @return int
     */
    public function getNumPropertyBits()
    {
        return BinaryReader::numBitsToRepresent(sizeof($this->properties));
    }

    /**
     * @return int
     */
    public function getNumProperties()
    {
        return sizeof($this->properties);
    }

    /**
     * @param BinaryReader $br
     */
    public function deserializeInit($br)
    {
        foreach ([
            'TheWorld:PersistentLevel.CrowdActor_TA_',
            'TheWorld:PersistentLevel.CrowdManager_TA_',
        ] as $substr) {
            if (strpos($this->class, $substr) !== false) {
                $this->rotator = Vector::deserializeByteVector($br);
                return;
            }
        }
        switch ($this->class) {
            case 'TAGame.PRI_TA':
            case 'TAGame.GRI_TA':
            case 'TAGame.Team_Soccar_TA':
            case 'TAGame.GameEvent_Soccar_TA':
            case 'TAGame.GameEvent_SoccarPrivate_TA':
            case 'TAGame.GameEvent_SoccarSplitscreen_TA':
                $this->rotator = Vector::deserializeByteVector($br);
                $unknown = $br->readBits(11);
                return;
            case 'TAGame.CarComponent_Jump_TA':
            case 'TAGame.CarComponent_Dodge_TA':
            case 'TAGame.CarComponent_Boost_TA':
            case 'TAGame.CarComponent_DoubleJump_TA':
            case 'TAGame.CarComponent_FlipCar_TA':
                $this->rotator = Vector::deserializeByteVector($br);
                $this->position = Vector::deserialize($br);
                return;
            case 'TAGame.Ball_TA':
            case 'TAGame.Car_TA':
                $this->rotator = Vector::deserializeByteVector($br);
                $this->position = Vector::deserialize($br);
                $this->orientation = Vector::deserializeOrientation($br);
                return;
        }
        throw new \Exception(
            'Could not deserialize actor class "' . $this->class . '".' . "\n"
            . $br->readBits(200)
        );
    }

    /**
     * @param ActorProperty $property
     */
    public function updateProperty($property)
    {
        if (!array_key_exists($property->id, $this->properties)) {
            throw new \Exception('Unexpected property ID "' . $property->id . '".');
        }
        $this->properties[$property->id] = $property;
    }
}
