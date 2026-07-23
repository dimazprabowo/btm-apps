<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case Manager = 'manager';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'Manajer Proyek',
            self::Member => 'Anggota',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Manager => 'indigo',
            self::Member => 'gray',
        };
    }

    public function badgeClass(): string
    {
        return color_badge_class($this->color());
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
