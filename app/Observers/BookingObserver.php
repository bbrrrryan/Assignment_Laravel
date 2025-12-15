<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRule;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    /**
     * Handle the Booking "updated" event.
     * 当预订状态更新时，检查是否需要奖励积分
     */
    public function updated(Booking $booking)
    {
        // 检查状态是否从其他状态变为 'completed'
        $originalStatus = $booking->getOriginal('status');
        $newStatus = $booking->status;

        // 只有当状态变为 'completed' 时才处理
        if ($originalStatus !== 'completed' && $newStatus === 'completed') {
            $this->awardPointsForCompletedBooking($booking);
        }
    }

    /**
     * 为完成的预订奖励积分
     */
    protected function awardPointsForCompletedBooking(Booking $booking)
    {
        try {
            // 查找与设施预订相关的所有忠诚度规则
            // 目前支持：
            // - facility_booking_complete：完成预订
            // - facility_booking_first：第一次完成预订
            // - facility_booking_long_duration：长时长预订
            $rules = LoyaltyRule::where('is_active', true)
                ->whereIn('action_type', [
                    'facility_booking_complete',
                    'facility_booking_first',
                    'facility_booking_long_duration',
                ])
                ->get();

            if ($rules->isEmpty()) {
                Log::info("No active loyalty rules found for facility booking actions");
                return;
            }

            foreach ($rules as $rule) {
                // 检查是否已经给过该规则对应的积分（防重复奖励）
                $existingPoint = LoyaltyPoint::where('user_id', $booking->user_id)
                    ->where('action_type', $rule->action_type)
                    ->where('related_id', $booking->id)
                    ->where('related_type', Booking::class)
                    ->first();

                if ($existingPoint) {
                    Log::info("Points already awarded for rule {$rule->action_type} on booking #{$booking->id}");
                    continue;
                }

                // 检查规则条件（如果有的话）
                if (!$this->checkRuleConditions($rule, $booking)) {
                    Log::info("Booking #{$booking->id} does not meet rule conditions for {$rule->action_type}");
                    continue;
                }

                // 创建积分记录
                LoyaltyPoint::create([
                    'user_id' => $booking->user_id,
                    'points' => $rule->points,
                    'action_type' => $rule->action_type,
                    'related_id' => $booking->id,
                    'related_type' => Booking::class,
                    'description' => $rule->description
                        ?? "Facility booking ({$rule->action_type}): {$booking->booking_number}",
                ]);

                Log::info("Awarded {$rule->points} points to user #{$booking->user_id} for {$rule->action_type} on booking #{$booking->id}");
            }

        } catch (\Exception $e) {
            // 记录错误但不中断预订状态更新
            Log::error("Failed to award points for booking #{$booking->id}: " . $e->getMessage());
        }
    }

    /**
     * 检查规则条件是否满足
     * 可以根据 conditions 字段中的条件进行验证
     */
    protected function checkRuleConditions(LoyaltyRule $rule, Booking $booking): bool
    {
        // 如果没有设置条件，默认通过
        if (empty($rule->conditions)) {
            $conditions = [];
        } else {
            $conditions = $rule->conditions;
        }

        // 示例：检查设施类型
        if (isset($conditions['facility_types']) && is_array($conditions['facility_types'])) {
            $facilityType = $booking->facility->type ?? null;
            if (!in_array($facilityType, $conditions['facility_types'])) {
                return false;
            }
        }

        // 示例：检查用户角色
        if (isset($conditions['user_roles']) && is_array($conditions['user_roles'])) {
            $userRole = $booking->user->role ?? null;
            if (!in_array($userRole, $conditions['user_roles'])) {
                return false;
            }
        }

        // 示例：检查预订时长
        if (isset($conditions['min_duration_hours'])) {
            if ($booking->duration_hours < $conditions['min_duration_hours']) {
                return false;
            }
        }

        // 根据 action_type 添加一些内置的规则行为
        switch ($rule->action_type) {
            case 'facility_booking_first':
                // 只有用户第一次完成预订时才奖励
                $previousCompletedCount = Booking::where('user_id', $booking->user_id)
                    ->where('status', 'completed')
                    ->where('id', '!=', $booking->id)
                    ->count();

                if ($previousCompletedCount > 0) {
                    return false;
                }
                break;

            case 'facility_booking_long_duration':
                // 如果没有在 conditions 中设置最小时长，默认按 2 小时计算
                $minDuration = $conditions['min_duration_hours'] ?? 2;
                if ($booking->duration_hours < $minDuration) {
                    return false;
                }
                break;

            default:
                // 其他 action_type 暂不增加特殊条件
                break;
        }

        // 可以在这里继续添加更多条件检查...

        return true;
    }
}

