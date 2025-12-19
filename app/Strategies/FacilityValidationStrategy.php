<?php

namespace App\Strategies;

use App\Models\Facility;

/**
 * 策略接口 - 定义设施验证和处理的通用方法
 * Strategy Pattern Interface for Facility Management
 */
interface FacilityValidationStrategy
{
    /**
     * 验证设施数据
     * 
     * @param array $data 验证数据
     * @param Facility|null $facility 现有设施（更新时使用）
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(array $data, ?Facility $facility = null): array;

    /**
     * 获取设施类型特定的默认值
     * 
     * @return array
     */
    public function getDefaultValues(): array;

    /**
     * 处理设施创建/更新前的特殊逻辑
     * 
     * @param array $data
     * @return array 处理后的数据
     */
    public function processBeforeSave(array $data): array;

    /**
     * 获取设施类型
     * 
     * @return string
     */
    public function getFacilityType(): string;
}

