<?php

namespace App\Support;

use App\Models\Attachment;

final class AttachmentFileIcon
{
    /** Пин релиза vscode-icons на jsDelivr (иконки: icons/file_type_{icon}.svg). */
    public const VSCODE_ICONS_TAG = 'v12.17.0';

    /**
     * @return array{mode: 'image'}|array{mode: 'brand', url: string, label: string}|array{mode: 'generic', label: string}
     */
    public static function forAttachment(Attachment $attachment): array
    {
        if ($attachment->canPreviewInline()) {
            return ['mode' => 'image'];
        }

        $cfg = config('sa_map.file_icons');
        if (! is_array($cfg)) {
            return self::generic($attachment);
        }

        $name = mb_strtolower($attachment->original_name);

        foreach ($cfg['patterns'] ?? [] as $row) {
            if (! is_array($row) || empty($row['icon'])) {
                continue;
            }
            if (str_contains($name, mb_strtolower((string) ($row['contains'] ?? '')))) {
                return self::brand($row);
            }
        }

        $ext = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
        $map = $cfg['extensions'] ?? [];
        if ($ext !== '' && isset($map[$ext]) && is_array($map[$ext])) {
            return self::brand($map[$ext]);
        }

        return self::generic($attachment);
    }

    /** Имя иконки vscode-icons (`file_type_{slug}.svg`) для упаковки в ZIP и локальных `src` в MD. */
    public static function vscodeIconSlug(Attachment $attachment): string
    {
        if ($attachment->canPreviewInline()) {
            $ext = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));

            return match ($ext) {
                'png' => 'png',
                'jpg', 'jpeg' => 'jpg',
                'gif' => 'gif',
                'webp' => 'webp',
                'svg' => 'svg',
                'bmp', 'ico' => 'image',
                default => 'image',
            };
        }

        $cfg = config('sa_map.file_icons');
        if (! is_array($cfg)) {
            return 'file';
        }

        $name = mb_strtolower($attachment->original_name);
        foreach ($cfg['patterns'] ?? [] as $row) {
            if (! is_array($row) || empty($row['icon'])) {
                continue;
            }
            if (str_contains($name, mb_strtolower((string) ($row['contains'] ?? '')))) {
                return self::normalizeIconSlug((string) $row['icon']);
            }
        }

        $ext = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
        $map = $cfg['extensions'] ?? [];
        if ($ext !== '' && isset($map[$ext]) && is_array($map[$ext]) && ! empty($map[$ext]['icon'])) {
            return self::normalizeIconSlug((string) $map[$ext]['icon']);
        }

        return 'file';
    }

    private static function normalizeIconSlug(string $icon): string
    {
        $icon = preg_replace('/[^a-z0-9_]/i', '', $icon) ?? '';

        return $icon !== '' ? $icon : 'file';
    }

    /**
     * @param  array{icon: string, label?: string}  $row
     * @return array{mode: 'brand', url: string, label: string}
     */
    private static function brand(array $row): array
    {
        $icon = preg_replace('/[^a-z0-9_]/i', '', (string) $row['icon']) ?? '';
        if ($icon === '') {
            $icon = 'file';
        }

        return [
            'mode' => 'brand',
            'url' => sprintf(
                'https://cdn.jsdelivr.net/gh/vscode-icons/vscode-icons@%s/icons/file_type_%s.svg',
                self::VSCODE_ICONS_TAG,
                $icon
            ),
            'label' => (string) ($row['label'] ?? $icon),
        ];
    }

    /**
     * @return array{mode: 'generic', label: string}
     */
    private static function generic(Attachment $attachment): array
    {
        $ext = strtoupper(pathinfo($attachment->original_name, PATHINFO_EXTENSION));

        return [
            'mode' => 'generic',
            'label' => $ext !== '' ? 'Файл .'.$ext : 'Файл',
        ];
    }
}
