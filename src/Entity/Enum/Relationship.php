<?php

namespace src\Entity\Enum\Member;

enum Relationship: string
{
    public const CHOICES = Relationship::Spouse->value
        . ', ' . Relationship::Child->value
        . ', ' . Relationship::Self->value;

    case Spouse = 'spouse';
    case Child = 'child';
    case Self = 'self';
}
