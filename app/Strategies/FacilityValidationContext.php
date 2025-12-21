<?php

/**
 * Author:Ng Jhun Hou
 */

namespace App\Strategies;

use App\Models\Facility;


class FacilityValidationContext
{
    private FacilityValidationStrategy $strategy;

    /*
     * 
     * @param string $facilityType 
     */
    public function __construct(string $facilityType = 'standard')
    {
        $this->strategy = new StandardFacilityStrategy();
    }

    /*
     * 
     * @param array $data
     * @param Facility|null $facility
     * @return array
     */
    public function validate(array $data, ?Facility $facility = null): array
    {
        return $this->strategy->validate($data, $facility);
    }

    /*
     * 
     * @return array
     */
    public function getDefaultValues(): array
    {
        return $this->strategy->getDefaultValues();
    }

    /*
     * 
     * @param array $data
     * @return array
     */
    public function processBeforeSave(array $data): array
    {
        return $this->strategy->processBeforeSave($data);
    }

    /*
     * 
     * @return FacilityValidationStrategy
     */
    public function getStrategy(): FacilityValidationStrategy
    {
        return $this->strategy;
    }
}

