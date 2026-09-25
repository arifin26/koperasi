<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class ReceiptPrintHelper
{
    /**
     * Paper specifications in millimeters (width and height for standard portrait).
     */
    protected static array $specs = [
        'default' => ['w' => 75,  'h' => 215, 'unit' => 'mm'],
        'a4'      => ['w' => 210, 'h' => 297, 'unit' => 'mm'],
        'a3'      => ['w' => 297, 'h' => 420, 'unit' => 'mm'],
        'a5'      => ['w' => 148, 'h' => 210, 'unit' => 'mm'],
        'f4'      => ['w' => 215, 'h' => 330, 'unit' => 'mm'],
        'b5'      => ['w' => 176, 'h' => 250, 'unit' => 'mm'],
        'c4'      => ['w' => 229, 'h' => 324, 'unit' => 'mm'],
        'c5'      => ['w' => 162, 'h' => 229, 'unit' => 'mm'],
        'c6'      => ['w' => 114, 'h' => 162, 'unit' => 'mm'],
        '3r'      => ['w' => 89,  'h' => 127, 'unit' => 'mm'],
        '4r'      => ['w' => 102, 'h' => 152, 'unit' => 'mm'],
        '10r'     => ['w' => 254, 'h' => 305, 'unit' => 'mm'],
    ];

    /**
     * Resolve paper dimensions, CSS @page size, and DomPDF paper parameters.
     *
     * @param Request|null $request
     * @return array{css: string, paper: array, orientation: string, size: string}
     */
    public static function resolve(?Request $request = null): array
    {
        $size = 'default';
        $orientation = 'landscape';

        if ($request) {
            $size = strtolower((string) $request->query('size', 'default'));
            $orientation = strtolower((string) $request->query('orientation', 'landscape'));
        }

        $orientation = ($orientation === 'portrait') ? 'portrait' : 'landscape';

        if (!isset(self::$specs[$size])) {
            $size = 'default';
        }

        $spec = self::$specs[$size];
        $min = min($spec['w'], $spec['h']);
        $max = max($spec['w'], $spec['h']);

        if ($orientation === 'portrait') {
            $w = $min;
            $h = $max;
        } else {
            $w = $max;
            $h = $min;
        }

        $css = "{$w}{$spec['unit']} {$h}{$spec['unit']}";

        // 1 inch = 25.4 mm = 72 pt
        $wPt = round(($w / 25.4) * 72, 2);
        $hPt = round(($h / 25.4) * 72, 2);

        $paper = [0, 0, $wPt, $hPt];

        return [
            'css'         => $css,
            'paper'       => $paper,
            'orientation' => $orientation,
            'size'        => $size,
        ];
    }
}
