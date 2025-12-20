<?php

namespace App\Strategies;

use App\Models\Facility;

/**
 * 策略上下文类 - 使用标准策略
 * Strategy Context - Uses standard strategy for all facility types
 */
class FacilityValidationContext
{
    private FacilityValidationStrategy $strategy;

    /**
     * 创建标准策略
     * 
     * @param string $facilityType (保留参数以保持兼容性，但不使用)
     */
    public function __construct(string $facilityType = 'standard')
    {
        $this->strategy = new StandardFacilityStrategy();
    }

    /**
     * 验证设施数据
     * 
     * @param array $data
     * @param Facility|null $facility
     * @return array
     */
    public function validate(array $data, ?Facility $facility = null): array
    {
        return $this->strategy->validate($data, $facility);
    }

    /**
     * 获取默认值
     * 
     * @return array
     */
    public function getDefaultValues(): array
    {
        return $this->strategy->getDefaultValues();
    }

    /**
     * 处理保存前的数据
     * 
     * @param array $data
     * @return array
     */
    public function processBeforeSave(array $data): array
    {
        return $this->strategy->processBeforeSave($data);
    }

    /**
     * 获取当前策略
     * 
     * @return FacilityValidationStrategy
     */
    public function getStrategy(): FacilityValidationStrategy
    {
        return $this->strategy;
    }
}

