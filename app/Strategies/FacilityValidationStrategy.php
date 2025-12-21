<?php
/**
 * Author:Ng Jhun Hou
 */
namespace App\Strategies;

use App\Models\Facility;

/*
 * Strategy Pattern Interface for Facility Management
 */
interface FacilityValidationStrategy
{
    /*
     * 
     * @param array $data 
     * @param Facility|null $facility 
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(array $data, ?Facility $facility = null): array;

    /*
     * 
     * @return array
     */
    public function getDefaultValues(): array;

    /*
     * 
     * @param array $data
     * @return array
     */
    public function processBeforeSave(array $data): array;

    /*
     * 
     * @return string
     */
    public function getFacilityType(): string;
}

