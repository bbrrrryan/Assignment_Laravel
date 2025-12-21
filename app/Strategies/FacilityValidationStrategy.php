<?php
/**
 * Author:Ng Jhun Hou
 */
namespace App\Strategies;

use App\Models\Facility;

interface FacilityValidationStrategy
{
    public function validate(array $data, ?Facility $facility = null): array;

    public function getDefaultValues(): array;

    public function processBeforeSave(array $data): array;

    public function getFacilityType(): string;
}

