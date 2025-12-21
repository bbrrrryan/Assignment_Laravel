<?php

/**
 * Author:Ng Jhun Hou
 */

namespace App\Strategies;

use App\Models\Facility;


class FacilityValidationContext
{
    private FacilityValidationStrategy $strategy;

    public function __construct(string $facilityType = 'standard')
    {
        $this->strategy = new StandardFacilityStrategy();
    }
    
    public function validate(array $data, ?Facility $facility = null): array
    {
        return $this->strategy->validate($data, $facility);
    }

    public function getDefaultValues(): array
    {
        return $this->strategy->getDefaultValues();
    }

    public function processBeforeSave(array $data): array
    {
        return $this->strategy->processBeforeSave($data);
    }

    public function getStrategy(): FacilityValidationStrategy
    {
        return $this->strategy;
    }
}

